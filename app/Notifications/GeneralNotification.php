<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $description,
        public string $url,
        public ?int $incomingUserId = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'            => $this->title,
            'description'      => $this->description,
            'url'              => $this->url,
            'incoming_user_id' => $this->incomingUserId,
        ];
    }
}
