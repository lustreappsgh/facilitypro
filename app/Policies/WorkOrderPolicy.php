<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;
use App\Policies\Concerns\HandlesAdminOverrides;
use App\Traits\ResolvesMaintenanceScope;

class WorkOrderPolicy
{
    use HandlesAdminOverrides;
    use ResolvesMaintenanceScope;

    public function viewAny(User $user): bool
    {
        $override = $this->allowAdmin($user, 'viewAny');
        if ($override !== null) {
            return $override;
        }

        return $user->can('work_orders.view');
    }

    public function view(User $user, WorkOrder $workOrder): bool
    {
        $override = $this->allowAdmin($user, 'view', $workOrder);
        if ($override !== null) {
            return $override;
        }

        return $user->can('work_orders.view');
    }

    public function create(User $user): bool
    {
        $override = $this->allowAdmin($user, 'create');
        if ($override !== null) {
            return $override;
        }

        return $user->can('work_orders.create');
    }

    public function update(User $user, WorkOrder $workOrder): bool
    {
        $override = $this->allowAdmin($user, 'update', $workOrder);
        if ($override !== null) {
            return $override;
        }

        if (! $user->can('work_orders.update')) {
            return false;
        }

        return $user->can('maintenance.manage_all')
            || $this->inMaintenanceScope($user, $workOrder->maintenanceRequest?->facility_id);
    }

    public function delete(User $user, WorkOrder $workOrder): bool
    {
        $override = $this->allowAdmin($user, 'delete', $workOrder);
        if ($override !== null) {
            return $override;
        }

        if (! $user->can('work_orders.delete')) {
            return false;
        }

        return $user->can('maintenance.manage_all')
            || $this->inMaintenanceScope($user, $workOrder->maintenanceRequest?->facility_id);
    }
}
