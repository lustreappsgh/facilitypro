<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\Inspection;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
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

test('facility manager cannot create an inspection in the future', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('inspections.create');
    $user->givePermissionTo('inspections.create');
    $user->assignRole(Role::findOrCreate('Facility Manager'));

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'Future Wing',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $this->actingAs($user);

    $response = $this->post(route('inspections.store'), [
        'inspection_date' => now()->addDay()->toDateString(),
        'facility_id' => $facility->id,
        'condition' => 'Good',
    ]);

    $response->assertSessionHasErrors('inspection_date');
    expect(Inspection::query()->count())->toBe(0);
});

test('inspections index defaults to previous and current week', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-07 09:00:00'));

    $user = User::factory()->create();
    Permission::findOrCreate('inspections.view');
    $user->givePermissionTo('inspections.view');
    $user->assignRole(Role::findOrCreate('Facility Manager'));

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'North Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    Inspection::forceCreate([
        'inspection_date' => '2026-02-17',
        'facility_id' => $facility->id,
        'condition' => 'Good',
        'comments' => null,
        'image' => null,
        'added_by' => $user->id,
        'updated_by' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Inspection::forceCreate([
        'inspection_date' => '2026-02-24',
        'facility_id' => $facility->id,
        'condition' => 'Good',
        'comments' => 'Previous week',
        'image' => null,
        'added_by' => $user->id,
        'updated_by' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Inspection::forceCreate([
        'inspection_date' => '2026-03-03',
        'facility_id' => $facility->id,
        'condition' => 'Bad',
        'comments' => 'Current week',
        'image' => null,
        'added_by' => $user->id,
        'updated_by' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user);

    $response = $this->get(route('inspections.index'));

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->where('data.filters.start_date', '2026-02-23')
            ->where('data.filters.end_date', '2026-03-08')
            ->has('data.groups', 2)
            ->where('data.groups.0.inspections.0.comments', 'Current week')
            ->where('data.groups.1.inspections.0.comments', 'Previous week')
    );

    Carbon::setTestNow();
});
