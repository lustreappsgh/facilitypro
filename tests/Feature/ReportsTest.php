<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

function reportUserWithPermissions(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }

    $user->givePermissionTo($permissions);

    return $user;
}

test('reports page requires permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('reports.index'))->assertForbidden();
});

test('reports page shows summary data', function () {
    $user = reportUserWithPermissions(['reports.view']);

    $facilityType = FacilityType::create(['name' => 'Campus']);
    Facility::create([
        'name' => 'North Campus Gym',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('reports.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('data.summary.facilities', 1)
    );
});
