<?php

namespace App\Http\Controllers;

use App\Domains\Inspections\DTOs\InspectionData;
use App\Domains\Inspections\Requests\InspectionRequest;
use App\Domains\Inspections\Services\InspectionService;
use App\Enums\Condition;
use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\Inspection;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InspectionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected InspectionService $inspectionService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Inspection::class);

        $user = $request->user();
        $canViewAllInspections = $user->can('users.manage') || $user->can('maintenance.manage_all');

        $startDateInput = $request->input('start_date');
        $endDateInput = $request->input('end_date');
        $facilityId = $request->input('facility_id');
        $userId = $request->input('user_id');
        $search = $request->string('search')->trim()->toString();

        $defaultStart = now()->startOfWeek(Carbon::SUNDAY)->subMonth()->toDateString();
        $defaultEnd = now()->endOfWeek(Carbon::SATURDAY)->toDateString();

        $startDate = $startDateInput ?: $defaultStart;
        $endDate = $endDateInput ?: $defaultEnd;
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $facilitiesQuery = Facility::userFacilities(null, $user)->orderedForDisplay();
        $users = collect();
        if ($canViewAllInspections) {
            $usersQuery = User::query()
                ->active()
                ->orderBy('name');

            if (! $user->can('users.manage')) {
                $usersQuery->where('manager_id', $user->id);
            }

            $users = $usersQuery->get(['id', 'name', 'email']);
        }

        $baseQuery = Inspection::userVisible($user)->with([
            'facility',
            'addedBy',
        ]);

        $weeksByYearMonth = (clone $baseQuery)
            ->whereNotNull('created_at')
            ->get()
            ->sortByDesc(fn (Inspection $inspection) => $inspection->created_at?->toDateTimeString() ?? '')
            ->groupBy(fn (Inspection $inspection) => $inspection->created_at?->copy()->startOfWeek(Carbon::SUNDAY)->format('Y-m'))
            ->map(function ($monthItems, $monthKey) {
                $reference = Carbon::createFromFormat('Y-m', $monthKey);
                $weeks = $monthItems
                    ->groupBy(fn (Inspection $inspection) => $inspection->created_at?->copy()->startOfWeek(Carbon::SUNDAY)->toDateString())
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

        $filteredInspections = (clone $baseQuery)
            ->whereBetween('created_at', ["{$startDate} 00:00:00", "{$endDate} 23:59:59"])
            ->when($facilityId, fn ($query) => $query->where('facility_id', $facilityId))
            ->when($canViewAllInspections && $userId, fn ($query) => $query->where('added_by', $userId))
            ->when($search !== '', function ($query) use ($search, $canViewAllInspections) {
                $query->where(function ($builder) use ($search, $canViewAllInspections) {
                    $builder->where('condition', 'like', "%{$search}%")
                        ->orWhere('comments', 'like', "%{$search}%")
                        ->orWhereHas('facility', fn ($facilityQuery) => $facilityQuery->where('name', 'like', "%{$search}%"));

                    if ($canViewAllInspections) {
                        $builder->orWhereHas('addedBy', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                    }
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('inspection_date')
            ->get();

        $groups = $filteredInspections
            ->groupBy(fn (Inspection $inspection) => $inspection->created_at?->copy()->startOfWeek(Carbon::SUNDAY)->toDateString() ?? 'unscheduled')
            ->map(fn ($items, $weekStart) => [
                'week_start' => $weekStart,
                'week_label' => $weekStart === 'unscheduled'
                    ? 'Unscheduled'
                    : Carbon::parse($weekStart)->format('M d, Y'),
                'inspections' => $items->values()->map(fn (Inspection $inspection) => [
                    'id' => $inspection->id,
                    'inspection_date' => $inspection->inspection_date?->toDateString(),
                    'condition' => $inspection->condition,
                    'comments' => $inspection->comments,
                    'facility' => $inspection->facility ? [
                        'id' => $inspection->facility->id,
                        'name' => $inspection->facility->name,
                    ] : null,
                    'inspector_name' => $canViewAllInspections ? $inspection->addedBy?->name : null,
                ])->all(),
            ])
            ->values()
            ->all();

        return Inertia::render('Inspections/Index', [
            'data' => [
                'groups' => $groups,
                'weeks_by_year_month' => $weeksByYearMonth,
                'facilities' => $facilitiesQuery->get(),
                'show_inspector' => $canViewAllInspections,
                'users' => $users,
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'search' => $search ?: null,
                    'facility_id' => $facilityId,
                    'user_id' => $canViewAllInspections ? $userId : null,
                ],
            ],
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'routes' => [
                'create' => route('inspections.create'),
                'index' => route('inspections.index'),
            ],
            'meta' => [
                'generated_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Inspection::class);

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

        $selectedFacilityIds = collect((array) $request->input('facility_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $singleFacilityId = $request->integer('facility_id');
        if ($singleFacilityId > 0 && ! in_array($singleFacilityId, $selectedFacilityIds, true)) {
            $selectedFacilityIds[] = $singleFacilityId;
        }

        return Inertia::render('Inspections/Create', [
            'facilities' => $facilities,
            'facilityTypes' => FacilityType::query()
                ->whereIn('id', $facilityTypeIds)
                ->orderBy('name')
                ->get(['id', 'name']),
            'conditions' => collect(Condition::cases())
                ->map(fn (Condition $condition) => $condition->name)
                ->values(),
            'selectedFacilityId' => $singleFacilityId ?: null,
            'selectedFacilityIds' => $selectedFacilityIds,
        ]);
    }

    public function store(InspectionRequest $request): RedirectResponse
    {
        $this->authorize('create', Inspection::class);

        $validated = $request->validated();
        $bulkInspections = collect($validated['bulk_inspections'] ?? [])
            ->filter(fn (array $item) => isset($item['facility_id']))
            ->unique('facility_id')
            ->values()
            ->all();

        if ($bulkInspections !== []) {
            foreach ($bulkInspections as $item) {
                $data = InspectionData::fromRequest($item);
                $this->inspectionService->createInspection($data);
            }

            $redirectTo = $request->input('redirect_to') ?? route('inspections.index');

            return redirect()
                ->to($redirectTo)
                ->with('success', 'Bulk inspections submitted successfully.');
        }

        $facilityIds = $validated['facility_ids'] ?? null;

        if ($facilityIds) {
            foreach ($facilityIds as $facilityId) {
                $data = InspectionData::fromRequest([
                    ...$validated,
                    'facility_id' => $facilityId,
                ]);
                $this->inspectionService->createInspection($data);
            }

            $redirectTo = $request->input('redirect_to') ?? route('inspections.index');

            return redirect()
                ->to($redirectTo)
                ->with('success', 'Inspections submitted successfully.');
        }

        $data = InspectionData::fromRequest($validated);
        $this->inspectionService->createInspection($data);

        $redirectTo = $request->input('redirect_to') ?? route('inspections.index');

        return redirect()
            ->to($redirectTo)
            ->with('success', 'Inspection submitted successfully.');
    }

    public function show(Inspection $inspection): Response
    {
        $this->authorize('view', $inspection);

        return Inertia::render('Inspections/Show', [
            'inspection' => $inspection->load(['facility', 'addedBy']),
        ]);
    }
}
