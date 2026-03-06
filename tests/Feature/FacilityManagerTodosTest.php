<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
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

test('facility manager can create bulk todos for assigned facilities', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('todos.create');
    $user->givePermissionTo('todos.create');
    $user->assignRole(Role::findOrCreate('Facility Manager'));

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facilityA = Facility::create([
        'name' => 'North Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);
    $facilityB = Facility::create([
        'name' => 'South Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post(route('todos.store'), [
            'bulk_todos' => [
                [
                    'facility_id' => $facilityA->id,
                    'description' => 'Weekly round check A',
                    'week' => now()->startOfWeek()->toDateString(),
                ],
                [
                    'facility_id' => $facilityB->id,
                    'description' => 'Weekly round check B',
                    'week' => now()->startOfWeek()->toDateString(),
                ],
            ],
        ])
        ->assertRedirect(route('todos.index'));

    expect(Todo::query()->where('user_id', $user->id)->count())->toBe(2);
    expect(Todo::query()->where('facility_id', $facilityA->id)->first()?->week_start?->toDateString())
        ->toBe(Carbon::parse(now()->startOfWeek()->toDateString())->toDateString());
});
