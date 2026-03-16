<?php

namespace App\Domains\Maintenance\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Maintenance\DTOs\MaintenanceRequestData;
use App\Domains\Notifications\Services\OperationalNotificationService;
use App\Models\MaintenanceRequest;

class UpdateMaintenanceRequestAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction,
        protected OperationalNotificationService $operationalNotificationService
    ) {}

    public function execute(MaintenanceRequest $request, MaintenanceRequestData $data): MaintenanceRequest
    {
        $before = $request->getOriginal();

        $request->update($data->toArray());

        $request = $request->refresh();

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'maintenance_request.updated',
            auditable_type: $request->getMorphClass(),
            auditable_id: $request->id,
            before: $before,
            after: $request->getAttributes(),
        ));

        $this->operationalNotificationService->maintenanceRequestUpdated($request);

        return $request;
    }
}
