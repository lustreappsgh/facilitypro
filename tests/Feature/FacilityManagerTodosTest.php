<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('facility manager cannot create todo for unassigned facility', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('todos.create');
    $user->givePermissionTo('todos.create');
    $user->assignRole(Role::findOrCreate('Facility Manager'));

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'Remote Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => User::factory()->create()->id,
    ]);

    $this->actingAs($user);

    $response = $this->post(route('todos.store'), [
        'facility_id' => $facility->id,
        'description' => 'Inspect exits',
        'week' => now()->toDateString(),
    ]);

    $response->assertForbidden();
});
