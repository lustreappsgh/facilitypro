<?php

namespace App\Domains\Maintenance\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceRequest;
use Exception;

class ReviewMaintenanceAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction
    ) {}

    public function execute(MaintenanceRequest $request): MaintenanceRequest
    {
        if ($request->status !== MaintenanceStatus::Pending->value) {
            throw new Exception('Request must be pending to start.');
        }

        $before = $request->getOriginal();

        $request->update([
            'status' => MaintenanceStatus::InProgress->value,
        ]);

        $request = $request->refresh();

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'maintenance_request.started',
            auditable_type: $request->getMorphClass(),
            auditable_id: $request->id,
            before: $before,
            after: $request->getAttributes(),
        ));

        return $request;
    }
}
