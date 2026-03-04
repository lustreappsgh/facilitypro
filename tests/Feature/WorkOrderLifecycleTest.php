<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\RequestType;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WorkOrder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('maintenance manager can update work order status', function () {
    $user = User::factory()->create();

    Permission::findOrCreate('work_orders.update');
    $user->givePermissionTo('work_orders.update');
    $user->assignRole(Role::findOrCreate('Maintenance Manager'));

    $facilityType = FacilityType::firstOrCreate(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'East Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Electrical']);
    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Panel inspection',
        'cost' => 900,
        'status' => 'reviewed',
        'requested_by' => $user->id,
    ]);

    $vendor = Vendor::create([
        'name' => 'Spark Services',
        'email' => 'hello@spark.test',
        'phone' => '555-0115',
        'service_type' => 'Electrical',
        'status' => 'active',
    ]);

    $workOrder = WorkOrder::create([
        'maintenance_request_id' => $maintenance->id,
        'vendor_id' => $vendor->id,
        'assigned_date' => now()->toDateString(),
        'scheduled_date' => now()->addWeek()->toDateString(),
        'completed_date' => null,
        'estimated_cost' => 1000,
        'actual_cost' => null,
        'status' => 'assigned',
        'assigned_by' => $user->id,
    ]);
    Payment::create([
        'maintenance_request_id' => $maintenance->id,
        'work_order_id' => $workOrder->id,
        'cost' => 1000,
        'amount_payed' => 1000,
        'status' => 'approved',
    ]);

    $this->actingAs($user);

    $response = $this->patch(route('work-orders.update', $workOrder), [
        'status' => 'in_progress',
    ]);

    $response->assertRedirect();
    expect($workOrder->refresh()->status)->toBe('in_progress');
});

test('work order can start without approved payment', function () {
    $user = User::factory()->create();

    Permission::findOrCreate('work_orders.update');
    $user->givePermissionTo('work_orders.update');
    $user->assignRole(Role::findOrCreate('Maintenance Manager'));

    $facilityType = FacilityType::firstOrCreate(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'South Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Electrical']);
    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Breaker issue',
        'cost' => 650,
        'status' => 'approved',
        'requested_by' => $user->id,
    ]);

    $vendor = Vendor::create([
        'name' => 'Circuit Crew',
        'email' => 'hello@circuit.test',
        'phone' => '555-0155',
        'service_type' => 'Electrical',
        'status' => 'active',
    ]);

    $workOrder = WorkOrder::create([
        'maintenance_request_id' => $maintenance->id,
        'vendor_id' => $vendor->id,
        'assigned_date' => now()->toDateString(),
        'scheduled_date' => now()->addDays(3)->toDateString(),
        'completed_date' => null,
        'estimated_cost' => 700,
        'actual_cost' => null,
        'status' => 'assigned',
        'assigned_by' => $user->id,
    ]);
    Payment::create([
        'maintenance_request_id' => $maintenance->id,
        'work_order_id' => $workOrder->id,
        'cost' => 700,
        'amount_payed' => 0,
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    $response = $this->patch(route('work-orders.update', $workOrder), [
        'status' => 'in_progress',
    ]);

    $response->assertRedirect();
    expect($workOrder->refresh()->status)->toBe('in_progress');
});

test('work order cannot be completed without actual cost', function () {
    $user = User::factory()->create();

    Permission::findOrCreate('work_orders.update');
    $user->givePermissionTo('work_orders.update');
    $user->assignRole(Role::findOrCreate('Maintenance Manager'));

    $facilityType = FacilityType::firstOrCreate(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'West Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Carpentry']);
    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Door repair',
        'cost' => 450,
        'status' => 'reviewed',
        'requested_by' => $user->id,
    ]);

    $vendor = Vendor::create([
        'name' => 'Woodcraft',
        'email' => 'support@woodcraft.test',
        'phone' => '555-0133',
        'service_type' => 'Carpentry',
        'status' => 'active',
    ]);

    $workOrder = WorkOrder::create([
        'maintenance_request_id' => $maintenance->id,
        'vendor_id' => $vendor->id,
        'assigned_date' => now()->toDateString(),
        'scheduled_date' => now()->addDays(2)->toDateString(),
        'completed_date' => null,
        'estimated_cost' => 500,
        'actual_cost' => null,
        'status' => 'assigned',
        'assigned_by' => $user->id,
    ]);
    Payment::create([
        'maintenance_request_id' => $maintenance->id,
        'work_order_id' => $workOrder->id,
        'cost' => 500,
        'amount_payed' => 500,
        'status' => 'approved',
    ]);

    $this->actingAs($user);

    $response = $this->patch(route('work-orders.update', $workOrder), [
        'status' => 'completed',
    ]);

    $response->assertSessionHasErrors('status');
    expect($workOrder->refresh()->status)->toBe('assigned');
});

test('completing a work order completes the maintenance request', function () {
    $user = User::factory()->create();

    Permission::findOrCreate('work_orders.update');
    $user->givePermissionTo('work_orders.update');
    $user->assignRole(Role::findOrCreate('Maintenance Manager'));

    $facilityType = FacilityType::firstOrCreate(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'North Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Plumbing']);
    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Pump replacement',
        'cost' => 1200,
        'status' => 'in_progress',
        'requested_by' => $user->id,
    ]);

    $vendor = Vendor::create([
        'name' => 'Pipe Pros',
        'email' => 'support@pipepros.test',
        'phone' => '555-0199',
        'service_type' => 'Plumbing',
        'status' => 'active',
    ]);

    $workOrder = WorkOrder::create([
        'maintenance_request_id' => $maintenance->id,
        'vendor_id' => $vendor->id,
        'assigned_date' => now()->toDateString(),
        'scheduled_date' => now()->addDays(3)->toDateString(),
        'completed_date' => null,
        'estimated_cost' => 1500,
        'actual_cost' => null,
        'status' => 'in_progress',
        'assigned_by' => $user->id,
    ]);
    Payment::create([
        'maintenance_request_id' => $maintenance->id,
        'work_order_id' => $workOrder->id,
        'cost' => 1500,
        'amount_payed' => 1500,
        'status' => 'approved',
    ]);

    $this->actingAs($user);

    $response = $this->patch(route('work-orders.update', $workOrder), [
        'status' => 'completed',
        'actual_cost' => 1400,
    ]);

    $response->assertRedirect();
    expect($workOrder->refresh()->status)->toBe('completed');
    expect($maintenance->refresh()->status)->toBe('completed');
});

test('cancelling a work order closes the maintenance request', function () {
    $user = User::factory()->create();

    Permission::findOrCreate('work_orders.update');
    $user->givePermissionTo('work_orders.update');
    $user->assignRole(Role::findOrCreate('Maintenance Manager'));

    $facilityType = FacilityType::firstOrCreate(['name' => 'Warehouse']);
    $facility = Facility::create([
        'name' => 'Central Depot',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'HVAC']);
    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Unit inspection',
        'cost' => 800,
        'status' => 'in_progress',
        'requested_by' => $user->id,
    ]);

    $vendor = Vendor::create([
        'name' => 'CoolAir Partners',
        'email' => 'hello@coolair.test',
        'phone' => '555-0183',
        'service_type' => 'HVAC',
        'status' => 'active',
    ]);

    $workOrder = WorkOrder::create([
        'maintenance_request_id' => $maintenance->id,
        'vendor_id' => $vendor->id,
        'assigned_date' => now()->toDateString(),
        'scheduled_date' => now()->addDays(5)->toDateString(),
        'completed_date' => null,
        'estimated_cost' => 900,
        'actual_cost' => null,
        'status' => 'assigned',
        'assigned_by' => $user->id,
    ]);
    Payment::create([
        'maintenance_request_id' => $maintenance->id,
        'work_order_id' => $workOrder->id,
        'cost' => 900,
        'amount_payed' => 900,
        'status' => 'approved',
    ]);

    $this->actingAs($user);

    $response = $this->patch(route('work-orders.update', $workOrder), [
        'status' => 'cancelled',
    ]);

    $response->assertRedirect();
    expect($workOrder->refresh()->status)->toBe('cancelled');
    expect($maintenance->refresh()->status)->toBe('cancelled');
});
