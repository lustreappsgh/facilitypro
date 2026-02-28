<?php

namespace App\Http\Controllers;

use App\Domains\Maintenance\DTOs\MaintenanceRequestData;
use App\Domains\Maintenance\Requests\MaintenanceRequestRequest;
use App\Domains\Maintenance\Services\MaintenanceService;
use App\Enums\MaintenanceStatus;
use App\Models\Facility;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\RequestType;
use App\Models\WorkOrder;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceRequestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected MaintenanceService $maintenanceService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', MaintenanceRequest::class);

        $user = $request->user();
        $canViewAllRequests = $user->can('maintenance.manage_all');
        $showRequesterName = $canViewAllRequests || $user->can('maintenance_requests.view');
        $showFacilityManagerName = $canViewAllRequests;

        $startDateInput = $request->input('start_date');
        $endDateInput = $request->input('end_date');
        $facilityId = $request->input('facility_id');
        $condition = $request->input('condition');
        $userId = $request->input('user_id');

        $defaultStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $defaultEnd = now()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $startDate = $startDateInput ?: $defaultStart;
        $endDate = $endDateInput ?: $defaultEnd;
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $facilitiesQuery = $canViewAllRequests || $user->can('users.manage')
            ? Facility::query()
            : Facility::maintenanceFacilities($user);

        $baseQuery = MaintenanceRequest::maintenanceScope($user)->with([
            'facility',
            'facility.manager',
            'requestedBy',
            'requestType',
            'workOrders' => fn ($builder) => $builder->latest()->limit(1),
        ]);

        $weeksByYearMonth = (clone $baseQuery)
            ->get()
            ->sortByDesc(fn (MaintenanceRequest $maintenanceRequest) => $maintenanceRequest->created_at?->toDateString() ?? '')
            ->groupBy(fn (MaintenanceRequest $maintenanceRequest) => $maintenanceRequest->created_at?->format('Y-m'))
            ->map(function ($monthItems, $monthKey) {
                $reference = Carbon::createFromFormat('Y-m', $monthKey);
                $weeks = $monthItems
                    ->groupBy(fn (MaintenanceRequest $maintenanceRequest) => $maintenanceRequest->created_at?->startOfWeek(Carbon::MONDAY)->toDateString())
                    ->keys()
                    ->sortDesc()
                    ->values()
                    ->map(fn (string $weekStart) => [
                        'week_start' => $weekStart,
                        'label' => Carbon::parse($weekStart)->format('M d, Y'),
                    ])
                    ->all();

                return [
                    'year' => (int) $reference->year,
                    'month' => $reference->format('F'),
                    'month_key' => $reference->format('Y-m'),
                    'weeks' => $weeks,
                ];
            })
            ->values()
            ->all();

        $filteredRequests = (clone $baseQuery)
            ->whereBetween('created_at', ["{$startDate} 00:00:00", "{$endDate} 23:59:59"])
            ->when($facilityId, fn ($query) => $query->where('facility_id', $facilityId))
            ->when($condition, fn ($query) => $query->whereHas('facility', fn ($facilityQuery) => $facilityQuery->where('condition', $condition)))
            ->when($canViewAllRequests && $userId, fn ($query) => $query->where('requested_by', $userId))
            ->orderByDesc('created_at')
            ->latest()
            ->get();

        $groups = $filteredRequests
            ->groupBy(fn (MaintenanceRequest $maintenanceRequest) => $maintenanceRequest->created_at?->startOfWeek(Carbon::MONDAY)->toDateString() ?? 'unscheduled')
            ->map(fn ($items, $weekStart) => [
                'week_start' => $weekStart,
                'week_label' => $weekStart === 'unscheduled'
                    ? 'Unscheduled'
                    : Carbon::parse($weekStart)->format('M d, Y'),
                'requests' => $items->values()->map(fn (MaintenanceRequest $maintenanceRequest) => [
                    'id' => $maintenanceRequest->id,
                    'status' => $maintenanceRequest->status,
                    'description' => $maintenanceRequest->description,
                    'cost' => $maintenanceRequest->cost,
                    'created_at' => $maintenanceRequest->created_at?->toDateString(),
                    'facility' => $maintenanceRequest->facility ? [
                        'id' => $maintenanceRequest->facility->id,
                        'name' => $maintenanceRequest->facility->name,
                    ] : null,
                    'facility_manager_name' => $showFacilityManagerName
                        ? $maintenanceRequest->facility?->manager?->name
                        : null,
                    'requested_by_name' => $showRequesterName
                        ? $maintenanceRequest->requestedBy?->name
                        : null,
                    'request_type_name' => $maintenanceRequest->requestType?->name,
                    'latest_work_order_id' => $maintenanceRequest->workOrders->first()?->id,
                ])->all(),
            ])
            ->values()
            ->all();

        return Inertia::render('MaintenanceRequests/Index', [
            'data' => [
                'groups' => $groups,
                'weeks_by_year_month' => $weeksByYearMonth,
                'facilities' => $facilitiesQuery->orderBy('name')->get(),
                'conditions' => (clone $facilitiesQuery)
                    ->whereNotNull('condition')
                    ->distinct()
                    ->orderBy('condition')
                    ->pluck('condition')
                    ->values(),
                'show_requester_name' => $showRequesterName,
                'show_facility_manager_name' => $showFacilityManagerName,
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'facility_id' => $facilityId,
                    'condition' => $condition,
                    'user_id' => $canViewAllRequests ? $userId : null,
                ],
            ],
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'routes' => [
                'create' => route('maintenance.create'),
                'index' => route('maintenance.index'),
            ],
            'meta' => [
                'generated_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    public function oversight(Request $request)
    {
        if (! $request->user()->can('maintenance.manage_all')) {
            abort(403);
        }

        $query = MaintenanceRequest::userRequests($request->user())
            ->with([
                'workOrders' => fn($builder) => $builder->latest()->limit(1),
            ]);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();
        $requestType = $request->string('request_type')->trim()->toString();
        $facility = $request->string('facility')->trim()->toString();
        $startDate = $request->string('start_date')->trim()->toString();
        $endDate = $request->string('end_date')->trim()->toString();

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('description', 'like', "%{$search}%")
                    ->orWhereHas('facility', function ($facilityQuery) use ($search) {
                        $facilityQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($requestType !== '') {
            $query->where('request_type_id', $requestType);
        }

        if ($facility !== '') {
            $query->where('facility_id', $facility);
        }

        if ($startDate !== '') {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate !== '') {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return Inertia::render('MaintenanceRequests/Oversight', [
            'requests' => $query->latest()->paginate(10)->withQueryString(),
            'statuses' => collect(MaintenanceStatus::cases())->map(fn(MaintenanceStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
            'requestTypes' => RequestType::orderBy('name')->get(['id', 'name']),
            'facilities' => Facility::maintenanceFacilities($request->user())
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => [
                'search' => $search ?: null,
                'status' => $status ?: null,
                'request_type' => $requestType ?: null,
                'facility' => $facility ?: null,
                'start_date' => $startDate ?: null,
                'end_date' => $endDate ?: null,
            ],
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', MaintenanceRequest::class);

        return Inertia::render('Maintenance/Create', [
            'facilities' => Facility::userFacilities(null, $request->user())
                ->orderBy('name')
                ->get(),
            'requestTypes' => RequestType::all(),
        ]);
    }

    public function store(MaintenanceRequestRequest $request)
    {
        $this->authorize('create', MaintenanceRequest::class);

        $validated = $request->validated();
        $facilityIds = $validated['facility_ids'] ?? null;

        if ($facilityIds) {
            foreach ($facilityIds as $facilityId) {
                $data = MaintenanceRequestData::fromRequest([
                    ...$validated,
                    'facility_id' => $facilityId,
                ]);
                $this->maintenanceService->create($data);
            }

            $redirectTo = $request->input('redirect_to') ?? route('maintenance.index');

            return redirect()
                ->to($redirectTo)
                ->with('success', 'Requests created.');
        }

        $data = MaintenanceRequestData::fromRequest($validated);
        $this->maintenanceService->create($data);

        $redirectTo = $request->input('redirect_to') ?? route('maintenance.index');

        return redirect()
            ->to($redirectTo)
            ->with('success', 'Request created.');
    }

    public function show(MaintenanceRequest $maintenance)
    {
        $this->authorize('view', $maintenance);

        return Inertia::render('MaintenanceRequests/Show', [
            'request' => $maintenance->load([
                'facility.facilityType',
                'facility.manager',
                'requestedBy',
                'requestType',
            ]),
            'workOrders' => WorkOrder::with(['vendor'])
                ->where('maintenance_request_id', $maintenance->id)
                ->latest()
                ->get(),
            'payments' => Payment::query()
                ->with(['approvals.approver'])
                ->where('maintenance_request_id', $maintenance->id)
                ->latest()
                ->get(),
        ]);
    }

    public function edit(MaintenanceRequest $maintenance)
    {
        $this->authorize('update', $maintenance);

        return Inertia::render('Maintenance/Edit', [
            'request' => $maintenance->load(['facility', 'requestType']),
            'facilities' => Facility::userFacilities(null, request()->user())
                ->orderBy('name')
                ->get(),
            'requestTypes' => RequestType::all(),
        ]);
    }

    public function update(MaintenanceRequestRequest $request, MaintenanceRequest $maintenance)
    {
        $this->authorize('update', $maintenance);

        $data = MaintenanceRequestData::fromRequest($request->validated());
        $this->maintenanceService->update($maintenance, $data);

        return back()->with('success', 'Request updated.');
    }

    public function review(MaintenanceRequest $maintenance)
    {
        $this->authorize('review', $maintenance);
        try {
            $this->maintenanceService->review($maintenance);
        } catch (DomainException $exception) {
            return back()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        }

        return back()->with('success', 'Request approved.');
    }

    public function reject(MaintenanceRequest $maintenance)
    {
        $this->authorize('review', $maintenance);
        try {
            $this->maintenanceService->reject($maintenance);
        } catch (DomainException $exception) {
            return back()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        }

        return back()->with('success', 'Request rejected.');
    }

    public function approve(MaintenanceRequest $maintenance)
    {
        return $this->review($maintenance);
    }

    public function start(MaintenanceRequest $maintenance)
    {
        $this->authorize('start', $maintenance);
        try {
            $this->maintenanceService->start($maintenance);
        } catch (DomainException $exception) {
            return back()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        }

        return back()->with('success', 'Maintenance started.');
    }

    public function complete(MaintenanceRequest $maintenance)
    {
        $this->authorize('complete', $maintenance);
        try {
            $this->maintenanceService->complete($maintenance);
        } catch (DomainException $exception) {
            return back()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        }

        return back()->with('success', 'Maintenance marked complete and pending payment.');
    }

    public function close(MaintenanceRequest $maintenance)
    {
        $this->authorize('close', $maintenance);
        try {
            $this->maintenanceService->close($maintenance);
        } catch (DomainException $exception) {
            return back()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        }

        return back()->with('success', 'Maintenance request closed.');
    }

    public function myRequests(Request $request)
    {
        $this->authorize('viewAny', MaintenanceRequest::class);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();
        $facility = $request->string('facility')->trim()->toString();
        $week = $request->string('week')->trim()->toString();
        $startDate = $request->string('start_date')->trim()->toString();
        $endDate = $request->string('end_date')->trim()->toString();

        $query = MaintenanceRequest::query()
            ->with([
                'facility',
                'requestedBy',
                'requestType',
                'workOrders' => fn($builder) => $builder->latest()->limit(1),
            ])
            ->where('requested_by', $request->user()->id)
            ->when($week, function ($query, $week) {
                $date = \Carbon\Carbon::parse($week);
                $query->whereDate('created_at', '>=', $date->startOfWeek())
                    ->whereDate('created_at', '<=', $date->endOfWeek());
            });

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('description', 'like', "%{$search}%")
                    ->orWhereHas('facility', function ($facilityQuery) use ($search) {
                        $facilityQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($facility !== '') {
            $query->where('facility_id', $facility);
        }

        if ($startDate !== '') {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate !== '') {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $statsQuery = clone $query;
        $activeCount = (clone $statsQuery)
            ->whereIn('status', MaintenanceStatus::active())
            ->count();
        $estimatedCost = (clone $statsQuery)->sum('cost');
        // Calculate average resolution days in PHP for SQLite compatibility
        $completedRequests = (clone $statsQuery)
            ->whereIn('status', [
                MaintenanceStatus::Closed->value,
                MaintenanceStatus::Completed->value,
            ])
            ->get(['created_at', 'updated_at']);

        $avgResolutionDays = null;
        if ($completedRequests->isNotEmpty()) {
            $totalDays = $completedRequests->sum(function ($request) {
                return $request->created_at->diffInDays($request->updated_at);
            });
            $avgResolutionDays = $totalDays / $completedRequests->count();
        }

        $requests = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('MaintenanceRequests/MyRequests', [
            'requests' => $requests,
            'statuses' => collect(MaintenanceStatus::cases())->map(fn(MaintenanceStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
            'facilities' => Facility::userFacilities(null, $request->user())
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => [
                'search' => $search ?: null,
                'status' => $status ?: null,
                'facility' => $facility ?: null,
                'week' => $week ?: null,
                'start_date' => $startDate ?: null,
                'end_date' => $endDate ?: null,
            ],
            'stats' => [
                'active_count' => $activeCount,
                'estimated_cost' => $estimatedCost,
                'avg_resolution_days' => $avgResolutionDays !== null ? round((float) $avgResolutionDays, 1) : null,
            ],
            'requestTypes' => RequestType::all(),
        ]);
    }

    public function adminIndex(Request $request)
    {
        $this->authorize('viewAny', MaintenanceRequest::class);

        $query = MaintenanceRequest::query()
            ->with([
                'facility',
                'requestedBy',
                'requestType',
                'workOrders' => fn($builder) => $builder->latest()->limit(1),
            ]);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();
        $requestType = $request->string('request_type')->trim()->toString();
        $facility = $request->string('facility')->trim()->toString();
        $startDate = $request->string('start_date')->trim()->toString();
        $endDate = $request->string('end_date')->trim()->toString();

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('description', 'like', "%{$search}%")
                    ->orWhereHas('facility', function ($facilityQuery) use ($search) {
                        $facilityQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($requestType !== '') {
            $query->where('request_type_id', $requestType);
        }

        if ($facility !== '') {
            $query->where('facility_id', $facility);
        }

        if ($startDate !== '') {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate !== '') {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return Inertia::render('MaintenanceRequests/AdminIndex', [
            'requests' => $query->latest()->paginate(10)->withQueryString(),
            'statuses' => collect(MaintenanceStatus::cases())->map(fn(MaintenanceStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
            'requestTypes' => RequestType::orderBy('name')->get(['id', 'name']),
            'facilities' => Facility::maintenanceFacilities($request->user())
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => [
                'search' => $search ?: null,
                'status' => $status ?: null,
                'request_type' => $requestType ?: null,
                'facility' => $facility ?: null,
                'start_date' => $startDate ?: null,
                'end_date' => $endDate ?: null,
            ],
        ]);
    }
}
