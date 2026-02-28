<?php

use App\Enums\Condition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->date('inspection_date')->useCurrent();
            $table->foreignId('facility_id')->constrained('facilities');
            $table->enum('condition', array_map(fn($type) => $type->name, Condition::cases()));
            $table->text('comments')->nullable();
            $table->string('image')->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};