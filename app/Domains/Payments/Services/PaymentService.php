<?php

namespace App\Domains\Payments\Services;

use App\Domains\Payments\Actions\ApprovePaymentAction;
use App\Domains\Payments\Actions\CreatePaymentAction;
use App\Domains\Payments\Actions\RejectPaymentAction;
use App\Domains\Payments\DTOs\PaymentData;
use App\Models\Payment;

class PaymentService
{
    public function __construct(
        protected CreatePaymentAction $createAction,
        protected ApprovePaymentAction $approveAction,
        protected RejectPaymentAction $rejectAction
    ) {}

    public function create(PaymentData $data): Payment
    {
        return $this->createAction->execute($data);
    }

    public function approve(Payment $payment, int $userId, ?string $comments = null): Payment
    {
        return $this->approveAction->execute($payment, $userId, $comments);
    }

    public function reject(Payment $payment, int $userId, string $comments): Payment
    {
        return $this->rejectAction->execute($payment, $userId, $comments);
    }
}
