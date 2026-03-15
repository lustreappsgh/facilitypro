import { usePermissions } from '@/composables/usePermissions';
import type { NavItem } from '@/types';
import { ClipboardList, CreditCard, Users, Wrench } from 'lucide-vue-next';
import { computed } from 'vue';

type NavItemDefinition = NavItem & {
    permission?: string;
    permissions?: string[];
};

export function useMaintenanceNavigation() {
    const { can, canAny } = usePermissions();

    const maintenanceItemDefinitions: NavItemDefinition[] = [
        {
            title: 'Work Orders',
            href: '/work-orders',
            icon: ClipboardList,
            permission: 'work_orders.view',
        },
        {
            title: 'Vendors',
            href: '/vendors',
            icon: Users,
            permission: 'vendors.view',
        },
        {
            title: 'Request Types',
            href: '/request-types',
            icon: Wrench,
            permission: 'request_types.manage',
        },
        {
            title: 'Payments',
            href: '/payments',
            icon: CreditCard,
            permission: 'payments.view',
        },
        {
            title: 'Approval Queue',
            href: '/payment-approvals',
            icon: CreditCard,
            permission: 'payments.view',
        },
    ];

    const maintenanceNavItems = computed(() => {
        return maintenanceItemDefinitions
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
        maintenanceNavItems,
    };
}
