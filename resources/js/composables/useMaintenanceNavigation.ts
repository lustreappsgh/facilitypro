import { usePermissions } from '@/composables/usePermissions';
import { index as paymentApprovalsIndex } from '@/routes/payment-approvals';
import { index as paymentsIndex } from '@/routes/payments';
import { index as requestTypesIndex } from '@/routes/request-types';
import { index as vendorsIndex } from '@/routes/vendors';
import { index as workOrdersIndex } from '@/routes/work-orders';
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
            href: workOrdersIndex(),
            icon: ClipboardList,
            permission: 'work_orders.view',
        },
        {
            title: 'Vendors',
            href: vendorsIndex(),
            icon: Users,
            permission: 'vendors.view',
        },
        {
            title: 'Request Types',
            href: requestTypesIndex(),
            icon: Wrench,
            permission: 'request_types.manage',
        },
        {
            title: 'Payments',
            href: paymentsIndex(),
            icon: CreditCard,
            permission: 'payments.view',
        },
        {
            title: 'Approval Queue',
            href: paymentApprovalsIndex(),
            icon: CreditCard,
            permissions: ['payments.approve', 'payments.reject'],
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
