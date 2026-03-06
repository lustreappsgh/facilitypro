<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModelHasRolesTableSeeder extends Seeder
{
    /**
     * Seed model_has_roles from the current database snapshot.
     */
    public function run(): void
    {
        $data = json_decode(
            file_get_contents(database_path('seeders/data/model_has_roles.json')),
            true
        );

        if (! is_array($data)) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        DB::table('model_has_roles')->truncate();

        if ($data !== []) {
            DB::table('model_has_roles')->insert($data);
        }

        Schema::enableForeignKeyConstraints();
    }
}
