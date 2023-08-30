<?php

namespace Barryvdh\Debugbar\DataCollector;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Address;

/**
 * Collects data about sent mail events
 *
 * https://github.com/symfony/mailer
 */
class SymfonyMailCollector extends DataCollector implements Renderable, AssetProvider
{
    public array $messageEvents = [];

    public function addMessageEvent(MessageSent $event)
    {
        $this->messageEvents[] = $event;
    }

    public function collect()
    {
        $mails = [];

        foreach ($this->messageEvents as $event) {
            $message = $event->sent->getSymfonySentMessage()->getOriginalMessage();

            $mailData = [
                'subject' => $message->getSubject(),
                'date' => $message->getDate(),
                'return_path' => $message->getReturnPath(),
                'sender' => $message->getSender(),
                'from' => $message->getFrom(),
                'reply_to' => $message->getReplyTo(),
                'to' => $message->getTo(),
                'cc' => $message->getCc(),
                'bcc' => $message->getBcc(),
                'text_body' => $message->getTextBody(),
                'attachments' => $message->getAttachments(),
                'headers' => $message->getHeaders()->toString(),
            ];

            $addressKeys = ['from', 'reply_to', 'to', 'cc', 'bcc'];

            foreach ($addressKeys as $addressKey) {
                $mailData[$addressKey] = array_map(function (Address $address) {
                    return $address->toString();
                }, $mailData[$addressKey]);
            }

            if (config('debugbar.options.mail.detailed')) {
                $mails[] = array_filter($mailData);
            } else {
                $mails[] = [
                    'to' => $mailData['to'],
                    'subject' => $mailData['subject'],
                    'headers' => $mailData['headers'],
                ];
            }
        }

        return array(
            'count' => count($mails),
            'mails' => $mails,
        );
    }

    public function getName()
    {
        return 'symfonymailer_mails';
    }

    public function getWidgets()
    {
        return array(
            'emails' => array(
                'icon' => 'inbox',
                'widget' => 'PhpDebugBar.Widgets.MailsWidget',
                'map' => 'symfonymailer_mails.mails',
                'default' => '[]',
                'title' => 'Mails'
            ),
            'emails:badge' => array(
                'map' => 'symfonymailer_mails.count',
                'default' => 'null'
            )
        );
    }

    public function getAssets()
    {
        return array(
            'css' => 'widgets/mails/widget.css',
            'js' => 'widgets/mails/widget.js'
        );
    }
}
