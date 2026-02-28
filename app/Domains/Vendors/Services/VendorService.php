<?php

namespace App\Domains\Vendors\Services;

use App\Domains\Vendors\Actions\CreateVendorAction;
use App\Domains\Vendors\DTOs\VendorData;
use App\Models\Vendor;

class VendorService
{
    public function __construct(
        protected CreateVendorAction $createAction
    ) {}

    public function create(VendorData $data): Vendor
    {
        return $this->createAction->execute($data);
    }
}
