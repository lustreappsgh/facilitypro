<?php

namespace App\Domains\Payments\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
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

        // Logic check: Does this approval finalize the payment?
        // Simple logic for now: One approval moves it to 'approved'.
        if ($payment->status === 'pending') {
            $payment->update(['status' => 'approved']);
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
