<?php

namespace App\Domains\Notifications\DTOs;

class NotificationReadData
{
    public function __construct(
        public readonly string $notification_id
    ) {}
}
