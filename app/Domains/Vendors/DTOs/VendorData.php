<?php

namespace App\Domains\Vendors\DTOs;

class VendorData
{
    public function __construct(
        public string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $service_type,
        public string $status,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            service_type: $data['service_type'] ?? null,
            status: $data['status'] ?? 'active',
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'service_type' => $this->service_type,
            'status' => $this->status,
        ];
    }
}
