<?php

use App\Enums\TodoStatus;
use App\Models\AuditLog;
use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\artisan;

function todoUserWithPermissions(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }

    $user->givePermissionTo($permissions);

    return $user;
}

test('facility manager can create a weekly todo', function () {
    $user = todoUserWithPermissions(['todos.create', 'todos.view']);

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'North Campus Gym',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $this->actingAs($user);

    $response = $this->post(route('todos.store'), [
        'facility_id' => $facility->id,
        'description' => 'Inspect fire exits',
    ]);

    $response->assertRedirect(route('todos.index'));

    $todo = Todo::query()->first();

    expect($todo)->not()->toBeNull()
        ->and($todo->status)->toBe(TodoStatus::Pending->value)
        ->and($todo->user_id)->toBe($user->id)
        ->and($todo->week_start->format('Y-m-d'))->toBe(now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'));

    expect(AuditLog::query()
        ->where('action', 'todo.created')
        ->where('actor_id', $user->id)
        ->exists()
    )->toBeTrue();
});

test('pending todo can be completed', function () {
    $user = todoUserWithPermissions(['todos.complete', 'todos.view']);

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'North Campus Gym',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $todo = Todo::create([
        'user_id' => $user->id,
        'facility_id' => $facility->id,
        'description' => 'Inspect fire exits',
        'week_start' => now()->next('Monday'),
        'status' => TodoStatus::Pending->value,
    ]);

    $this->actingAs($user);

    $response = $this->post(route('todos.complete', $todo));

    $response->assertRedirect();

    expect($todo->refresh()->status)->toBe(TodoStatus::Completed->value);
});

test('overdue todo command marks past pending todos as overdue', function () {
    $user = User::factory()->create();
    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'Test Facility',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    // Create a todo for 2 weeks ago
    $pendingTodo = Todo::create([
        'user_id' => $user->id,
        'facility_id' => $facility->id,
        'description' => 'Old task',
        'week_start' => now()->subWeeks(2)->startOfWeek(),
        'status' => TodoStatus::Pending->value,
    ]);

    // Create a todo for next week (should stay pending)
    $futureTodo = Todo::create([
        'user_id' => $user->id,
        'facility_id' => $facility->id,
        'description' => 'Future task',
        'week_start' => now()->next('Monday'),
        'status' => TodoStatus::Pending->value,
    ]);

    artisan('todos:mark-overdue')
        ->assertExitCode(0);

    expect($pendingTodo->refresh()->status)->toBe(TodoStatus::Overdue->value)
        ->and($futureTodo->refresh()->status)->toBe(TodoStatus::Pending->value);
});
