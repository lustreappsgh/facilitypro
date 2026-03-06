<?php

namespace App\Http\Controllers;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\Users\Actions\CreateUserAction;
use App\Domains\Users\Actions\GrantMaintenanceManagerAccessAction;
use App\Domains\Users\Actions\RevokeMaintenanceManagerAccessAction;
use App\Domains\Users\Actions\UpdateMaintenanceRequestTypesAction;
use App\Domains\Users\Actions\UpdateManagerReportsAction;
use App\Domains\Users\Actions\UpdateUserAction;
use App\Domains\Users\DTOs\MaintenanceRequestTypesData;
use App\Domains\Users\DTOs\ManagerAccessData;
use App\Domains\Users\DTOs\ManagerReportsData;
use App\Domains\Users\DTOs\UserData;
use App\Domains\Users\Requests\MaintenanceRequestTypesRequest;
use App\Domains\Users\Requests\UserBulkStatusRequest;
use App\Domains\Users\Requests\ManagerReportsRequest;
use App\Domains\Users\Requests\UserRequest;
use App\Models\Facility;
use App\Models\RequestType;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected CreateUserAction $createUserAction,
        protected UpdateUserAction $updateUserAction,
        protected RecordAuditLogAction $recordAuditLogAction,
        protected GrantMaintenanceManagerAccessAction $grantMaintenanceManagerAccessAction,
        protected RevokeMaintenanceManagerAccessAction $revokeMaintenanceManagerAccessAction,
        protected UpdateManagerReportsAction $updateManagerReportsAction,
        protected UpdateMaintenanceRequestTypesAction $updateMaintenanceRequestTypesAction
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $search = $request->string('search')->trim()->toString();
        $role = $request->string('role')->trim()->toString();
        $statusInput = $request->input('status');
        $status = is_string($statusInput)
            ? trim($statusInput)
            : 'active';
        $perPage = (int) $request->input('per_page', 10);
        if ($perPage <= 0) {
            $perPage = 10;
        }
        $perPage = min(max($perPage, 5), 50);

        $users = User::query()
            ->with('roles')
            ->when(
                $request->user()->can('maintenance.manage_all') && ! $request->user()->can('users.manage'),
                fn($query) => $query->where('manager_id', $request->user()->id)
            )
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role !== '', fn($query) => $query->whereHas('roles', fn($roles) => $roles->where('name', $role)))
            ->when($status !== '' && $status !== 'all', fn($query) => $query->where('is_active', $status === 'active'))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'data' => [
                'users' => $users,
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'prev_page_url' => $users->previousPageUrl(),
                    'next_page_url' => $users->nextPageUrl(),
                ],
            ],
            'filters' => [
                'search' => $search ?: null,
                'role' => $role ?: null,
                'status' => $status,
                'per_page' => $perPage,
            ],
            'roles' => Role::orderBy('name')->get(['id', 'name']),
            'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
            'routes' => [
                'index' => route('users.index'),
                'create' => route('users.create'),
                'bulkStatus' => route('users.bulk-status'),
            ],
            'meta' => [
                'generated_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Users/Create', [
            'roles' => Role::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(UserRequest $request)
    {
        $this->authorize('create', User::class);

        $payload = $request->validated();
        $password = $payload['password'] ?? Str::password(16);
        $profilePhotoPath = null;

        if ($request->hasFile('profile_photo')) {
            $profilePhotoPath = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        $data = new UserData(
            name: $payload['name'],
            email: $payload['email'],
            password: $password,
            is_active: $payload['is_active'] ?? true,
            is_default_password: ! isset($payload['password']),
            manager_id: $payload['manager_id'] ?? null,
            profile_photo_path: $profilePhotoPath,
        );

        $this->createUserAction->execute(
            $data,
            $payload['roles'] ?? []
        );

        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        $disallowedSupervisorRoles = ['Admin', 'Manager'];
        $manager = $user->manager;

        $managerOptions = User::query()
            ->active()
            ->role('Facility Manager')
            ->whereDoesntHave('roles', fn($query) => $query->whereIn('name', $disallowedSupervisorRoles))
            ->whereNull('manager_id')
            ->whereKeyNot($user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn(User $option) => [
                'id' => $option->id,
                'name' => $option->name,
                'email' => $option->email,
                'disabled' => false,
            ]);

        if ($manager && ! $managerOptions->contains('id', $manager->id)) {
            $managerOptions = $managerOptions->prepend([
                'id' => $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
                'disabled' => true,
                'note' => 'Current (not eligible)',
            ]);
        }

        $reportOptionsQuery = User::query()
            ->active()
            ->role('Facility Manager')
            ->whereKeyNot($user->id)
            ->orderBy('name');

        $reportOptions = $reportOptionsQuery->get(['id', 'name', 'email']);
        $directReportIds = User::query()
            ->active()
            ->role('Facility Manager')
            ->where('manager_id', $user->id)
            ->pluck('id')
            ->values();

        return Inertia::render('Users/Edit', [
            'user' => $user->load(['roles', 'manager']),
            'roles' => Role::orderBy('name')->get(['id', 'name']),
            'assignedRoles' => $user->roles->pluck('name')->values(),
            'managerOptions' => $managerOptions,
            'managerAssignment' => [
                'manager_id' => $user->manager_id,
                'manager' => $manager
                    ? [
                        'id' => $manager->id,
                        'name' => $manager->name,
                        'email' => $manager->email,
                    ]
                    : null,
                'has_maintenance_access' => $manager?->can('maintenance_requests.view') ?? false,
                'other_direct_reports' => $manager
                    ? $manager->subordinates()->whereKeyNot($user->id)->where('is_active', true)->count()
                    : 0,
                'is_facility_manager' => $user->can('inspections.create'),
            ],
            'reportOptions' => $reportOptions->map(fn(User $report) => [
                'id' => $report->id,
                'name' => $report->name,
                'email' => $report->email,
            ]),
            'directReportIds' => $directReportIds,
            'requestTypes' => RequestType::orderBy('name')->get(['id', 'name']),
            'allowedRequestTypeIds' => $user->maintenanceRequestTypes()
                ->pluck('request_types.id')
                ->map(fn ($id) => (int) $id)
                ->values(),
            'routes' => [
                'grantManagerAccess' => route('users.manager-access.grant', $user),
                'revokeManagerAccess' => route('users.manager-access.revoke', $user),
                'updateDirectReports' => route('users.manager-reports.update', $user),
                'updateMaintenanceRequestTypes' => route('users.maintenance-request-types.update', $user),
            ],
        ]);
    }

    public function update(UserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $payload = $request->validated();
        $profilePhotoPath = $user->profile_photo_path;

        if ($request->hasFile('profile_photo')) {
            $profilePhotoPath = $request->file('profile_photo')->store('profile-photos', 'public');

            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
        }

        $data = new UserData(
            name: $payload['name'] ?? $user->name,
            email: $payload['email'] ?? $user->email,
            password: $payload['password'] ?? null,
            is_active: $payload['is_active'] ?? $user->is_active,
            is_default_password: isset($payload['password']) && $payload['password'] ? false : $user->is_default_password,
            manager_id: $payload['manager_id'] ?? $user->manager_id,
            profile_photo_path: $profilePhotoPath,
        );

        $roles = array_key_exists('roles', $payload)
            ? ($payload['roles'] ?? [])
            : null;

        $this->updateUserAction->execute(
            $user,
            $data,
            $roles
        );

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);

        if ($request->user()->is($user)) {
            return back()->withErrors([
                'user_id' => 'You cannot delete your own account.',
            ]);
        }

        $actorId = auth()->id();
        if (! is_int($actorId)) {
            abort(500, 'Audit actor is required.');
        }

        try {
            $before = $user->getOriginal();
            $user->delete();

            $this->recordAuditLogAction->execute(new AuditLogData(
                actor_id: $actorId,
                action: 'user.deleted',
                auditable_type: $user->getMorphClass(),
                auditable_id: $user->id,
                before: $before,
                after: null,
            ));

            return back()->with('success', 'User deleted.');
        } catch (QueryException $exception) {
            $releasedFacilities = 0;
            $releasedReports = 0;
            $before = $user->getOriginal();

            DB::transaction(function () use ($user, &$releasedFacilities, &$releasedReports, $actorId, $before) {
                $releasedFacilities = Facility::query()
                    ->where('managed_by', $user->id)
                    ->update(['managed_by' => null]);

                $releasedReports = User::query()
                    ->where('manager_id', $user->id)
                    ->update(['manager_id' => null]);

                $user->forceFill([
                    'is_active' => false,
                ])->save();

                $this->recordAuditLogAction->execute(new AuditLogData(
                    actor_id: $actorId,
                    action: 'user.offboarded',
                    auditable_type: $user->getMorphClass(),
                    auditable_id: $user->id,
                    before: $before,
                    after: [
                        ...$user->getAttributes(),
                        'released_facilities' => $releasedFacilities,
                        'released_direct_reports' => $releasedReports,
                        'fallback_reason' => 'foreign_key_constraint',
                    ],
                ));
            });

            return back()->with(
                'success',
                "User could not be deleted due to related records. Account was marked inactive and {$releasedFacilities} facilities were released for reassignment."
            );
        }
    }

    public function bulkStatus(UserBulkStatusRequest $request)
    {
        if (! $request->user()->can('users.manage')) {
            abort(403);
        }

        $action = $request->validated('action');
        $userIds = $request->validated('user_ids');
        $isActive = $action === 'activate';

        if (! $isActive && in_array($request->user()->id, $userIds, true)) {
            return back()->withErrors([
                'user_ids' => 'You cannot deactivate your own account.',
            ]);
        }

        $actorId = auth()->id();
        if (! is_int($actorId)) {
            abort(500, 'Audit actor is required.');
        }

        $users = User::query()
            ->whereIn('id', $userIds)
            ->get();

        foreach ($users as $user) {
            $before = $user->getOriginal();
            $user->update(['is_active' => $isActive]);

            $this->recordAuditLogAction->execute(new AuditLogData(
                actor_id: $actorId,
                action: 'user.status_updated',
                auditable_type: $user->getMorphClass(),
                auditable_id: $user->id,
                before: $before,
                after: $user->getAttributes(),
            ));
        }

        $label = $isActive ? 'activated' : 'deactivated';

        return back()->with('success', sprintf('Users %s.', $label));
    }

    public function grantManagerAccess(User $user)
    {
        $this->authorize('update', $user);

        $manager = $user->manager;
        if (! $manager) {
            return back()->withErrors([
                'manager_id' => 'Select a manager before granting maintenance access.',
            ]);
        }

        $this->grantMaintenanceManagerAccessAction->execute(
            new ManagerAccessData(
                facility_manager_id: $user->id,
                manager_id: $manager->id,
            )
        );

        return back()->with('success', 'Maintenance manager access granted.');
    }

    public function revokeManagerAccess(User $user)
    {
        $this->authorize('update', $user);

        $manager = $user->manager;
        if (! $manager) {
            return back()->withErrors([
                'manager_id' => 'Select a manager before removing maintenance access.',
            ]);
        }

        try {
            $this->revokeMaintenanceManagerAccessAction->execute(
                new ManagerAccessData(
                    facility_manager_id: $user->id,
                    manager_id: $manager->id,
                )
            );
        } catch (DomainException $exception) {
            return back()->withErrors([
                'manager_id' => $exception->getMessage(),
            ]);
        }

        return back()->with('success', 'Maintenance manager access removed.');
    }

    public function updateManagerReports(ManagerReportsRequest $request, User $user)
    {
        $this->authorize('update', $user);

        try {
            $this->updateManagerReportsAction->execute(
                $user,
                new ManagerReportsData(
                    report_ids: $request->validated('report_ids', [])
                )
            );
        } catch (DomainException $exception) {
            return back()->withErrors([
                'report_ids' => $exception->getMessage(),
            ]);
        }

        return back()->with('success', 'Direct reports updated.');
    }

    public function updateMaintenanceRequestTypes(MaintenanceRequestTypesRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $data = new MaintenanceRequestTypesData(
            request_type_ids: $request->validated('request_type_ids', []),
        );

        $this->updateMaintenanceRequestTypesAction->execute($user, $data);

        return back()->with('success', 'Maintenance request types updated.');
    }
}
