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
        if (! Schema::hasTable('payment_approvals')) {
            Schema::create('payment_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->constrained('payments');
                $table->foreignId('approver_id')->constrained('users');
                $table->string('status');
                $table->text('comments')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_approvals');
    }
};
