<?php

namespace App\Domains\Users\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Users\DTOs\ManagerReportsData;
use App\Models\User;
use DomainException;
use Illuminate\Support\Arr;

class UpdateManagerReportsAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction
    ) {}

    public function execute(User $manager, ManagerReportsData $data): void
    {
        if ($manager->manager_id && ! empty($data->report_ids)) {
            throw new DomainException('Remove the supervising manager before assigning direct reports.');
        }

        $eligibleReportIds = User::query()
            ->active()
            ->role('Facility Manager')
            ->whereKeyNot($manager->id)
            ->pluck('id');

        $requestedIds = collect($data->report_ids)
            ->filter(fn ($id) => is_int($id))
            ->values();

        $invalidIds = $requestedIds->diff($eligibleReportIds);
        if ($invalidIds->isNotEmpty()) {
            throw new DomainException('One or more selected users are not eligible to be assigned.');
        }

        $currentIds = User::query()
            ->active()
            ->where('manager_id', $manager->id)
            ->pluck('id');

        $attachIds = $requestedIds->diff($currentIds);
        $detachIds = $currentIds->diff($requestedIds);

        $invalidManagers = User::query()
            ->active()
            ->whereIn('id', $requestedIds)
            ->whereHas('subordinates', fn ($query) => $query->role('Facility Manager'))
            ->pluck('id');

        if ($invalidManagers->isNotEmpty()) {
            throw new DomainException('One or more selected users already manage other facility managers.');
        }

        foreach ($attachIds as $userId) {
            $report = User::query()->active()->find($userId);
            if (! $report) {
                continue;
            }

            $before = $report->getOriginal();
            $report->update(['manager_id' => $manager->id]);

            $this->recordAuditLogAction->execute(new AuditLogData(
                actor_id: $this->resolveActorId(),
                action: 'user.manager_assigned',
                auditable_type: $report->getMorphClass(),
                auditable_id: $report->id,
                before: $before,
                after: Arr::except($report->getAttributes(), ['password']),
            ));
        }

        foreach ($detachIds as $userId) {
            $report = User::query()->active()->find($userId);
            if (! $report) {
                continue;
            }

            $before = $report->getOriginal();
            $report->update(['manager_id' => null]);

            $this->recordAuditLogAction->execute(new AuditLogData(
                actor_id: $this->resolveActorId(),
                action: 'user.manager_unassigned',
                auditable_type: $report->getMorphClass(),
                auditable_id: $report->id,
                before: $before,
                after: Arr::except($report->getAttributes(), ['password']),
            ));
        }
    }
}
