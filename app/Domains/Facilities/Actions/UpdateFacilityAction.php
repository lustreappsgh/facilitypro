<?php

namespace App\Domains\Facilities\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Facilities\DTOs\FacilityData;
use App\Models\Facility;
use DomainException;

class UpdateFacilityAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected RecordAuditLogAction $recordAuditLogAction
    ) {}

    public function execute(Facility $facility, FacilityData $data): Facility
    {
        $before = $facility->getOriginal();

        $this->assertNoHierarchyCycle($facility, $data->parent_id);

        $facility->update($data->toArray());

        $facility = $facility->refresh();

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'facility.updated',
            auditable_type: $facility->getMorphClass(),
            auditable_id: $facility->id,
            before: $before,
            after: $facility->getAttributes(),
        ));

        if ($before['parent_id'] !== $facility->parent_id) {
            $this->recordAuditLogAction->execute(new AuditLogData(
                actor_id: $this->resolveActorId(),
                action: 'facility.hierarchy_updated',
                auditable_type: $facility->getMorphClass(),
                auditable_id: $facility->id,
                before: ['parent_id' => $before['parent_id']],
                after: ['parent_id' => $facility->parent_id],
            ));
        }

        return $facility;
    }

    private function assertNoHierarchyCycle(Facility $facility, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($parentId === $facility->id) {
            throw new DomainException('A facility cannot be its own parent.');
        }

        $currentParentId = $parentId;
        while ($currentParentId !== null) {
            $current = Facility::query()
                ->select(['id', 'parent_id'])
                ->find($currentParentId);

            if (! $current) {
                return;
            }

            if ($current->id === $facility->id) {
                throw new DomainException('Facility hierarchy cycle detected.');
            }

            $currentParentId = $current->parent_id;
        }
    }
}
