<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;
use App\Policies\Concerns\HandlesAdminOverrides;

class VendorPolicy
{
    use HandlesAdminOverrides;

    public function viewAny(User $user): bool
    {
        $override = $this->allowAdmin($user, 'viewAny');
        if ($override !== null) {
            return $override;
        }

        return $user->can('vendors.view');
    }

    public function view(User $user, Vendor $vendor): bool
    {
        $override = $this->allowAdmin($user, 'view', $vendor);
        if ($override !== null) {
            return $override;
        }

        return $user->can('vendors.view');
    }

    public function create(User $user): bool
    {
        $override = $this->allowAdmin($user, 'create');
        if ($override !== null) {
            return $override;
        }

        return $user->can('vendors.create');
    }

    public function update(User $user, Vendor $vendor): bool
    {
        $override = $this->allowAdmin($user, 'update', $vendor);
        if ($override !== null) {
            return $override;
        }

        return $user->can('vendors.update');
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        $override = $this->allowAdmin($user, 'delete', $vendor);
        if ($override !== null) {
            return $override;
        }

        return $user->can('vendors.delete');
    }
}
