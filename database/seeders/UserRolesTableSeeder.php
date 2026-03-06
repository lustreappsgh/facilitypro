<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class UserRolesTableSeeder extends Seeder
{
    /**
     * Seed the application's user roles from the current snapshot.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $assignments = json_decode(
            file_get_contents(database_path('seeders/data/model_has_roles.json')),
            true
        );

        if (! is_array($assignments)) {
            return;
        }

        $rolesById = collect(json_decode(
            file_get_contents(database_path('seeders/data/roles.json')),
            true
        ))->keyBy('id');

        User::query()->each(function (User $user) use ($assignments, $rolesById): void {
            $roleNames = collect($assignments)
                ->filter(fn (array $row): bool => (string) $row['model_type'] === User::class)
                ->filter(fn (array $row): bool => (int) $row['model_id'] === $user->id)
                ->map(fn (array $row): ?string => $rolesById->get((int) $row['role_id'])['name'] ?? null)
                ->filter()
                ->values()
                ->all();

            $user->syncRoles($roleNames);
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
