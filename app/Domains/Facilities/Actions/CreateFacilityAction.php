<?php

namespace App\Domains\Facilities\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Facilities\DTOs\FacilityData;
use App\Models\Facility;

class CreateFacilityAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction
    ) {}

    public function execute(FacilityData $data): Facility
    {
        $facility = Facility::create($data->toArray());

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'facility.created',
            auditable_type: $facility->getMorphClass(),
            auditable_id: $facility->id,
            before: null,
            after: $facility->getAttributes(),
        ));

        return $facility;
    }
}
