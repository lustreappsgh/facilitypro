<?php

namespace App\Domains\Todos\Services;

use App\Domains\Todos\Actions\CompleteTodoAction;
use App\Domains\Todos\Actions\CreateTodoAction;
use App\Domains\Todos\Actions\UpdateTodoAction;
use App\Domains\Todos\DTOs\TodoData;
use App\Models\Todo;
use Illuminate\Support\Collection;

class TodoService
{
    public function __construct(
        protected CreateTodoAction $createTodoAction,
        protected UpdateTodoAction $updateTodoAction,
        protected CompleteTodoAction $completeTodoAction,
    ) {}

    public function create(TodoData $data): Todo
    {
        return $this->createTodoAction->execute($data);
    }

    public function update(Todo $todo, TodoData $data): Todo
    {
        return $this->updateTodoAction->execute($todo, $data);
    }

    public function complete(Todo $todo): Todo
    {
        return $this->completeTodoAction->execute($todo);
    }
}
