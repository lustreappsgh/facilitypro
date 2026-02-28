<?php

namespace App\Domains\Facilities\Services;

use App\Domains\Facilities\Actions\CreateFacilityAction;
use App\Domains\Facilities\Actions\UpdateFacilityAction;
use App\Domains\Facilities\DTOs\FacilityData;
use App\Models\Facility;

class FacilityService
{
    public function __construct(
        protected CreateFacilityAction $createFacilityAction,
        protected UpdateFacilityAction $updateFacilityAction
    ) {}

    public function createFacility(FacilityData $data): Facility
    {
        return $this->createFacilityAction->execute($data);
    }

    public function updateFacility(Facility $facility, FacilityData $data): Facility
    {
        return $this->updateFacilityAction->execute($facility, $data);
    }
}
