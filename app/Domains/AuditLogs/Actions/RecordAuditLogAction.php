<?php

namespace App\Domains\AuditLogs\Actions;

use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Schema;

class RecordAuditLogAction
{
    public function execute(AuditLogData $data): AuditLog
    {
        $payload = $data->toArray();

        if (! $payload['ip_address']) {
            $payload['ip_address'] = request()->ip();
        }

        if (! $payload['user_agent']) {
            $payload['user_agent'] = request()->userAgent();
        }

        if (
            Schema::hasColumn('audit_logs', 'user_id')
            && empty($payload['user_id'] ?? null)
            && ! empty($payload['actor_id'] ?? null)
        ) {
            $payload['user_id'] = $payload['actor_id'];
        }

        return AuditLog::create($payload);
    }
}
