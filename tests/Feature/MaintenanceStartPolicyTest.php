<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\MaintenanceRequest;
use App\Models\RequestType;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('maintenance start is allowed for in-scope maintenance managers', function () {
    $user = User::factory()->create();

    Permission::findOrCreate('maintenance.start');
    $user->givePermissionTo('maintenance.start');
    $user->assignRole(Role::findOrCreate('Maintenance Manager'));

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'Start Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Electrical']);
    $user->maintenanceRequestTypes()->sync([$requestType->id]);
    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Start maintenance',
        'cost' => 250,
        'status' => 'approved',
        'requested_by' => $user->id,
    ]);

    $this->actingAs($user);

    $response = $this->post(route('maintenance.start', $maintenance));

    $response->assertRedirect();
    expect($maintenance->refresh()->status)->toBe('in_progress');
});

test('maintenance start is forbidden outside manager scope', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Permission::findOrCreate('maintenance.start');
    $user->givePermissionTo('maintenance.start');
    $user->assignRole(Role::findOrCreate('Maintenance Manager'));

    $facilityType = FacilityType::create(['name' => 'Remote']);
    $facility = Facility::create([
        'name' => 'Remote Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $otherUser->id,
    ]);

    $allowedType = RequestType::firstOrCreate(['name' => 'Plumbing']);
    $requestType = RequestType::firstOrCreate(['name' => 'Electrical']);
    $user->maintenanceRequestTypes()->sync([$allowedType->id]);
    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Not in scope',
        'cost' => 300,
        'status' => 'approved',
        'requested_by' => $otherUser->id,
    ]);

    $this->actingAs($user)
        ->post(route('maintenance.start', $maintenance))
        ->assertForbidden();
});
