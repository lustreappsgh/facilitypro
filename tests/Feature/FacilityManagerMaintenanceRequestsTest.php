<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\MaintenanceRequest;
use App\Models\RequestType;
use App\Models\User;
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
