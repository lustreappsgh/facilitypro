<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('maintenance manager can view vendors but cannot create', function () {
    $user = User::factory()->create();

    Permission::findOrCreate('vendors.view');
    $user->givePermissionTo('vendors.view');
    $user->assignRole(Role::findOrCreate('Maintenance Manager'));

    $this->actingAs($user);

    $this->get(route('vendors.index'))->assertOk();
    $this->get(route('vendors.create'))->assertForbidden();
});
