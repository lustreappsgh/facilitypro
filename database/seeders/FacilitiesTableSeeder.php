<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FacilitiesTableSeeder extends Seeder
{
    /**
     * Seed the application's facilities from a snapshot.
     */
    public function run(): void
    {
        $data = json_decode(
            file_get_contents(database_path('seeders/data/facilities.json')),
            true
        );

        if (! is_array($data) || $data === []) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        DB::table('facilities')->upsert(
            $data,
            ['id'],
            [
                'name',
                'facility_type_id',
                'parent_id',
                'condition',
                'managed_by',
                'created_at',
                'updated_at',
            ]
        );

        Schema::enableForeignKeyConstraints();
    }
}
