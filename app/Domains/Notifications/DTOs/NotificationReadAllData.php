<?php

namespace App\Domains\Notifications\DTOs;

class NotificationReadAllData
{
    public function __construct(
        public readonly int $user_id
    ) {}
}
