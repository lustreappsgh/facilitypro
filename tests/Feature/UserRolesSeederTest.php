<?php

use App\Models\User;
use Database\Seeders\PermissionsTableSeeder;
use Database\Seeders\RolesTableSeeder;
use Database\Seeders\UserRolesTableSeeder;
use Database\Seeders\UsersTableSeeder;

test('user roles seeder assigns expected roles', function () {
    $this->seed(UsersTableSeeder::class);
    $this->seed(RolesTableSeeder::class);
    $this->seed(PermissionsTableSeeder::class);
    $this->seed(UserRolesTableSeeder::class);

    $superAdmin = User::query()->where('email', 'superadmin@email.com')->firstOrFail();
    $ruth = User::query()->where('email', 'ruth@anagkazofacilities.org')->firstOrFail();
    $kwadwo = User::query()->where('email', 'kwadwo.bonsu@anagkazofacilities.org')->firstOrFail();
    $sendy = User::query()->where('email', 'sendy.shibambo@anagkazofacilities.org')->firstOrFail();

    expect($superAdmin->hasRole('Admin'))->toBeTrue();
    expect($superAdmin->hasRole('Facility Manager'))->toBeFalse();

    expect($ruth->hasRole('Admin'))->toBeTrue();
    expect($ruth->hasRole('Facility Manager'))->toBeFalse();

    expect($kwadwo->hasRole('Facility Manager'))->toBeTrue();
    expect($kwadwo->hasRole('Maintenance Manager'))->toBeTrue();

    expect($sendy->hasRole('Facility Manager'))->toBeTrue();
    expect($sendy->hasRole('Manager'))->toBeTrue();
});
