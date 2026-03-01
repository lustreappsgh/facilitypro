import { dashboard } from '@/routes';
import { index as facilitiesIndex, my as facilitiesMy } from '@/routes/facilities';
import { index as facilityTypesIndex } from '@/routes/facility-types';
import inspections, { my as inspectionsMy } from '@/routes/inspections';
import { index as reportsIndex } from '@/routes/reports';
import { index as auditLogsIndex } from '@/routes/audit-logs';
import { index as todosIndex } from '@/routes/todos';
import maintenance from '@/routes/maintenance/index';
import { edit as profileEdit } from '@/routes/profile';
import { usePermissions } from '@/composables/usePermissions';
import type { NavItem } from '@/types';
import {
    BarChart3,
    Building2,
    CheckSquare,
    ClipboardSignature,
    FileSearch,
    LayoutGrid,
    Settings,
    Wrench,
} from 'lucide-vue-next';
import { computed } from 'vue';

type NavItemDefinition = NavItem & {
    permission?: string;
    permissions?: string[];
};

export function useMainNavigation() {
    const { can, canAny } = usePermissions();
    const useManagerScopedViews = !canAny(['users.manage', 'maintenance.manage_all']);

    const navItemDefinitions: NavItemDefinition[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
            permissions: [
                'inspections.create',
                'maintenance.start',
                'work_orders.create',
                'payments.approve',
                'audit.view',
            ],
        },
        {
            title: 'Facilities',
            href: useManagerScopedViews ? facilitiesMy() : facilitiesIndex(),
            icon: Building2,
            permission: 'facilities.view',
        },
        {
            title: 'Facility Types',
            href: facilityTypesIndex(),
            icon: Building2,
            permission: 'facility_types.manage',
        },
        {
            title: 'Inspections',
            href: useManagerScopedViews ? inspectionsMy() : inspections.index(),
            icon: ClipboardSignature,
            permission: 'inspections.view',
        },
        {
            title: 'Todos',
            href: todosIndex(),
            icon: CheckSquare,
            permission: 'todos.view',
        },
        {
            title: 'Maintenance Requests',
            href: useManagerScopedViews ? maintenance.my() : maintenance.index(),
            icon: Wrench,
            permissions: [
                'maintenance.view',
                'maintenance_requests.view',
                'maintenance_requests.create',
            ],
        },

        {
            title: 'Reports',
            href: reportsIndex(),
            icon: BarChart3,
            permission: 'reports.view',
        },
        {
            title: 'Audit Logs',
            href: auditLogsIndex(),
            icon: FileSearch,
            permission: 'audit.view',
        },
        {
            title: 'Settings',
            href: profileEdit(),
            icon: Settings,
            permission: 'settings.view',
        },
    ];

    const mainNavItems = computed(() => {
        return navItemDefinitions
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

    return {
        mainNavItems,
    };
}
