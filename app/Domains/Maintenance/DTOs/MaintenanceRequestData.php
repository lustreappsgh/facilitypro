<?php

namespace App\Domains\Maintenance\DTOs;

class MaintenanceRequestData
{
    public function __construct(
        public int $facility_id,
        public int $request_type_id,
        public ?string $description,
        public ?int $cost,
        public ?string $status,
        public int $requested_by,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            facility_id: (int) $data['facility_id'],
            request_type_id: (int) $data['request_type_id'],
            description: $data['description'] ?? null,
            cost: isset($data['cost']) ? (int) $data['cost'] : null,
            status: $data['status'] ?? null,
            requested_by: $data['requested_by'] ?? auth()->id(),
        );
    }

    public function toArray(): array
    {
        $payload = [
            'facility_id' => $this->facility_id,
            'request_type_id' => $this->request_type_id,
            'description' => $this->description,
            'cost' => $this->cost,
            'requested_by' => $this->requested_by,
        ];

        if ($this->status !== null) {
            $payload['status'] = $this->status;
        }

        return $payload;
    }
}
