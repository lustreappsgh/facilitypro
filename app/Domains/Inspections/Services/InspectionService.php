<?php

namespace App\Domains\Inspections\Services;

use App\Domains\Inspections\Actions\CreateInspectionAction;
use App\Domains\Inspections\DTOs\InspectionData;
use App\Models\Inspection;

class InspectionService
{
    public function __construct(
        protected CreateInspectionAction $createInspectionAction
    ) {}

    public function createInspection(InspectionData $data): Inspection
    {
        return $this->createInspectionAction->execute($data);
    }
}
