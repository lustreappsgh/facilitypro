<?php

use App\Models\AuditLog;
use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function adminAuditUser(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }

    $user->givePermissionTo($permissions);

    return $user;
}

test('audit logs capture ip and user agent for admin actions', function () {
    $user = adminAuditUser(['facilities.create', 'audit.view']);
    $facilityType = FacilityType::create(['name' => 'Campus']);

    $this->actingAs($user);

    $response = $this->post(route('facilities.store'), [
        'name' => 'North Campus Gym',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $response->assertRedirect(route('facilities.index'));

    $facility = Facility::query()->where('name', 'North Campus Gym')->firstOrFail();
    $log = AuditLog::query()
        ->where('action', 'facility.created')
        ->where('auditable_id', $facility->id)
        ->latest()
        ->first();

    expect($log)->not->toBeNull();
    expect($log->ip_address)->not->toBeNull();
    expect($log->user_agent)->not->toBeNull();
});
