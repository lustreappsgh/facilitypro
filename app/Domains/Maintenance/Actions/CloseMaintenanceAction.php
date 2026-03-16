<?php

namespace App\Domains\Maintenance\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Notifications\Services\OperationalNotificationService;
use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceRequest;
use DomainException;

class CloseMaintenanceAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction,
        protected OperationalNotificationService $operationalNotificationService
    ) {}

    public function execute(MaintenanceRequest $request): MaintenanceRequest
    {
        if (! in_array($request->status, [
            MaintenanceStatus::Paid->value,
            MaintenanceStatus::Completed->value,
        ], true)) {
            throw new DomainException('Only paid requests can be closed.');
        }

        $before = $request->getOriginal();

        $request->update([
            'status' => MaintenanceStatus::Closed->value,
        ]);

        $request = $request->refresh();

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'maintenance_request.closed',
            auditable_type: $request->getMorphClass(),
            auditable_id: $request->id,
            before: $before,
            after: $request->getAttributes(),
        ));

        $this->operationalNotificationService->maintenanceRequestClosed($request);

        return $request;
    }
}
