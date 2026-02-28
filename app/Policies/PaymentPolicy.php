<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use App\Policies\Concerns\HandlesAdminOverrides;
use App\Traits\ResolvesMaintenanceScope;

class PaymentPolicy
{
    use HandlesAdminOverrides;
    use ResolvesMaintenanceScope;

    public function viewAny(User $user): bool
    {
        $override = $this->allowAdmin($user, 'viewAny');
        if ($override !== null) {
            return $override;
        }

        return $user->can('payments.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        $override = $this->allowAdmin($user, 'view', $payment);
        if ($override !== null) {
            return $override;
        }

        return $user->can('payments.view');
    }

    public function create(User $user): bool
    {
        $override = $this->allowAdmin($user, 'create');
        if ($override !== null) {
            return $override;
        }

        return $user->can('payments.create');
    }

    public function submit(User $user, Payment $payment): bool
    {
        $override = $this->allowAdmin($user, 'submit', $payment);
        if ($override !== null) {
            return $override;
        }

        return $user->can('payments.submit');
    }

    public function approve(User $user, Payment $payment): bool
    {
        $override = $this->allowAdmin($user, 'approve', $payment);
        if ($override !== null) {
            return $override;
        }

        return $user->can('payments.approve');
    }

    public function approveAny(User $user): bool
    {
        $override = $this->allowAdmin($user, 'approveAny');
        if ($override !== null) {
            return $override;
        }

        return $user->can('payments.approve');
    }

    public function reject(User $user, Payment $payment): bool
    {
        $override = $this->allowAdmin($user, 'reject', $payment);
        if ($override !== null) {
            return $override;
        }

        return $user->can('payments.reject');
    }

    public function markPaid(User $user, Payment $payment): bool
    {
        $override = $this->allowAdmin($user, 'markPaid', $payment);
        if ($override !== null) {
            return $override;
        }

        return $user->can('payments.mark_paid');
    }
}
