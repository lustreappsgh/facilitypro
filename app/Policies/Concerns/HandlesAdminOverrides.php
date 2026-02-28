<?php

namespace App\Policies\Concerns;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Models\User;

trait HandlesAdminOverrides
{
    protected function allowAdmin(User $user, string $ability, mixed $model = null): ?bool
    {
        if (! $user->can('users.manage')) {
            return null;
        }

        if ($this->isSensitiveAbility($ability)) {
            $auditableType = null;
            $auditableId = null;

            if (is_object($model) && method_exists($model, 'getMorphClass')) {
                $auditableType = $model->getMorphClass();
                $auditableId = $model->getKey();
            }

            app(RecordAuditLogAction::class)->execute(
                new AuditLogData(
                    actor_id: $user->id,
                    action: 'admin_override.' . $ability,
                    auditable_type: $auditableType,
                    auditable_id: $auditableId,
                    before: null,
                    after: null,
                )
            );
        }

        return true;
    }

    private function isSensitiveAbility(string $ability): bool
    {
        return ! in_array($ability, ['view', 'viewAny'], true);
    }
}
