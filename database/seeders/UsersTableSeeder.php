<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UsersTableSeeder extends Seeder
{
    /**
     * Seed the application's users from a snapshot of the current database.
     */
    public function run(): void
    {
        $users = json_decode(
            file_get_contents(database_path('seeders/data/users.json')),
            true
        );

        if (! is_array($users) || $users === []) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        DB::table('users')->upsert(
            $users,
            ['id'],
            [
                'name',
                'email',
                'is_active',
                'profile_photo_path',
                'email_verified_at',
                'password',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'remember_token',
                'created_at',
                'updated_at',
                'is_default_password',
                'manager_id',
            ]
        );

        Schema::enableForeignKeyConstraints();
    }
}
