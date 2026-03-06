<?php

namespace App\Http\Controllers;

use App\Domains\Todos\DTOs\TodoData;

use App\Domains\Todos\Requests\TodoBulkCompleteRequest;
use App\Domains\Todos\Requests\TodoRequest;
use App\Domains\Todos\Services\TodoService;
use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TodoController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected TodoService $todoService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Todo::class);

        $user = $request->user();
        $canViewAllTodos = $user->can('users.manage') || $user->can('maintenance.manage_all');

        $startDateInput = $request->input('start_date');
        $endDateInput = $request->input('end_date');
        $facilityId = $request->input('facility_id');
        $userId = $request->input('user_id');

        $defaultStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $defaultEnd = now()->addWeek()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $startDate = $startDateInput ?: $defaultStart;
        $endDate = $endDateInput ?: $defaultEnd;
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $facilitiesQuery = Facility::userFacilities(null, $user);
        $users = collect();
        if ($canViewAllTodos) {
            $usersQuery = User::query()
                ->active()
                ->orderBy('name');

            if (! $user->can('users.manage')) {
                $usersQuery->where('manager_id', $user->id);
            }

            $users = $usersQuery->get(['id', 'name', 'email']);
        }

        $baseQuery = Todo::userVisible($user)
            ->with([
                'facility',
                'facility.manager',
            ]);

        $weeksByYearMonth = (clone $baseQuery)
            ->whereNotNull('week_start')
            ->get()
            ->sortByDesc(fn (Todo $todo) => $todo->week_start?->toDateString() ?? '')
            ->groupBy(fn (Todo $todo) => $todo->week_start?->format('Y-m'))
            ->map(function ($monthItems, $monthKey) {
                $reference = Carbon::createFromFormat('Y-m', $monthKey);
                $weeks = $monthItems
                    ->groupBy(fn (Todo $todo) => $todo->week_start?->toDateString())
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

        $filteredTodos = (clone $baseQuery)
            ->whereBetween('week_start', [$startDate, $endDate])
            ->when($facilityId, fn ($query) => $query->where('facility_id', $facilityId))
            ->when($canViewAllTodos && $userId, fn ($query) => $query->where('user_id', $userId))
            ->orderByDesc('week_start')
            ->orderByRaw(
                "CASE status
                    WHEN 'pending' THEN 0
                    WHEN 'overdue' THEN 1
                    ELSE 2
                END"
            )
            ->latest()
            ->get();

        $groups = $filteredTodos
            ->groupBy(fn (Todo $todo) => $todo->week_start?->toDateString() ?? 'unscheduled')
            ->map(fn ($items, $weekStart) => [
                'week_start' => $weekStart,
                'week_label' => $weekStart === 'unscheduled'
                    ? 'Unscheduled'
                    : Carbon::parse($weekStart)->format('M d, Y'),
                'todos' => $items->values()->map(fn (Todo $todo) => [
                    'id' => $todo->id,
                    'description' => $todo->description,
                    'status' => $todo->status,
                    'week_start' => $todo->week_start?->toDateString(),
                    'completed_at' => $todo->completed_at?->toDateTimeString(),
                    'facility' => $todo->facility ? [
                        'id' => $todo->facility->id,
                        'name' => $todo->facility->name,
                    ] : null,
                    'facility_manager_name' => $canViewAllTodos
                        ? $todo->facility?->manager?->name
                        : null,
                ])->all(),
            ])
            ->values()
            ->all();
            
        return Inertia::render('Todos/Index', [
            'data' => [
                'groups' => $groups,
                'weeks_by_year_month' => $weeksByYearMonth,
                'facilities' => $facilitiesQuery->orderBy('name')->get(),
                'show_manager_name' => $canViewAllTodos,
                'users' => $users,
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'facility_id' => $facilityId,
                    'user_id' => $canViewAllTodos ? $userId : null,
                ],
            ],
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'routes' => [
                'create' => route('todos.create'),
                'index' => route('todos.index'),
                'bulkComplete' => route('todos.bulk-complete'),
            ],
            'meta' => [
                'generated_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Todo::class);

        $facilities = Facility::userFacilities(null, $request->user())
            ->with('facilityType:id,name')
            ->orderBy('name')
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

        return Inertia::render('Todos/Create', [
            'data' => [
                'facilities' => $facilities,
                'facilityTypes' => FacilityType::query()
                    ->whereIn('id', $facilityTypeIds)
                    ->orderBy('name')
                    ->get(['id', 'name']),
                'selectedFacilityId' => $singleFacilityId ?: null,
                'selectedFacilityIds' => $selectedFacilityIds,
            ],
            'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
            'routes' => [
                'store' => route('todos.store'),
                'index' => route('todos.index'),
            ],
            'meta' => [
                'generated_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    public function store(TodoRequest $request): RedirectResponse
    {
        $this->authorize('create', Todo::class);

        $validated = $request->validated();
        $bulkTodos = collect($validated['bulk_todos'] ?? [])
            ->filter(fn (array $item) => isset($item['facility_id']))
            ->unique('facility_id')
            ->values()
            ->all();

        if ($bulkTodos !== []) {
            foreach ($bulkTodos as $item) {
                $data = TodoData::fromRequest([
                    ...$item,
                    'user_id' => auth()->id(),
                ]);
                $this->todoService->create($data);
            }

            $redirectTo = $request->input('redirect_to') ?? route('todos.index');

            return redirect()
                ->to($redirectTo)
                ->with('success', 'Bulk todos created.');
        }

        if (! empty($validated['facility_ids'])) {
            foreach ($validated['facility_ids'] as $facilityId) {
                $data = new TodoData(
                    facility_id: (int) $facilityId,
                    description: $validated['description'],
                    week: $validated['week'] ?? now()->next('Monday')->format('Y-m-d'),
                    user_id: auth()->id(),
                );
                $this->todoService->create($data);
            }

            return redirect()->route('todos.index')->with('success', 'Todos created.');
        }

        $data = TodoData::fromRequest($validated);
        $this->todoService->create($data);

        return redirect()->route('todos.index')->with('success', 'Todo created.');
    }

    public function edit(Request $request, Todo $todo): Response
    {
        $this->authorize('update', $todo);

        $facilitiesQuery = Facility::userFacilities(null, $request->user());

        return Inertia::render('Todos/Edit', [
            'data' => [
                'todo' => $todo->load('facility'),
                'facilities' => $facilitiesQuery->orderBy('name')->get(),
            ],
            'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
            'routes' => [
                'update' => route('todos.update', $todo),
                'index' => route('todos.index'),
            ],
            'meta' => [
                'generated_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    public function update(TodoRequest $request, Todo $todo): RedirectResponse
    {
        $this->authorize('update', $todo);

        $data = TodoData::fromRequest($request->validated());
        $this->todoService->update($todo, $data);

        return redirect()->route('todos.index')->with('success', 'Todo updated.');
    }



    public function complete(Request $request, Todo $todo): RedirectResponse
    {
        $this->authorize('complete', $todo);

        $this->todoService->complete($todo);

        return back()->with('success', 'Todo marked complete.');
    }

    public function bulkComplete(TodoBulkCompleteRequest $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->can('todos.complete')) {
            abort(403);
        }

        $todoIds = $request->validated('todo_ids');
        $todos = Todo::query()->whereIn('id', $todoIds)->get();

        $completedCount = 0;
        foreach ($todos as $todo) {
            if (! $user->can('complete', $todo)) {
                continue;
            }

            $this->todoService->complete($todo);
            $completedCount++;
        }

        if ($completedCount === 0) {
            return back()->withErrors([
                'todo_ids' => 'No selected todos could be completed.',
            ]);
        }

        return back()->with('success', sprintf('%d todo(s) marked complete.', $completedCount));
    }

    public function weeklyIndex(Request $request): RedirectResponse
    {
        return redirect()->route('todos.index', $request->only(['start_date', 'end_date', 'facility_id']));
    }


}
