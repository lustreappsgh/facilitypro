<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE work_orders DROP FOREIGN KEY work_orders_vendor_id_foreign');
            DB::statement('ALTER TABLE work_orders MODIFY vendor_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE work_orders ADD CONSTRAINT work_orders_vendor_id_foreign FOREIGN KEY (vendor_id) REFERENCES vendors(id)');
            return;
        }

        Schema::table('work_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE work_orders DROP FOREIGN KEY work_orders_vendor_id_foreign');
            DB::statement('ALTER TABLE work_orders MODIFY vendor_id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE work_orders ADD CONSTRAINT work_orders_vendor_id_foreign FOREIGN KEY (vendor_id) REFERENCES vendors(id)');
            return;
        }

        Schema::table('work_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_id')->nullable(false)->change();
        });
    }
};

