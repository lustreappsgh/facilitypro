<?php

namespace App\Domains\Users\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateManagerRequestTypePermissionsAction
{
    public function execute(User $user, array $requestTypes): void
    {
        $rows = collect($requestTypes)
            ->mapWithKeys(fn (array $item) => [
                (int) $item['request_type_id'] => [
                    'can_approve' => (bool) ($item['can_approve'] ?? false),
                    'can_reject' => (bool) ($item['can_reject'] ?? false),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ])
            ->all();

        DB::transaction(function () use ($user, $rows): void {
            $user->managerRequestTypePermissions()->sync($rows);
        });
    }
}
