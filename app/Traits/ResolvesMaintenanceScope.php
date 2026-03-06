<?php

namespace App\Traits;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait ResolvesMaintenanceScope
{
    protected function maintenanceScopeManagerIds(User $user): Builder
    {
        if ($user->can('users.manage')) {
            return User::query()
                ->select('id')
                ->where('is_active', true);
        }

        // Manager oversight: only direct-report facility managers (and self for owned records).
        if ($user->can('maintenance.manage_all') && ! $user->can('users.manage')) {
            return $this->managerOversightManagerIds($user);
        }

        // Elevated maintenance scope without manager-oversight semantics (e.g. super admin grants).
        if ($user->can('maintenance.manage_all')) {
            return User::query()
                ->select('id')
                ->where('is_active', true);
        }

        // Maintenance manager intake: all active facility managers in the system.
        if ($user->can('maintenance_requests.view') && $user->can('maintenance.start')) {
            return User::query()
                ->select('id')
                ->where('is_active', true)
                ->role('Facility Manager');
        }

        return $this->managerOversightManagerIds($user);
    }

    protected function managerOversightManagerIds(User $user): Builder
    {
        return User::query()
            ->select('id')
            ->where('is_active', true)
            ->where(function ($query) use ($user) {
                $query->where('manager_id', $user->id)
                    ->orWhere('id', $user->id);
            });
    }

    protected function maintenanceScopeFacilityIds(User $user): Builder
    {
        return Facility::query()
            ->select('id')
            ->whereIn('managed_by', $this->maintenanceScopeManagerIds($user));
    }

    protected function applyMaintenanceScope(Builder $query, User $user, string $facilityColumn = 'facility_id'): Builder
    {
        return $query->whereIn($facilityColumn, $this->maintenanceScopeFacilityIds($user));
    }

    protected function inMaintenanceScope(User $user, ?int $facilityId): bool
    {
        if (! $facilityId) {
            return false;
        }

        return Facility::query()
            ->whereKey($facilityId)
            ->whereIn('managed_by', $this->maintenanceScopeManagerIds($user))
            ->exists();
    }
}
