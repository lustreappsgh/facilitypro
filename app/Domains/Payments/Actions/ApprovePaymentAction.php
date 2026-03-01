<?php

namespace App\Domains\Payments\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Enums\MaintenanceStatus;
use App\Models\Payment;
use App\Models\PaymentApproval;
use DomainException;

class ApprovePaymentAction
{
    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction
    ) {}

    public function execute(Payment $payment, int $userId, ?string $comments = null): Payment
    {
        if ($payment->status !== 'pending') {
            throw new DomainException('Only pending payments can be approved.');
        }

        if ($payment->cost === null || $payment->cost <= 0) {
            throw new DomainException('Payment cost must be set before approval.');
        }

        $alreadyApproved = PaymentApproval::query()
            ->where('payment_id', $payment->id)
            ->where('approver_id', $userId)
            ->exists();

        if ($alreadyApproved) {
            throw new DomainException('You have already approved this payment.');
        }

        $before = $payment->getOriginal();

        PaymentApproval::create([
            'payment_id' => $payment->id,
            'approver_id' => $userId,
            'status' => 'approved',
            'comments' => $comments,
        ]);

        // Approval gates work-order creation; final settlement can happen later.
        if ($payment->status === 'pending') {
            $payment->update([
                'status' => 'paid',
                'amount_payed' => $payment->cost,
            ]);
        }

        $workOrder = $payment->workOrder;
        if ($workOrder && $workOrder->status === 'assigned') {
            $maintenanceRequest = $payment->maintenanceRequest;
            $workOrderBefore = $workOrder->getOriginal();
            $workOrder->update([
                'status' => 'in_progress',
                'scheduled_date' => $workOrder->scheduled_date
                    ?? $maintenanceRequest?->week_start
                    ?? $workOrder->assigned_date
                    ?? now()->toDateString(),
            ]);

            $this->recordAuditLogAction->execute(new AuditLogData(
                actor_id: $userId,
                action: 'work_order.started',
                auditable_type: $workOrder->getMorphClass(),
                auditable_id: $workOrder->id,
                before: $workOrderBefore,
                after: $workOrder->getAttributes(),
            ));
        }

        $maintenanceRequest = $payment->maintenanceRequest;
        if (
            $maintenanceRequest
            && $workOrder
            && in_array($maintenanceRequest->status, [
                MaintenanceStatus::Submitted->value,
                MaintenanceStatus::Pending->value,
                MaintenanceStatus::Rejected->value,
                MaintenanceStatus::Approved->value,
                MaintenanceStatus::WorkOrderCreated->value,
            ], true)
        ) {
            $maintenanceRequest->update([
                'status' => MaintenanceStatus::InProgress->value,
                'cost' => $payment->cost,
            ]);
        } elseif (
            $maintenanceRequest
            && in_array($maintenanceRequest->status, [
                MaintenanceStatus::Submitted->value,
                MaintenanceStatus::Pending->value,
                MaintenanceStatus::Rejected->value,
            ], true)
        ) {
            $maintenanceRequest->update([
                'status' => MaintenanceStatus::Approved->value,
                'cost' => $payment->cost,
            ]);
        } elseif (
            $maintenanceRequest
            && in_array($maintenanceRequest->status, [
                MaintenanceStatus::CompletedPendingPayment->value,
                MaintenanceStatus::Completed->value,
            ], true)
        ) {
            $maintenanceRequest->update([
                'status' => MaintenanceStatus::Paid->value,
            ]);
        }

        $payment = $payment->refresh();

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $userId,
            action: 'payment.approved',
            auditable_type: $payment->getMorphClass(),
            auditable_id: $payment->id,
            before: $before,
            after: $payment->getAttributes(),
        ));

        return $payment;
    }
}
