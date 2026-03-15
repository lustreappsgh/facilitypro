<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\RequestType;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function phaseHUserWithPermissions(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }

    $user->givePermissionTo($permissions);

    return $user;
}

test('audit logs are forbidden without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('audit-logs.index'))
        ->assertForbidden();
});

test('audit logs are accessible with permission', function () {
    $user = phaseHUserWithPermissions(['audit.view']);

    $this->actingAs($user)
        ->get(route('audit-logs.index'))
        ->assertOk();
});

test('payment approvals are forbidden without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('payment-approvals.index'))
        ->assertForbidden();
});

test('payment approvals are accessible with permission', function () {
    $user = phaseHUserWithPermissions(['payments.view']);

    $this->actingAs($user)
        ->get(route('payment-approvals.index'))
        ->assertOk();
});

test('non-admin users cannot approve payments from the approval queue', function () {
    $user = phaseHUserWithPermissions(['payments.view', 'payments.approve']);

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'Approval Campus',
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
        'description' => 'Approval test',
        'cost' => 400,
        'status' => 'completed_pending_payment',
        'requested_by' => $user->id,
    ]);

    $payment = Payment::create([
        'maintenance_request_id' => $maintenance->id,
        'cost' => 400,
        'amount_payed' => 0,
        'comments' => null,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->post(route('payments.approve', $payment), [
            'comments' => 'Attempted approval.',
        ])
        ->assertForbidden();
});
