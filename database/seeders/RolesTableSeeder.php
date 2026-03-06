<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    /**
     * Seed roles from the current database snapshot.
     */
    public function run(): void
    {
        $data = json_decode(
            file_get_contents(database_path('seeders/data/roles.json')),
            true
        );

        if (! is_array($data) || $data === []) {
            return;
        }

        DB::table('roles')->upsert(
            $data,
            ['id'],
            ['name', 'guard_name', 'created_at', 'updated_at']
        );
    }
}
