<?php

use Database\Seeders\PermissionsTableSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Support\Facades\DB;

test('permissions seeder loads the current permission snapshots', function () {
    $this->seed(RolesTableSeeder::class);
    $this->seed(PermissionsTableSeeder::class);

    expect(DB::table('permissions')->count())->toBe(48);
    expect(DB::table('role_has_permissions')->count())->toBe(80);

    expect(DB::table('permissions')->where('id', 1)->value('name'))
        ->toBe('facilities.view');
});
