<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_request_type_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('request_type_id')->constrained()->cascadeOnDelete();
            $table->boolean('can_approve')->default(false);
            $table->boolean('can_reject')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'request_type_id'], 'maintenance_request_type_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_request_type_user');
    }
};
