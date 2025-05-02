<?php

declare(strict_types=1);

namespace Fruitcake\LaravelDebugbar\CollectorProviders;

use Fruitcake\LaravelDebugbar\DataCollector\SwiftMailer\SwiftLogCollector;
use Fruitcake\LaravelDebugbar\DataCollector\SwiftMailer\SwiftMailCollector;
use DebugBar\Bridge\Symfony\SymfonyMailCollector;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;

class MailCollectorProvider extends AbstractCollectorProvider
{
    public function __invoke(Dispatcher $events, array $options): void
    {
        if (! $this->debugbar->checkVersion('9.0') && class_exists(\Swift_Mailer::class)) {
            /** @var \Swift_Mailer $mailer */
            $mailer = app('mailer')->getSwiftMailer();
            $mailCollector = new SwiftMailCollector($mailer);
            $this->addCollector($mailCollector);

            if ($options['show_body'] ?? true) {
                $mailCollector->showMessageBody();

                if ($this->hasCollector('messages')) {
                    $messages = $this->debugbar->getMessagesCollector();
                    $messages->aggregate(new SwiftLogCollector($mailer));
                }
            }

            if ($this->hasCollector('time') && ($options['timeline'] ?? true)) {
                $timeCollector = $this->debugbar->getTimeCollector();

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
            $events->listen(MessageSent::class, function (MessageSent $e) use ($timeCollector): void {
                $name = 'Mail: ' . $e->message->getSubject();
                if ($timeCollector->hasStartedMeasure($name)) {
                    $timeCollector->stopMeasure($name);
                } else {
                    $timeCollector->addMeasure($name);
                }
            });
        }
    }
}
