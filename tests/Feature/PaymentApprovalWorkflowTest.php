<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\RequestType;
use App\Models\User;
use Spatie\Permission\Models\Permission;

test('payment approval marks payment approved and completes pending maintenance', function () {
    $approver = User::factory()->create();

    Permission::findOrCreate('payments.approve');
    Permission::findOrCreate('users.manage');
    $approver->givePermissionTo('payments.approve');
    $approver->givePermissionTo('users.manage');

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'Approval Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $approver->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'General']);
    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Approval test',
        'cost' => 400,
        'status' => 'completed_pending_payment',
        'requested_by' => $approver->id,
    ]);

    $payment = Payment::create([
        'maintenance_request_id' => $maintenance->id,
        'cost' => 400,
        'amount_payed' => 0,
        'comments' => null,
        'status' => 'pending',
    ]);

    $this->actingAs($approver);

    $response = $this->post(route('payments.approve', $payment), [
        'comments' => 'Looks good.',
    ]);

    $response->assertRedirect();
    expect($payment->refresh()->status)->toBe('approved');
    expect($payment->refresh()->amount_payed)->toBe(0);
    expect($maintenance->refresh()->status)->toBe('completed');
});
