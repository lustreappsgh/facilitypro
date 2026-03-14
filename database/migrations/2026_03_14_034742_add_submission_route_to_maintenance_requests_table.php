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
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->string('submission_route')
                ->default('maintenance_manager')
                ->after('status');
            $table->index('submission_route', 'maintenance_requests_submission_route_index');
        });

        DB::table('maintenance_requests')
            ->whereNull('submission_route')
            ->update(['submission_route' => 'maintenance_manager']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropIndex('maintenance_requests_submission_route_index');
            $table->dropColumn('submission_route');
        });
    }
};
