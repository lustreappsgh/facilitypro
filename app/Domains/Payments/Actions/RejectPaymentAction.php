<?php

namespace App\Domains\Payments\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Models\Payment;
use App\Models\PaymentApproval;
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

        $alreadyRejected = PaymentApproval::query()
            ->where('payment_id', $payment->id)
            ->where('approver_id', $userId)
            ->where('status', 'rejected')
            ->exists();

        if ($alreadyRejected) {
            throw new DomainException('You have already rejected this payment.');
        }

        $before = $payment->getOriginal();

        PaymentApproval::create([
            'payment_id' => $payment->id,
            'approver_id' => $userId,
            'status' => 'rejected',
            'comments' => $comments,
        ]);

        $payment->update(['status' => 'rejected']);

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
