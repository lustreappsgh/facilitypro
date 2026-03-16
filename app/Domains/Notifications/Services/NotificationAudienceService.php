<?php

namespace App\Domains\Notifications\Services;

use App\Models\Facility;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Todo;
use App\Models\User;
use App\Models\WorkOrder;

class NotificationAudienceService
{
    /**
     * @return array<int, int>
     */
    public function maintenanceRequestStakeholders(MaintenanceRequest $request): array
    {
        $request->loadMissing('facility.manager');

        return $this->uniqueIds([
            $request->requested_by,
            ...$this->facilityStakeholders($request->facility),
        ]);
    }

    /**
     * @return array<int, int>
     */
    public function maintenanceRequestAudience(MaintenanceRequest $request): array
    {
        return $this->uniqueIds([
            ...$this->maintenanceRequestStakeholders($request),
            ...$this->maintenanceReviewers($request),
        ]);
    }

    /**
     * @return array<int, int>
     */
    public function maintenanceReviewers(MaintenanceRequest $request): array
    {
        return User::query()
            ->active()
            ->get()
            ->filter(function (User $user) use ($request): bool {
                if (! $this->canAny($user, [
                    'users.manage',
                    'maintenance.manage_all',
                    'maintenance_requests.view',
                    'maintenance.start',
                    'work_orders.create',
                ])) {
                    return false;
                }

                return MaintenanceRequest::query()
                    ->maintenanceScope($user)
                    ->whereKey($request->id)
                    ->exists();
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function workOrderAudience(WorkOrder $workOrder): array
    {
        $workOrder->loadMissing('maintenanceRequest.facility.manager');

        return $this->uniqueIds([
            $workOrder->assigned_by,
            ...($workOrder->maintenanceRequest
                ? $this->maintenanceRequestStakeholders($workOrder->maintenanceRequest)
                : []),
        ]);
    }

    /**
     * @return array<int, int>
     */
    public function paymentAudience(Payment $payment): array
    {
        $payment->loadMissing('maintenanceRequest.facility.manager', 'workOrder');

        return $this->uniqueIds([
            ...($payment->maintenanceRequest
                ? $this->maintenanceRequestStakeholders($payment->maintenanceRequest)
                : []),
            ...$this->paymentReviewers($payment),
        ]);
    }

    /**
     * @return array<int, int>
     */
    public function paymentReviewers(Payment $payment): array
    {
        return User::query()
            ->active()
            ->get()
            ->filter(function (User $user) use ($payment): bool {
                if (! $user->can('payments.view')) {
                    return false;
                }

                if ($user->can('users.manage')) {
                    return true;
                }

                $maintenanceRequest = $payment->maintenanceRequest;
                if (! $maintenanceRequest) {
                    return false;
                }

                return MaintenanceRequest::query()
                    ->maintenanceScope($user)
                    ->whereKey($maintenanceRequest->id)
                    ->exists();
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function todoAudience(Todo $todo): array
    {
        $todo->loadMissing('user.manager', 'facility.manager');

        return $this->uniqueIds([
            $todo->user_id,
            $todo->user?->manager_id,
            $todo->facility?->managed_by,
        ]);
    }

    /**
     * @return array<int, int>
     */
    public function facilityStakeholders(?Facility $facility): array
    {
        if (! $facility) {
            return [];
        }

        $facility->loadMissing('manager');

        return $this->uniqueIds([
            $facility->managed_by,
            $facility->manager?->manager_id,
        ]);
    }

    /**
     * @return array<int, int>
     */
    public function userAndManagerAudience(User $user): array
    {
        $user->loadMissing('manager');

        return $this->uniqueIds([
            $user->id,
            $user->manager_id,
        ]);
    }

    protected function canAny(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, int|null>  $ids
     * @return array<int, int>
     */
    protected function uniqueIds(array $ids): array
    {
        return collect($ids)
            ->filter(fn ($id) => is_int($id))
            ->unique()
            ->values()
            ->all();
    }
}
