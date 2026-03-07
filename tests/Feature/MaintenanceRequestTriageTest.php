<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\RequestType;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WorkOrder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('maintenance manager approval sends request for final approval', function () {
    $manager = User::factory()->create();

    Permission::findOrCreate('maintenance.review');
    Permission::findOrCreate('maintenance_requests.view');
    Permission::findOrCreate('work_orders.create');
    $manager->givePermissionTo(['maintenance.review', 'maintenance_requests.view', 'work_orders.create']);
    $manager->assignRole(Role::findOrCreate('Maintenance Manager'));

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'South Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $manager->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Plumbing']);
    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Pipe leak',
        'cost' => 350,
        'status' => 'submitted',
        'requested_by' => $manager->id,
    ]);

    $vendor = Vendor::create([
        'name' => 'Pipe Pros',
        'email' => 'pipe.pros@example.test',
        'phone' => '555-0101',
        'service_type' => 'Plumbing',
        'status' => 'active',
    ]);

    $this->actingAs($manager);

    $response = $this->post(route('maintenance.approve', $maintenance), [
        'vendor_id' => $vendor->id,
        'estimated_cost' => 400,
        'scheduled_date' => now()->addDay()->toDateString(),
    ]);

    $response->assertRedirect();
    expect($maintenance->refresh()->status)->toBe('work_order_created');
});

test('admin final approval moves manager-approved request to in progress', function () {
    $admin = User::factory()->create();
    $manager = User::factory()->create();

    Permission::findOrCreate('maintenance.manage_all');
    Permission::findOrCreate('maintenance.review');
    Permission::findOrCreate('maintenance_requests.view');
    Permission::findOrCreate('users.manage');
    Permission::findOrCreate('work_orders.create');
    $admin->givePermissionTo(['maintenance.manage_all', 'maintenance.review', 'maintenance_requests.view', 'users.manage']);
    $manager->givePermissionTo(['maintenance.review', 'maintenance_requests.view', 'work_orders.create']);
    $manager->assignRole(Role::findOrCreate('Maintenance Manager'));

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'North Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $manager->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Electrical']);
    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Generator issue',
        'cost' => 900,
        'status' => 'work_order_created',
        'requested_by' => $manager->id,
    ]);

    $vendor = Vendor::create([
        'name' => 'Grid Restore',
        'email' => 'grid.restore@example.test',
        'phone' => '555-0191',
        'service_type' => 'Electrical',
        'status' => 'active',
    ]);

    $workOrder = WorkOrder::create([
        'maintenance_request_id' => $maintenance->id,
        'vendor_id' => $vendor->id,
        'assigned_date' => now()->toDateString(),
        'scheduled_date' => now()->addDay()->toDateString(),
        'completed_date' => null,
        'estimated_cost' => 900,
        'actual_cost' => null,
        'status' => 'assigned',
        'assigned_by' => $manager->id,
    ]);

    $this->actingAs($admin);

    $response = $this->post(route('maintenance.approve', $maintenance));

    $response->assertRedirect();
    expect($maintenance->refresh()->status)->toBe('in_progress');
    expect($workOrder->refresh()->status)->toBe('in_progress');
});

test('admin can fast-track request from submitted to work order and approved payment', function () {
    $admin = User::factory()->create();

    Permission::findOrCreate('maintenance.manage_all');
    Permission::findOrCreate('maintenance.review');
    Permission::findOrCreate('maintenance_requests.view');
    Permission::findOrCreate('users.manage');
    $admin->givePermissionTo(['maintenance.manage_all', 'maintenance.review', 'maintenance_requests.view', 'users.manage']);

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'Admin Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $admin->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'General']);
    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Fast-track maintenance',
        'cost' => 500,
        'status' => 'submitted',
        'requested_by' => $admin->id,
    ]);

    $vendor = Vendor::create([
        'name' => 'Rapid Repairs',
        'email' => 'rapid.repairs@example.test',
        'phone' => '555-0110',
        'service_type' => 'General',
        'status' => 'active',
    ]);

    $this->actingAs($admin);

    $response = $this->post(route('maintenance.approve', $maintenance), [
        'vendor_id' => $vendor->id,
        'estimated_cost' => 650,
        'scheduled_date' => now()->addDay()->toDateString(),
    ]);

    $response->assertRedirect();
    expect($maintenance->refresh()->status)->toBe('in_progress');
    expect($maintenance->workOrders()->latest('id')->first()?->status)->toBe('in_progress');

    $payment = Payment::query()
        ->where('maintenance_request_id', $maintenance->id)
        ->latest('id')
        ->first();

    expect($payment)->not->toBeNull();
    expect($payment?->status)->toBe('approved');
});

test('manager can open bulk review flow for a single request using maintenance_request_id', function () {
    $manager = User::factory()->create();

    Permission::findOrCreate('maintenance.manage_all');
    Permission::findOrCreate('maintenance_requests.view');
    Permission::findOrCreate('work_orders.create');
    $manager->givePermissionTo(['maintenance.manage_all', 'maintenance_requests.view', 'work_orders.create']);

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'Kumah Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $manager->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'General']);
    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Single review request',
        'cost' => 500,
        'status' => 'submitted',
        'requested_by' => $manager->id,
    ]);

    $this->actingAs($manager);

    $response = $this->get(route('work-orders.bulk-create', [
        'maintenance_request_id' => $maintenance->id,
        'intent' => 'review',
    ]));

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->where('selection.request_ids', [$maintenance->id])
            ->where('selection.intent', 'review')
            ->where('maintenanceRequests.0.id', $maintenance->id)
    );
});

test('manager with maintenance manage all can approve a submitted request through work order creation', function () {
    $manager = User::factory()->create();

    Permission::findOrCreate('maintenance.manage_all');
    Permission::findOrCreate('maintenance_requests.view');
    Permission::findOrCreate('work_orders.create');
    $manager->givePermissionTo(['maintenance.manage_all', 'maintenance_requests.view', 'work_orders.create']);
    $manager->assignRole(Role::findOrCreate('Manager'));

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'Kumah North',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $manager->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Plumbing']);
    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Burst pipe',
        'cost' => 700,
        'status' => 'submitted',
        'requested_by' => $manager->id,
    ]);

    $vendor = Vendor::create([
        'name' => 'Kumah Repairs',
        'email' => 'kumah.repairs@example.test',
        'phone' => '555-0134',
        'service_type' => 'Plumbing',
        'status' => 'active',
    ]);

    $this->actingAs($manager);

    $response = $this->post(route('work-orders.bulk-store'), [
        'bulk_orders' => [
            [
                'maintenance_request_id' => $maintenance->id,
                'vendor_id' => $vendor->id,
                'estimated_cost' => 725,
                'review_action' => 'approve',
            ],
        ],
    ]);

    $response->assertRedirect(route('work-orders.index'));
    expect($maintenance->refresh()->status)->toBe('work_order_created');
    expect($maintenance->workOrders()->count())->toBe(1);
});
