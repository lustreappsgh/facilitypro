<?php

namespace App\Policies;

use App\Models\Facility;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminOverrides;

class FacilityPolicy
{
    use HandlesAdminOverrides;

    public function view(User $user, Facility $facility): bool
    {
        $override = $this->allowAdmin($user, 'view', $facility);
        if ($override !== null) {
            return $override;
        }

        if (! $user->can('facilities.view')) {
            return false;
        }

        return $facility->managed_by === $user->id;
    }

    public function create(User $user): bool
    {
        $override = $this->allowAdmin($user, 'create');
        if ($override !== null) {
            return $override;
        }

        return $user->can('facilities.create');
    }

    public function update(User $user, Facility $facility): bool
    {
        $override = $this->allowAdmin($user, 'update', $facility);
        if ($override !== null) {
            return $override;
        }

        if (! $user->can('facilities.update')) {
            return false;
        }

        return $facility->managed_by === $user->id;
    }

    public function viewAny(User $user): bool
    {
        $override = $this->allowAdmin($user, 'viewAny');
        if ($override !== null) {
            return $override;
        }

        return $user->can('facilities.view');
    }

    public function delete(User $user, Facility $facility): bool
    {
        $override = $this->allowAdmin($user, 'delete', $facility);
        if ($override !== null) {
            return $override;
        }

        return $user->can('facilities.delete');
    }

    public function assignManager(User $user, Facility $facility): bool
    {
        $override = $this->allowAdmin($user, 'assignManager', $facility);
        if ($override !== null) {
            return $override;
        }

        return $user->can('facilities.assign_manager');
    }
}
