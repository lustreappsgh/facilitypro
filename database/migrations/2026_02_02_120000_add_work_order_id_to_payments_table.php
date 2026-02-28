<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('work_order_id')
                ->nullable()
                ->after('maintenance_request_id')
                ->constrained('work_orders')
                ->nullOnDelete();
        });

        DB::statement(
            'UPDATE payments SET work_order_id = (
                SELECT work_orders.id
                FROM work_orders
                WHERE work_orders.maintenance_request_id = payments.maintenance_request_id
                ORDER BY work_orders.created_at DESC
                LIMIT 1
            )'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['work_order_id']);
            $table->dropColumn('work_order_id');
        });
    }
};
