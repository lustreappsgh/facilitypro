<?php

use Database\Seeders\FacilitiesTableSeeder;
use Database\Seeders\FacilityTypesTableSeeder;
use Database\Seeders\RequestTypesTableSeeder;
use Database\Seeders\UsersTableSeeder;
use Illuminate\Support\Facades\DB;

test('master data seeders load the current snapshots', function () {
    $this->seed(UsersTableSeeder::class);
    $this->seed(FacilityTypesTableSeeder::class);
    $this->seed(RequestTypesTableSeeder::class);
    $this->seed(FacilitiesTableSeeder::class);

    expect(DB::table('facility_types')->count())->toBe(44);
    expect(DB::table('request_types')->count())->toBe(25);
    expect(DB::table('facilities')->count())->toBe(1179);

    expect(DB::table('facility_types')->where('id', 1)->value('name'))
        ->toBe('Academic block');
    expect(DB::table('request_types')->where('id', 1)->value('name'))
        ->toBe('Electrical');
    expect(DB::table('facilities')->where('id', 1)->value('name'))
        ->toBe('ACADEMIC BLOCK');
});
