<?php

namespace App\Domains\Todos\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Todos\DTOs\TodoData;
use App\Enums\TodoStatus;
use App\Models\Facility;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class CreateTodoAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction
    ) {}

    public function execute(TodoData $data): Todo
    {
        $user = User::query()->find($data->user_id);
        $facility = Facility::query()->find($data->facility_id);

        if ($user && ! $user->can('users.manage') && $facility?->managed_by !== $user->id) {
            throw new AuthorizationException('Facility is not assigned to this manager.');
        }

        $todo = Todo::create(array_merge($data->toArray(), [
            'status' => TodoStatus::Pending->value,
        ]));


        // Force the week to be the upcoming Monday
        $todo->week_start = now()->next('Monday');
        $todo->save();

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'todo.created',
            auditable_type: $todo->getMorphClass(),
            auditable_id: $todo->id,
            before: null,
            after: $todo->getAttributes(),
        ));

        return $todo;
    }
}
