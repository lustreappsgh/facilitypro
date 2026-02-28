<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_request_id')->constrained('maintenance_requests');
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->date('assigned_date')->useCurrent();
            $table->date('scheduled_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->integer('estimated_cost')->nullable();
            $table->integer('actual_cost')->nullable();
            $table->string('status')->default('assigned');
            $table->foreignId('assigned_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
