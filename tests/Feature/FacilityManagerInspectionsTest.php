<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('facility manager cannot create inspection for unassigned facility', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('inspections.create');
    $user->givePermissionTo('inspections.create');
    $user->assignRole(Role::findOrCreate('Facility Manager'));

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'West Wing',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => User::factory()->create()->id,
    ]);

    $this->actingAs($user);

    $response = $this->post(route('inspections.store'), [
        'inspection_date' => now()->toDateString(),
        'facility_id' => $facility->id,
        'condition' => 'Good',
        'comments' => null,
        'image' => null,
    ]);

    $response->assertForbidden();
});
