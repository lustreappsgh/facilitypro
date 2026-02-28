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
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->index('status', 'maintenance_requests_status_index');
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->index('status', 'work_orders_status_index');
            $table->index('scheduled_date', 'work_orders_scheduled_date_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('status', 'payments_status_index');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->index('status', 'vendors_status_index');
            $table->index('service_type', 'vendors_service_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropIndex('maintenance_requests_status_index');
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropIndex('work_orders_status_index');
            $table->dropIndex('work_orders_scheduled_date_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_status_index');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex('vendors_status_index');
            $table->dropIndex('vendors_service_type_index');
        });
    }
};
