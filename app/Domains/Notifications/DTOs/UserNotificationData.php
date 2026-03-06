<?php

namespace App\Domains\Notifications\DTOs;

class UserNotificationData
{
    public function __construct(
        public readonly int $user_id,
        public readonly string $event,
        public readonly string $title,
        public readonly string $body,
        public readonly ?string $action_url = null,
        public readonly array $meta = [],
    ) {}

    public function toArray(): array
    {
        return [
            'event' => $this->event,
            'title' => $this->title,
            'body' => $this->body,
            'action_url' => $this->action_url,
            'meta' => $this->meta,
        ];
    }
}
