<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\MaintenanceRequest;
use App\Models\RequestType;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::findOrCreate('maintenance.review');
    Permission::findOrCreate('maintenance_requests.view');
    Permission::findOrCreate('maintenance.manage_all');
    Permission::findOrCreate('work_orders.create');
    Permission::findOrCreate('maintenance_requests.create');
    Permission::findOrCreate('inspections.create');
    Permission::findOrCreate('todos.create');
    Permission::findOrCreate('facilities.view');
    Permission::findOrCreate('users.manage');
    Permission::findOrCreate('roles.manage');

    Role::findOrCreate('Manager');
    Role::findOrCreate('Maintenance Manager');
    Role::findOrCreate('Facility Manager');
});

test('maintenance manager cannot review own request', function () {
    $maintenanceManager = User::factory()->create();
    $maintenanceManager->givePermissionTo(['maintenance.review', 'maintenance_requests.view', 'work_orders.create']);
    $maintenanceManager->assignRole('Maintenance Manager');

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'North Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => User::factory()->create()->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'HVAC']);
    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Self submitted',
        'status' => 'submitted',
        'requested_by' => $maintenanceManager->id,
    ]);

    $this->actingAs($maintenanceManager)
        ->post(route('maintenance.reject', $maintenance), [
            'rejection_reason' => 'Cannot self review',
        ])
        ->assertForbidden();
});

test('manager role approval settings control approve and reject actions by request type', function () {
    $admin = User::factory()->create();
    $manager = User::factory()->create();
    $facilityManager = User::factory()->create([
        'manager_id' => $manager->id,
    ]);

    $admin->givePermissionTo(['users.manage', 'roles.manage']);
    $manager->givePermissionTo(['maintenance.manage_all', 'maintenance_requests.view', 'work_orders.create']);
    $manager->assignRole('Manager');
    $facilityManager->assignRole('Facility Manager');

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'South Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $facilityManager->id,
    ]);

    $plumbing = RequestType::firstOrCreate(['name' => 'Plumbing']);
    $electrical = RequestType::firstOrCreate(['name' => 'Electrical']);

    $allowed = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $plumbing->id,
        'description' => 'Allowed type',
        'status' => 'submitted',
        'requested_by' => $facilityManager->id,
    ]);

    $blocked = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $electrical->id,
        'description' => 'Blocked type',
        'status' => 'submitted',
        'requested_by' => $facilityManager->id,
    ]);

    $managerRole = Role::findByName('Manager');

    $this->actingAs($admin)
        ->post(route('roles.request-types.update', $managerRole), [
            'request_type_permissions' => [
                [
                    'request_type_id' => $plumbing->id,
                    'can_approve' => true,
                    'can_reject' => true,
                ],
                [
                    'request_type_id' => $electrical->id,
                    'can_approve' => false,
                    'can_reject' => false,
                ],
            ],
        ])
        ->assertRedirect();

    $this->actingAs($manager)
        ->post(route('maintenance.reject', $allowed), [
            'rejection_reason' => 'Need more details',
        ])
        ->assertRedirect();

    expect($allowed->refresh()->status)->toBe('rejected');
    expect($allowed->rejection_reason)->toBe('Need more details');

    $this->actingAs($manager)
        ->post(route('maintenance.reject', $blocked), [
            'rejection_reason' => 'Blocked by settings',
        ])
        ->assertForbidden();
});

test('manager create pages only include overseen facilities', function () {
    $manager = User::factory()->create();
    $facilityManager = User::factory()->create([
        'manager_id' => $manager->id,
    ]);
    $otherManager = User::factory()->create();

    $manager->givePermissionTo([
        'maintenance.manage_all',
        'maintenance_requests.create',
        'inspections.create',
        'todos.create',
        'facilities.view',
    ]);
    $manager->assignRole('Manager');
    $facilityManager->assignRole('Facility Manager');
    $otherManager->assignRole('Facility Manager');

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $visibleFacility = Facility::create([
        'name' => 'Visible Facility',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $manager->id,
    ]);
    Facility::create([
        'name' => 'Direct Report Facility',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $facilityManager->id,
    ]);
    Facility::create([
        'name' => 'Other Manager Facility',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $otherManager->id,
    ]);

    $this->actingAs($manager)
        ->get(route('maintenance.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('facilities', 1)
            ->where('facilities.0.id', $visibleFacility->id));

    $this->actingAs($manager)
        ->get(route('inspections.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('facilities', 1)
            ->where('facilities.0.id', $visibleFacility->id));

    $this->actingAs($manager)
        ->get(route('todos.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('data.facilities', 1)
            ->where('data.facilities.0.id', $visibleFacility->id));
});

test('admin can manage manager role approval settings from role edit page', function () {
    $admin = User::factory()->create();
    $admin->givePermissionTo('roles.manage');

    $role = Role::findByName('Manager');

    $this->actingAs($admin)
        ->get(route('roles.edit', $role))
        ->assertSuccessful();
});
