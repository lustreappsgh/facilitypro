<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\HandlesAdminOverrides;

class UserPolicy
{
    use HandlesAdminOverrides;

    public function viewAny(User $user): bool
    {
        $override = $this->allowAdmin($user, 'viewAny');
        if ($override !== null) {
            return $override;
        }

        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        $override = $this->allowAdmin($user, 'create');
        if ($override !== null) {
            return $override;
        }

        return $user->can('users.manage');
    }

    public function update(User $user, User $model): bool
    {
        $override = $this->allowAdmin($user, 'update', $model);
        if ($override !== null) {
            return $override;
        }

        return $user->can('users.manage');
    }

    public function view(User $user, User $model): bool
    {
        $override = $this->allowAdmin($user, 'view', $model);
        if ($override !== null) {
            return $override;
        }

        return $user->can('users.view');
    }

    public function delete(User $user, User $model): bool
    {
        $override = $this->allowAdmin($user, 'delete', $model);
        if ($override !== null) {
            return $override;
        }

        return $user->can('users.manage');
    }
}
