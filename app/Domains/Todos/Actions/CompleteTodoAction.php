<?php

namespace App\Domains\Todos\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Notifications\Services\OperationalNotificationService;
use App\Enums\TodoStatus;
use App\Models\Todo;
use RuntimeException;

class CompleteTodoAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction,
        protected OperationalNotificationService $operationalNotificationService
    ) {}

    public function execute(Todo $todo): Todo
    {
        if (! in_array($todo->status, [TodoStatus::Pending->value, TodoStatus::Overdue->value], true)) {
            throw new RuntimeException('Only pending or overdue todos can be completed.');
        }

        $before = $todo->getOriginal();

        $todo->update([
            'status' => TodoStatus::Completed->value,
            'completed_at' => now(),
        ]);

        $todo = $todo->refresh();

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'todo.completed',
            auditable_type: $todo->getMorphClass(),
            auditable_id: $todo->id,
            before: $before,
            after: $todo->getAttributes(),
        ));

        $this->operationalNotificationService->todoCompleted($todo);

        return $todo;
    }
}
