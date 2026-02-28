<?php

namespace App\Domains\Todos\DTOs;

class TodoData
{
    public function __construct(
        public int $facility_id,
        public string $description,
        public string $week,
        public int $user_id,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            facility_id: (int) $data['facility_id'],
            description: $data['description'],
            week: now()->next('Monday')->format('Y-m-d'),
            user_id: (int) ($data['user_id'] ?? auth()->id()),
        );
    }

    public function toArray(): array
    {
        return [
            'facility_id' => $this->facility_id,
            'description' => $this->description,
            'week_start' => $this->week,
            'user_id' => $this->user_id,
        ];
    }
}
