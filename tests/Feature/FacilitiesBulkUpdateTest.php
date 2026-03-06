<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('facility manager can bulk update condition for assigned facilities', function () {
    Permission::findOrCreate('facilities.update');

    $manager = User::factory()->create();
    $manager->givePermissionTo('facilities.update');

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facilityA = Facility::create([
        'name' => 'Facility A',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $manager->id,
    ]);
    $facilityB = Facility::create([
        'name' => 'Facility B',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Warning',
        'managed_by' => $manager->id,
    ]);

    $this->actingAs($manager)
        ->post(route('facilities.bulk-update'), [
            'facility_ids' => [$facilityA->id, $facilityB->id],
            'condition' => 'Bad',
        ])
        ->assertRedirect();

    expect($facilityA->fresh()->condition)->toBe('Bad');
    expect($facilityB->fresh()->condition)->toBe('Bad');
});

test('bulk update ignores restricted fields for users without assign-manager permission', function () {
    Permission::findOrCreate('facilities.update');

    $manager = User::factory()->create();
    $manager->givePermissionTo('facilities.update');

    $otherManager = User::factory()->create();

    $typeA = FacilityType::create(['name' => 'Campus']);
    $typeB = FacilityType::create(['name' => 'Warehouse']);

    $parent = Facility::create([
        'name' => 'Parent Facility',
        'facility_type_id' => $typeA->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $manager->id,
    ]);

    $facility = Facility::create([
        'name' => 'Child Facility',
        'facility_type_id' => $typeA->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $manager->id,
    ]);

    $this->actingAs($manager)
        ->post(route('facilities.bulk-update'), [
            'facility_ids' => [$facility->id],
            'condition' => 'Bad',
            'facility_type_id' => $typeB->id,
            'parent_id' => $parent->id,
            'managed_by' => $otherManager->id,
        ])
        ->assertRedirect();

    $updated = $facility->fresh();
    expect($updated->condition)->toBe('Bad');
    expect($updated->facility_type_id)->toBe($typeA->id);
    expect($updated->parent_id)->toBeNull();
    expect($updated->managed_by)->toBe($manager->id);
});
