<?php

namespace App\Http\Controllers;

use App\Domains\Facilities\DTOs\FacilityData;
use App\Domains\Facilities\Requests\FacilityHierarchyRequest;
use App\Domains\Facilities\Requests\FacilityRequest;
use App\Domains\Facilities\Services\FacilityService;
use App\Enums\Condition;
use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\RequestType;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FacilityController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected FacilityService $facilityService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Facility::class);

        $user = $request->user();
        $canManagePortfolio = $user->can('facilities.assign_manager')
            || $user->can('users.manage')
            || $user->can('maintenance.manage_all');
        $canViewMaintenanceScope = $user->can('maintenance_requests.view');
        $isFacilityManager = $user->can('facilities.view') || $user->can('facilities.update');

        if (! $canManagePortfolio && ! $canViewMaintenanceScope) {
            return $this->myFacilities($request);
        }

        $managerId = $request->query('manager_id');
        if (! $managerId && $isFacilityManager && $canViewMaintenanceScope && ! $canManagePortfolio) {
            $managerId = 'self';
        }
        $search = $request->query('search');
        $condition = $request->query('condition');
        $facilityTypeId = $request->query('facility_type_id');

        $baseFacilitiesQuery = $canManagePortfolio
            ? Facility::query()
            : Facility::maintenanceFacilities($user);

        $facilitiesQuery = (clone $baseFacilitiesQuery)
            ->with(['facilityType', 'manager', 'parent'])
            ->latest();

        if ($managerId === 'self') {
            $facilitiesQuery->where('managed_by', $user->id);
        } elseif ($managerId === 'unassigned') {
            $facilitiesQuery->whereNull('managed_by');
        } elseif ($managerId) {
            $facilitiesQuery->where('managed_by', $managerId);
        }

        if ($search) {
            $facilitiesQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereHas('manager', fn($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('facilityType', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }
        if ($condition) {
            $facilitiesQuery->where('condition', $condition);
        }
        if ($facilityTypeId) {
            $facilitiesQuery->where('facility_type_id', $facilityTypeId);
        }

        $facilities = $facilitiesQuery->paginate(50)->withQueryString();

        // Fetch facility managers for the sidebar
        $managersQuery = User::permission('inspections.view')
            ->whereDoesntHave('permissions', fn($query) => $query->where('name', 'users.manage'))
            ->orderBy('name')
            ->withCount('facilities');

        if ($user->can('maintenance_requests.view') && ! $user->can('maintenance.manage_all')) {
            $managersQuery->where('manager_id', $user->id);
        }

        $managers = $managersQuery->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'facilities_count' => $user->facilities_count,
            ]);

        // Add a "System / Unassigned" option to the managers list if necessary
        $unassignedCount = (clone $baseFacilitiesQuery)->whereNull('managed_by')->count();
        if ($unassignedCount > 0) {
            $managers->prepend([
                'id' => 'unassigned',
                'name' => 'Unassigned Portfolio',
                'facilities_count' => $unassignedCount,
            ]);
        }

        if ($isFacilityManager) {
            $selfFacilitiesCount = Facility::query()
                ->where('managed_by', $user->id)
                ->count();

            $managers->prepend([
                'id' => 'self',
                'name' => 'My Facilities',
                'facilities_count' => $selfFacilitiesCount,
            ]);
        }

        $formFacilitiesQuery = $canManagePortfolio
            ? (clone $baseFacilitiesQuery)
            : Facility::userFacilities(null, $user);

        return Inertia::render('Facilities/Index', [
            'facilities' => $facilities,
            'managers' => $managers,
            'activeManagerId' => $managerId,
            'formOptions' => [
                'facilities' => (clone $formFacilitiesQuery)->orderBy('name')->get(['id', 'name']),
                'facilityTypes' => FacilityType::orderBy('name')->get(['id', 'name']),
                'conditions' => collect(Condition::cases())
                    ->map(fn(Condition $condition) => $condition->name)
                    ->values(),
                'requestTypes' => RequestType::orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Facility::class);

        $usersQuery = User::permission('inspections.view')
            ->whereDoesntHave('permissions', fn($query) => $query->where('name', 'users.manage'))
            ->orderBy('name');

        if ($request->user()->can('maintenance_requests.view') && ! $request->user()->can('maintenance.manage_all')) {
            $usersQuery->where('manager_id', $request->user()->id);
        }

        return Inertia::render('Facilities/Create', [
            'facilityTypes' => FacilityType::all(),
            'users' => $usersQuery->get(),
            'parents' => Facility::userFacilities()->get(),
        ]);
    }

    public function store(FacilityRequest $request)
    {
        $this->authorize('create', Facility::class);

        $data = FacilityData::fromRequest($request->validated());
        $this->facilityService->createFacility($data);

        return redirect()->route('facilities.index')->with('success', 'Facility created successfully.');
    }

    public function show(Facility $facility)
    {
        $this->authorize('view', $facility);

        $lastInspection = $facility->inspections()
            ->with('addedBy')
            ->latest('inspection_date')
            ->first();

        $lastMaintenance = $facility->maintenanceRequests()
            ->latest('created_at')
            ->first();

        return Inertia::render('Facilities/Show', [
            'facility' => $facility->load(['facilityType', 'parent', 'manager']),
            'inspections' => $facility->inspections()
                ->with('addedBy')
                ->latest('inspection_date')
                ->take(5)
                ->get()
                ->map(fn($inspection) => [
                    'id' => $inspection->id,
                    'inspection_date' => $inspection->inspection_date,
                    'added_by' => $inspection->addedBy?->name,
                ]),
            'maintenanceRequests' => $facility->maintenanceRequests()
                ->with('requestType')
                ->latest('created_at')
                ->take(5)
                ->get()
                ->map(fn($request) => [
                    'id' => $request->id,
                    'status' => $request->status,
                    'created_at' => $request->created_at,
                    'request_type' => $request->requestType?->name,
                ]),
            'children' => $facility->children()
                ->with('facilityType')
                ->orderBy('name')
                ->get()
                ->map(fn($child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'condition' => $child->condition,
                    'facility_type' => $child->facilityType?->name,
                ]),
            'lastInspection' => $lastInspection
                ? [
                    'date' => $lastInspection->inspection_date,
                    'added_by' => $lastInspection->addedBy?->name,
                ]
                : null,
            'lastMaintenance' => $lastMaintenance
                ? [
                    'date' => $lastMaintenance->created_at,
                    'status' => $lastMaintenance->status,
                ]
                : null,
        ]);
    }

    public function edit(Request $request, Facility $facility)
    {
        $this->authorize('update', $facility);

        $usersQuery = User::permission('inspections.view')
            ->whereDoesntHave('permissions', fn($query) => $query->where('name', 'users.manage'))
            ->orderBy('name');

        if ($request->user()->can('maintenance_requests.view') && ! $request->user()->can('maintenance.manage_all')) {
            $usersQuery->where('manager_id', $request->user()->id);
        }

        return Inertia::render('Facilities/Edit', [
            'facility' => $facility,
            'facilityTypes' => FacilityType::all(),
            'users' => $usersQuery->get(),
            'parents' => Facility::userFacilities()
                ->where('id', '!=', $facility->id)
                ->get(),
        ]);
    }

    public function update(FacilityRequest $request, Facility $facility)
    {
        $this->authorize('update', $facility);

        $data = FacilityData::fromRequest($request->validated());

        if (! $request->user()->can('facilities.assign_manager')) {
            $data = new FacilityData(
                name: $facility->name,
                facility_type_id: $facility->facility_type_id,
                parent_id: $facility->parent_id,
                condition: $data->condition,
                managed_by: $facility->managed_by,
            );
        }

        try {
            $this->facilityService->updateFacility($facility, $data);
        } catch (DomainException $exception) {
            return back()->withErrors([
                'parent_id' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('facilities.index')->with('success', 'Facility updated successfully.');
    }

    public function myFacilities(Request $request)
    {
        $this->authorize('viewAny', Facility::class);

        $facilities = Facility::userFacilities()
            ->with([
                'facilityType',
                'parent',
                'inspections' => fn($query) => $query->latest('inspection_date')->limit(1),
            ])
            ->withCount([
                'maintenanceRequests as open_maintenance_requests_count' => fn($query) => $query
                    ->where('status', '!=', 'completed'),
            ])
            ->get();

        $parentCounts = $facilities->groupBy('parent_id')->map->count();
        $campusPayType = FacilityType::query()
            ->where('name', 'like', '%Campus Pay%')
            ->first();

        // Group by facility type
        $grouped = $facilities->groupBy('facility_type_id');

        // Prepare data for accordion
        $facilityGroups = [];
        foreach ($grouped as $typeId => $facilitiesInType) {
            $facilityType = $facilitiesInType->first()->facilityType;
            $facilityGroups[] = [
                'facility_type' => $facilityType ? [
                    'id' => $facilityType->id,
                    'name' => $facilityType->name,
                ] : null,
                'facilities' => $facilitiesInType->map(fn($f) => [
                    'id' => $f->id,
                    'name' => $f->name,
                    'condition' => $f->condition,
                    'facility_type' => $f->facilityType?->name,
                    'parent' => $f->parent ? [
                        'id' => $f->parent->id,
                        'name' => $f->parent->name,
                    ] : null,
                    'parent_facility_count' => $f->parent_id
                        ? ($parentCounts[$f->parent_id] ?? 0)
                        : null,
                    'last_inspection' => $f->inspections->first()?->inspection_date,
                    'open_requests' => $f->open_maintenance_requests_count ?? 0,
                ]),
                'count' => $facilitiesInType->count(),
            ];
        }

        if ($campusPayType) {
            $hasCampusPayGroup = collect($facilityGroups)->contains(
                fn($group) => ($group['facility_type']['id'] ?? null) === $campusPayType->id
            );

            if (! $hasCampusPayGroup) {
                $facilityGroups[] = [
                    'facility_type' => [
                        'id' => $campusPayType->id,
                        'name' => $campusPayType->name,
                    ],
                    'facilities' => [],
                    'count' => 0,
                ];
            }
        }

        // Metrics
        $totalFacilities = $facilities->count();
        $openRequests = $facilities->sum('open_maintenance_requests_count');

        return Inertia::render('Facilities/MyFacilities', [
            'facilityGroups' => $facilityGroups,
            'metrics' => [
                'totalFacilities' => $totalFacilities,
                'openRequests' => $openRequests,
            ],
            'formOptions' => [
                'facilities' => $facilities->map(fn($facility) => [
                    'id' => $facility->id,
                    'name' => $facility->name,
                ])->values(),
                'conditions' => collect(Condition::cases())
                    ->map(fn(Condition $condition) => $condition->name)
                    ->values(),
                'requestTypes' => RequestType::orderBy('name')->get(['id', 'name']),
            ],
            'permissions' => [
                'facilities.view',
                'inspections.create',
                'maintenance.create',
            ],
        ]);
    }

    public function adminIndex(Request $request)
    {
        $this->authorize('viewAny', Facility::class);

        $managerId = $request->query('manager_id');

        $facilities = Facility::query()
            ->with(['facilityType', 'manager', 'parent'])
            ->when($managerId, fn ($query) => $query->where('managed_by', $managerId))
            ->orderBy('name')
            ->get();

        return Inertia::render('Facilities/AdminIndex', [
            'facilities' => $facilities,
            'facilityTypes' => FacilityType::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'manager_id' => $managerId,
            ],
        ]);
    }

    public function updateHierarchy(FacilityHierarchyRequest $request, Facility $facility)
    {
        $this->authorize('update', $facility);

        $data = new FacilityData(
            name: $facility->name,
            facility_type_id: $facility->facility_type_id,
            parent_id: $request->validated('parent_id'),
            condition: $facility->condition,
            managed_by: $facility->managed_by,
        );

        try {
            $this->facilityService->updateFacility($facility, $data);
        } catch (DomainException $exception) {
            return back()->withErrors([
                'parent_id' => $exception->getMessage(),
            ]);
        }

        return back()->with('success', 'Facility hierarchy updated successfully.');
    }
}
