<?php

namespace App\Domains\Users\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Users\DTOs\UserData;
use App\Models\User;
use Illuminate\Support\Arr;

class UpdateUserAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction
    ) {}

    /**
     * @param  array<int, string>|null  $roles
     */
    public function execute(User $user, UserData $data, ?array $roles = null): User
    {
        $before = array_merge($user->getOriginal(), [
            'roles' => Arr::sort($user->getRoleNames()->toArray()),
        ]);

        $payload = $data->toArray();
        if ($data->password !== null) {
            $payload['password'] = $data->password;
        }

        $user->update($payload);

        if (is_array($roles)) {
            $user->syncRoles($roles);
        }
        $user = $user->refresh();

        $after = array_merge($user->getAttributes(), [
            'roles' => Arr::sort($user->getRoleNames()->toArray()),
        ]);

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'user.updated',
            auditable_type: $user->getMorphClass(),
            auditable_id: $user->id,
            before: $before,
            after: $after,
        ));

        return $user;
    }
}
