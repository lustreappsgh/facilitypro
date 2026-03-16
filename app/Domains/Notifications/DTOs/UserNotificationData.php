<?php

namespace App\Domains\Notifications\DTOs;

class UserNotificationData
{
    public const SeverityInfo = 'info';

    public const SeveritySuccess = 'success';

    public const SeverityWarning = 'warning';

    public const SeverityDanger = 'danger';

    public function __construct(
        public readonly int $user_id,
        public readonly string $event,
        public readonly string $title,
        public readonly string $body,
        public readonly ?string $action_url = null,
        public readonly array $meta = [],
        public readonly string $category = 'system',
        public readonly string $severity = self::SeverityInfo,
    ) {}

    public function toArray(): array
    {
        return [
            'event' => $this->event,
            'title' => $this->title,
            'body' => $this->body,
            'action_url' => $this->action_url,
            'meta' => $this->meta,
            'category' => $this->category,
            'severity' => $this->severity,
        ];
    }
}
