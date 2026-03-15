<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\MaintenanceRequest;
use App\Models\RequestType;
use App\Models\User;
use App\Models\WorkOrder;
use Spatie\Permission\Models\Permission;

test('work order can be created without a vendor assignment', function () {
    $user = User::factory()->create();

    Permission::findOrCreate('work_orders.create');
    Permission::findOrCreate('maintenance_requests.view');
    $user->givePermissionTo(['work_orders.create', 'maintenance_requests.view']);

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'Operations Block',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'General']);
    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'priority' => 'medium',
        'description' => 'General repairs',
        'cost' => 500,
        'status' => 'submitted',
        'submission_route' => MaintenanceRequest::SubmissionRouteMaintenanceManager,
        'requested_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post(route('work-orders.store'), [
            'maintenance_request_id' => $maintenance->id,
            'estimated_cost' => 550,
            'scheduled_date' => now()->addDay()->toDateString(),
            'status' => 'assigned',
        ])
        ->assertRedirect(route('work-orders.index'));

    expect(WorkOrder::query()->where('maintenance_request_id', $maintenance->id)->first()?->vendor_id)->toBeNull();
});
