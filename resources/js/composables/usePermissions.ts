import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const page = usePage();

    const permissions = computed(() => {
        return page.props.auth?.permissions || [];
    });

    const can = (permission: string): boolean => {
        return permissions.value.includes(permission);
    };

    const canAny = (permissionsToCheck: string[]): boolean => {
        return permissionsToCheck.some((permission) => can(permission));
    };

    const canAll = (permissionsToCheck: string[]): boolean => {
        return permissionsToCheck.every((permission) => can(permission));
    };

    return {
        permissions,
        can,
        canAny,
        canAll,
    };
}
