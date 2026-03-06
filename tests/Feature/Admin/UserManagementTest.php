<?php

use App\Models\AuditLog;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function adminUserWithPermissions(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }

    $user->givePermissionTo($permissions);

    return $user;
}

test('admin can view users index', function () {
    $user = adminUserWithPermissions(['users.view']);

    $this->actingAs($user);

    $response = $this->get(route('users.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page->has('data.users.data'));
});

test('users index requires permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('users.index'))->assertForbidden();
});

test('admin can create user with audit log', function () {
    $user = adminUserWithPermissions(['users.manage']);

    $this->actingAs($user);

    $response = $this->post(route('users.store'), [
        'name' => 'Admin Created',
        'email' => 'created@example.com',
        'password' => 'StrongPass123',
        'roles' => [],
        'is_active' => true,
    ]);

    $response->assertRedirect(route('users.index'));

    expect(User::query()->where('email', 'created@example.com')->exists())->toBeTrue();
    expect(AuditLog::query()->where('action', 'user.created')->exists())->toBeTrue();
});

test('admin can update user and audit log is captured', function () {
    Role::findOrCreate('Admin');
    $user = adminUserWithPermissions(['users.manage']);
    $user->assignRole('Admin');
    $target = User::factory()->create([
        'name' => 'Original Name',
    ]);

    $this->actingAs($user);

    $response = $this->put(route('users.update', $target), [
        'name' => 'Updated Name',
        'email' => $target->email,
        'roles' => [],
        'is_active' => true,
    ]);

    $response->assertRedirect(route('users.index'));

    expect($target->refresh()->name)->toBe('Updated Name');
    expect(AuditLog::query()->where('action', 'user.updated')->exists())->toBeTrue();
});

test('admin can update user email and role even when the user already has a manager assigned', function () {
    Role::findOrCreate('Admin');
    Role::findOrCreate('Manager');
    Role::findOrCreate('Facility Manager');

    $admin = adminUserWithPermissions(['users.manage']);
    $admin->assignRole('Admin');

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $target = User::factory()->create([
        'email' => 'before@example.com',
        'manager_id' => $manager->id,
    ]);
    $target->assignRole('Facility Manager');

    $this->actingAs($admin);

    $response = $this->put(route('users.update', $target), [
        'name' => $target->name,
        'email' => 'after@example.com',
        'roles' => ['Manager'],
        'is_active' => true,
        'manager_id' => $manager->id,
    ]);

    $response->assertRedirect(route('users.index'));

    $target->refresh();

    expect($target->email)->toBe('after@example.com');
    expect($target->hasRole('Manager'))->toBeTrue();
    expect($target->manager_id)->toBeNull();
});

test('admin can bulk deactivate users with audit log', function () {
    $user = adminUserWithPermissions(['users.manage']);
    $target = User::factory()->create(['is_active' => true]);

    $this->actingAs($user);

    $response = $this->post(route('users.bulk-status'), [
        'action' => 'deactivate',
        'user_ids' => [$target->id],
    ]);

    $response->assertRedirect();

    expect($target->refresh()->is_active)->toBeFalse();
    expect(
        AuditLog::query()
            ->where('action', 'user.status_updated')
            ->where('auditable_id', $target->id)
            ->exists()
    )->toBeTrue();
});
