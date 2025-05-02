<?php

declare(strict_types=1);

namespace Fruitcake\LaravelDebugbar\CollectorProviders;

use Fruitcake\LaravelDebugbar\DataCollector\SwiftMailer\SwiftLogCollector;
use Fruitcake\LaravelDebugbar\DataCollector\SwiftMailer\SwiftMailCollector;
use DebugBar\Bridge\Symfony\SymfonyMailCollector;
use Illuminate\Events\Dispatcher;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;

class MailCollectorProvider extends AbstractCollectorProvider
{
    public function __invoke(Dispatcher $events, array $options): void
    {
        if (! $this->debugbar->checkVersion('9.0')) {
            $mailer = app('mailer')->getSwiftMailer();
            $mailCollector = new SwiftMailCollector($mailer);

            if ($options['show_body'] && $this->hasCollector('messages')) {
                $this->debugbar['messages']->aggregate(new SwiftLogCollector($mailer));
            }

            if ($this->hasCollector('time') && ($options['timeline'] ?? true)) {
                /** @var TimeDataCollector $timeCollector */
                $timeCollector = $this->getCollector('time');

                $events->listen(MessageSending::class, fn(MessageSending $e) => $timeCollector->startMeasure('Mail: ' . $e->message->getSubject()));
                $events->listen(MessageSent::class, fn(MessageSent $e) => $timeCollector->stopMeasure('Mail: ' . $e->message->getSubject()));
            }

            return;
        }

        $mailCollector = new SymfonyMailCollector();
        $this->addCollector($mailCollector);

        $events->listen(function (MessageSent $event) use ($mailCollector): void {
            $mailCollector->addSymfonyMessage($event->sent->getSymfonySentMessage());
        });

        if (($options['show_body'] ?? true) || ($options['full_log'] ?? false)) {
            $mailCollector->showMessageBody();
        }

        if ($options['timeline'] ?? true) {
            $timeCollector = $this->debugbar->getTimeCollector();

            $events->listen(MessageSending::class, fn(MessageSending $e) => $timeCollector->startMeasure('Mail: ' . $e->message->getSubject()));
            $events->listen(MessageSent::class, fn(MessageSent $e) => $timeCollector->stopMeasure('Mail: ' . $e->message->getSubject()));
        }
    }
}
