<?php

namespace App\Domains\Payments\DTOs;

class PaymentData
{
    public function __construct(
        public int $maintenance_request_id,
        public ?int $work_order_id,
        public int $cost,
        public int $amount_payed,
        public string $status,
        public ?string $comments,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            maintenance_request_id: (int) $data['maintenance_request_id'],
            work_order_id: isset($data['work_order_id']) ? (int) $data['work_order_id'] : null,
            cost: (int) $data['cost'],
            amount_payed: (int) $data['amount_payed'],
            status: $data['status'] ?? 'pending',
            comments: $data['comments'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'maintenance_request_id' => $this->maintenance_request_id,
            'work_order_id' => $this->work_order_id,
            'cost' => $this->cost,
            'amount_payed' => $this->amount_payed,
            'status' => $this->status,
            'comments' => $this->comments,
        ];
    }
}
