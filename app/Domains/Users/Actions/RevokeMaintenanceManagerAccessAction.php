<?php

namespace App\Domains\Users\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Notifications\Services\OperationalNotificationService;
use App\Domains\Users\DTOs\ManagerAccessData;
use App\Models\User;
use DomainException;
use Illuminate\Support\Arr;

class RevokeMaintenanceManagerAccessAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction,
        protected OperationalNotificationService $operationalNotificationService
    ) {}

    public function execute(ManagerAccessData $data): User
    {
        $manager = User::query()->active()->findOrFail($data->manager_id);
        $hasOtherReports = $manager->subordinates()
            ->whereKeyNot($data->facility_manager_id)
            ->exists();

        if ($hasOtherReports) {
            throw new DomainException('Manager still supervises other facility managers. Remove those assignments first.');
        }

        $before = [
            'roles' => Arr::sort($manager->getRoleNames()->toArray()),
        ];

        if ($manager->hasRole('Maintenance Manager')) {
            $manager->removeRole('Maintenance Manager');
        }

        $manager->refresh();

        $after = [
            'roles' => Arr::sort($manager->getRoleNames()->toArray()),
            'facility_manager_id' => $data->facility_manager_id,
        ];

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'user.manager_access_revoked',
            auditable_type: $manager->getMorphClass(),
            auditable_id: $manager->id,
            before: $before,
            after: $after,
        ));

        $this->operationalNotificationService->managerAccessRevoked($manager);

        return $manager;
    }
}
