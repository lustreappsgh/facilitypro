import { usePermissions } from '@/composables/usePermissions';
import { index as permissionsIndex } from '@/routes/permissions';
import { index as rolesIndex } from '@/routes/roles';
import { index as usersIndex } from '@/routes/users';
import type { NavItem } from '@/types';
import {
    ShieldCheck,
    ShieldPlus,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';

type NavItemDefinition = NavItem & {
    permission?: string;
    permissions?: string[];
};

export function useAdminNavigation() {
    const { can, canAny } = usePermissions();

    const adminItemDefinitions: NavItemDefinition[] = [
        {
            title: 'Users',
            href: usersIndex(),
            icon: Users,
            permissions: ['users.view', 'users.manage'],
        },
        {
            title: 'Roles',
            href: rolesIndex(),
            icon: ShieldPlus,
            permission: 'roles.manage',
        },
        {
            title: 'Permissions',
            href: permissionsIndex(),
            icon: ShieldCheck,
            permission: 'permissions.view',
        },
    ];

    const adminNavItems = computed(() => {
        return adminItemDefinitions
            .filter((item) => {
                if (item.permission) {
                    return can(item.permission);
                }

                if (item.permissions) {
                    return canAny(item.permissions);
                }

                return true;
            })
            .map((item) => {
                const { permission, permissions, ...rest } = item;
                void permission;
                void permissions;
                return rest;
            });
    });

    return { adminNavItems };
}
