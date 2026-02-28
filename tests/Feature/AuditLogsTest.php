<?php

use App\Domains\Facilities\Actions\CreateFacilityAction;
use App\Domains\Facilities\DTOs\FacilityData;
use App\Models\AuditLog;
use App\Models\FacilityType;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;

function auditUserWithPermissions(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }

    $user->givePermissionTo($permissions);

    return $user;
}

test('audit logs require permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('audit-logs.index'))->assertForbidden();
});

test('audit log is recorded for facility creation', function () {
    $user = auditUserWithPermissions(['audit.view', 'facilities.create']);

    $facilityType = FacilityType::create(['name' => 'Campus']);

    $this->actingAs($user);

    $action = app(CreateFacilityAction::class);

    $action->execute(new FacilityData(
        name: 'North Campus Gym',
        facility_type_id: $facilityType->id,
        parent_id: null,
        condition: 'Good',
        managed_by: $user->id,
    ));

    expect(AuditLog::query()
        ->where('action', 'facility.created')
        ->where('actor_id', $user->id)
        ->exists()
    )->toBeTrue();
});

test('audit logs index renders entries', function () {
    $user = auditUserWithPermissions(['audit.view', 'facilities.create']);

    $facilityType = FacilityType::create(['name' => 'Campus']);

    $this->actingAs($user);

    $action = app(CreateFacilityAction::class);

    $action->execute(new FacilityData(
        name: 'North Campus Gym',
        facility_type_id: $facilityType->id,
        parent_id: null,
        condition: 'Good',
        managed_by: $user->id,
    ));

    $response = $this->get(route('audit-logs.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('data.logs.data', 1)
    );
});
