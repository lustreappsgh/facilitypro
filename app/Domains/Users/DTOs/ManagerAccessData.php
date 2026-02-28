<?php

namespace App\Domains\Users\DTOs;

class ManagerAccessData
{
    public function __construct(
        public int $facility_manager_id,
        public int $manager_id,
    ) {}
}
