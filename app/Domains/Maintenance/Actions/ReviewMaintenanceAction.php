<?php

namespace App\Domains\Maintenance\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Notifications\Services\OperationalNotificationService;
use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceRequest;
use App\Models\WorkOrder;
use DomainException;

class ReviewMaintenanceAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction,
        protected OperationalNotificationService $operationalNotificationService
    ) {}

    public function execute(MaintenanceRequest $request): MaintenanceRequest
    {
        $actor = auth()->user();
        if (! $actor) {
            throw new DomainException('Authenticated user is required.');
        }

        $isFinalApprover = $actor->can('users.manage');

        if ($isFinalApprover) {
            if (! in_array($request->status, [
                MaintenanceStatus::Approved->value,
                MaintenanceStatus::Assigned->value,
                MaintenanceStatus::WorkOrderCreated->value,
            ], true)) {
                throw new DomainException('Only manager-approved requests can receive final approval.');
            }
        } elseif (! in_array($request->status, MaintenanceStatus::approvalQueue(), true)) {
            throw new DomainException('Only submitted requests can be approved by maintenance managers.');
        }

        $before = $request->getOriginal();

        $request->update([
            'status' => $isFinalApprover
                ? MaintenanceStatus::InProgress->value
                : MaintenanceStatus::Approved->value,
        ]);

        $request = $request->refresh();
        $this->syncWorkOrdersForFinalApproval($request, $isFinalApprover);

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: $isFinalApprover
                ? 'maintenance_request.final_approved'
                : 'maintenance_request.approved',
            auditable_type: $request->getMorphClass(),
            auditable_id: $request->id,
            before: $before,
            after: $request->getAttributes(),
        ));

        $this->operationalNotificationService->maintenanceRequestApproved($request);

        return $request;
    }

    protected function syncWorkOrdersForFinalApproval(MaintenanceRequest $request, bool $isFinalApprover): void
    {
        if (! $isFinalApprover) {
            return;
        }

        $request->workOrders()
            ->where('status', 'assigned')
            ->get()
            ->each(function (WorkOrder $workOrder): void {
                $before = $workOrder->getOriginal();

                $workOrder->update([
                    'status' => 'in_progress',
                ]);

                $workOrder->refresh();

                $this->recordAuditLogAction->execute(new AuditLogData(
                    actor_id: $this->resolveActorId(),
                    action: 'work_order.updated',
                    auditable_type: $workOrder->getMorphClass(),
                    auditable_id: $workOrder->id,
                    before: $before,
                    after: $workOrder->getAttributes(),
                ));
            });
    }
}
