<?php

namespace App\Domains\Users\DTOs;

class MaintenanceRequestTypesData
{
    /**
     * @param array<int> $request_type_ids
     */
    public function __construct(
        public array $request_type_ids,
    ) {}
}
