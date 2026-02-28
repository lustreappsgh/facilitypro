<?php

use App\Models\RequestType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('request_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $requestTypes = [
            ['name' => 'Electrical'],
            ['name' => 'Carpentery - doors '],
            ['name' => 'Carpentery - General'],
            ['name' => 'Plumbing'],
            ['name' => 'Masonary '],
            ['name' => 'Air conditioning '],
            ['name' => 'Borehole'],
            ['name' => 'Waterhouse '],
            ['name' => 'Generator '],
            ['name' => 'Lawn management '],
            ['name' => 'Snacks & drinks '],
            ['name' => 'Subscription '],
            ['name' => 'Painting '],
            ['name' => 'Equipment maintenance '],
            ['name' => 'Tiling '],
            ['name' => 'Animal Feed/care '],
            ['name' => 'Paid work '],
            ['name' => 'Clinic '],
            ['name' => 'Septic tank dislodgement '],
            ['name' => 'Refuse disposal '],
            ['name' => 'Fuel - lawnmower '],
            ['name' => 'Fuel - incinerator '],
            ['name' => 'Fuel - generators '],
            ['name' => 'ECG'],
            ['name' => 'BMCDR Meals'],
        ];

        RequestType::insert($requestTypes);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_types');
    }
};