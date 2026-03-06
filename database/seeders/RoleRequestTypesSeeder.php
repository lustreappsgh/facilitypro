<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoleRequestTypesSeeder extends Seeder
{
    public function run(): void
    {
        $rows = json_decode(
            file_get_contents(database_path('seeders/data/maintenance_request_type_role.json')),
            true
        );

        if (! is_array($rows)) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        DB::table('maintenance_request_type_role')->truncate();

        if ($rows !== []) {
            DB::table('maintenance_request_type_role')->insert($rows);
        }

        Schema::enableForeignKeyConstraints();
    }
}
