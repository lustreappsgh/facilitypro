<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\MaintenanceRequest;
use App\Models\RequestType;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('maintenance manager can start maintenance requests', function () {
    $user = User::factory()->create();

    Permission::findOrCreate('maintenance.start');
    Permission::findOrCreate('maintenance_requests.view');
    $user->givePermissionTo(['maintenance.start', 'maintenance_requests.view']);
    $user->assignRole(Role::findOrCreate('Maintenance Manager'));

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'South Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Plumbing']);
    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Pipe leak',
        'cost' => 350,
        'status' => 'pending',
        'requested_by' => $user->id,
    ]);

    $this->actingAs($user);

    $response = $this->post(route('maintenance.start', $maintenance));

    $response->assertRedirect();
    expect($maintenance->refresh()->status)->toBe('in_progress');
});
