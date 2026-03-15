<?php

namespace App\Domains\Roles\Actions;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UpdateRoleRequestTypesAction
{
    /**
     * @param  array<int, array{request_type_id:int,can_approve?:bool,can_reject?:bool}>  $requestTypePermissions
     */
    public function execute(Role $role, array $requestTypePermissions): void
    {
        $rows = collect($requestTypePermissions)
            ->mapWithKeys(fn (array $item) => [
                (int) $item['request_type_id'] => [
                    'can_approve' => (bool) ($item['can_approve'] ?? false),
                    'can_reject' => (bool) ($item['can_reject'] ?? false),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ])
            ->all();

        DB::transaction(function () use ($role, $rows): void {
            DB::table('maintenance_request_type_role')->where('role_id', $role->id)->delete();

            if ($rows === []) {
                return;
            }

            $payload = collect($rows)
                ->map(fn (array $row, int $requestTypeId) => [
                    'role_id' => $role->id,
                    'request_type_id' => $requestTypeId,
                    ...$row,
                ])
                ->values()
                ->all();

            DB::table('maintenance_request_type_role')->insert($payload);
        });
    }
}
