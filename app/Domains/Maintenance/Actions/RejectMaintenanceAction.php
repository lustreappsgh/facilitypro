<?php

namespace App\Domains\Maintenance\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Notifications\Services\OperationalNotificationService;
use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceRequest;
use DomainException;

class RejectMaintenanceAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction,
        protected OperationalNotificationService $operationalNotificationService
    ) {}

    public function execute(MaintenanceRequest $request, string $reason): MaintenanceRequest
    {
        $actor = auth()->user();
        if (! $actor) {
            throw new DomainException('Authenticated user is required.');
        }

        $isFinalApprover = $actor->can('maintenance.manage_all')
            || $actor->can('users.manage');

        $finalApproverStatuses = array_merge(
            MaintenanceStatus::approvalQueue(),
            [
                MaintenanceStatus::Approved->value,
                MaintenanceStatus::Assigned->value,
                MaintenanceStatus::WorkOrderCreated->value,
            ]
        );

        if ($isFinalApprover) {
            if (! in_array($request->status, $finalApproverStatuses, true)) {
                throw new DomainException('Only submitted or manager-approved requests can be rejected.');
            }
        } elseif (! in_array($request->status, MaintenanceStatus::approvalQueue(), true)) {
            throw new DomainException('Only submitted requests can be rejected by maintenance managers.');
        }

        $before = $request->getOriginal();

        $request->update([
            'status' => MaintenanceStatus::Rejected->value,
            'rejection_reason' => $reason,
        ]);

        $request = $request->refresh();

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: $isFinalApprover
                ? 'maintenance_request.final_rejected'
                : 'maintenance_request.rejected',
            auditable_type: $request->getMorphClass(),
            auditable_id: $request->id,
            before: $before,
            after: $request->getAttributes(),
        ));

        $this->operationalNotificationService->maintenanceRequestRejected($request, $reason);

        return $request;
    }
}
