<?php

namespace App\Domains\Users\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Users\DTOs\UserData;
use App\Models\User;
use Illuminate\Support\Arr;

class CreateUserAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction
    ) {}

    /**
     * @param  array<int, string>  $roles
     */
    public function execute(UserData $data, array $roles = []): User
    {
        $user = User::create($data->toCreateArray());

        if ($roles !== []) {
            $user->assignRole($roles);
        }

        $after = array_merge($user->getAttributes(), [
            'roles' => Arr::sort($user->getRoleNames()->toArray()),
        ]);

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'user.created',
            auditable_type: $user->getMorphClass(),
            auditable_id: $user->id,
            before: null,
            after: $after,
        ));

        return $user;
    }
}
