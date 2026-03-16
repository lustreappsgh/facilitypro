<?php

namespace App\Domains\Inspections\Actions;

use App\Domains\AuditLogs\Actions\RecordAuditLogAction;
use App\Domains\AuditLogs\DTOs\AuditLogData;
use App\Domains\AuditLogs\Traits\ResolvesAuditActor;
use App\Domains\Inspections\DTOs\InspectionData;
use App\Domains\Maintenance\Actions\CreateMaintenanceRequestAction;
use App\Domains\Maintenance\DTOs\MaintenanceRequestData;
use App\Domains\Notifications\Services\OperationalNotificationService;
use App\Models\Facility;
use App\Models\Inspection;
use Illuminate\Auth\Access\AuthorizationException;

class CreateInspectionAction
{
    use ResolvesAuditActor;

    public function __construct(
        protected CreateMaintenanceRequestAction $createMaintenanceRequestAction,
        protected RecordAuditLogAction $recordAuditLogAction,
        protected OperationalNotificationService $operationalNotificationService,
    ) {}

    public function execute(InspectionData $data): Inspection
    {
        $facility = Facility::query()->find($data->facility_id);
        $user = auth()->user();

        if ($facility && $user && ! $user->can('users.manage') && $facility->managed_by !== $user->id) {
            throw new AuthorizationException('Facility is not assigned to this manager.');
        }

        if ($facility && $facility->condition !== $data->condition) {
            $facility->update(['condition' => $data->condition]);
        }

        $inspection = Inspection::create($data->toArray());

        $this->recordAuditLogAction->execute(new AuditLogData(
            actor_id: $this->resolveActorId(),
            action: 'inspection.created',
            auditable_type: $inspection->getMorphClass(),
            auditable_id: $inspection->id,
            before: null,
            after: $inspection->getAttributes(),
        ));

        $this->operationalNotificationService->inspectionCreated($inspection);

        if ($data->condition !== 'Good' && $data->maintenance_request_type_id) {
            $maintenanceData = new MaintenanceRequestData(
                facility_id: $data->facility_id,
                request_type_id: $data->maintenance_request_type_id,
                description: "Generated from Inspection #{$inspection->id}: ".($data->comments ?? 'Issue found'),
                status: 'pending',
                cost: null,
                requested_by: $data->added_by,
            );
            $this->createMaintenanceRequestAction->execute($maintenanceData);
        }

        return $inspection;
    }
}
