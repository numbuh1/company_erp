<?php

namespace App\Listeners;

use App\Models\AppSetting;
use App\Models\MailLog;
use Illuminate\Mail\Events\MessageSent;

class LogSentMessage
{
    public function handle(MessageSent $event): void
    {
        if (AppSetting::get('mail_logging_enabled', '1') !== '1') {
            return;
        }

        $message = $event->message;

        $extract = fn(array $addresses) => array_map(
            fn($a) => $a->getAddress(),
            $addresses
        );

        $mailableClass = null;
        if (isset($event->data['__laravel_notification'])) {
            $mailableClass = $event->data['__laravel_notification'];
        } elseif (isset($event->data['__mailable'])) {
            $mailableClass = get_class($event->data['__mailable']);
        }

        MailLog::create([
            'subject'        => $message->getSubject(),
            'to'             => $extract($message->getTo()),
            'cc'             => $extract($message->getCc()) ?: null,
            'bcc'            => $extract($message->getBcc()) ?: null,
            'from'           => $extract($message->getFrom())[0] ?? null,
            'body'           => $message->getHtmlBody() ?? $message->getTextBody(),
            'mailable_class' => $mailableClass,
            'status'         => 'sent',
        ]);
    }
}
