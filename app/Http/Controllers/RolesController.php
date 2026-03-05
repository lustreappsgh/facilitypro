<?php

namespace App\Http\Controllers;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\Roles\Requests\RoleRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesController extends Controller
{
    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction
    ) {}

    public function index(Request $request)
    {
        if (! $request->user()->can('roles.manage')) {
            abort(403);
        }

        $search = $request->string('search')->trim()->toString();

        $roles = Role::query()
            ->with('permissions')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Roles/Index', [
            'data' => [
                'roles' => $roles,
            ],
            'filters' => [
                'search' => $search ?: null,
            ],
            'permissions' => Permission::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request)
    {
        if (! $request->user()->can('roles.manage')) {
            abort(403);
        }

        return Inertia::render('Roles/Create', [
            'permissions' => Permission::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(RoleRequest $request)
    {
        if (! $request->user()->can('roles.manage')) {
            abort(403);
        }

        $payload = $request->validated();
        $role = Role::create(['name' => $payload['name']]);
        $role->syncPermissions($payload['permissions'] ?? []);

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $request->user()->id,
            action: 'role.created',
            auditable_type: $role->getMorphClass(),
            auditable_id: $role->id,
            before: null,
            after: [
                'name' => $role->name,
                'permissions' => $role->getPermissionNames()->toArray(),
            ],
        ));

        return redirect()->route('roles.index')->with('success', 'Role created.');
    }

    public function edit(Request $request, Role $role)
    {
        if (! $request->user()->can('roles.manage')) {
            abort(403);
        }

        return Inertia::render('Roles/Edit', [
            'role' => $role->load('permissions'),
            'permissions' => Permission::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(RoleRequest $request, Role $role)
    {
        if (! $request->user()->can('roles.manage')) {
            abort(403);
        }

        $before = [
            'name' => $role->name,
            'permissions' => $role->getPermissionNames()->toArray(),
        ];

        $payload = $request->validated();
        $role->update(['name' => $payload['name']]);
        $role->syncPermissions($payload['permissions'] ?? []);

        $role = $role->refresh();

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $request->user()->id,
            action: 'role.updated',
            auditable_type: $role->getMorphClass(),
            auditable_id: $role->id,
            before: $before,
            after: [
                'name' => $role->name,
                'permissions' => $role->getPermissionNames()->toArray(),
            ],
        ));

        return redirect()->route('roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Request $request, Role $role)
    {
        if (! $request->user()->can('roles.manage')) {
            abort(403);
        }

        if (User::role($role->name)->active()->exists()) {
            return back()->withErrors([
                'role' => 'Role is assigned to users and cannot be deleted.',
            ]);
        }

        $before = [
            'name' => $role->name,
            'permissions' => $role->getPermissionNames()->toArray(),
        ];

        $role->delete();

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $request->user()->id,
            action: 'role.deleted',
            auditable_type: $role->getMorphClass(),
            auditable_id: $role->id,
            before: $before,
            after: null,
        ));

        return back()->with('success', 'Role deleted.');
    }
}
