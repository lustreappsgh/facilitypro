<?php

namespace App\Policies;

use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminOverrides;
use App\Traits\ResolvesMaintenanceScope;

class MaintenanceRequestPolicy
{
    use HandlesAdminOverrides;
    use ResolvesMaintenanceScope;

    public function view(User $user, MaintenanceRequest $maintenanceRequest): bool
    {
        $override = $this->allowAdmin($user, 'view', $maintenanceRequest);
        if ($override !== null) {
            return $override;
        }

        if (! ($user->can('maintenance.view') || $user->can('maintenance_requests.view'))) {
            return false;
        }

        if ($user->can('maintenance.manage_all')) {
            return true;
        }

        if ($user->can('maintenance_requests.view')) {
            return $this->inMaintenanceScope($user, $maintenanceRequest->facility_id);
        }

        return $maintenanceRequest->requested_by === $user->id
            || $maintenanceRequest->facility?->managed_by === $user->id;
    }

    public function viewAny(User $user): bool
    {
        $override = $this->allowAdmin($user, 'viewAny');
        if ($override !== null) {
            return $override;
        }

        return $user->can('maintenance.manage_all')
            || $user->can('maintenance.view')
            || $user->can('maintenance_requests.view');
    }

    public function update(User $user, MaintenanceRequest $maintenanceRequest): bool
    {
        $override = $this->allowAdmin($user, 'update', $maintenanceRequest);
        if ($override !== null) {
            return $override;
        }

        if (! ($user->can('maintenance.update') || $user->can('maintenance_requests.update'))) {
            return false;
        }

        if ($user->can('maintenance.manage_all')) {
            return true;
        }

        if ($user->can('maintenance_requests.update')) {
            if (! in_array($maintenanceRequest->status, MaintenanceStatus::requesterEditable(), true)) {
                return false;
            }

            return $this->inMaintenanceScope($user, $maintenanceRequest->facility_id)
                && ! $maintenanceRequest->workOrders()->exists();
        }

        return $maintenanceRequest->requested_by === $user->id
            && in_array($maintenanceRequest->status, MaintenanceStatus::requesterEditable(), true)
            && ! $maintenanceRequest->workOrders()->exists();
    }

    public function review(User $user, MaintenanceRequest $maintenanceRequest): bool
    {
        $override = $this->allowAdmin($user, 'review', $maintenanceRequest);
        if ($override !== null) {
            return $override;
        }

        if (! ($user->can('maintenance.review') || $user->can('work_orders.create'))) {
            return false;
        }

        return $user->can('maintenance.manage_all')
            || $this->inMaintenanceScope($user, $maintenanceRequest->facility_id);
    }

    public function create(User $user): bool
    {
        $override = $this->allowAdmin($user, 'create');
        if ($override !== null) {
            return $override;
        }

        return $user->can('maintenance.create') || $user->can('maintenance_requests.create');
    }

    public function assign(User $user, MaintenanceRequest $maintenanceRequest): bool
    {
        $override = $this->allowAdmin($user, 'assign', $maintenanceRequest);
        if ($override !== null) {
            return $override;
        }

        if (! $user->can('maintenance.assign')) {
            return false;
        }

        return $user->can('maintenance.manage_all')
            || $this->inMaintenanceScope($user, $maintenanceRequest->facility_id);
    }

    public function start(User $user, MaintenanceRequest $maintenanceRequest): bool
    {
        $override = $this->allowAdmin($user, 'start', $maintenanceRequest);
        if ($override !== null) {
            return $override;
        }

        if (! $user->can('maintenance.start')) {
            return false;
        }

        return $user->can('maintenance.manage_all')
            || $this->inMaintenanceScope($user, $maintenanceRequest->facility_id);
    }

    public function complete(User $user, MaintenanceRequest $maintenanceRequest): bool
    {
        $override = $this->allowAdmin($user, 'complete', $maintenanceRequest);
        if ($override !== null) {
            return $override;
        }

        if (! $user->can('maintenance.complete')) {
            return false;
        }

        return $user->can('maintenance.manage_all')
            || $this->inMaintenanceScope($user, $maintenanceRequest->facility_id);
    }

    public function close(User $user, MaintenanceRequest $maintenanceRequest): bool
    {
        $override = $this->allowAdmin($user, 'close', $maintenanceRequest);
        if ($override !== null) {
            return $override;
        }

        if (! $user->can('maintenance.close')) {
            return false;
        }

        return $user->can('maintenance.manage_all')
            || $this->inMaintenanceScope($user, $maintenanceRequest->facility_id);
    }
}
