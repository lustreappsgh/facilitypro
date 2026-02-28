<?php

namespace App\Domains\Vendors\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Vendors\DTOs\VendorData;
use App\Models\Vendor;

class CreateVendorAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction
    ) {}

    public function execute(VendorData $data): Vendor
    {
        $vendor = Vendor::create($data->toArray());

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'vendor.created',
            auditable_type: $vendor->getMorphClass(),
            auditable_id: $vendor->id,
            before: null,
            after: $vendor->getAttributes(),
        ));

        return $vendor;
    }
}
