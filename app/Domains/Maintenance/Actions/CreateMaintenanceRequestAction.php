<?php

namespace App\Domains\Maintenance\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Maintenance\DTOs\MaintenanceRequestData;
use App\Models\Facility;
use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceRequest;
use Illuminate\Auth\Access\AuthorizationException;

class CreateMaintenanceRequestAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction
    ) {}

    public function execute(MaintenanceRequestData $data): MaintenanceRequest
    {
        $facility = Facility::query()->find($data->facility_id);
        $user = auth()->user();

        if ($facility && $user && ! $user->can('maintenance.manage_all') && $facility->managed_by !== $user->id) {
            throw new AuthorizationException('Facility is not assigned to this manager.');
        }

        $payload = $data->toArray();
        $payload['status'] = MaintenanceStatus::Pending->value;

        $request = MaintenanceRequest::create($payload);

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'maintenance_request.created',
            auditable_type: $request->getMorphClass(),
            auditable_id: $request->id,
            before: null,
            after: $request->getAttributes(),
        ));

        return $request;
    }
}
