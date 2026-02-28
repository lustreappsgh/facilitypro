<?php

use App\Models\AuditLog;
use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\MaintenanceRequest;
use App\Models\RequestType;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function adminMasterUser(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }

    $user->givePermissionTo($permissions);

    return $user;
}

test('admin can manage facility types and log audit events', function () {
    $user = adminMasterUser(['facility_types.manage']);

    $this->actingAs($user);

    $create = $this->post(route('facility-types.store'), [
        'name' => 'Campus',
    ]);
    $create->assertRedirect();

    $facilityType = FacilityType::query()->where('name', 'Campus')->firstOrFail();
    expect(AuditLog::query()->where('action', 'facility_type.created')->exists())->toBeTrue();

    $update = $this->put(route('facility-types.update', $facilityType), [
        'name' => 'Campus Updated',
    ]);
    $update->assertRedirect();
    expect($facilityType->refresh()->name)->toBe('Campus Updated');
    expect(AuditLog::query()->where('action', 'facility_type.updated')->exists())->toBeTrue();

    $delete = $this->delete(route('facility-types.destroy', $facilityType));
    $delete->assertRedirect();
    expect(FacilityType::query()->where('id', $facilityType->id)->exists())->toBeFalse();
    expect(AuditLog::query()->where('action', 'facility_type.deleted')->exists())->toBeTrue();
});

test('facility type cannot be deleted if in use', function () {
    $user = adminMasterUser(['facility_types.manage']);

    $facilityType = FacilityType::create(['name' => 'In Use']);
    $facility = Facility::create([
        'name' => 'North Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $this->actingAs($user);

    $response = $this->delete(route('facility-types.destroy', $facilityType));
    $response->assertSessionHasErrors('facilityType');

    expect($facility->refresh()->facility_type_id)->toBe($facilityType->id);
});

test('admin can manage request types and log audit events', function () {
    $user = adminMasterUser(['request_types.manage']);

    $this->actingAs($user);

    $create = $this->post(route('request-types.store'), [
        'name' => 'Electrical',
    ]);
    $create->assertRedirect();

    $requestType = RequestType::query()->where('name', 'Electrical')->firstOrFail();
    expect(AuditLog::query()->where('action', 'request_type.created')->exists())->toBeTrue();

    $update = $this->put(route('request-types.update', $requestType), [
        'name' => 'Electrical Updated',
    ]);
    $update->assertRedirect();
    expect($requestType->refresh()->name)->toBe('Electrical Updated');
    expect(AuditLog::query()->where('action', 'request_type.updated')->exists())->toBeTrue();

    $delete = $this->delete(route('request-types.destroy', $requestType));
    $delete->assertRedirect();
    expect(RequestType::query()->where('id', $requestType->id)->exists())->toBeFalse();
    expect(AuditLog::query()->where('action', 'request_type.deleted')->exists())->toBeTrue();
});

test('request type cannot be deleted if in use', function () {
    $user = adminMasterUser(['request_types.manage']);

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'South Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $requestType = RequestType::create(['name' => 'Plumbing']);

    MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Leak',
        'cost' => 200,
        'status' => 'pending',
        'requested_by' => $user->id,
    ]);

    $this->actingAs($user);

    $response = $this->delete(route('request-types.destroy', $requestType));
    $response->assertSessionHasErrors('requestType');
});
