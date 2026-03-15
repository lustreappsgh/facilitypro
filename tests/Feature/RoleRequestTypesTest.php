<?php

use App\Models\RequestType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('roles can be assigned request type approval settings', function () {
    $admin = User::factory()->create();
    Permission::findOrCreate('roles.manage');
    $admin->givePermissionTo('roles.manage');

    $role = Role::findOrCreate('Manager');
    $requestType = RequestType::firstOrCreate(['name' => 'Electrical']);

    $this->actingAs($admin)
        ->post(route('roles.request-types.update', $role), [
            'request_type_permissions' => [
                [
                    'request_type_id' => $requestType->id,
                    'can_approve' => true,
                    'can_reject' => false,
                ],
            ],
        ])
        ->assertRedirect();

    $assigned = DB::table('maintenance_request_type_role')
        ->where('role_id', $role->id)
        ->first(['request_type_id', 'can_approve', 'can_reject']);

    expect((int) $assigned->request_type_id)->toBe($requestType->id)
        ->and((bool) $assigned->can_approve)->toBeTrue()
        ->and((bool) $assigned->can_reject)->toBeFalse();
});
