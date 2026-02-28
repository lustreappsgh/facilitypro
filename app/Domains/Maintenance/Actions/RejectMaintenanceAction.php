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
        if (! in_array($request->status, MaintenanceStatus::approvalQueue(), true)) {
            throw new DomainException('Only submitted requests can be rejected.');
        }

        $before = $request->getOriginal();

        $request->update([
            'status' => MaintenanceStatus::Rejected->value,
        ]);

        $request = $request->refresh();

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'maintenance_request.rejected',
            auditable_type: $request->getMorphClass(),
            auditable_id: $request->id,
            before: $before,
            after: $request->getAttributes(),
        ));

        return $request;
    }
}
