<?php

namespace App\Domains\Inspections\DTOs;

class InspectionData
{
    public function __construct(
        public string $inspection_date,
        public int $facility_id,
        public string $condition,
        public ?string $comments,
        public ?string $image,
        public int $added_by,
        public ?int $maintenance_request_type_id = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            inspection_date: $data['inspection_date'] ?? now()->toDateString(),
            facility_id: (int) $data['facility_id'],
            condition: $data['condition'],
            comments: $data['comments'] ?? null,
            image: $data['image'] ?? null,
            added_by: $data['added_by'] ?? auth()->id(),
            maintenance_request_type_id: isset($data['maintenance_request_type_id']) ? (int) $data['maintenance_request_type_id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'inspection_date' => $this->inspection_date,
            'facility_id' => $this->facility_id,
            'condition' => $this->condition,
            'comments' => $this->comments,
            'image' => $this->image,
            'added_by' => $this->added_by,
        ];
    }
}
