<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Seed permissions and role permission assignments from a snapshot.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = json_decode(
            file_get_contents(database_path('seeders/data/permissions.json')),
            true
        );
        $rolePermissions = json_decode(
            file_get_contents(database_path('seeders/data/role_has_permissions.json')),
            true
        );

        if (! is_array($permissions) || $permissions === []) {
            return;
        }

        DB::table('permissions')->upsert(
            $permissions,
            ['id'],
            ['name', 'guard_name', 'created_at', 'updated_at']
        );

        if (! is_array($rolePermissions)) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            return;
        }

        Schema::disableForeignKeyConstraints();

        DB::table('role_has_permissions')->truncate();

        if ($rolePermissions !== []) {
            DB::table('role_has_permissions')->insert($rolePermissions);
        }

        Schema::enableForeignKeyConstraints();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
