<?php

use App\Models\User;
use Database\Seeders\UsersTableSeeder;

test('users table seeder loads the snapshot', function () {
    $this->seed(UsersTableSeeder::class);

    expect(User::count())->toBe(22);

    $superAdmin = User::query()
        ->where('email', 'superadmin@email.com')
        ->first();

    expect($superAdmin)->not->toBeNull();
    expect($superAdmin->name)->toBe('Super Admin');
    expect($superAdmin->is_active)->toBeTrue();
});
