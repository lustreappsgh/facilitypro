<?php

use App\Domains\Notifications\Actions\SendUserNotificationAction;
use App\Domains\Notifications\DTOs\UserNotificationData;
use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\Inspection;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\RequestType;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function dashboardUserWithPermissions(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }

    $user->givePermissionTo($permissions);

    return $user;
}

test('dashboard shows facility manager stats', function () {
    // Create the Facility Manager role needed by the dashboard service
    Role::findOrCreate('Facility Manager');

    $user = dashboardUserWithPermissions([
        'inspections.create',
        'facilities.view',
        'maintenance.view',
    ]);

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'North Campus Gym',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    Inspection::forceCreate([
        'inspection_date' => now()->toDateString(),
        'facility_id' => $facility->id,
        'condition' => 'Good',
        'comments' => null,
        'image' => null,
        'added_by' => $user->id,
        'updated_by' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Plumbing']);

    MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Pipe leak',
        'cost' => 200,
        'status' => 'pending',
        'requested_by' => $user->id,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->where('data.facilityManager.inspectionsSubmitted', 1)
            ->where('data.facilityManager.openMaintenanceRequests', 1)
            ->where('data.facilityManager.facilitiesManaged', 1)
    );
});

test('dashboard includes high priority unread notifications', function () {
    $user = dashboardUserWithPermissions([
        'inspections.create',
        'facilities.view',
        'maintenance.view',
    ]);

    $this->actingAs($user);

    app(SendUserNotificationAction::class)->execute(new UserNotificationData(
        user_id: $user->id,
        event: 'payment.created',
        title: 'Approval needed',
        body: 'Payment is waiting for review.',
        category: 'payments',
        severity: UserNotificationData::SeverityWarning,
    ));

    app(SendUserNotificationAction::class)->execute(new UserNotificationData(
        user_id: $user->id,
        event: 'todo.created',
        title: 'Routine update',
        body: 'Todo added.',
        category: 'todos',
        severity: UserNotificationData::SeverityInfo,
    ));

    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('data.facilityManager.notifications.unread_count', 2)
            ->has('data.facilityManager.notifications.items', 1)
            ->where('data.facilityManager.notifications.items.0.category', 'payments')
            ->where('data.facilityManager.notifications.items.0.severity', 'warning'));
});

test('dashboard shows manager approval stats', function () {
    // Create the Facility Manager role needed by the dashboard service
    Role::findOrCreate('Facility Manager');

    $user = dashboardUserWithPermissions([
        'maintenance.manage_all',
        'payments.view',
    ]);

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'East Wing',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $user->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Electrical']);

    $maintenance = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'description' => 'Wiring fix',
        'cost' => 1500,
        'status' => 'pending',
        'requested_by' => $user->id,
    ]);

    Payment::create([
        'maintenance_request_id' => $maintenance->id,
        'cost' => 1500,
        'amount_payed' => 0,
        'comments' => null,
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->where('data.manager.pendingApprovals', 1)
            ->where('data.manager.pendingApprovalCost', 1500)
            ->where('data.manager.users.0.id', $user->id)
    );
});

test('maintenance manager dashboard data omits inspection and todo user card metrics', function () {
    Role::findOrCreate('Facility Manager');

    $maintenanceManager = dashboardUserWithPermissions([
        'maintenance.start',
        'maintenance_requests.view',
        'work_orders.create',
    ]);

    $facilityManager = User::factory()->create([
        'manager_id' => $maintenanceManager->id,
    ]);
    $facilityManager->assignRole(Role::findOrCreate('Facility Manager'));

    $this->actingAs($maintenanceManager);

    $response = $this->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->has('data.maintenanceManager.users', 1)
            ->where('data.maintenanceManager.users.0.id', $facilityManager->id)
            ->missing('data.maintenanceManager.users.0.inspections_last_week')
            ->missing('data.maintenanceManager.users.0.upcoming_todos')
    );
});
