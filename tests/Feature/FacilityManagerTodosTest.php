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
        'week' => now()->addWeek()->startOfWeek()->toDateString(),
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
                    'week' => now()->addWeeks(2)->startOfWeek()->toDateString(),
                ],
                [
                    'facility_id' => $facilityB->id,
                    'description' => 'Weekly round check B',
                    'week' => now()->addWeeks(2)->startOfWeek()->toDateString(),
                ],
            ],
        ])
        ->assertRedirect(route('todos.index'));

    expect(Todo::query()->where('user_id', $user->id)->count())->toBe(2);
    expect(Todo::query()->where('facility_id', $facilityA->id)->first()?->week_start?->toDateString())
        ->toBe(Carbon::parse(now()->addWeeks(2)->startOfWeek()->toDateString())->toDateString());
});

test('facility manager cannot create todo for the current or previous week', function (string $week) {
    Carbon::setTestNow(Carbon::parse('2026-03-07 09:00:00'));

    $user = User::factory()->create();
    Permission::findOrCreate('todos.create');
    $user->givePermissionTo('todos.create');
    $user->assignRole(Role::findOrCreate('Facility Manager'));

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'Operations Hub',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $this->actingAs($user);

    $response = $this->post(route('todos.store'), [
        'facility_id' => $facility->id,
        'description' => 'Weekly round check',
        'week' => $week,
    ]);

    $response->assertSessionHasErrors('week');
    expect(Todo::query()->count())->toBe(0);

    Carbon::setTestNow();
})->with([
    'current week' => ['2026-03-02'],
    'previous week' => ['2026-02-23'],
]);
