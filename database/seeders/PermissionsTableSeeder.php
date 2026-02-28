<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // create canonical permissions

        // Facilities
        Permission::firstOrCreate(['name' => 'facilities.view']);
        Permission::firstOrCreate(['name' => 'facilities.create']);
        Permission::firstOrCreate(['name' => 'facilities.update']);
        Permission::firstOrCreate(['name' => 'facilities.delete']);
        Permission::firstOrCreate(['name' => 'facilities.assign_manager']);

        // Inspections
        Permission::firstOrCreate(['name' => 'inspections.view']);
        Permission::firstOrCreate(['name' => 'inspections.create']);
        Permission::firstOrCreate(['name' => 'inspections.update']);
        Permission::firstOrCreate(['name' => 'inspections.lock']);

        // Maintenance Requests
        Permission::firstOrCreate(['name' => 'maintenance.view']);
        Permission::firstOrCreate(['name' => 'maintenance.create']);
        Permission::firstOrCreate(['name' => 'maintenance.update']);
        Permission::firstOrCreate(['name' => 'maintenance.review']);
        Permission::firstOrCreate(['name' => 'maintenance.assign']);
        Permission::firstOrCreate(['name' => 'maintenance.start']);
        Permission::firstOrCreate(['name' => 'maintenance.complete']);
        Permission::firstOrCreate(['name' => 'maintenance.close']);
        Permission::firstOrCreate(['name' => 'maintenance.manage_all']);
        Permission::firstOrCreate(['name' => 'maintenance_requests.view']);
        Permission::firstOrCreate(['name' => 'maintenance_requests.create']);
        Permission::firstOrCreate(['name' => 'maintenance_requests.update']);

        // Work Orders
        Permission::firstOrCreate(['name' => 'work_orders.view']);
        Permission::firstOrCreate(['name' => 'work_orders.create']);
        Permission::firstOrCreate(['name' => 'work_orders.update']);
        Permission::firstOrCreate(['name' => 'work_orders.delete']);

        // Vendors
        Permission::firstOrCreate(['name' => 'vendors.view']);
        Permission::firstOrCreate(['name' => 'vendors.create']);
        Permission::firstOrCreate(['name' => 'vendors.update']);
        Permission::firstOrCreate(['name' => 'vendors.delete']);

        // Payments
        Permission::firstOrCreate(['name' => 'payments.view']);
        Permission::firstOrCreate(['name' => 'payments.create']);
        Permission::firstOrCreate(['name' => 'payments.submit']);
        Permission::firstOrCreate(['name' => 'payments.approve']);
        Permission::firstOrCreate(['name' => 'payments.reject']);
        Permission::firstOrCreate(['name' => 'payments.mark_paid']);

        // Todos
        Permission::firstOrCreate(['name' => 'todos.view']);
        Permission::firstOrCreate(['name' => 'todos.create']);
        Permission::firstOrCreate(['name' => 'todos.update']);
        Permission::firstOrCreate(['name' => 'todos.complete']);

        // Reports & Audit
        Permission::firstOrCreate(['name' => 'reports.view']);
        Permission::firstOrCreate(['name' => 'audit.view']);
        Permission::firstOrCreate(['name' => 'users.view']);
        Permission::firstOrCreate(['name' => 'users.manage']);
        Permission::firstOrCreate(['name' => 'roles.manage']);
        Permission::firstOrCreate(['name' => 'permissions.view']);
        Permission::firstOrCreate(['name' => 'facility_types.manage']);
        Permission::firstOrCreate(['name' => 'request_types.manage']);
        Permission::firstOrCreate(['name' => 'settings.view']);

        // create roles and assign permissions as bundles
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $admin->givePermissionTo(Permission::all()); // Admin gets all permissions

        $manager = Role::firstOrCreate(['name' => 'Manager']);
        // Manager handles approvals and oversight
        $manager->givePermissionTo([
            'payments.view',
            'payments.approve',
            'payments.reject',
            'maintenance_requests.view',
            'maintenance.manage_all',
            'work_orders.view',
            'reports.view',
            'audit.view',
        ]);

        $facilityManager = Role::firstOrCreate(['name' => 'Facility Manager']);
        // Facility Manager creates inspections and maintenance requests
        $facilityManager->givePermissionTo([
            'facilities.view',
            'facilities.update',
            'inspections.view',
            'inspections.create',
            'inspections.update',
            'maintenance.view',
            'maintenance.create',
            'maintenance.update',
            'maintenance_requests.create',
            'maintenance_requests.update',
            'todos.view',
            'todos.create',
            'todos.update',
            'todos.complete',
            'settings.view',
        ]);

        $maintenanceManager = Role::firstOrCreate(['name' => 'Maintenance Manager']);
        // Maintenance Manager assigns and manages work orders
        $maintenanceManager->syncPermissions([
            'maintenance_requests.view',
            'maintenance.start',
            'work_orders.view',
            'work_orders.create',
            'work_orders.update',
            'vendors.view',
            'vendors.create',
            'payments.view',
            'settings.view',
        ]);

    }
}
