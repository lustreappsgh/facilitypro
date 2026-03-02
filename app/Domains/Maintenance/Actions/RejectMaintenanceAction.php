<?php

namespace App\Domains\Maintenance\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceRequest;
use DomainException;

class RejectMaintenanceAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction
    ) {}

    public function execute(MaintenanceRequest $request): MaintenanceRequest
    {
        $actor = auth()->user();
        if (! $actor) {
            throw new DomainException('Authenticated user is required.');
        }

        $isFinalApprover = $actor->can('maintenance.manage_all');

        if ($isFinalApprover) {
            if (! in_array($request->status, [
                MaintenanceStatus::Approved->value,
                MaintenanceStatus::Assigned->value,
                MaintenanceStatus::WorkOrderCreated->value,
            ], true)) {
                throw new DomainException('Only manager-approved requests can be rejected by admin.');
            }
        } elseif (! in_array($request->status, MaintenanceStatus::approvalQueue(), true)) {
            throw new DomainException('Only submitted requests can be rejected by maintenance managers.');
        }

        $before = $request->getOriginal();

        $request->update([
            'status' => MaintenanceStatus::Rejected->value,
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

        return $request;
    }
}
