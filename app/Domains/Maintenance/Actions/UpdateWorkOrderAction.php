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

class UpdateWorkOrderAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction,
        protected OperationalNotificationService $operationalNotificationService
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

        $this->operationalNotificationService->workOrderUpdated($workOrder);

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
        $vendorChanging = array_key_exists('vendor_id', $payload)
            && (string) ($payload['vendor_id'] ?? '') !== (string) ($workOrder->vendor_id ?? '');
        $statusChanging = array_key_exists('status', $payload)
            && ($payload['status'] ?? $workOrder->status) !== $workOrder->status;

        if ($vendorChanging && empty($payload['vendor_id'])) {
            throw new DomainException('Vendor is required once assignment begins.');
        }

        if (! array_key_exists('status', $payload)) {
            return;
        }

        $current = $workOrder->status ?? 'assigned';
        $next = $payload['status'] ?? $current;

        if ($current === $next) {
            return;
        }

        $allowed = [
            'assigned' => ['in_progress', 'cancelled'],
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

            $vendorId = $payload['vendor_id'] ?? $workOrder->vendor_id;
            if (! $vendorId) {
                throw new DomainException('Assign a vendor before completing this work order.');
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
            $paymentStatus = $workOrder->payment?->status;
            $targetStatus = in_array($paymentStatus, ['approved', 'paid'], true)
                ? MaintenanceStatus::Completed->value
                : MaintenanceStatus::CompletedPendingPayment->value;

            if (in_array(
                $maintenanceRequest->status,
                [
                    MaintenanceStatus::Completed->value,
                    MaintenanceStatus::Closed->value,
                    MaintenanceStatus::Cancelled->value,
                ],
                true
            )) {
                return;
            }

            $this->updateMaintenanceRequest(
                $maintenanceRequest,
                $targetStatus,
                'maintenance_request.completed'
            );

            return;
        }

        if ($nextStatus === 'in_progress') {
            if ($maintenanceRequest->status === MaintenanceStatus::InProgress->value) {
                return;
            }

            if (in_array($maintenanceRequest->status, MaintenanceStatus::terminal(), true)) {
                return;
            }

            $this->updateMaintenanceRequest(
                $maintenanceRequest,
                MaintenanceStatus::InProgress->value,
                'maintenance_request.started'
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
