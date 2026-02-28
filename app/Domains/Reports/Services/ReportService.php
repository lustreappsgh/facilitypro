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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function summary(): array
    {
        return [
            'facilities' => Facility::query()->count(),
            'inspections' => Inspection::query()->count(),
            'maintenanceRequests' => MaintenanceRequest::query()->count(),
            'workOrders' => WorkOrder::query()->count(),
            'vendors' => Vendor::query()->count(),
            'paymentsPending' => Payment::query()
                ->where('status', 'pending')
                ->count(),
        ];
    }

    public function dashboard(?string $startDate = null, ?string $endDate = null): array
    {
        $cacheKey = sprintf(
            'reports.dashboard.%s.%s',
            $startDate ?? 'all',
            $endDate ?? 'all'
        );

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($startDate, $endDate) {
            $start = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
            $end = $endDate ? Carbon::parse($endDate)->endOfDay() : null;

            $paymentScope = Payment::query()
                ->when($start, fn ($query) => $query->where('created_at', '>=', $start))
                ->when($end, fn ($query) => $query->where('created_at', '<=', $end));

            $pendingApprovals = (clone $paymentScope)
                ->where('status', 'pending');

            $facilityCosts = Payment::query()
                ->select(
                    'facilities.id',
                    'facilities.name',
                    DB::raw('SUM(payments.cost) as total_cost')
                )
                ->join('maintenance_requests', 'payments.maintenance_request_id', '=', 'maintenance_requests.id')
                ->join('facilities', 'maintenance_requests.facility_id', '=', 'facilities.id')
                ->when($start, fn ($query) => $query->where('payments.created_at', '>=', $start))
                ->when($end, fn ($query) => $query->where('payments.created_at', '<=', $end))
                ->groupBy('facilities.id', 'facilities.name')
                ->orderByDesc('total_cost')
                ->limit(10)
                ->get();

            $vendorCosts = Payment::query()
                ->select(
                    'vendors.id',
                    'vendors.name',
                    DB::raw('SUM(payments.cost) as total_cost')
                )
                ->join('maintenance_requests', 'payments.maintenance_request_id', '=', 'maintenance_requests.id')
                ->join('work_orders', 'maintenance_requests.id', '=', 'work_orders.maintenance_request_id')
                ->join('vendors', 'work_orders.vendor_id', '=', 'vendors.id')
                ->when($start, fn ($query) => $query->where('payments.created_at', '>=', $start))
                ->when($end, fn ($query) => $query->where('payments.created_at', '<=', $end))
                ->groupBy('vendors.id', 'vendors.name')
                ->orderByDesc('total_cost')
                ->limit(10)
                ->get();

            $typeCosts = Payment::query()
                ->select(
                    'request_types.id',
                    'request_types.name',
                    DB::raw('SUM(payments.cost) as total_cost')
                )
                ->join('maintenance_requests', 'payments.maintenance_request_id', '=', 'maintenance_requests.id')
                ->join('request_types', 'maintenance_requests.request_type_id', '=', 'request_types.id')
                ->when($start, fn ($query) => $query->where('payments.created_at', '>=', $start))
                ->when($end, fn ($query) => $query->where('payments.created_at', '<=', $end))
                ->groupBy('request_types.id', 'request_types.name')
                ->orderByDesc('total_cost')
                ->limit(10)
                ->get();

            $trendStart = Carbon::now()->subMonths(5)->startOfMonth();
            $trend = Payment::query()
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '%Y-%m') as period"),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(cost) as total_cost')
                )
                ->where('created_at', '>=', $trendStart)
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            $trendLookup = $trend->keyBy('period');
            $trendSeries = collect(range(0, 5))
                ->map(function ($offset) use ($trendLookup, $trendStart) {
                    $period = $trendStart->copy()->addMonths($offset)->format('Y-m');
                    $data = $trendLookup->get($period);

                    return [
                        'period' => $period,
                        'count' => (int) ($data->count ?? 0),
                        'total_cost' => (int) ($data->total_cost ?? 0),
                    ];
                })
                ->values();

            return [
                'summary' => [
                    'facilities' => Facility::query()->count(),
                    'inspections' => Inspection::query()->count(),
                    'maintenanceRequests' => MaintenanceRequest::query()->count(),
                    'workOrders' => WorkOrder::query()->count(),
                    'vendors' => Vendor::query()->count(),
                    'requestTypes' => RequestType::query()->count(),
                ],
                'approvals' => [
                    'pending' => $pendingApprovals->count(),
                    'pendingCost' => $pendingApprovals->sum('cost'),
                ],
                'breakdowns' => [
                    'facilities' => $facilityCosts,
                    'vendors' => $vendorCosts,
                    'types' => $typeCosts,
                ],
                'trend' => $trendSeries,
            ];
        });
    }

    public function adminDashboard(?string $startDate = null, ?string $endDate = null): array
    {
        $cacheKey = sprintf(
            'reports.admin_dashboard.%s.%s',
            $startDate ?? 'all',
            $endDate ?? 'all'
        );

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($startDate, $endDate) {
            $start = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
            $end = $endDate ? Carbon::parse($endDate)->endOfDay() : null;

            $paymentScope = Payment::query()
                ->when($start, fn ($query) => $query->where('created_at', '>=', $start))
                ->when($end, fn ($query) => $query->where('created_at', '<=', $end));

            $trendStart = Carbon::now()->subMonths(5)->startOfMonth();

            $paymentTrendRaw = Payment::query()
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '%Y-%m') as period"),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(cost) as total_cost')
                )
                ->where('created_at', '>=', $trendStart)
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            $maintenanceTrendRaw = MaintenanceRequest::query()
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '%Y-%m') as period"),
                    DB::raw('COUNT(*) as count')
                )
                ->where('created_at', '>=', $trendStart)
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            $workOrderTrendRaw = WorkOrder::query()
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '%Y-%m') as period"),
                    DB::raw('COUNT(*) as count')
                )
                ->where('created_at', '>=', $trendStart)
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            return [
                'summary' => [
                    'users' => User::query()->count(),
                    'facilities' => Facility::query()->count(),
                    'facilityTypes' => FacilityType::query()->count(),
                    'requestTypes' => RequestType::query()->count(),
                    'inspections' => Inspection::query()->count(),
                    'maintenanceRequests' => MaintenanceRequest::query()->count(),
                    'workOrders' => WorkOrder::query()->count(),
                    'vendors' => Vendor::query()->count(),
                    'payments' => Payment::query()->count(),
                ],
                'statusBreakdown' => [
                    'maintenanceRequests' => MaintenanceRequest::query()
                        ->select('status', DB::raw('COUNT(*) as count'))
                        ->groupBy('status')
                        ->pluck('count', 'status'),
                    'workOrders' => WorkOrder::query()
                        ->select('status', DB::raw('COUNT(*) as count'))
                        ->groupBy('status')
                        ->pluck('count', 'status'),
                    'payments' => Payment::query()
                        ->select('status', DB::raw('COUNT(*) as count'))
                        ->groupBy('status')
                        ->pluck('count', 'status'),
                    'todos' => Todo::query()
                        ->select('status', DB::raw('COUNT(*) as count'))
                        ->groupBy('status')
                        ->pluck('count', 'status'),
                ],
                'costs' => [
                    'total' => (int) $paymentScope->sum('cost'),
                    'average' => (int) $paymentScope->avg('cost'),
                ],
                'aging' => [
                    'openMaintenanceOver14Days' => MaintenanceRequest::query()
                        ->whereIn('status', ['pending', 'in_progress'])
                        ->where('created_at', '<=', Carbon::now()->subDays(14))
                        ->count(),
                    'overdueWorkOrders' => WorkOrder::query()
                        ->whereNotIn('status', ['completed', 'cancelled'])
                        ->whereNotNull('scheduled_date')
                        ->whereDate('scheduled_date', '<', Carbon::today())
                        ->count(),
                    'pendingPaymentsOver30Days' => Payment::query()
                        ->where('status', 'pending')
                        ->where('created_at', '<=', Carbon::now()->subDays(30))
                        ->count(),
                ],
                'trends' => [
                    'payments' => $this->buildMonthlySeries($paymentTrendRaw, $trendStart, 6, ['count', 'total_cost']),
                    'maintenanceRequests' => $this->buildMonthlySeries($maintenanceTrendRaw, $trendStart, 6, ['count']),
                    'workOrders' => $this->buildMonthlySeries($workOrderTrendRaw, $trendStart, 6, ['count']),
                ],
            ];
        });
    }

    private function buildMonthlySeries($trend, Carbon $trendStart, int $months, array $metrics): array
    {
        $trendLookup = $trend->keyBy('period');

        return collect(range(0, $months - 1))
            ->map(function ($offset) use ($trendLookup, $trendStart, $metrics) {
                $period = $trendStart->copy()->addMonths($offset)->format('Y-m');
                $data = $trendLookup->get($period);

                $entry = ['period' => $period];
                foreach ($metrics as $metric) {
                    $entry[$metric] = (int) ($data->{$metric} ?? 0);
                }

                return $entry;
            })
            ->values()
            ->toArray();
    }
}
