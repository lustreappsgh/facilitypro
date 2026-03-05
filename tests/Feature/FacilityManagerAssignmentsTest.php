<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('manager assignments page loads for users with assign permission', function () {
    Permission::findOrCreate('facilities.assign_manager');
    Permission::findOrCreate('inspections.view');

    $admin = User::factory()->create();
    $admin->givePermissionTo(['facilities.assign_manager', 'inspections.view']);

    $manager = User::factory()->create(['name' => 'Manager One']);
    $manager->givePermissionTo('inspections.view');

    $facilityType = FacilityType::create(['name' => 'Campus']);
    Facility::create([
        'name' => 'Main Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('facilities.manager-assignments'))
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->component('Facilities/ManagerAssignments')
            ->has('facilities.data', 1)
            ->has('managers', 2)
        );
});

test('bulk assign manager endpoint assigns and unassigns facilities', function () {
    Permission::findOrCreate('facilities.assign_manager');
    Permission::findOrCreate('inspections.view');

    $admin = User::factory()->create();
    $admin->givePermissionTo('facilities.assign_manager');

    $manager = User::factory()->create();
    $manager->givePermissionTo('inspections.view');

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'South Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => null,
    ]);

    $this->actingAs($admin)
        ->post(route('facilities.bulk-assign-manager'), [
            'facility_ids' => [$facility->id],
            'manager_id' => $manager->id,
        ])
        ->assertRedirect();

    expect($facility->fresh()->managed_by)->toBe($manager->id);

    $this->actingAs($admin)
        ->post(route('facilities.bulk-assign-manager'), [
            'facility_ids' => [$facility->id],
            'manager_id' => null,
        ])
        ->assertRedirect();

    expect($facility->fresh()->managed_by)->toBeNull();
});

