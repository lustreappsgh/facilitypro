<?php

namespace App\Policies;

use App\Policies\Concerns\HandlesAdminOverrides;
use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    use HandlesAdminOverrides;

    public function viewAny(User $user): bool
    {
        $override = $this->allowAdmin($user, 'viewAny');
        if ($override !== null) {
            return $override;
        }

        return $user->can('audit.view');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        $override = $this->allowAdmin($user, 'view', $auditLog);
        if ($override !== null) {
            return $override;
        }

        return $user->can('audit.view');
    }
}
