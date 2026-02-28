<?php

use App\Models\FacilityType;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('facility_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $facilityTypes = [
            ['name' => 'Academic block'],
            ['name' => 'Accomodation'],
            ['name' => 'Dormitories'],
            ['name' => 'Huts'],
            ['name' => 'Animals'],
            ['name' => 'Apartment'],
            ['name' => 'Boardroom'],
            ['name' => 'Boreholes'],
            ['name' => 'Brigde'],
            ['name' => 'Clinic'],
            ['name' => 'Conference hall'],
            ['name' => 'Gallery'],
            ['name' => 'Garden'],
            ['name' => 'Generators'],
            ['name' => 'Golf carts'],
            ['name' => 'Chapel'],
            ['name' => 'Incinerator'],
            ['name' => 'Lawnmowers'],
            ['name' => 'Lawns'],
            ['name' => 'Library'],
            ['name' => 'Lounge'],
            ['name' => 'Parks and roads'],
            ['name' => 'Plantations'],
            ['name' => 'Plaza'],
            ['name' => 'Refuse area'],
            ['name' => 'Restaurant'],
            ['name' => 'Septic tanks biodigesters'],
            ['name' => 'Storerooms'],
            ['name' => 'Terrace'],
            ['name' => 'The office'],
            ['name' => 'The volante'],
            ['name' => 'Vehicles'],
            ['name' => 'Washrooms'],
            ['name' => 'Water houses'],
            ['name' => 'Waterfall'],
        ];

        FacilityType::insert($facilityTypes);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facility_types');
    }
};