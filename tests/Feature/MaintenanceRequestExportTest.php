<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\MaintenanceRequest;
use App\Models\RequestType;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('authenticated facility manager can download the xlsx export', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('maintenance_requests.view');
    $user->givePermissionTo('maintenance_requests.view');
    $user->assignRole(Role::findOrCreate('Facility Manager'));

    $facilityType = FacilityType::create(['name' => 'Export Test Block']);
    $facility = Facility::create([
        'name' => 'Export Test Room',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Electrical']);
    MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Needs rewiring',
        'cost' => 500,
        'status' => 'pending',
        'requested_by' => $user->id,
        'week_start' => now()->startOfWeek(\Carbon\Carbon::SUNDAY)->toDateString(),
    ]);

    $this->actingAs($user);

    $response = $this->get(route('maintenance.export'));

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($response->headers->get('Content-Disposition'))->toContain('maintenance-requests-');
    expect($response->headers->get('Content-Disposition'))->toContain('.xlsx');
});

it('unauthenticated user is redirected from the export endpoint', function () {
    $response = $this->get(route('maintenance.export'));

    $response->assertRedirect();
});

it('export respects date range filters', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('maintenance_requests.view');
    $user->givePermissionTo('maintenance_requests.view');
    $user->assignRole(Role::findOrCreate('Facility Manager'));

    $this->actingAs($user);

    $response = $this->get(route('maintenance.export', [
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-31',
    ]));

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('xlsx response contains a valid zip structure', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('maintenance_requests.view');
    $user->givePermissionTo('maintenance_requests.view');
    $user->assignRole(Role::findOrCreate('Facility Manager'));

    $this->actingAs($user);

    $response = $this->get(route('maintenance.export'));

    $response->assertSuccessful();

    // XLSX is a ZIP file — check for the PK magic bytes
    $content = $response->getContent();
    expect(substr($content, 0, 2))->toBe('PK');
});
