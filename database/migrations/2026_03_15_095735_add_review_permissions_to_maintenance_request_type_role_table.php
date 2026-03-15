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
        Schema::table('maintenance_request_type_role', function (Blueprint $table) {
            $table->boolean('can_approve')->default(true)->after('request_type_id');
            $table->boolean('can_reject')->default(true)->after('can_approve');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_request_type_role', function (Blueprint $table) {
            $table->dropColumn(['can_approve', 'can_reject']);
        });
    }
};
