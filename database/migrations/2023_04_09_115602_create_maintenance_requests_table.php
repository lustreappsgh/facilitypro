<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained('facilities');
            $table->foreignId('request_type_id')->constrained('request_types');
            $table->text('description')->nullable();
            $table->bigInteger('cost')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('requested_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};