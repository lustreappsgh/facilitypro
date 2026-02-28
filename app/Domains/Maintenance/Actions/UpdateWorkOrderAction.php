<?php

namespace App\Domains\Maintenance\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceRequest;
use App\Models\WorkOrder;
use DomainException;

class UpdateWorkOrderAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction
    ) {}

    public function execute(WorkOrder $workOrder, array $attributes): WorkOrder
    {
        if (in_array($workOrder->status, ['completed', 'cancelled'], true)) {
            throw new DomainException('Completed or cancelled work orders cannot be updated.');
        }

        $before = $workOrder->getOriginal();
        $payload = $this->buildPayload($workOrder, $attributes);

        $this->validateTransition($workOrder, $payload);

        $workOrder->update($payload);
        $workOrder->refresh();

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'work_order.updated',
            auditable_type: $workOrder->getMorphClass(),
            auditable_id: $workOrder->id,
            before: $before,
            after: $workOrder->getAttributes(),
        ));

        $this->syncMaintenanceRequestStatus(
            $workOrder,
            $before['status'] ?? null,
            $workOrder->status
        );

        return $workOrder;
    }

    protected function buildPayload(WorkOrder $workOrder, array $attributes): array
    {
        $payload = collect($attributes)
            ->only(['vendor_id', 'scheduled_date', 'estimated_cost', 'actual_cost', 'status'])
            ->toArray();

        if (array_key_exists('status', $payload)) {
            $payload['status'] = $payload['status'] ?: $workOrder->status;
        }

        if (($payload['status'] ?? null) === 'completed') {
            $payload['completed_date'] = $workOrder->completed_date?->toDateString() ?? now()->toDateString();
        }

        return $payload;
    }

    protected function validateTransition(WorkOrder $workOrder, array $payload): void
    {
        if (! array_key_exists('status', $payload)) {
            return;
        }

        $current = $workOrder->status ?? 'in_progress';
        $next = $payload['status'] ?? $current;

        if ($current === $next) {
            return;
        }

        $allowed = [
            'in_progress' => ['completed', 'cancelled'],
        ];

        if (! in_array($next, $allowed[$current] ?? [], true)) {
            throw new DomainException("Work order status cannot transition from {$current} to {$next}.");
        }

        if ($next === 'completed') {
            $actualCost = $payload['actual_cost'] ?? $workOrder->actual_cost;
            if ($actualCost === null) {
                throw new DomainException('Completed work orders require an actual cost.');
            }
        }
    }

    protected function syncMaintenanceRequestStatus(
        WorkOrder $workOrder,
        ?string $previousStatus,
        ?string $nextStatus
    ): void {
        if ($nextStatus === null || $previousStatus === $nextStatus) {
            return;
        }

        $maintenanceRequest = $workOrder->maintenanceRequest;
        if (! $maintenanceRequest) {
            return;
        }

        if ($nextStatus === 'completed') {
            if (in_array(
                $maintenanceRequest->status,
                [
                    MaintenanceStatus::Completed->value,
                    MaintenanceStatus::Cancelled->value,
                ],
                true
            )) {
                return;
            }

            $this->updateMaintenanceRequest(
                $maintenanceRequest,
                MaintenanceStatus::Completed->value,
                'maintenance_request.completed'
            );

            return;
        }

        if ($nextStatus === 'cancelled') {
            if (in_array(
                $maintenanceRequest->status,
                [
                    MaintenanceStatus::Completed->value,
                    MaintenanceStatus::Cancelled->value,
                ],
                true
            )) {
                return;
            }

            $this->updateMaintenanceRequest(
                $maintenanceRequest,
                MaintenanceStatus::Cancelled->value,
                'maintenance_request.updated'
            );
        }
    }

    protected function updateMaintenanceRequest(
        MaintenanceRequest $maintenanceRequest,
        string $status,
        string $action
    ): void {
        $before = $maintenanceRequest->getOriginal();

        $maintenanceRequest->update([
            'status' => $status,
        ]);

        $maintenanceRequest->refresh();

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: $action,
            auditable_type: $maintenanceRequest->getMorphClass(),
            auditable_id: $maintenanceRequest->id,
            before: $before,
            after: $maintenanceRequest->getAttributes(),
        ));
    }
}
