<?php

namespace App\Domains\Users\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Notifications\Services\OperationalNotificationService;
use App\Domains\Users\DTOs\ManagerAccessData;
use App\Models\User;
use Illuminate\Support\Arr;

class GrantMaintenanceManagerAccessAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction,
        protected OperationalNotificationService $operationalNotificationService
    ) {}

    public function execute(ManagerAccessData $data): User
    {
        $manager = User::query()->active()->findOrFail($data->manager_id);

        $before = [
            'roles' => Arr::sort($manager->getRoleNames()->toArray()),
        ];

        if (! $manager->hasRole('Maintenance Manager')) {
            $manager->assignRole('Maintenance Manager');
        }

        $manager->refresh();

        $after = [
            'roles' => Arr::sort($manager->getRoleNames()->toArray()),
            'facility_manager_id' => $data->facility_manager_id,
        ];

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'user.manager_access_granted',
            auditable_type: $manager->getMorphClass(),
            auditable_id: $manager->id,
            before: $before,
            after: $after,
        ));

        $this->operationalNotificationService->managerAccessGranted($manager);

        return $manager;
    }
}
