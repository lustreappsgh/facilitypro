<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;

function usersUserWithPermissions(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }

    $user->givePermissionTo($permissions);

    return $user;
}

test('users index is forbidden without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertForbidden();
});

test('users index is accessible with permission', function () {
    $user = usersUserWithPermissions(['users.view']);

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertOk();
});
