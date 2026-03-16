<?php

namespace App\Domains\Todos\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Notifications\Services\OperationalNotificationService;
use App\Domains\Todos\DTOs\TodoData;
use App\Enums\TodoStatus;
use App\Models\Todo;
use RuntimeException;

class UpdateTodoAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction,
        protected OperationalNotificationService $operationalNotificationService
    ) {}

    public function execute(Todo $todo, TodoData $data): Todo
    {
        if (! in_array($todo->status, [TodoStatus::Draft->value, TodoStatus::Rejected->value], true)) {
            throw new RuntimeException('Only draft or rejected todos can be updated.');
        }

        $before = $todo->getOriginal();

        $todo->update($data->toArray());

        $todo = $todo->refresh();

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'todo.updated',
            auditable_type: $todo->getMorphClass(),
            auditable_id: $todo->id,
            before: $before,
            after: $todo->getAttributes(),
        ));

        $this->operationalNotificationService->todoUpdated($todo);

        return $todo;
    }
}
