<?php

namespace App\Domains\Maintenance\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceRequest;
use Exception;

class CompleteMaintenanceAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction
    ) {}

    public function execute(MaintenanceRequest $request): MaintenanceRequest
    {
        if ($request->status !== MaintenanceStatus::InProgress->value) {
            throw new Exception('Request must be in progress to complete.');
        }

        $before = $request->getOriginal();

        $request->update([
            'status' => MaintenanceStatus::Completed->value,
        ]);

        $request = $request->refresh();

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'maintenance_request.completed',
            auditable_type: $request->getMorphClass(),
            auditable_id: $request->id,
            before: $before,
            after: $request->getAttributes(),
        ));

        return $request;
    }
}
