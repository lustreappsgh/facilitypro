<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminOverrides;

class ReportPolicy
{
    use HandlesAdminOverrides;

    public function viewAny(User $user): bool
    {
        $override = $this->allowAdmin($user, 'viewAny');
        if ($override !== null) {
            return $override;
        }

        return $user->can('reports.view');
    }

    public function view(User $user, Report $report): bool
    {
        $override = $this->allowAdmin($user, 'view', $report);
        if ($override !== null) {
            return $override;
        }

        return $user->can('reports.view');
    }
}
