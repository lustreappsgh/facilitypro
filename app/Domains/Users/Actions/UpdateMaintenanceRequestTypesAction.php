<?php

namespace App\Domains\Users\Actions;

use App\Domains\Users\DTOs\MaintenanceRequestTypesData;
use App\Models\User;

class UpdateMaintenanceRequestTypesAction
{
    public function execute(User $user, MaintenanceRequestTypesData $data): void
    {
        $user->maintenanceRequestTypes()->sync($data->request_type_ids);
    }
}
