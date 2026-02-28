<?php

namespace App\Http\Controllers;

use App\Domains\Inspections\DTOs\InspectionData;
use App\Domains\Inspections\Requests\InspectionRequest;
use App\Domains\Inspections\Services\InspectionService;
use App\Enums\Condition;
use App\Models\Facility;
use App\Models\Inspection;
use App\Models\RequestType;
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
        $canViewAllInspections = $user->can('users.manage');

        $startDateInput = $request->input('start_date');
        $endDateInput = $request->input('end_date');
        $facilityId = $request->input('facility_id');
        $userId = $request->input('user_id');

        $defaultStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $defaultEnd = now()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $startDate = $startDateInput ?: $defaultStart;
        $endDate = $endDateInput ?: $defaultEnd;
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $facilitiesQuery = Facility::userFacilities(null, $user);

        $baseQuery = Inspection::userVisible($user)->with([
            'facility',
            'addedBy',
        ]);

        $weeksByYearMonth = (clone $baseQuery)
            ->whereNotNull('inspection_date')
            ->get()
            ->sortByDesc(fn (Inspection $inspection) => $inspection->inspection_date?->toDateString() ?? '')
            ->groupBy(fn (Inspection $inspection) => $inspection->inspection_date?->format('Y-m'))
            ->map(function ($monthItems, $monthKey) {
                $reference = Carbon::createFromFormat('Y-m', $monthKey);
                $weeks = $monthItems
                    ->groupBy(fn (Inspection $inspection) => $inspection->inspection_date?->startOfWeek(Carbon::MONDAY)->toDateString())
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
            ->whereBetween('inspection_date', [$startDate, $endDate])
            ->when($facilityId, fn ($query) => $query->where('facility_id', $facilityId))
            ->when($canViewAllInspections && $userId, fn ($query) => $query->where('added_by', $userId))
            ->orderByDesc('inspection_date')
            ->latest()
            ->get();

        $groups = $filteredInspections
            ->groupBy(fn (Inspection $inspection) => $inspection->inspection_date?->startOfWeek(Carbon::MONDAY)->toDateString() ?? 'unscheduled')
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
                'facilities' => $facilitiesQuery->orderBy('name')->get(),
                'show_inspector' => $canViewAllInspections,
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
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

        return Inertia::render('Inspections/Create', [
            'facilities' => Facility::userFacilities(null, $request->user())->orderBy('name')->get(),
            'conditions' => collect(Condition::cases())
                ->map(fn (Condition $condition) => $condition->name)
                ->values(),
            'requestTypes' => RequestType::all(),
        ]);
    }

    public function store(InspectionRequest $request): RedirectResponse
    {
        $this->authorize('create', Inspection::class);

        $validated = $request->validated();
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
