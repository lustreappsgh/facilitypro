<?php

namespace App\Domains\Notifications\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Notifications\DTOs\UserNotificationData;
use App\Domains\Notifications\Notifications\UserNotification;
use App\Models\Notification;
use App\Models\User;

class SendUserNotificationAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction
    ) {}

    public function execute(UserNotificationData $data): void
    {
        $user = User::query()
            ->active()
            ->find($data->user_id);

        if (! $user) {
            return;
        }

        $notification = new UserNotification($data);
        $user->notify($notification);

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'notification.created',
            auditable_type: Notification::class,
            auditable_id: $notification->id,
            before: null,
            after: array_merge($data->toArray(), [
                'user_id' => $user->id,
            ]),
        ));
    }
}
