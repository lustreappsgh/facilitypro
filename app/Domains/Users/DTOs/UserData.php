<?php

namespace App\Domains\Users\DTOs;

class UserData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        public bool $is_active,
        public bool $is_default_password,
        public ?int $manager_id = null,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'is_default_password' => $this->is_default_password,
            'manager_id' => $this->manager_id,
        ];
    }

    public function toCreateArray(): array
    {
        if ($this->password === null) {
            throw new \InvalidArgumentException('Password is required for user creation.');
        }

        return array_merge($this->toArray(), [
            'password' => $this->password,
        ]);
    }
}
