<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\DTOs\UserNotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class UserNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected UserNotificationData $data
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->data->toArray();
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'data' => $this->data->toArray(),
            'created_at' => now()->toIso8601String(),
            'read_at' => null,
        ]);
    }
}
