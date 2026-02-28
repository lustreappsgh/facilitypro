<?php

namespace App\Domains\Maintenance\Services;

use App\Domains\Maintenance\Actions\CompleteMaintenanceAction;
use App\Domains\Maintenance\Actions\CloseMaintenanceAction;
use App\Domains\Maintenance\Actions\CreateMaintenanceRequestAction;
use App\Domains\Maintenance\Actions\RejectMaintenanceAction;
use App\Domains\Maintenance\Actions\ReviewMaintenanceAction;
use App\Domains\Maintenance\Actions\StartMaintenanceAction;
use App\Domains\Maintenance\Actions\UpdateMaintenanceRequestAction;
use App\Domains\Maintenance\DTOs\MaintenanceRequestData;
use App\Models\MaintenanceRequest;

class MaintenanceService
{
    public function __construct(
        protected CreateMaintenanceRequestAction $createAction,
        protected UpdateMaintenanceRequestAction $updateAction,
        protected ReviewMaintenanceAction $reviewAction,
        protected RejectMaintenanceAction $rejectAction,
        protected StartMaintenanceAction $startAction,
        protected CompleteMaintenanceAction $completeAction,
        protected CloseMaintenanceAction $closeAction
    ) {}

    public function create(MaintenanceRequestData $data): MaintenanceRequest
    {
        return $this->createAction->execute($data);
    }

    public function update(MaintenanceRequest $request, MaintenanceRequestData $data): MaintenanceRequest
    {
        return $this->updateAction->execute($request, $data);
    }

    public function review(MaintenanceRequest $request): MaintenanceRequest
    {
        return $this->reviewAction->execute($request);
    }

    public function reject(MaintenanceRequest $request): MaintenanceRequest
    {
        return $this->rejectAction->execute($request);
    }

    public function start(MaintenanceRequest $request): MaintenanceRequest
    {
        return $this->startAction->execute($request);
    }

    public function complete(MaintenanceRequest $request): MaintenanceRequest
    {
        return $this->completeAction->execute($request);
    }

    public function close(MaintenanceRequest $request): MaintenanceRequest
    {
        return $this->closeAction->execute($request);
    }
}
