<?php

use Database\Seeders\PermissionsTableSeeder;
use Database\Seeders\RequestTypesTableSeeder;
use Database\Seeders\RoleRequestTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Support\Facades\DB;

test('role request type seeder loads the current snapshot', function () {
    $this->seed(RequestTypesTableSeeder::class);
    $this->seed(RolesTableSeeder::class);
    $this->seed(PermissionsTableSeeder::class);
    $this->seed(RoleRequestTypesSeeder::class);

    expect(DB::table('maintenance_request_type_role')->count())->toBe(50);

    expect(DB::table('maintenance_request_type_role')->where('role_id', 1)->count())->toBe(25);
    expect(DB::table('maintenance_request_type_role')->where('role_id', 2)->count())->toBe(21);
    expect(DB::table('maintenance_request_type_role')->where('role_id', 4)->count())->toBe(4);
});
