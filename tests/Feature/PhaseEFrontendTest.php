<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\Inspection;
use App\Models\MaintenanceRequest;
use App\Models\RequestType;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WorkOrder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function createUserWithPermissions(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }

    $user->givePermissionTo($permissions);

    return $user;
}

test('facility pages load', function () {
    // Create permissions needed by the controller
    Permission::findOrCreate('inspections.view');

    $user = createUserWithPermissions([
        'facilities.view',
        'facilities.create',
        'facilities.update',
    ]);

    Role::findOrCreate('Facility Manager');

    $facilityType = FacilityType::create(['name' => 'Campus']);

    $facility = Facility::create([
        'name' => 'North Campus Gym',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $this->actingAs($user);

    // User with only 'facilities.view' but not 'facilities.view_all' gets redirected to MyFacilities
    $this->get(route('facilities.index'))
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page->component('Facilities/MyFacilities'));
    $this->get(route('facilities.create'))
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page->component('Facilities/Create'));
    $this->get(route('facilities.edit', $facility))
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page->component('Facilities/Edit'));
});

test('inspection pages load', function () {
    $user = createUserWithPermissions([
        'inspections.view',
        'inspections.create',
    ]);

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'North Campus Gym',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $inspection = Inspection::forceCreate([
        'inspection_date' => now()->toDateString(),
        'facility_id' => $facility->id,
        'condition' => 'Good',
        'comments' => 'All good.',
        'image' => null,
        'added_by' => $user->id,
        'updated_by' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user);

    $this->get(route('inspections.index'))->assertOk();
    $this->get(route('inspections.create'))->assertOk();
    $this->get(route('inspections.show', $inspection))->assertOk();
});

test('maintenance request pages load', function () {
    $user = createUserWithPermissions([
        'maintenance.view',
        'maintenance.create',
    ]);

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'North Campus Gym',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Plumbing']);

    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Leak in main hall.',
        'cost' => 2500,
        'status' => 'pending',
        'requested_by' => $user->id,
    ]);

    $this->actingAs($user);

    $this->get(route('maintenance.index'))->assertOk();
    $this->get(route('maintenance.create'))->assertOk();
    $this->get(route('maintenance.show', $maintenance))->assertOk();
});

test('vendor and work order pages load', function () {
    $user = createUserWithPermissions([
        'vendors.view',
        'vendors.create',
        'work_orders.view',
        'work_orders.create',
    ]);

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'North Campus Gym',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'HVAC']);

    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'AC unit repair.',
        'cost' => 5000,
        'status' => 'reviewed',
        'requested_by' => $user->id,
    ]);

    $vendor = Vendor::create([
        'name' => 'Acme HVAC',
        'email' => 'info@acme.test',
        'phone' => '555-0100',
        'service_type' => 'HVAC',
        'status' => 'active',
    ]);

    $workOrder = WorkOrder::create([
        'maintenance_request_id' => $maintenance->id,
        'vendor_id' => $vendor->id,
        'assigned_date' => now()->toDateString(),
        'scheduled_date' => now()->addWeek()->toDateString(),
        'completed_date' => null,
        'estimated_cost' => 5200,
        'actual_cost' => null,
        'status' => 'assigned',
        'assigned_by' => $user->id,
    ]);

    $this->actingAs($user);

    $this->get(route('vendors.index'))->assertOk();
    $this->get(route('vendors.create'))->assertOk();
    $this->get(route('work-orders.index'))->assertOk();
    $this->get(route('work-orders.create'))->assertOk();
    $this->get(route('work-orders.show', $workOrder))->assertOk();
});

test('payment and report pages load', function () {
    $user = createUserWithPermissions([
        'payments.view',
        'reports.view',
    ]);

    $this->actingAs($user);

    $this->get(route('payments.index'))->assertOk();
    $this->get(route('reports.index'))->assertOk();
});
