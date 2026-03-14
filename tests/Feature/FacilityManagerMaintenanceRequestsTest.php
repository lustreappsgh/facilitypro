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
use Spatie\Permission\Models\Role;

test('facility manager can update own pending maintenance request', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('maintenance.update');
    $user->givePermissionTo('maintenance.update');
    $user->assignRole(Role::findOrCreate('Facility Manager'));

    $facilityType = FacilityType::create(['name' => 'Campus']);
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
        'description' => 'Leak',
        'cost' => 200,
        'status' => 'pending',
        'requested_by' => $user->id,
    ]);

    $this->actingAs($user);

    $response = $this->patch(route('maintenance.update', $maintenance), [
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Leak in hallway',
        'cost' => 300,
        'status' => 'pending',
    ]);

    $response->assertRedirect();
    expect($maintenance->refresh()->description)->toBe('Leak in hallway');
});

test('facility manager cannot update maintenance request that is not theirs', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('maintenance.update');
    $user->givePermissionTo('maintenance.update');
    $user->assignRole(Role::findOrCreate('Facility Manager'));

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'South Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $otherUser = User::factory()->create();
    $requestType = RequestType::firstOrCreate(['name' => 'Electrical']);
    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Panel issue',
        'cost' => 400,
        'status' => 'pending',
        'requested_by' => $otherUser->id,
    ]);

    $this->actingAs($user);

    $response = $this->patch(route('maintenance.update', $maintenance), [
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Attempted edit',
        'cost' => 500,
        'status' => 'pending',
    ]);

    $response->assertForbidden();
});

test('maintenance requests index ignores facility filters in the payload', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('maintenance_requests.view');
    $user->givePermissionTo('maintenance_requests.view');
    $user->assignRole(Role::findOrCreate('Facility Manager'));

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facilityA = Facility::create([
        'name' => 'North Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);
    $facilityB = Facility::create([
        'name' => 'South Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Electrical']);
    MaintenanceRequest::create([
        'facility_id' => $facilityA->id,
        'request_type_id' => $requestType->id,
        'description' => 'North request',
        'cost' => 200,
        'status' => 'pending',
        'requested_by' => $user->id,
        'week_start' => now()->startOfWeek()->toDateString(),
    ]);
    MaintenanceRequest::create([
        'facility_id' => $facilityB->id,
        'request_type_id' => $requestType->id,
        'description' => 'South request',
        'cost' => 300,
        'status' => 'submitted',
        'requested_by' => $user->id,
        'week_start' => now()->startOfWeek()->toDateString(),
    ]);

    $this->actingAs($user);

    $response = $this->get(route('maintenance.index', ['facility_id' => $facilityA->id]));

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->where('data.filters.facility_id', null)
            ->where('data.facilities', [])
            ->has('data.groups.0.requests', 2)
    );
});

test('facility manager can delete own pending maintenance request', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('maintenance.update');
    $user->givePermissionTo('maintenance.update');
    $user->assignRole(Role::findOrCreate('Facility Manager'));

    $facilityType = FacilityType::create(['name' => 'Delete Campus']);
    $facility = Facility::create([
        'name' => 'Delete Test Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Delete Plumbing']);
    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Delete me',
        'cost' => 200,
        'status' => 'pending',
        'requested_by' => $user->id,
    ]);

    $this->actingAs($user);

    $response = $this->delete(route('maintenance.destroy', $maintenance), [
        'redirect_to' => route('maintenance.index'),
    ]);

    $response->assertRedirect(route('maintenance.index'));
    expect(MaintenanceRequest::query()->whereKey($maintenance->id)->exists())->toBeFalse();
});

test('facility manager cannot delete approved maintenance request', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('maintenance.update');
    $user->givePermissionTo('maintenance.update');
    $user->assignRole(Role::findOrCreate('Facility Manager'));

    $facilityType = FacilityType::create(['name' => 'Approved Campus']);
    $facility = Facility::create([
        'name' => 'Approved Test Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Approved Plumbing']);
    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Already approved',
        'cost' => 200,
        'status' => 'approved',
        'requested_by' => $user->id,
    ]);

    $this->actingAs($user);

    $response = $this->delete(route('maintenance.destroy', $maintenance), [
        'redirect_to' => route('maintenance.index'),
    ]);

    $response->assertForbidden();
    expect(MaintenanceRequest::query()->whereKey($maintenance->id)->exists())->toBeTrue();
});

test('facility manager cannot delete pending maintenance request with a work order', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('maintenance.update');
    $user->givePermissionTo('maintenance.update');
    $user->assignRole(Role::findOrCreate('Facility Manager'));

    $facilityType = FacilityType::create(['name' => 'Work Order Campus']);
    $facility = Facility::create([
        'name' => 'Work Order Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Work Order Plumbing']);
    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Has work order',
        'cost' => 200,
        'status' => 'pending',
        'requested_by' => $user->id,
    ]);

    $vendor = Vendor::create([
        'name' => 'Delete Guard Vendor',
        'status' => 'active',
    ]);

    WorkOrder::create([
        'maintenance_request_id' => $maintenance->id,
        'vendor_id' => $vendor->id,
        'assigned_date' => now()->toDateString(),
        'status' => 'assigned',
        'assigned_by' => $user->id,
    ]);

    $this->actingAs($user);

    $response = $this->delete(route('maintenance.destroy', $maintenance), [
        'redirect_to' => route('maintenance.index'),
    ]);

    $response->assertForbidden();
    expect(MaintenanceRequest::query()->whereKey($maintenance->id)->exists())->toBeTrue();
});

test('facility manager can bulk delete own pending maintenance requests', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('maintenance.update');
    $user->givePermissionTo('maintenance.update');
    $user->assignRole(Role::findOrCreate('Facility Manager'));

    $facilityType = FacilityType::create(['name' => 'Bulk Delete Campus']);
    $facility = Facility::create([
        'name' => 'Bulk Delete Test Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Bulk Delete Plumbing']);
    $firstRequest = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Delete one',
        'cost' => 100,
        'status' => 'pending',
        'requested_by' => $user->id,
    ]);
    $secondRequest = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Delete two',
        'cost' => 200,
        'status' => 'submitted',
        'requested_by' => $user->id,
    ]);
    $untouchedRequest = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Keep me',
        'cost' => 300,
        'status' => 'approved',
        'requested_by' => $user->id,
    ]);

    $this->actingAs($user);

    $response = $this->post(route('maintenance.bulk-destroy'), [
        'maintenance_request_ids' => [$firstRequest->id, $secondRequest->id],
        'redirect_to' => route('maintenance.index'),
    ]);

    $response->assertRedirect(route('maintenance.index'));
    expect(MaintenanceRequest::query()->whereKey($firstRequest->id)->exists())->toBeFalse();
    expect(MaintenanceRequest::query()->whereKey($secondRequest->id)->exists())->toBeFalse();
    expect(MaintenanceRequest::query()->whereKey($untouchedRequest->id)->exists())->toBeTrue();
});

test('facility manager cannot bulk delete requests when any selected request is not deletable', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('maintenance.update');
    $user->givePermissionTo('maintenance.update');
    $user->assignRole(Role::findOrCreate('Facility Manager'));

    $facilityType = FacilityType::create(['name' => 'Bulk Delete Guard Campus']);
    $facility = Facility::create([
        'name' => 'Bulk Delete Guard Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Bulk Delete Guard Plumbing']);
    $deletableRequest = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Delete candidate',
        'cost' => 100,
        'status' => 'pending',
        'requested_by' => $user->id,
    ]);
    $blockedRequest = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Blocked candidate',
        'cost' => 200,
        'status' => 'approved',
        'requested_by' => $user->id,
    ]);

    $this->actingAs($user);

    $response = $this->post(route('maintenance.bulk-destroy'), [
        'maintenance_request_ids' => [$deletableRequest->id, $blockedRequest->id],
        'redirect_to' => route('maintenance.index'),
    ]);

    $response->assertForbidden();
    expect(MaintenanceRequest::query()->whereKey($deletableRequest->id)->exists())->toBeTrue();
    expect(MaintenanceRequest::query()->whereKey($blockedRequest->id)->exists())->toBeTrue();
});
