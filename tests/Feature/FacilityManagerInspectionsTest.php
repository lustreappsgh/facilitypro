<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\Inspection;
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

test('facility manager can create bulk inspections for assigned facilities', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('inspections.create');
    $user->givePermissionTo('inspections.create');
    $user->assignRole(Role::findOrCreate('Facility Manager'));

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facilityA = Facility::create([
        'name' => 'East Wing',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);
    $facilityB = Facility::create([
        'name' => 'South Wing',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post(route('inspections.store'), [
            'bulk_inspections' => [
                [
                    'facility_id' => $facilityA->id,
                    'inspection_date' => now()->toDateString(),
                    'condition' => 'Bad',
                    'comments' => 'Bulk inspection note A',
                ],
                [
                    'facility_id' => $facilityB->id,
                    'inspection_date' => now()->toDateString(),
                    'condition' => 'Bad',
                    'comments' => 'Bulk inspection note B',
                ],
            ],
        ])
        ->assertRedirect();

    expect(Inspection::query()->where('added_by', $user->id)->count())->toBe(2);
});
