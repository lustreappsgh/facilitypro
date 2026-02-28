<?php

namespace App\Domains\Facilities\DTOs;

class FacilityData
{
    public function __construct(
        public string $name,
        public int $facility_type_id,
        public ?int $parent_id,
        public string $condition,
        public ?int $managed_by,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            facility_type_id: (int) $data['facility_type_id'],
            parent_id: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            condition: $data['condition'],
            managed_by: isset($data['managed_by']) ? (int) $data['managed_by'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'facility_type_id' => $this->facility_type_id,
            'parent_id' => $this->parent_id,
            'condition' => $this->condition,
            'managed_by' => $this->managed_by,
        ];
    }
}
