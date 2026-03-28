<?php

namespace App\Http\Controllers;

use App\Domains\Maintenance\Actions\CreateWorkOrderAction;
use App\Domains\Maintenance\DTOs\MaintenanceRequestData;
use App\Domains\Maintenance\DTOs\WorkOrderData;
use App\Domains\Maintenance\Requests\BulkDeleteMaintenanceRequestsRequest;
use App\Domains\Maintenance\Requests\MaintenanceRequestRequest;
use App\Domains\Maintenance\Services\MaintenanceService;
use App\Enums\MaintenanceStatus;
use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\RequestType;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WorkOrder;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceRequestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected MaintenanceService $maintenanceService,
        protected CreateWorkOrderAction $createWorkOrderAction
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', MaintenanceRequest::class);

        $user = $request->user();
        $canViewAllRequests = $user->can('maintenance.manage_all');
        $isFacilityManager = method_exists($user, 'hasRole') && $user->hasRole('Facility Manager');
        $showRequesterName = $canViewAllRequests || $user->can('maintenance_requests.view');
        $showFacilityManagerName = $canViewAllRequests;
        $splitOwnRequests = ! $user->can('users.manage')
            && ($user->can('maintenance.manage_all') || $user->can('work_orders.create'));

        $startDateInput = $request->input('start_date');
        $endDateInput = $request->input('end_date');
        $userId = $request->input('user_id');
        $search = $request->string('search')->trim()->toString();

        $defaultStart = now()->startOfWeek(Carbon::SUNDAY)->subMonth()->toDateString();
        $defaultEnd = now()->endOfWeek(Carbon::SATURDAY)->toDateString();

        $startDate = $startDateInput ?: $defaultStart;
        $endDate = $endDateInput ?: $defaultEnd;
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $users = collect();
        if ($canViewAllRequests) {
            $usersQuery = User::query()
                ->active()
                ->orderBy('name');

            if (! $user->can('users.manage')) {
                $usersQuery->where('manager_id', $user->id);
            }

            $users = $usersQuery->get(['id', 'name', 'email']);
        }

        $baseQuery = MaintenanceRequest::maintenanceScope($user)->with([
            'facility',
            'facility.manager',
            'requestedBy',
            'requestType',
            'workOrders' => fn ($builder) => $builder->latest()->limit(1),
        ])->withCount('workOrders');

        $weeksByYearMonth = (clone $baseQuery)
            ->get()
            ->map(function (MaintenanceRequest $maintenanceRequest): MaintenanceRequest {
                if (! $maintenanceRequest->week_start && $maintenanceRequest->created_at) {
                    $maintenanceRequest->week_start = $maintenanceRequest->created_at
                        ->copy()
                        ->startOfWeek(Carbon::SUNDAY);
                }

                return $maintenanceRequest;
            })
            ->filter(fn (MaintenanceRequest $maintenanceRequest) => $maintenanceRequest->week_start)
            ->sortByDesc(fn (MaintenanceRequest $maintenanceRequest) => $maintenanceRequest->week_start?->toDateString() ?? '')
            ->groupBy(fn (MaintenanceRequest $maintenanceRequest) => $maintenanceRequest->week_start?->format('Y-m'))
            ->map(function ($monthItems, $monthKey) {
                $reference = Carbon::createFromFormat('Y-m', $monthKey);
                $weeks = $monthItems
                    ->groupBy(fn (MaintenanceRequest $maintenanceRequest) => $maintenanceRequest->week_start?->toDateString())
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
            ->whereBetween('week_start', [$startDate, $endDate])
            ->when($canViewAllRequests && $userId, fn ($query) => $query->where('requested_by', $userId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('description', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('priority', 'like', "%{$search}%")
                        ->orWhereHas('facility', fn ($facilityQuery) => $facilityQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('requestType', fn ($typeQuery) => $typeQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('requestedBy', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('week_start')
            ->orderByDesc('created_at')
            ->orderByRaw(
                "CASE
                    WHEN status = 'pending' AND work_orders_count = 0 THEN 0
                    WHEN status = 'submitted' AND work_orders_count = 0 THEN 1
                    WHEN status = 'pending' THEN 2
                    WHEN status = 'submitted' THEN 3
                    ELSE 4
                END"
            )
            ->orderByRaw(
                "CASE status
                    WHEN 'pending' THEN 0
                    WHEN 'submitted' THEN 1
                    ELSE 2
                END"
            )
            ->get();

        $groups = $filteredRequests
            ->groupBy(function (MaintenanceRequest $maintenanceRequest): string {
                if ($maintenanceRequest->week_start) {
                    return $maintenanceRequest->week_start->toDateString();
                }

                if ($maintenanceRequest->created_at) {
                    return $maintenanceRequest->created_at
                        ->copy()
                        ->startOfWeek(Carbon::SUNDAY)
                        ->toDateString();
                }

                return 'unscheduled';
            })
            ->map(fn ($items, $weekStart) => [
                'week_start' => $weekStart,
                'week_label' => $weekStart === 'unscheduled'
                    ? 'Unscheduled'
                    : Carbon::parse($weekStart)->format('M d, Y'),
                'requests' => $items->values()->map(fn (MaintenanceRequest $maintenanceRequest) => [
                    'id' => $maintenanceRequest->id,
                    'status' => $maintenanceRequest->status,
                    'submission_route' => $maintenanceRequest->submission_route,
                    'priority' => $maintenanceRequest->priority,
                    'description' => $maintenanceRequest->description,
                    'rejection_reason' => $maintenanceRequest->rejection_reason,
                    'cost' => $maintenanceRequest->cost,
                    'created_at' => $maintenanceRequest->created_at?->toDateString(),
                    'week_start' => $maintenanceRequest->week_start?->toDateString(),
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
                    'requested_by_id' => $showRequesterName
                        ? $maintenanceRequest->requested_by
                        : null,
                    'is_self_request' => (int) $maintenanceRequest->requested_by === (int) $user->id,
                    'request_type_name' => $maintenanceRequest->requestType?->name,
                    'latest_work_order_id' => $maintenanceRequest->workOrders->first()?->id,
                    'has_work_order' => ($maintenanceRequest->work_orders_count ?? 0) > 0,
                    'can_approve' => $maintenanceRequest->canBeReviewedBy($user, 'approve'),
                    'can_reject' => $maintenanceRequest->canBeReviewedBy($user, 'reject'),
                ])->all(),
            ])
            ->values()
            ->all();

        $sections = $splitOwnRequests
            ? collect([
                [
                    'key' => 'own',
                    'title' => 'My requests',
                    'description' => 'Requests you submitted directly.',
                    'groups' => collect($groups)
                        ->map(fn (array $group) => [
                            ...$group,
                            'requests' => collect($group['requests'])
                                ->filter(fn (array $item) => $item['is_self_request'])
                                ->values()
                                ->all(),
                        ])
                        ->filter(fn (array $group) => $group['requests'] !== [])
                        ->values()
                        ->all(),
                ],
                [
                    'key' => 'team',
                    'title' => 'Team requests',
                    'description' => 'Requests submitted by facility managers and direct reports in your scope.',
                    'groups' => collect($groups)
                        ->map(fn (array $group) => [
                            ...$group,
                            'requests' => collect($group['requests'])
                                ->reject(fn (array $item) => $item['is_self_request'])
                                ->values()
                                ->all(),
                        ])
                        ->filter(fn (array $group) => $group['requests'] !== [])
                        ->values()
                        ->all(),
                ],
            ])->filter(fn (array $section) => $section['groups'] !== [])->values()->all()
            : [[
                'key' => 'all',
                'title' => 'All requests',
                'description' => 'Requests in your current filter range.',
                'groups' => $groups,
            ]];

        return Inertia::render('MaintenanceRequests/Index', [
            'data' => [
                'groups' => $groups,
                'sections' => $sections,
                'weeks_by_year_month' => $weeksByYearMonth,
                'facilities' => [],
                'show_requester_name' => $showRequesterName,
                'show_facility_manager_name' => $showFacilityManagerName,
                'is_facility_manager' => $isFacilityManager,
                'split_own_requests' => $splitOwnRequests,
                'users' => $users,
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'search' => $search ?: null,
                    'facility_id' => null,
                    'user_id' => $canViewAllRequests ? $userId : null,
                ],
                'priorities' => MaintenanceRequest::priorities(),
            ],
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'routes' => [
                'create' => route('maintenance.create'),
                'bulkDestroy' => route('maintenance.bulk-destroy'),
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
                'workOrders' => fn ($builder) => $builder->latest()->limit(1),
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
            'statuses' => collect(MaintenanceStatus::cases())->map(fn (MaintenanceStatus $status) => [
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

        $facilities = ($request->user()->can('maintenance.manage_all') && ! $request->user()->can('users.manage')
            ? Facility::query()->where('facilities.managed_by', $request->user()->id)
            : ($request->user()->can('maintenance.manage_all')
                ? Facility::maintenanceFacilities($request->user())
                : Facility::userFacilities(null, $request->user())))
            ->with('facilityType:id,name')
            ->orderedForDisplay()
            ->get(['id', 'name', 'facility_type_id']);

        $facilityTypeIds = $facilities
            ->pluck('facility_type_id')
            ->filter()
            ->unique()
            ->values();

        return Inertia::render('Maintenance/Create', [
            'facilities' => $facilities,
            'facilityTypes' => FacilityType::query()
                ->whereIn('id', $facilityTypeIds)
                ->orderBy('name')
                ->get(['id', 'name']),
            'requestTypes' => RequestType::all(),
            'priorities' => MaintenanceRequest::priorities(),
            'selectedFacilityId' => $request->integer('facility_id') ?: null,
        ]);
    }

    public function store(MaintenanceRequestRequest $request)
    {
        $this->authorize('create', MaintenanceRequest::class);

        $validated = $request->validated();
        $submissionRoute = $validated['submission_route'] ?? MaintenanceRequest::SubmissionRouteMaintenanceManager;
        $bulkRequests = collect($validated['bulk_requests'] ?? [])
            ->filter(fn (array $item) => isset($item['facility_id']))
            ->unique('facility_id')
            ->values()
            ->all();

        if ($bulkRequests !== []) {
            foreach ($bulkRequests as $item) {
                $data = MaintenanceRequestData::fromRequest([
                    ...$item,
                    'submission_route' => $submissionRoute,
                ]);
                $this->maintenanceService->create($data);
            }

            $redirectTo = $request->input('redirect_to') ?? route('maintenance.index');

            return redirect()
                ->to($redirectTo)
                ->with('success', 'Bulk requests created.');
        }

        $facilityIds = $validated['facility_ids'] ?? null;

        if ($facilityIds) {
            foreach ($facilityIds as $facilityId) {
                $data = MaintenanceRequestData::fromRequest([
                    ...$validated,
                    'facility_id' => $facilityId,
                    'submission_route' => $submissionRoute,
                ]);
                $this->maintenanceService->create($data);
            }

            $redirectTo = $request->input('redirect_to') ?? route('maintenance.index');

            return redirect()
                ->to($redirectTo)
                ->with('success', 'Requests created.');
        }

        $data = MaintenanceRequestData::fromRequest([
            ...$validated,
            'submission_route' => $submissionRoute,
        ]);
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
            'vendors' => Vendor::where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'actions' => [
                'can_approve' => $maintenance->canBeReviewedBy(request()->user(), 'approve'),
                'can_reject' => $maintenance->canBeReviewedBy(request()->user(), 'reject'),
            ],
        ]);
    }

    public function edit(MaintenanceRequest $maintenance)
    {
        $this->authorize('update', $maintenance);

        return Inertia::render('Maintenance/Edit', [
            'request' => $maintenance->load(['facility', 'requestType']),
            'facilities' => (request()->user()->can('maintenance.manage_all')
                ? Facility::maintenanceFacilities(request()->user())
                : Facility::userFacilities(null, request()->user()))
                ->orderedForDisplay()
                ->get(),
            'requestTypes' => RequestType::all(),
            'priorities' => MaintenanceRequest::priorities(),
        ]);
    }

    public function update(MaintenanceRequestRequest $request, MaintenanceRequest $maintenance)
    {
        $this->authorize('update', $maintenance);

        $data = MaintenanceRequestData::fromRequest(
            $request->validated(),
            defaultSubmissionRoute: null
        );
        $this->maintenanceService->update($maintenance, $data);

        return back()->with('success', 'Request updated.');
    }

    public function destroy(Request $request, MaintenanceRequest $maintenance)
    {
        $this->authorize('delete', $maintenance);

        try {
            $maintenance->delete();
        } catch (QueryException $exception) {
            return back()->withErrors([
                'maintenance' => 'Request cannot be deleted because it has related records.',
            ]);
        }

        $redirectTo = $request->input('redirect_to');

        if (is_string($redirectTo) && $redirectTo !== '') {
            return redirect()->to($redirectTo)->with('success', 'Request deleted.');
        }

        return redirect()->route('maintenance.index')->with('success', 'Request deleted.');
    }

    public function bulkDestroy(BulkDeleteMaintenanceRequestsRequest $request)
    {
        $maintenanceRequestIds = $request->validated('maintenance_request_ids');

        $maintenanceRequests = MaintenanceRequest::query()
            ->whereIn('id', $maintenanceRequestIds)
            ->get()
            ->keyBy('id');

        if ($maintenanceRequests->count() !== count($maintenanceRequestIds)) {
            abort(403);
        }

        foreach ($maintenanceRequestIds as $maintenanceRequestId) {
            $maintenanceRequest = $maintenanceRequests->get($maintenanceRequestId);
            if (! $maintenanceRequest) {
                abort(403);
            }

            $this->authorize('delete', $maintenanceRequest);
        }

        try {
            DB::transaction(function () use ($maintenanceRequestIds, $maintenanceRequests) {
                foreach ($maintenanceRequestIds as $maintenanceRequestId) {
                    $maintenanceRequest = $maintenanceRequests->get($maintenanceRequestId);
                    if ($maintenanceRequest) {
                        $maintenanceRequest->delete();
                    }
                }
            });
        } catch (QueryException $exception) {
            return back()->withErrors([
                'maintenance' => 'One or more requests could not be deleted because they have related records.',
            ]);
        }

        $redirectTo = $request->input('redirect_to');

        if (is_string($redirectTo) && $redirectTo !== '') {
            return redirect()
                ->to($redirectTo)
                ->with('success', sprintf('Deleted %d requests.', count($maintenanceRequestIds)));
        }

        return redirect()
            ->route('maintenance.index')
            ->with('success', sprintf('Deleted %d requests.', count($maintenanceRequestIds)));
    }

    public function review(MaintenanceRequest $maintenance)
    {
        $this->authorize('approve', $maintenance);

        $comments = request()->string('comments')->trim()->toString();
        $isFinalApproval = request()->user()?->can('users.manage') === true;
        $isAdminFastTrack = $isFinalApproval && in_array($maintenance->status, [
            MaintenanceStatus::Submitted->value,
            MaintenanceStatus::Pending->value,
        ], true);
        $validated = [];

        if (! $isFinalApproval || $isAdminFastTrack) {
            $validated = request()->validate([
                'comments' => ['nullable', 'string', 'max:1000'],
                'vendor_id' => ['nullable', 'exists:vendors,id'],
                'estimated_cost' => ['nullable', 'integer', 'min:0'],
                'scheduled_date' => ['nullable', 'date'],
            ]);
        }

        try {
            if ((! $isFinalApproval || $isAdminFastTrack) && $maintenance->workOrders()->exists()) {
                throw new DomainException('This request already has a work order.');
            }

            DB::transaction(function () use ($maintenance, $validated, $isFinalApproval, $isAdminFastTrack) {
                if (! $isFinalApproval) {
                    $this->maintenanceService->review($maintenance);

                    $this->createWorkOrderAction->execute(WorkOrderData::fromRequest([
                        'maintenance_request_id' => $maintenance->id,
                        'vendor_id' => $validated['vendor_id'],
                        'estimated_cost' => $validated['estimated_cost'],
                        'scheduled_date' => $validated['scheduled_date'] ?? null,
                        'status' => 'assigned',
                    ]));
                }

                if ($isAdminFastTrack) {
                    $this->createWorkOrderAction->execute(WorkOrderData::fromRequest([
                        'maintenance_request_id' => $maintenance->id,
                        'vendor_id' => $validated['vendor_id'],
                        'estimated_cost' => $validated['estimated_cost'],
                        'scheduled_date' => $validated['scheduled_date'] ?? null,
                        'status' => 'assigned',
                    ]));
                }

                if ($isFinalApproval) {
                    $this->maintenanceService->review($maintenance->refresh());
                }
            });
        } catch (DomainException $exception) {
            return back()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        }

        if ($isAdminFastTrack) {
            return back()->with('success', 'Request approved, work order created, and payment sent for approval.');
        }

        if ($isFinalApproval) {
            return back()->with('success', 'Request finally approved and moved to in progress.');
        }

        return back()->with('success', 'Request approved and sent for final approval.');
    }

    public function reject(MaintenanceRequest $maintenance)
    {
        $this->authorize('reject', $maintenance);

        $validated = request()->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        try {
            $this->maintenanceService->reject($maintenance, $validated['rejection_reason']);
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

        $query = [
            'start_date' => $request->string('start_date')->trim()->toString() ?: null,
            'end_date' => $request->string('end_date')->trim()->toString() ?: null,
        ];

        return redirect()->route('maintenance.index', array_filter($query));
    }

    public function adminIndex(Request $request)
    {
        $this->authorize('viewAny', MaintenanceRequest::class);

        $query = MaintenanceRequest::query()
            ->with([
                'facility',
                'requestedBy',
                'requestType',
                'workOrders' => fn ($builder) => $builder->latest()->limit(1),
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

        $requests = $query->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(function (MaintenanceRequest $maintenanceRequest) use ($request): array {
                $requestType = $maintenanceRequest->requestType;
                $requestedBy = $maintenanceRequest->requestedBy;

                return [
                    'id' => $maintenanceRequest->id,
                    'status' => $maintenanceRequest->status,
                    'cost' => $maintenanceRequest->cost,
                    'description' => $maintenanceRequest->description,
                    'created_at' => $maintenanceRequest->created_at?->toDateString(),
                    'facility' => $maintenanceRequest->facility
                        ? [
                            'id' => $maintenanceRequest->facility->id,
                            'name' => $maintenanceRequest->facility->name,
                        ]
                        : null,
                    'requestedBy' => $requestedBy
                        ? [
                            'id' => $requestedBy->id,
                            'name' => $requestedBy->name,
                        ]
                        : null,
                    'requested_by' => $requestedBy
                        ? [
                            'id' => $requestedBy->id,
                            'name' => $requestedBy->name,
                        ]
                        : null,
                    'requestType' => $requestType
                        ? [
                            'id' => $requestType->id,
                            'name' => $requestType->name,
                        ]
                        : null,
                    'request_type' => $requestType
                        ? [
                            'id' => $requestType->id,
                            'name' => $requestType->name,
                        ]
                        : null,
                    'can_approve' => $maintenanceRequest->canBeReviewedBy($request->user(), 'approve'),
                    'can_reject' => $maintenanceRequest->canBeReviewedBy($request->user(), 'reject'),
                ];
            });

        return Inertia::render('MaintenanceRequests/AdminIndex', [
            'requests' => $requests,
            'statuses' => collect(MaintenanceStatus::cases())->map(fn (MaintenanceStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
            'requestTypes' => RequestType::orderBy('name')->get(['id', 'name']),
            'facilities' => Facility::maintenanceFacilities($request->user())
                ->orderedForDisplay()
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



