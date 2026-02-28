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
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        if (! Schema::hasColumn('audit_logs', 'actor_id')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->foreignId('actor_id')->nullable()->after('user_id');
            });
        }

        if (Schema::hasColumn('audit_logs', 'user_id') && Schema::hasColumn('audit_logs', 'actor_id')) {
            DB::table('audit_logs')
                ->whereNull('actor_id')
                ->update(['actor_id' => DB::raw('user_id')]);

            Schema::table('audit_logs', function (Blueprint $table) {
                $table->foreign('actor_id')->references('id')->on('users');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        if (Schema::hasColumn('audit_logs', 'actor_id')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropForeign(['actor_id']);
                $table->dropColumn('actor_id');
            });
        }
    }
};
