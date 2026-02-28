<?php

namespace App\Domains\Reports\Services;

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\Inspection;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\RequestType;
use App\Models\Todo;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WorkOrder;
use Carbon\Carbon;
use App\Enums\MaintenanceStatus;
use App\Enums\TodoStatus;

class DashboardService
{
    public function forUser(User $user): array
    {
        $data = [];
        $facilityManagers = null;

        if ($user->can('inspections.create')) {
            $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
            $weekEnd = Carbon::now()->endOfWeek(Carbon::SUNDAY);

            $data['facilityManager'] = [
                'inspectionsSubmitted' => Inspection::userVisible($user)
                    ->count(),
                'openMaintenanceRequests' => MaintenanceRequest::userRequests($user)
                    ->whereIn('status', MaintenanceStatus::active())
                    ->count(),
                'facilitiesManaged' => Facility::userFacilities(null, $user)
                    ->count(),
                'inspectionsThisWeek' => Inspection::userVisible($user)
                    ->whereBetween('inspection_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                    ->count(),
                'todosThisWeek' => Todo::userVisible($user)
                    ->whereBetween('week_start', [$weekStart->toDateString(), $weekEnd->toDateString()])
                    ->count(),
                'pendingTodos' => Todo::userVisible($user)
                    ->whereIn('status', [TodoStatus::Pending->value, TodoStatus::Overdue->value])
                    ->count(),
                'pendingRequests' => MaintenanceRequest::maintenanceScope($user)
                    ->whereIn('status', MaintenanceStatus::approvalQueue())
                    ->count(),
            ];
        }

        if ($user->can('work_orders.create') || $user->can('maintenance.start')) {
            $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
            $weekEnd = Carbon::now()->endOfWeek(Carbon::SUNDAY);

            $data['maintenanceManager'] = [
                'openRequests' => MaintenanceRequest::userRequests($user)
                ->whereIn('status', MaintenanceStatus::active())
                    ->count(),
                'pendingRequests' => MaintenanceRequest::userRequests($user)
                    ->whereIn('status', MaintenanceStatus::approvalQueue())
                    ->count(),
                'requestsThisWeek' => MaintenanceRequest::userRequests($user)
                    ->whereBetween('created_at', ["{$weekStart->toDateString()} 00:00:00", "{$weekEnd->toDateString()} 23:59:59"])
                    ->count(),
                'workOrdersInFlight' => WorkOrder::userVisible($user)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->count(),
                'workOrdersThisWeek' => WorkOrder::userVisible($user)
                    ->whereBetween('assigned_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                    ->count(),
                'pendingPayments' => Payment::query()
                    ->whereHas('maintenanceRequest', fn ($q) => $q->maintenanceScope($user))
                    ->where('status', 'pending')
                    ->count(),
            ];
        }

        if ($user->can('payments.approve')) {
            $facilityManagers ??= $this->facilityManagers();
            $todoSummary = $this->todoSummary();
            $inspectionSummary = $this->inspectionSummary();
            $oldestPending = Payment::query()
                ->whereHas('maintenanceRequest', fn ($q) => $q->maintenanceScope($user))
                ->where('status', 'pending')
                ->orderBy('created_at')
                ->first(['created_at', 'cost']);

            $highCostThreshold = 10000;
            $highCostPending = Payment::query()
                ->whereHas('maintenanceRequest', fn ($q) => $q->maintenanceScope($user))
                ->where('status', 'pending')
                ->where('cost', '>=', $highCostThreshold);

            $data['manager'] = [
                'pendingApprovals' => Payment::query()
                    ->whereHas('maintenanceRequest', fn ($q) => $q->maintenanceScope($user))
                    ->where('status', 'pending')
                    ->count(),
                'pendingApprovalCost' => Payment::query()
                    ->whereHas('maintenanceRequest', fn ($q) => $q->maintenanceScope($user))
                    ->where('status', 'pending')
                    ->sum('cost'),
                'oldestPendingDate' => $oldestPending?->created_at?->toDateString(),
                'oldestPendingDays' => $oldestPending?->created_at?->diffInDays(Carbon::now()),
                'highCostThreshold' => $highCostThreshold,
                'highCostPendingCount' => $highCostPending->count(),
                'highCostPendingTotal' => $highCostPending->sum('cost'),
                'facilityManagers' => $facilityManagers,
                'todoSummary' => $todoSummary,
                'inspectionSummary' => $inspectionSummary,
            ];
        }

        if ($user->can('audit.view')) {
            $facilityManagers ??= $this->facilityManagers();
            $staleThresholdDays = 14;
            $staleDate = Carbon::now()->subDays($staleThresholdDays);
            $userCards = $this->userCards();

            $data['admin'] = [
                'totals' => [
                    'facilities' => Facility::query()->count(),
                    'facilityTypes' => FacilityType::query()->count(),
                    'requestTypes' => RequestType::query()->count(),
                    'users' => User::query()->count(),
                    'inspections' => Inspection::query()->count(),
                    'maintenanceRequests' => MaintenanceRequest::query()->count(),
                    'vendors' => Vendor::query()->count(),
                    'workOrders' => WorkOrder::query()->count(),
                    'payments' => Payment::query()->count(),
                ],
                'pending' => [
                    'maintenanceRequests' => MaintenanceRequest::query()
                        ->whereIn('status', MaintenanceStatus::active())
                        ->count(),
                    'workOrders' => WorkOrder::query()
                        ->whereNotIn('status', ['completed', 'cancelled'])
                        ->count(),
                    'payments' => Payment::query()
                        ->where('status', 'pending')
                        ->count(),
                    'todos' => Todo::query()
                        ->where('status', 'submitted')
                        ->count(),
                ],
                'health' => [
                    'inactiveUsers' => User::query()
                        ->where('is_active', false)
                        ->count(),
                    'overdueWorkOrders' => WorkOrder::query()
                        ->whereNotIn('status', ['completed', 'cancelled'])
                        ->whereNotNull('scheduled_date')
                        ->whereDate('scheduled_date', '<', Carbon::today())
                        ->count(),
                    'staleMaintenanceRequests' => MaintenanceRequest::query()
                        ->whereIn('status', MaintenanceStatus::approvalQueue())
                        ->where('created_at', '<=', $staleDate)
                        ->count(),
                    'staleThresholdDays' => $staleThresholdDays,
                ],
                'facilityManagers' => $facilityManagers,
                'users' => $userCards,
            ];
        }

        return $data;
    }

    protected function facilityManagers(): array
    {
        return User::query()
            ->role('Facility Manager')
            ->withCount('facilities')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'profile_photo_path', 'is_active'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo_url' => $user->profile_photo_url,
                'is_active' => $user->is_active,
                'facilities_managed' => $user->facilities_count ?? 0,
            ])
            ->all();
    }

    protected function userCards(): array
    {
        $lastWeekStart = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->toDateString();
        $lastWeekEnd = Carbon::now()->subWeek()->endOfWeek(Carbon::SUNDAY)->toDateString();
        $today = Carbon::today()->toDateString();

        return User::query()
            ->with(['roles'])
            ->whereDoesntHave('roles', fn ($query) => $query->whereIn('name', ['Super Admin', 'Admin']))
            ->withCount(['facilities', 'maintenanceRequestsRequested'])
            ->addSelect([
                'inspections_last_week' => Inspection::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('added_by', 'users.id')
                    ->whereBetween('inspection_date', [$lastWeekStart, $lastWeekEnd]),
                'upcoming_todos' => Todo::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('user_id', 'users.id')
                    ->whereDate('week_start', '>=', $today)
                    ->where('status', '!=', TodoStatus::Completed->value),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'profile_photo_path', 'is_active'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo_url' => $user->profile_photo_url,
                'is_active' => $user->is_active,
                'facilities_managed' => $user->facilities_count ?? 0,
                'inspections_last_week' => (int) ($user->inspections_last_week ?? 0),
                'upcoming_todos' => (int) ($user->upcoming_todos ?? 0),
                'requests_submitted' => (int) ($user->maintenance_requests_requested_count ?? 0),
                'roles' => $user->roles->pluck('name')->values()->all(),
            ])
            ->all();
    }

    protected function todoSummary(): array
    {
        return [
            'pending' => Todo::query()
                ->where('status', TodoStatus::Pending->value)
                ->count(),
            'overdue' => Todo::query()
                ->where('status', TodoStatus::Overdue->value)
                ->count(),
            'total' => Todo::query()->count(),
        ];
    }

    protected function inspectionSummary(): array
    {
        $latest = Inspection::query()
            ->latest('inspection_date')
            ->first(['inspection_date']);

        return [
            'total' => Inspection::query()->count(),
            'last_7_days' => Inspection::query()
                ->whereDate('inspection_date', '>=', Carbon::now()->subDays(7))
                ->count(),
            'latest_date' => $latest?->inspection_date?->toDateString(),
        ];
    }
}
