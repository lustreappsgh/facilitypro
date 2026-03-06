<?php

namespace App\Domains\Notifications\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Notifications\DTOs\NotificationReadData;
use App\Models\Notification;

class MarkNotificationReadAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction
    ) {}

    public function execute(NotificationReadData $data): ?Notification
    {
        $notification = Notification::query()->find($data->notification_id);

        if (! $notification) {
            return null;
        }

        $before = $notification->getOriginal();

        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        $notification = $notification->refresh();

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'notification.read',
            auditable_type: $notification->getMorphClass(),
            auditable_id: $notification->id,
            before: $before,
            after: $notification->getAttributes(),
        ));

        return $notification;
    }
}
