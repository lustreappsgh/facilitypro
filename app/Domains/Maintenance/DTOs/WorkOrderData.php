<?php

namespace App\Domains\Maintenance\DTOs;

class WorkOrderData
{
    public function __construct(
        public int $maintenance_request_id,
        public ?int $vendor_id,
        public string $assigned_date,
        public ?string $scheduled_date,
        public ?int $estimated_cost,
        public ?int $actual_cost,
        public int $assigned_by,
        public ?string $status = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            maintenance_request_id: (int) $data['maintenance_request_id'],
            vendor_id: isset($data['vendor_id']) && $data['vendor_id'] !== ''
                ? (int) $data['vendor_id']
                : null,
            assigned_date: $data['assigned_date'] ?? now()->toDateString(),
            scheduled_date: $data['scheduled_date'] ?? null,
            estimated_cost: isset($data['estimated_cost']) ? (int) $data['estimated_cost'] : null,
            actual_cost: isset($data['actual_cost']) ? (int) $data['actual_cost'] : null,
            assigned_by: auth()->id(),
            status: $data['status'] ?? 'assigned',
        );
    }

    public function toArray(): array
    {
        $payload = [
            'maintenance_request_id' => $this->maintenance_request_id,
            'vendor_id' => $this->vendor_id,
            'assigned_date' => $this->assigned_date,
            'scheduled_date' => $this->scheduled_date,
            'estimated_cost' => $this->estimated_cost,
            'actual_cost' => $this->actual_cost,
            'assigned_by' => $this->assigned_by,
        ];

        if ($this->status !== null) {
            $payload['status'] = $this->status;
        }

        return $payload;
    }
}
