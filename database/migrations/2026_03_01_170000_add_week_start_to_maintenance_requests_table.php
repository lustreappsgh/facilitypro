<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->date('week_start')->nullable()->after('cost');
        });

        DB::table('maintenance_requests')
            ->select(['id', 'created_at'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $createdAt = $row->created_at ? Carbon::parse($row->created_at) : now();
                    $weekStart = $createdAt->copy()->startOfWeek(Carbon::MONDAY)->addWeek()->toDateString();

                    DB::table('maintenance_requests')
                        ->where('id', $row->id)
                        ->update(['week_start' => $weekStart]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropColumn('week_start');
        });
    }
};
