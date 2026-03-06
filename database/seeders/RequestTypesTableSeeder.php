<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RequestTypesTableSeeder extends Seeder
{
    /**
     * Seed the application's request types from a snapshot.
     */
    public function run(): void
    {
        $data = json_decode(
            file_get_contents(database_path('seeders/data/request_types.json')),
            true
        );

        if (! is_array($data) || $data === []) {
            return;
        }

        DB::table('request_types')->upsert(
            $data,
            ['id'],
            ['name', 'created_at', 'updated_at']
        );
    }
}
