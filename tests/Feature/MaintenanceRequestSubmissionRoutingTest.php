<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\MaintenanceRequest;
use App\Models\RequestType;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WorkOrder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

test('facility manager can submit request to administrator route', function () {
    $facilityManager = User::factory()->create();
    Permission::findOrCreate('maintenance.create');
    $facilityManager->givePermissionTo('maintenance.create');

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'South Campus Hall',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $facilityManager->id,
    ]);
    $requestType = RequestType::firstOrCreate(['name' => 'Electrical']);

    $this->actingAs($facilityManager);

    $response = $this->post(route('maintenance.store'), [
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Power fluctuations in Hall A',
        'cost' => 1200,
        'submission_route' => MaintenanceRequest::SubmissionRouteAdmin,
    ]);

    $response->assertRedirect(route('maintenance.index'));

    $created = MaintenanceRequest::query()->latest('id')->firstOrFail();

    expect($created->submission_route)->toBe(MaintenanceRequest::SubmissionRouteAdmin)
        ->and($created->status)->toBe('submitted');
});

test('maintenance request submission defaults to maintenance manager route', function () {
    $facilityManager = User::factory()->create();
    Permission::findOrCreate('maintenance.create');
    $facilityManager->givePermissionTo('maintenance.create');

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'North Campus Hall',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $facilityManager->id,
    ]);
    $requestType = RequestType::firstOrCreate(['name' => 'Plumbing']);

    $this->actingAs($facilityManager);

    $response = $this->post(route('maintenance.store'), [
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Pipe leak near main entrance',
        'cost' => 700,
    ]);

    $response->assertRedirect(route('maintenance.index'));

    $created = MaintenanceRequest::query()->latest('id')->firstOrFail();

    expect($created->submission_route)->toBe(MaintenanceRequest::SubmissionRouteMaintenanceManager)
        ->and($created->status)->toBe('submitted');
});

test('maintenance manager cannot review a request routed to admin', function () {
    $maintenanceManager = User::factory()->create();
    Permission::findOrCreate('maintenance.review');
    Permission::findOrCreate('maintenance_requests.view');
    Permission::findOrCreate('work_orders.create');
    $maintenanceManager->givePermissionTo(['maintenance.review', 'maintenance_requests.view', 'work_orders.create']);

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'West Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $maintenanceManager->id,
    ]);
    $requestType = RequestType::firstOrCreate(['name' => 'General']);

    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Door lock replacement',
        'cost' => 300,
        'status' => 'submitted',
        'submission_route' => MaintenanceRequest::SubmissionRouteAdmin,
        'requested_by' => $maintenanceManager->id,
        'week_start' => now()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString(),
    ]);

    $vendor = Vendor::create([
        'name' => 'Rapid Doors',
        'email' => 'rapid.doors@example.test',
        'phone' => '555-0202',
        'service_type' => 'General',
        'status' => 'active',
    ]);

    $this->actingAs($maintenanceManager);

    $response = $this->post(route('maintenance.approve', $maintenance), [
        'vendor_id' => $vendor->id,
        'estimated_cost' => 350,
        'scheduled_date' => now()->addDay()->toDateString(),
    ]);

    $response->assertForbidden();
    expect($maintenance->refresh()->status)->toBe('submitted');
    expect(
        WorkOrder::query()->where('maintenance_request_id', $maintenance->id)->exists()
    )->toBeFalse();
});

test('admin can review a request routed to admin from submitted status', function () {
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
    $requestType = RequestType::firstOrCreate(['name' => 'HVAC']);

    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Cooling issue',
        'cost' => 950,
        'status' => 'submitted',
        'submission_route' => MaintenanceRequest::SubmissionRouteAdmin,
        'requested_by' => $admin->id,
        'week_start' => now()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString(),
    ]);

    $vendor = Vendor::create([
        'name' => 'Cool Tech',
        'email' => 'cool.tech@example.test',
        'phone' => '555-0303',
        'service_type' => 'HVAC',
        'status' => 'active',
    ]);

    $this->actingAs($admin);

    $response = $this->post(route('maintenance.approve', $maintenance), [
        'vendor_id' => $vendor->id,
        'estimated_cost' => 1000,
        'scheduled_date' => now()->addDay()->toDateString(),
    ]);

    $response->assertRedirect();
    expect($maintenance->refresh()->status)->toBe('in_progress');
    expect($maintenance->workOrders()->latest('id')->first()?->status)->toBe('in_progress');
});

test('maintenance index excludes admin-routed requests for maintenance manager scope', function () {
    $maintenanceManager = User::factory()->create();
    Permission::findOrCreate('maintenance_requests.view');
    $maintenanceManager->givePermissionTo('maintenance_requests.view');

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'Central Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $maintenanceManager->id,
    ]);
    $requestType = RequestType::firstOrCreate(['name' => 'Electrical']);
    $weekStart = now()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString();

    $managerRouted = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Panel maintenance',
        'cost' => 420,
        'status' => 'submitted',
        'submission_route' => MaintenanceRequest::SubmissionRouteMaintenanceManager,
        'requested_by' => $maintenanceManager->id,
        'week_start' => $weekStart,
    ]);

    $adminRouted = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Direct admin escalation',
        'cost' => 880,
        'status' => 'submitted',
        'submission_route' => MaintenanceRequest::SubmissionRouteAdmin,
        'requested_by' => $maintenanceManager->id,
        'week_start' => $weekStart,
    ]);

    $this->actingAs($maintenanceManager);

    $response = $this->get(route('maintenance.index'));

    $response->assertOk();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('MaintenanceRequests/Index')
            ->where('data.groups', function ($groups) use ($managerRouted, $adminRouted): bool {
                $requestIds = collect($groups)
                    ->flatMap(fn (array $group) => collect($group['requests'] ?? [])->pluck('id'))
                    ->values()
                    ->all();

                return in_array($managerRouted->id, $requestIds, true)
                    && ! in_array($adminRouted->id, $requestIds, true);
            })
    );
});
