<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;

function phaseHUserWithPermissions(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }

    $user->givePermissionTo($permissions);

    return $user;
}

test('audit logs are forbidden without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('audit-logs.index'))
        ->assertForbidden();
});

test('audit logs are accessible with permission', function () {
    $user = phaseHUserWithPermissions(['audit.view']);

    $this->actingAs($user)
        ->get(route('audit-logs.index'))
        ->assertOk();
});

test('payment approvals are forbidden without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('payment-approvals.index'))
        ->assertForbidden();
});

test('payment approvals are accessible with permission', function () {
    $user = phaseHUserWithPermissions(['payments.approve']);

    $this->actingAs($user)
        ->get(route('payment-approvals.index'))
        ->assertOk();
});


