<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FacilityTypesTableSeeder extends Seeder
{
    /**
     * Seed the application's facility types from a snapshot.
     */
    public function run(): void
    {
        $data = json_decode(
            file_get_contents(database_path('seeders/data/facility_types.json')),
            true
        );

        if (! is_array($data) || $data === []) {
            return;
        }

        DB::table('facility_types')->upsert(
            $data,
            ['id'],
            ['name', 'created_at', 'updated_at']
        );
    }
}
