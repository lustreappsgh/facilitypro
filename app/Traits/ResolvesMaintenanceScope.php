<?php

namespace App\Traits;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait ResolvesMaintenanceScope
{
    protected function maintenanceScopeManagerIds(User $user): Builder
    {
        return User::query()
            ->select('id')
            ->where('manager_id', $user->id)
            ->orWhere('id', $user->id);
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
