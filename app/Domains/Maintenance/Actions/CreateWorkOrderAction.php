<?php

namespace App\Domains\Maintenance\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Maintenance\DTOs\WorkOrderData;
use App\Domains\Payments\DTOs\PaymentData;
use App\Domains\Payments\Services\PaymentService;
use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\WorkOrder;
use DomainException;

class CreateWorkOrderAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction,
        protected PaymentService $paymentService
    ) {}

    public function execute(WorkOrderData $data): WorkOrder
    {
        $request = MaintenanceRequest::findOrFail($data->maintenance_request_id);

        if (! in_array($request->status, MaintenanceStatus::assignmentReady(), true)) {
            throw new DomainException('Work orders can only be created for approved requests.');
        }

        $payload = $data->toArray();
        $payload['status'] = $payload['status'] ?? 'assigned';
        $workOrder = WorkOrder::create($payload);

        if (! Payment::query()->where('work_order_id', $workOrder->id)->exists()) {
            $this->paymentService->create(new PaymentData(
                maintenance_request_id: $request->id,
                work_order_id: $workOrder->id,
                cost: 0,
                amount_payed: 0,
                status: 'pending',
                comments: "Auto-created for work order #{$workOrder->id}.",
            ));
        }

        if ($request->status !== MaintenanceStatus::WorkOrderCreated->value) {
            $before = $request->getOriginal();
            $request->update([
                'status' => MaintenanceStatus::WorkOrderCreated->value,
            ]);
            $request->refresh();

            $this->recordAuditLogAction->execute(new AuditLogData(
                actor_id: $this->resolveActorId(),
                action: 'maintenance_request.work_order_created',
                auditable_type: $request->getMorphClass(),
                auditable_id: $request->id,
                before: $before,
                after: $request->getAttributes(),
            ));
        }

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'work_order.created',
            auditable_type: $workOrder->getMorphClass(),
            auditable_id: $workOrder->id,
            before: null,
            after: $workOrder->getAttributes(),
        ));

        return $workOrder;
    }
}
