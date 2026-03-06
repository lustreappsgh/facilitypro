<?php

namespace App\Domains\Maintenance\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Notifications\Actions\SendUserNotificationAction;
use App\Domains\Notifications\DTOs\UserNotificationData;
use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceRequest;
use DomainException;

class CloseMaintenanceAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction,
        protected SendUserNotificationAction $sendUserNotificationAction
    ) {}

    public function execute(MaintenanceRequest $request): MaintenanceRequest
    {
        if (! in_array($request->status, [
            MaintenanceStatus::Paid->value,
            MaintenanceStatus::Completed->value,
        ], true)) {
            throw new DomainException('Only paid requests can be closed.');
        }

        $before = $request->getOriginal();

        $request->update([
            'status' => MaintenanceStatus::Closed->value,
        ]);

        $request = $request->refresh();

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'maintenance_request.closed',
            auditable_type: $request->getMorphClass(),
            auditable_id: $request->id,
            before: $before,
            after: $request->getAttributes(),
        ));

        $this->sendUserNotificationAction->execute(new UserNotificationData(
            user_id: (int) $request->requested_by,
            event: 'maintenance_request.closed',
            title: 'Maintenance closed',
            body: 'Request #'.$request->id.' has been closed.',
            action_url: route('maintenance.show', $request),
            meta: [
                'maintenance_request_id' => $request->id,
                'status' => $request->status,
            ],
        ));

        return $request;
    }
}
