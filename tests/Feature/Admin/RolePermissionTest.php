<?php

use App\Models\AuditLog;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function adminRoleUser(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }

    $user->givePermissionTo($permissions);

    return $user;
}

test('admin can create role with permissions', function () {
    $user = adminRoleUser(['roles.manage']);
    Permission::findOrCreate('reports.view');

    $this->actingAs($user);

    $response = $this->post(route('roles.store'), [
        'name' => 'Finance Lead',
        'permissions' => ['reports.view'],
    ]);

    $response->assertRedirect(route('roles.index'));

    expect(Role::query()->where('name', 'Finance Lead')->exists())->toBeTrue();
    expect(AuditLog::query()->where('action', 'role.created')->exists())->toBeTrue();
});

test('admin can update and delete role', function () {
    $user = adminRoleUser(['roles.manage']);
    Permission::findOrCreate('reports.view');

    $role = Role::create(['name' => 'Ops Lead']);

    $this->actingAs($user);

    $update = $this->put(route('roles.update', $role), [
        'name' => 'Ops Director',
        'permissions' => ['reports.view'],
    ]);

    $update->assertRedirect(route('roles.index'));
    expect($role->refresh()->name)->toBe('Ops Director');
    expect(AuditLog::query()->where('action', 'role.updated')->exists())->toBeTrue();

    $delete = $this->delete(route('roles.destroy', $role));
    $delete->assertRedirect();
    expect(Role::query()->where('id', $role->id)->exists())->toBeFalse();
    expect(AuditLog::query()->where('action', 'role.deleted')->exists())->toBeTrue();
});
