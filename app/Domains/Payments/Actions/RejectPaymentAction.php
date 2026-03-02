<?php

namespace App\Domains\Payments\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Enums\MaintenanceStatus;
use App\Models\Payment;
use App\Models\PaymentApproval;
use App\Models\User;
use DomainException;

class RejectPaymentAction
{
    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction
    ) {}

    public function execute(Payment $payment, int $userId, string $comments): Payment
    {
        if ($payment->status !== 'pending') {
            throw new DomainException('Only pending payments can be rejected.');
        }

        $actorColumn = PaymentApproval::actorColumn();

        $alreadyRejected = PaymentApproval::query()
            ->where('payment_id', $payment->id)
            ->where($actorColumn, $userId)
            ->where('status', 'rejected')
            ->exists();

        if ($alreadyRejected) {
            throw new DomainException('You have already rejected this payment.');
        }

        $before = $payment->getOriginal();

        $approvalData = [
            'payment_id' => $payment->id,
            $actorColumn => $userId,
            'status' => 'rejected',
            'comments' => $comments,
        ];

        if (PaymentApproval::hasApprovalLevelColumn()) {
            $approver = User::query()->find($userId);
            $approvalData['approval_level'] = $approver?->can('maintenance.manage_all') ? 'admin' : 'manager';
        }

        PaymentApproval::create($approvalData);

        $payment->update(['status' => 'rejected']);

        if ($payment->maintenanceRequest) {
            $payment->maintenanceRequest->update([
                'status' => MaintenanceStatus::Rejected->value,
            ]);
        }

        $payment = $payment->refresh();

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $userId,
            action: 'payment.rejected',
            auditable_type: $payment->getMorphClass(),
            auditable_id: $payment->id,
            before: $before,
            after: $payment->getAttributes(),
        ));

        return $payment;
    }
}
