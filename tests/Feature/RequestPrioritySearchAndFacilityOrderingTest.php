<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\Inspection;
use App\Models\MaintenanceRequest;
use App\Models\RequestType;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

function workflowUserWithPermissions(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }

    $user->givePermissionTo($permissions);

    return $user;
}

test('maintenance requests are ordered by priority within the weekly group', function () {
    $user = workflowUserWithPermissions(['maintenance.view']);

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'North Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Electrical']);
    $weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();

    $low = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'priority' => 'low',
        'description' => 'Low priority',
        'cost' => 100,
        'status' => 'submitted',
        'requested_by' => $user->id,
        'week_start' => $weekStart,
    ]);

    $high = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'priority' => 'high',
        'description' => 'High priority',
        'cost' => 300,
        'status' => 'submitted',
        'requested_by' => $user->id,
        'week_start' => $weekStart,
    ]);

    $this->actingAs($user)
        ->get(route('maintenance.index', [
            'start_date' => $weekStart,
            'end_date' => $weekStart,
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('data.groups.0.requests.0.id', $high->id)
            ->where('data.groups.0.requests.1.id', $low->id));
});

test('inspection index supports live search query filtering', function () {
    $user = workflowUserWithPermissions(['inspections.view', 'facilities.view']);

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'Generator Block',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    Inspection::forceCreate([
        'inspection_date' => now()->toDateString(),
        'facility_id' => $facility->id,
        'condition' => 'Bad',
        'comments' => 'Generator room needs follow-up',
        'image' => null,
        'added_by' => $user->id,
        'updated_by' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Inspection::forceCreate([
        'inspection_date' => now()->toDateString(),
        'facility_id' => $facility->id,
        'condition' => 'Good',
        'comments' => 'Routine corridor check',
        'image' => null,
        'added_by' => $user->id,
        'updated_by' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('inspections.index', [
            'start_date' => now()->subWeek()->toDateString(),
            'end_date' => now()->toDateString(),
            'search' => 'follow-up',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('data.filters.search', 'follow-up')
            ->where('data.groups.0.inspections', fn ($inspections) => count($inspections) === 1));
});

test('todo index supports live search query filtering', function () {
    $user = workflowUserWithPermissions(['todos.view']);

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'Safety Block',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    Todo::create([
        'user_id' => $user->id,
        'facility_id' => $facility->id,
        'description' => 'Check fire extinguishers',
        'week_start' => now()->startOfWeek(Carbon::MONDAY),
        'status' => 'pending',
    ]);

    Todo::create([
        'user_id' => $user->id,
        'facility_id' => $facility->id,
        'description' => 'Inspect corridor lights',
        'week_start' => now()->startOfWeek(Carbon::MONDAY),
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get(route('todos.index', [
            'start_date' => now()->startOfWeek(Carbon::MONDAY)->toDateString(),
            'end_date' => now()->endOfWeek(Carbon::SUNDAY)->toDateString(),
            'search' => 'fire',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('data.filters.search', 'fire')
            ->where('data.groups.0.todos', fn ($todos) => count($todos) === 1));
});

test('my facilities page is ordered by facility type parent and facility name', function () {
    $user = workflowUserWithPermissions(['facilities.view']);

    $alphaType = FacilityType::create(['name' => 'Academic']);
    $residentialType = FacilityType::create(['name' => 'Residential']);

    $zetaParent = Facility::create([
        'name' => 'Zeta Parent',
        'facility_type_id' => $alphaType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    Facility::create([
        'name' => 'Bravo Child',
        'facility_type_id' => $alphaType->id,
        'parent_id' => $zetaParent->id,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    Facility::create([
        'name' => 'Alpha Child',
        'facility_type_id' => $alphaType->id,
        'parent_id' => $zetaParent->id,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    Facility::create([
        'name' => 'Dorm A',
        'facility_type_id' => $residentialType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('facilities.my'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('facilityGroups.0.facility_type.name', 'Academic')
            ->where('facilityGroups.0.facilities.0.name', 'Zeta Parent')
            ->where('facilityGroups.0.facilities.1.name', 'Alpha Child')
            ->where('facilityGroups.0.facilities.2.name', 'Bravo Child')
            ->where('facilityGroups.1.facility_type.name', 'Residential'));
});
