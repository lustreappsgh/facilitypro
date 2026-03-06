<?php

use Database\Seeders\ModelHasRolesTableSeeder;
use Database\Seeders\RolesTableSeeder;
use Database\Seeders\UsersTableSeeder;
use Illuminate\Support\Facades\DB;

test('roles and model_has_roles seeders load current snapshots', function () {
    $this->seed(UsersTableSeeder::class);
    $this->seed(RolesTableSeeder::class);
    $this->seed(ModelHasRolesTableSeeder::class);

    expect(DB::table('roles')->count())->toBe(4);
    expect(DB::table('model_has_roles')->count())->toBe(23);
});
