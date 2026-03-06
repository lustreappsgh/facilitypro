<?php

namespace App\Domains\Notifications\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Notifications\DTOs\NotificationReadAllData;
use App\Models\User;

class MarkAllNotificationsReadAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction
    ) {}

    public function execute(NotificationReadAllData $data): void
    {
        $user = User::query()->find($data->user_id);

        if (! $user) {
            return;
        }

        $unread = $user->unreadNotifications()->get();
        $count = $unread->count();

        if ($count === 0) {
            return;
        }

        $unread->markAsRead();

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'notification.read_all',
            auditable_type: null,
            auditable_id: null,
            before: ['unread_count' => $count],
            after: ['unread_count' => 0],
        ));
    }
}
