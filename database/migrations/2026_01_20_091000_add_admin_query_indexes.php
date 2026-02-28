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
        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'actor_id') && ! $this->indexExists('audit_logs', 'audit_logs_actor_id_index')) {
                $table->index('actor_id', 'audit_logs_actor_id_index');
            } elseif (Schema::hasColumn('audit_logs', 'user_id') && ! $this->indexExists('audit_logs', 'audit_logs_user_id_index')) {
                $table->index('user_id', 'audit_logs_user_id_index');
            }
            if (Schema::hasColumn('audit_logs', 'action') && ! $this->indexExists('audit_logs', 'audit_logs_action_index')) {
                $table->index('action', 'audit_logs_action_index');
            }
            if (Schema::hasColumn('audit_logs', 'auditable_type') && Schema::hasColumn('audit_logs', 'auditable_id')) {
                if (! $this->indexExists('audit_logs', 'audit_logs_auditable_lookup')) {
                    $table->index(['auditable_type', 'auditable_id'], 'audit_logs_auditable_lookup');
                }
            }
            if (Schema::hasColumn('audit_logs', 'created_at') && ! $this->indexExists('audit_logs', 'audit_logs_created_at_index')) {
                $table->index('created_at', 'audit_logs_created_at_index');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! $this->indexExists('users', 'users_is_active_index')) {
                $table->index('is_active', 'users_is_active_index');
            }
        });

        Schema::table('maintenance_requests', function (Blueprint $table) {
            if (! $this->indexExists('maintenance_requests', 'maintenance_requests_status_index')) {
                $table->index('status', 'maintenance_requests_status_index');
            }
            if (! $this->indexExists('maintenance_requests', 'maintenance_requests_created_at_index')) {
                $table->index('created_at', 'maintenance_requests_created_at_index');
            }
        });

        Schema::table('work_orders', function (Blueprint $table) {
            if (! $this->indexExists('work_orders', 'work_orders_status_index')) {
                $table->index('status', 'work_orders_status_index');
            }
            if (! $this->indexExists('work_orders', 'work_orders_scheduled_date_index')) {
                $table->index('scheduled_date', 'work_orders_scheduled_date_index');
            }
            if (! $this->indexExists('work_orders', 'work_orders_created_at_index')) {
                $table->index('created_at', 'work_orders_created_at_index');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (! $this->indexExists('payments', 'payments_status_index')) {
                $table->index('status', 'payments_status_index');
            }
            if (! $this->indexExists('payments', 'payments_created_at_index')) {
                $table->index('created_at', 'payments_created_at_index');
            }
        });

        Schema::table('todos', function (Blueprint $table) {
            if (! $this->indexExists('todos', 'todos_status_index')) {
                $table->index('status', 'todos_status_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if ($this->indexExists('audit_logs', 'audit_logs_actor_id_index')) {
                $table->dropIndex('audit_logs_actor_id_index');
            }
            if ($this->indexExists('audit_logs', 'audit_logs_user_id_index')) {
                $table->dropIndex('audit_logs_user_id_index');
            }
            if ($this->indexExists('audit_logs', 'audit_logs_action_index')) {
                $table->dropIndex('audit_logs_action_index');
            }
            if ($this->indexExists('audit_logs', 'audit_logs_auditable_lookup')) {
                $table->dropIndex('audit_logs_auditable_lookup');
            }
            if ($this->indexExists('audit_logs', 'audit_logs_created_at_index')) {
                $table->dropIndex('audit_logs_created_at_index');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if ($this->indexExists('users', 'users_is_active_index')) {
                $table->dropIndex('users_is_active_index');
            }
        });

        Schema::table('maintenance_requests', function (Blueprint $table) {
            if ($this->indexExists('maintenance_requests', 'maintenance_requests_status_index')) {
                $table->dropIndex('maintenance_requests_status_index');
            }
            if ($this->indexExists('maintenance_requests', 'maintenance_requests_created_at_index')) {
                $table->dropIndex('maintenance_requests_created_at_index');
            }
        });

        Schema::table('work_orders', function (Blueprint $table) {
            if ($this->indexExists('work_orders', 'work_orders_status_index')) {
                $table->dropIndex('work_orders_status_index');
            }
            if ($this->indexExists('work_orders', 'work_orders_scheduled_date_index')) {
                $table->dropIndex('work_orders_scheduled_date_index');
            }
            if ($this->indexExists('work_orders', 'work_orders_created_at_index')) {
                $table->dropIndex('work_orders_created_at_index');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if ($this->indexExists('payments', 'payments_status_index')) {
                $table->dropIndex('payments_status_index');
            }
            if ($this->indexExists('payments', 'payments_created_at_index')) {
                $table->dropIndex('payments_created_at_index');
            }
        });

        Schema::table('todos', function (Blueprint $table) {
            if ($this->indexExists('todos', 'todos_status_index')) {
                $table->dropIndex('todos_status_index');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $rows = DB::select("PRAGMA index_list('$table')");
            foreach ($rows as $row) {
                if (($row->name ?? null) === $index) {
                    return true;
                }
            }

            return false;
        }

        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
