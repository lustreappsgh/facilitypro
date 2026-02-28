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

test('maintenance manager dashboard shows key metrics', function () {
    $user = User::factory()->create();

    $permissions = [
        'maintenance.start',
        'maintenance_requests.view',
        'work_orders.view',
        'work_orders.create',
        'payments.view',
        'vendors.view',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }

    $user->givePermissionTo($permissions);
    $user->assignRole(Role::findOrCreate('Maintenance Manager'));

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'North Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $requestType = RequestType::create(['name' => 'HVAC']);

    $pendingRequest = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Filter replacement',
        'cost' => 1200,
        'status' => 'pending',
        'requested_by' => $user->id,
    ]);

    $inProgressRequest = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Compressor maintenance',
        'cost' => 2200,
        'status' => 'in_progress',
        'requested_by' => $user->id,
    ]);

    MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Closed request',
        'cost' => 500,
        'status' => 'completed',
        'requested_by' => $user->id,
    ]);

    $vendor = Vendor::create([
        'name' => 'Acme HVAC',
        'email' => 'ops@acme.test',
        'phone' => '555-0100',
        'service_type' => 'HVAC',
        'status' => 'active',
    ]);

    WorkOrder::create([
        'maintenance_request_id' => $pendingRequest->id,
        'vendor_id' => $vendor->id,
        'assigned_date' => now()->toDateString(),
        'scheduled_date' => now()->addDays(3)->toDateString(),
        'completed_date' => null,
        'estimated_cost' => 1500,
        'actual_cost' => null,
        'status' => 'assigned',
        'assigned_by' => $user->id,
    ]);

    WorkOrder::create([
        'maintenance_request_id' => $inProgressRequest->id,
        'vendor_id' => $vendor->id,
        'assigned_date' => now()->toDateString(),
        'scheduled_date' => now()->toDateString(),
        'completed_date' => now()->toDateString(),
        'estimated_cost' => 2100,
        'actual_cost' => 2300,
        'status' => 'completed',
        'assigned_by' => $user->id,
    ]);

    Payment::create([
        'maintenance_request_id' => $pendingRequest->id,
        'cost' => 1200,
        'amount_payed' => 0,
        'comments' => null,
        'status' => 'pending',
    ]);

    Payment::create([
        'maintenance_request_id' => $pendingRequest->id,
        'cost' => 100,
        'amount_payed' => 100,
        'comments' => null,
        'status' => 'approved',
    ]);

    $this->actingAs($user);

    $response = $this->get(route('maintenance.dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('metrics.open_requests', 2)
        ->where('metrics.work_orders_in_flight', 1)
        ->where('metrics.pending_payments', 1)
    );
});
