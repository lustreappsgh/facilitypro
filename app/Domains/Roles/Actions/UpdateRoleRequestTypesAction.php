<?php

namespace App\Domains\Roles\Actions;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UpdateRoleRequestTypesAction
{
    /**
     * @param  array<int, int>  $requestTypeIds
     */
    public function execute(Role $role, array $requestTypeIds): void
    {
        $uniqueIds = array_values(array_unique($requestTypeIds));

        DB::transaction(function () use ($role, $uniqueIds): void {
            DB::table('maintenance_request_type_role')
                ->where('role_id', $role->id)
                ->delete();

            if ($uniqueIds === []) {
                return;
            }

            $now = now();
            $rows = array_map(
                fn (int $requestTypeId) => [
                    'role_id' => $role->id,
                    'request_type_id' => $requestTypeId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $uniqueIds
            );

            DB::table('maintenance_request_type_role')->insert($rows);
        });
    }
}
