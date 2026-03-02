<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\RequestType;
use App\Models\User;
use App\Models\Vendor;
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
    Permission::findOrCreate('work_orders.create');
    $admin->givePermissionTo(['maintenance.manage_all', 'maintenance.review', 'maintenance_requests.view']);
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
        'status' => 'approved',
        'requested_by' => $manager->id,
    ]);

    $this->actingAs($admin);

    $response = $this->post(route('maintenance.approve', $maintenance));

    $response->assertRedirect();
    expect($maintenance->refresh()->status)->toBe('in_progress');
});

test('admin can fast-track request from submitted to work order and approved payment', function () {
    $admin = User::factory()->create();

    Permission::findOrCreate('maintenance.manage_all');
    Permission::findOrCreate('maintenance.review');
    Permission::findOrCreate('maintenance_requests.view');
    $admin->givePermissionTo(['maintenance.manage_all', 'maintenance.review', 'maintenance_requests.view']);

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

    $payment = Payment::query()
        ->where('maintenance_request_id', $maintenance->id)
        ->latest('id')
        ->first();

    expect($payment)->not->toBeNull();
    expect($payment?->status)->toBe('paid');
});
