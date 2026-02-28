<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\FacilityTypesController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\MaintenanceManagerDashboardController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RequestTypesController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canRegister' => Features::enabled(Features::registration()),
//     ]);
// })->name('home');

Route::get('/', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('facilities/my', [FacilityController::class, 'myFacilities'])->name('facilities.my');
    Route::get('facilities/admin', [FacilityController::class, 'adminIndex'])
        ->name('facilities.admin')
        ->middleware('throttle:admin-actions');
    Route::patch('facilities/{facility}/hierarchy', [FacilityController::class, 'updateHierarchy'])
        ->name('facilities.hierarchy')
        ->middleware('throttle:admin-actions');
    Route::post('facilities/bulk-assign-manager', [FacilityController::class, 'bulkAssignManager'])
        ->name('facilities.bulk-assign-manager')
        ->middleware('throttle:admin-actions');
    Route::resource('facilities', FacilityController::class);

    Route::get('inspections/my', [InspectionController::class, 'index'])->name('inspections.my');
    Route::get('inspections/admin', [InspectionController::class, 'index'])
        ->name('inspections.admin')
        ->middleware('throttle:admin-actions');
    Route::resource('inspections', InspectionController::class);

    Route::get('maintenance/my', [MaintenanceRequestController::class, 'index'])->name('maintenance.my');
    Route::get('maintenance/admin', [MaintenanceRequestController::class, 'index'])
        ->name('maintenance.admin')
        ->middleware('throttle:admin-actions');
    Route::get('maintenance/oversight', [MaintenanceRequestController::class, 'index'])
        ->name('maintenance.oversight');
    Route::resource('maintenance', MaintenanceRequestController::class);
    Route::get('maintenance-dashboard', [MaintenanceManagerDashboardController::class, 'index'])
        ->name('maintenance.dashboard');
    Route::post('maintenance/{maintenance}/review', [MaintenanceRequestController::class, 'review'])->name('maintenance.review');
    Route::post('maintenance/{maintenance}/approve', [MaintenanceRequestController::class, 'approve'])->name('maintenance.approve');
    Route::post('maintenance/{maintenance}/reject', [MaintenanceRequestController::class, 'reject'])->name('maintenance.reject');
    Route::post('maintenance/{maintenance}/start', [MaintenanceRequestController::class, 'start'])->name('maintenance.start');
    Route::post('maintenance/{maintenance}/complete', [MaintenanceRequestController::class, 'complete'])->name('maintenance.complete');
    Route::post('maintenance/{maintenance}/close', [MaintenanceRequestController::class, 'close'])->name('maintenance.close');

    Route::resource('vendors', VendorController::class);
    Route::get('work-orders/oversight', [WorkOrderController::class, 'oversight'])
        ->name('work-orders.oversight');
    Route::get('work-orders/admin', [WorkOrderController::class, 'adminIndex'])
        ->name('work-orders.admin')
        ->middleware('throttle:admin-actions');
    Route::patch('work-orders/{work_order}/payment', [WorkOrderController::class, 'updatePayment'])
        ->name('work-orders.payment.update');
    Route::resource('work-orders', WorkOrderController::class);

    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/admin', [PaymentController::class, 'adminIndex'])
        ->name('payments.admin')
        ->middleware('throttle:admin-actions');
    Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    Route::get('payment-approvals', [PaymentController::class, 'approvals'])->name('payment-approvals.index');
    Route::post('payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
    Route::post('payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/dashboard', [ReportController::class, 'dashboard'])->name('reports.dashboard');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('reports/admin', [ReportController::class, 'adminDashboard'])
        ->name('reports.admin')
        ->middleware('throttle:admin-actions');
    Route::get('reports/admin/export', [ReportController::class, 'adminExport'])
        ->name('reports.admin.export')
        ->middleware('throttle:admin-actions');

    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('audit-logs/governance', [AuditLogController::class, 'governance'])
        ->name('audit-logs.governance');

    Route::get('users', [UsersController::class, 'index'])
        ->name('users.index')
        ->middleware('throttle:admin-actions');
    Route::get('users/create', [UsersController::class, 'create'])
        ->name('users.create')
        ->middleware('throttle:admin-actions');
    Route::post('users', [UsersController::class, 'store'])
        ->name('users.store')
        ->middleware('throttle:admin-actions');
    Route::get('users/{user}/edit', [UsersController::class, 'edit'])
        ->name('users.edit')
        ->middleware('throttle:admin-actions');
    Route::put('users/{user}', [UsersController::class, 'update'])
        ->name('users.update')
        ->middleware('throttle:admin-actions');
    Route::post('users/{user}/manager-reports', [UsersController::class, 'updateManagerReports'])
        ->name('users.manager-reports.update')
        ->middleware('throttle:admin-actions');
    Route::post('users/{user}/manager-access/grant', [UsersController::class, 'grantManagerAccess'])
        ->name('users.manager-access.grant')
        ->middleware('throttle:admin-actions');
    Route::post('users/{user}/manager-access/revoke', [UsersController::class, 'revokeManagerAccess'])
        ->name('users.manager-access.revoke')
        ->middleware('throttle:admin-actions');
    Route::post('users/bulk-status', [UsersController::class, 'bulkStatus'])
        ->name('users.bulk-status')
        ->middleware('throttle:admin-actions');

    Route::resource('roles', RolesController::class)
        ->except(['show'])
        ->middleware('throttle:admin-actions');
    Route::get('permissions', [PermissionsController::class, 'index'])
        ->name('permissions.index')
        ->middleware('throttle:admin-actions');

    Route::get('facility-types', [FacilityTypesController::class, 'index'])
        ->name('facility-types.index')
        ->middleware('throttle:admin-actions');
    Route::post('facility-types', [FacilityTypesController::class, 'store'])
        ->name('facility-types.store')
        ->middleware('throttle:admin-actions');
    Route::put('facility-types/{facilityType}', [FacilityTypesController::class, 'update'])
        ->name('facility-types.update')
        ->middleware('throttle:admin-actions');
    Route::delete('facility-types/{facilityType}', [FacilityTypesController::class, 'destroy'])
        ->name('facility-types.destroy')
        ->middleware('throttle:admin-actions');

    Route::get('request-types', [RequestTypesController::class, 'index'])
        ->name('request-types.index')
        ->middleware('throttle:admin-actions');
    Route::post('request-types', [RequestTypesController::class, 'store'])
        ->name('request-types.store')
        ->middleware('throttle:admin-actions');
    Route::put('request-types/{requestType}', [RequestTypesController::class, 'update'])
        ->name('request-types.update')
        ->middleware('throttle:admin-actions');
    Route::delete('request-types/{requestType}', [RequestTypesController::class, 'destroy'])
        ->name('request-types.destroy')
        ->middleware('throttle:admin-actions');

    Route::get('todos/weekly', [TodoController::class, 'weeklyIndex'])->name('todos.weekly');
    Route::resource('todos', TodoController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::post('todos/{todo}/complete', [TodoController::class, 'complete'])->name('todos.complete');
    Route::post('todos/bulk-complete', [TodoController::class, 'bulkComplete'])->name('todos.bulk-complete');
});

require __DIR__.'/settings.php';
