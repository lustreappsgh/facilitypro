<?php

namespace App\Domains\AuditLogs\DTOs;

class AuditLogData
{
    public function __construct(
        public int $actor_id,
        public string $action,
        public ?string $auditable_type,
        public ?int $auditable_id,
        public ?array $before,
        public ?array $after,
        public ?string $ip_address = null,
        public ?string $user_agent = null,
    ) {}

    public function toArray(): array
    {
        return [
            'actor_id' => $this->actor_id,
            'action' => $this->action,
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'before' => $this->before,
            'after' => $this->after,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
        ];
    }
}
