<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class PermissionsController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->can('permissions.view')) {
            abort(403);
        }

        $search = $request->string('search')->trim()->toString();

        $permissions = Permission::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get();

        $grouped = $permissions->groupBy(function (Permission $permission) {
            $parts = explode('.', $permission->name);
            return $parts[0] ?? 'other';
        })->map(fn ($items) => $items->values());

        return Inertia::render('Permissions/Index', [
            'permissions' => $permissions,
            'groups' => $grouped,
            'filters' => [
                'search' => $search ?: null,
            ],
        ]);
    }
}
