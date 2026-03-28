<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\Inspection;
use App\Models\MaintenanceRequest;
use App\Models\RequestType;
use App\Models\Todo;
use App\Models\User;

test('week labels start on sunday for requests, todos, and inspections', function () {
    $user = User::factory()->create();
    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'Week Label Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);
    $requestType = RequestType::firstOrCreate(['name' => 'General']);

    $maintenanceRequest = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Sunday request',
        'cost' => 100,
        'status' => 'submitted',
        'requested_by' => $user->id,
        'week_start' => '2026-03-01',
    ]);

    $todo = Todo::create([
        'user_id' => $user->id,
        'facility_id' => $facility->id,
        'description' => 'Sunday todo',
        'status' => 'pending',
        'week_start' => '2026-03-01',
    ]);

    $inspection = Inspection::create([
        'inspection_date' => '2026-03-01',
        'facility_id' => $facility->id,
        'condition' => 'Good',
        'comments' => 'Sunday inspection',
        'image' => null,
        'added_by' => $user->id,
    ]);
    $inspection->forceFill([
        'created_at' => '2026-03-01 10:00:00',
    ])->save();

    expect($maintenanceRequest->fresh()->month_week)->toBe('March wk 1 (Mar 01 - Mar 07)');
    expect($todo->fresh()->month_week)->toBe('March wk 1 (Mar 01 - Mar 07)');
    expect($inspection->fresh()->month_week)->toBe('March wk 1 (Mar 01 - Mar 07)');
});
