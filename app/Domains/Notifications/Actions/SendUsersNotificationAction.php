<?php

namespace App\Domains\Notifications\Actions;

use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Notifications\DTOs\UserNotificationData;

class SendUsersNotificationAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected SendUserNotificationAction $sendUserNotificationAction
    ) {}

    /**
     * @param  array<int, int>  $userIds
     */
    public function execute(
        array $userIds,
        string $event,
        string $title,
        string $body,
        ?string $actionUrl = null,
        array $meta = [],
        string $category = 'system',
        string $severity = UserNotificationData::SeverityInfo,
        bool $excludeActor = true,
    ): void {
        $actorId = $excludeActor ? $this->resolveActorId() : null;

        collect($userIds)
            ->filter(fn ($userId) => is_int($userId))
            ->unique()
            ->reject(fn (int $userId) => $actorId !== null && $userId === $actorId)
            ->each(function (int $userId) use ($actionUrl, $body, $category, $event, $meta, $severity, $title): void {
                $this->sendUserNotificationAction->execute(new UserNotificationData(
                    user_id: $userId,
                    event: $event,
                    title: $title,
                    body: $body,
                    action_url: $actionUrl,
                    meta: $meta,
                    category: $category,
                    severity: $severity,
                ));
            });
    }
}
