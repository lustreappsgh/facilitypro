<?php

namespace App\Policies;

use App\Models\Inspection;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminOverrides;

class InspectionPolicy
{
    use HandlesAdminOverrides;

    public function view(User $user, Inspection $inspection): bool
    {
        $override = $this->allowAdmin($user, 'view', $inspection);
        if ($override !== null) {
            return $override;
        }

        if (! $user->can('inspections.view')) {
            return false;
        }

        return $inspection->added_by === $user->id
            || $inspection->facility?->managed_by === $user->id;
    }

    public function viewAny(User $user): bool
    {
        $override = $this->allowAdmin($user, 'viewAny');
        if ($override !== null) {
            return $override;
        }

        return $user->can('inspections.view');
    }

    public function create(User $user): bool
    {
        $override = $this->allowAdmin($user, 'create');
        if ($override !== null) {
            return $override;
        }

        return $user->can('inspections.create');
    }

    public function update(User $user, Inspection $inspection): bool
    {
        $override = $this->allowAdmin($user, 'update', $inspection);
        if ($override !== null) {
            return $override;
        }

        if (! $user->can('inspections.update')) {
            return false;
        }

        return $inspection->added_by === $user->id;
    }

    public function lock(User $user, Inspection $inspection): bool
    {
        $override = $this->allowAdmin($user, 'lock', $inspection);
        if ($override !== null) {
            return $override;
        }

        return $user->can('inspections.lock');
    }
}
