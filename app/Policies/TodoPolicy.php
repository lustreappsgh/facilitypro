<?php

namespace App\Policies;

use App\Enums\TodoStatus;
use App\Models\Todo;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminOverrides;

class TodoPolicy
{
    use HandlesAdminOverrides;

    public function viewAny(User $user): bool
    {
        $override = $this->allowAdmin($user, 'viewAny');
        if ($override !== null) {
            return $override;
        }

        return $user->can('todos.view');
    }

    public function view(User $user, Todo $todo): bool
    {
        $override = $this->allowAdmin($user, 'view', $todo);
        if ($override !== null) {
            return $override;
        }

        if (! $user->can('todos.view')) {
            return false;
        }

        return $todo->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        $override = $this->allowAdmin($user, 'create');
        if ($override !== null) {
            return $override;
        }

        return $user->can('todos.create');
    }

    public function update(User $user, Todo $todo): bool
    {
        $override = $this->allowAdmin($user, 'update', $todo);
        if ($override !== null) {
            return $override;
        }

        return $user->can('todos.update')
            && $todo->user_id === $user->id
            && $todo->status === TodoStatus::Pending->value;
    }



    public function complete(User $user, Todo $todo): bool
    {
        $override = $this->allowAdmin($user, 'complete', $todo);
        if ($override !== null) {
            return $override;
        }

        return $user->can('todos.complete')
            && $todo->user_id === $user->id
            && in_array($todo->status, [TodoStatus::Pending->value, TodoStatus::Overdue->value], true);
    }
}
