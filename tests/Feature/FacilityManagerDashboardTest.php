<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('facility manager dashboard includes facilities managed count', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('inspections.create');
    Permission::findOrCreate('facilities.view');
    $user->givePermissionTo(['inspections.create', 'facilities.view']);
    $user->assignRole(Role::findOrCreate('Facility Manager'));

    $facilityType = FacilityType::create(['name' => 'Campus']);
    Facility::create([
        'name' => 'East Wing',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(
        fn(Assert $page) => $page
            ->where('data.facilityManager.facilitiesManaged', 1)
    );
});
