<?php

use App\Domains\Maintenance\Actions\CreateWorkOrderAction;
use App\Domains\Maintenance\DTOs\WorkOrderData;
use App\Domains\Notifications\Actions\SendUserNotificationAction;
use App\Domains\Notifications\DTOs\UserNotificationData;
use App\Domains\Payments\Actions\ApprovePaymentAction;
use App\Domains\Todos\Actions\CreateTodoAction;
use App\Domains\Todos\DTOs\TodoData;
use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\MaintenanceRequest;
use App\Models\RequestType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach ([
        'maintenance_requests.create',
        'maintenance_requests.view',
        'maintenance.start',
        'maintenance.manage_all',
        'payments.view',
        'todos.create',
        'work_orders.create',
        'facilities.view',
        'users.manage',
    ] as $permission) {
        Permission::findOrCreate($permission);
    }

    Role::findOrCreate('Facility Manager');
    Role::findOrCreate('Maintenance Manager');
    Role::findOrCreate('Manager');
    Role::findOrCreate('Admin');
});

test('maintenance request creation notifies reviewers but not the actor', function () {
    $facilityManager = User::factory()->create();
    $manager = User::factory()->create();
    $maintenanceManager = User::factory()->create();

    $facilityManager->assignRole('Facility Manager');
    $manager->assignRole('Manager');
    $maintenanceManager->assignRole('Maintenance Manager');

    $facilityManager->givePermissionTo('maintenance_requests.create');
    $manager->givePermissionTo(['maintenance.manage_all', 'maintenance_requests.view']);
    $maintenanceManager->givePermissionTo(['maintenance_requests.view', 'maintenance.start']);

    $facilityManager->update(['manager_id' => $manager->id]);

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'North Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $facilityManager->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Electrical']);
    DB::table('maintenance_request_type_role')->insert([
        'role_id' => Role::findByName('Maintenance Manager')->id,
        'request_type_id' => $requestType->id,
        'can_approve' => true,
        'can_reject' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($facilityManager)
        ->post(route('maintenance.store'), [
            'facility_id' => $facility->id,
            'request_type_id' => $requestType->id,
            'priority' => 'high',
            'description' => 'Power outage in Block A',
        ])
        ->assertRedirect();

    expect($facilityManager->notifications()->count())->toBe(0);
    expect($manager->notifications()->latest()->first()?->data['event'])->toBe('maintenance_request.created');
    expect($maintenanceManager->notifications()->latest()->first()?->data['event'])->toBe('maintenance_request.created');
});

test('work order and payment events notify stakeholders', function () {
    $facilityManager = User::factory()->create();
    $manager = User::factory()->create();
    $admin = User::factory()->create();

    $facilityManager->assignRole('Facility Manager');
    $manager->assignRole('Manager');
    $admin->assignRole('Admin');

    $manager->givePermissionTo(['maintenance.manage_all', 'maintenance_requests.view', 'payments.view', 'work_orders.create']);
    $admin->givePermissionTo(['users.manage', 'payments.view']);

    $facilityManager->update(['manager_id' => $manager->id]);

    $facilityType = FacilityType::create(['name' => 'Campus']);
    $facility = Facility::create([
        'name' => 'South Campus',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $facilityManager->id,
    ]);

    $requestType = RequestType::firstOrCreate(['name' => 'Plumbing']);
    $request = MaintenanceRequest::create([
        'facility_id' => $facility->id,
        'request_type_id' => $requestType->id,
        'priority' => 'medium',
        'description' => 'Burst pipe',
        'status' => 'approved',
        'requested_by' => $facilityManager->id,
    ]);

    $this->actingAs($manager);

    $workOrder = app(CreateWorkOrderAction::class)->execute(new WorkOrderData(
        maintenance_request_id: $request->id,
        vendor_id: null,
        assigned_date: now()->toDateString(),
        scheduled_date: now()->addDay()->toDateString(),
        estimated_cost: 750,
        actual_cost: null,
        status: 'assigned',
        assigned_by: $manager->id,
    ));

    $payment = $workOrder->payment()->first();

    expect($facilityManager->notifications()->pluck('data')->map(fn (array $data) => $data['event'])->all())
        ->toContain('work_order.created');
    expect($admin->notifications()->pluck('data')->map(fn (array $data) => $data['event'])->all())
        ->toContain('payment.created');
    expect($facilityManager->notifications()->pluck('data')->map(fn (array $data) => $data['event'])->all())
        ->toContain('payment.created');

    app(ApprovePaymentAction::class)->execute($payment, $admin->id, 'Approved');

    expect($facilityManager->fresh()->notifications()->pluck('data')->map(fn (array $data) => $data['event'])->all())
        ->toContain('payment.approved');
});

test('todo creation notifies assignee and supervisor', function () {
    $facilityManager = User::factory()->create();
    $manager = User::factory()->create();
    $admin = User::factory()->create();

    $facilityManager->assignRole('Facility Manager');
    $manager->assignRole('Manager');
    $admin->assignRole('Admin');
    $admin->givePermissionTo(['users.manage', 'todos.create']);

    $facilityManager->update(['manager_id' => $manager->id]);

    $facilityType = FacilityType::create(['name' => 'Block']);
    $facility = Facility::create([
        'name' => 'Block 2',
        'facility_type_id' => $facilityType->id,
        'parent_id' => null,
        'condition' => 'Good',
        'managed_by' => $facilityManager->id,
    ]);

    $this->actingAs($admin);

    app(CreateTodoAction::class)->execute(new TodoData(
        facility_id: $facility->id,
        description: 'Weekly safety walkthrough',
        week: now()->startOfWeek()->toDateString(),
        user_id: $facilityManager->id,
    ));

    expect($facilityManager->notifications()->latest()->first()?->data['event'])->toBe('todo.created');
    expect($manager->notifications()->latest()->first()?->data['event'])->toBe('todo.created');
});

test('notifications inbox filters unread and category and allows marking read', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $send = app(SendUserNotificationAction::class);

    $send->execute(new UserNotificationData(
        user_id: $user->id,
        event: 'maintenance_request.created',
        title: 'Maintenance event',
        body: 'Created',
        category: 'maintenance',
    ));

    $send->execute(new UserNotificationData(
        user_id: $user->id,
        event: 'todo.created',
        title: 'Todo event',
        body: 'Created',
        category: 'todos',
    ));

    $todoNotification = $user->notifications()
        ->where('data->category', 'todos')
        ->first();
    $todoNotification?->markAsRead();

    $this->get(route('notifications.inbox', [
        'category' => 'maintenance',
        'unread' => 1,
    ]))->assertInertia(fn (Assert $page) => $page
        ->component('Notifications/Index')
        ->has('notifications.data', 1)
        ->where('notifications.data.0.data.category', 'maintenance')
        ->where('filters.unread', true));

    $notification = $user->unreadNotifications()->first();

    $this->post(route('notifications.read', $notification))
        ->assertOk();

    expect($notification->fresh()->read_at)->not()->toBeNull();
});
