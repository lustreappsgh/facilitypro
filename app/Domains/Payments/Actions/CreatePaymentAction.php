<?php

namespace App\Domains\Payments\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Notifications\Services\OperationalNotificationService;
use App\Domains\Payments\DTOs\PaymentData;
use App\Models\Payment;

class CreatePaymentAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction,
        protected OperationalNotificationService $operationalNotificationService
    ) {}

    public function execute(PaymentData $data): Payment
    {
        $payment = Payment::create($data->toArray());

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'payment.created',
            auditable_type: $payment->getMorphClass(),
            auditable_id: $payment->id,
            before: null,
            after: $payment->getAttributes(),
        ));

        $this->operationalNotificationService->paymentCreated($payment);

        return $payment;
    }
}
