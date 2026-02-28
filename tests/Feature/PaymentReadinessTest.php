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

test('maintenance manager only sees payments for assigned work orders', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Permission::findOrCreate('payments.view');
    Permission::findOrCreate('todos.review');
    Permission::findOrCreate('maintenance_requests.view');
    $user->givePermissionTo(['payments.view', 'todos.review', 'maintenance_requests.view']);
    $user->assignRole(Role::findOrCreate('Maintenance Manager'));

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facilityOne = Facility::create([
        'name' => 'Central Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $otherFacility = Facility::create([
        'name' => 'West Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $otherUser->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'General']);
    $requestOne = MaintenanceRequest::create([
        'facility_id' => $facilityOne->id,
        'request_type_id' => $requestType->id,
        'description' => 'Repair flooring',
        'cost' => 800,
        'status' => 'reviewed',
        'requested_by' => $user->id,
    ]);

    $requestTwo = MaintenanceRequest::create([
        'facility_id' => $otherFacility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Paint touch-ups',
        'cost' => 600,
        'status' => 'reviewed',
        'requested_by' => $otherUser->id,
    ]);

    $vendor = Vendor::create([
        'name' => 'General Fixers',
        'email' => 'service@fixers.test',
        'phone' => '555-0119',
        'service_type' => 'General',
        'status' => 'active',
    ]);

    WorkOrder::create([
        'maintenance_request_id' => $requestOne->id,
        'vendor_id' => $vendor->id,
        'assigned_date' => now()->toDateString(),
        'scheduled_date' => now()->addDay()->toDateString(),
        'completed_date' => null,
        'estimated_cost' => 850,
        'actual_cost' => null,
        'status' => 'assigned',
        'assigned_by' => $user->id,
    ]);

    WorkOrder::create([
        'maintenance_request_id' => $requestTwo->id,
        'vendor_id' => $vendor->id,
        'assigned_date' => now()->toDateString(),
        'scheduled_date' => now()->addDays(2)->toDateString(),
        'completed_date' => null,
        'estimated_cost' => 700,
        'actual_cost' => null,
        'status' => 'assigned',
        'assigned_by' => $otherUser->id,
    ]);

    Payment::create([
        'maintenance_request_id' => $requestOne->id,
        'cost' => 850,
        'amount_payed' => 0,
        'comments' => null,
        'status' => 'pending',
    ]);

    Payment::create([
        'maintenance_request_id' => $requestTwo->id,
        'cost' => 700,
        'amount_payed' => 0,
        'comments' => null,
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    $response = $this->get(route('payments.index'));

    $response->assertOk();
    $response->assertInertia(
        fn(Assert $page) => $page
            ->has('data.payments.data', 1)
            ->where('data.payments.data.0.maintenance_request_id', $requestOne->id)
    );
});
