<?php

use App\Models\RequestType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('roles can be assigned request type access', function () {
    $admin = User::factory()->create();
    Permission::findOrCreate('roles.manage');
    $admin->givePermissionTo('roles.manage');

    $role = Role::findOrCreate('Manager');
    $requestType = RequestType::firstOrCreate(['name' => 'Electrical']);

    $this->actingAs($admin)
        ->post(route('roles.request-types.update', $role), [
            'request_type_ids' => [$requestType->id],
        ])
        ->assertRedirect();

    $assigned = DB::table('maintenance_request_type_role')
        ->where('role_id', $role->id)
        ->pluck('request_type_id')
        ->all();

    expect($assigned)->toBe([$requestType->id]);
});
