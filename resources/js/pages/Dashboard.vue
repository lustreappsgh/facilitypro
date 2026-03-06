<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import AdminDashboard from '@/components/Dashboard/AdminDashboard.vue';
import ManagerDashboard from '@/components/Dashboard/ManagerDashboard.vue';
import MaintenanceManagerDashboard from '@/components/Dashboard/MaintenanceManagerDashboard.vue';
import FacilityManagerDashboard from '@/components/Dashboard/FacilityManagerDashboard.vue';
import { HeadsetIcon } from 'lucide-vue-next';

interface FacilityManagerData {
    inspectionsSubmitted: number;
    openMaintenanceRequests: number;
    facilitiesManaged: number;
    inspectionsThisWeek?: number;
    todosThisWeek?: number;
    pendingTodos?: number;
    pendingRequests?: number;
}

interface MaintenanceManagerData {
    openRequests: number;
    pendingRequests?: number;
    requestsThisWeek?: number;
    workOrdersInFlight: number;
    workOrdersThisWeek?: number;
    pendingPayments: number;
    users?: {
        id: number;
        name: string;
        email: string;
        profile_photo_url: string;
        is_active: boolean;
        facilities_managed: number;
        inspections_last_week: number;
        upcoming_todos: number;
        requests_submitted: number;
        roles: string[];
    }[];
}

interface ManagerData {
    pendingApprovals: number;
    pendingApprovalCost: number;
    oldestPendingDate?: string | null;
    oldestPendingDays?: number | null;
    highCostThreshold?: number | null;
    highCostPendingCount?: number | null;
    highCostPendingTotal?: number | null;
    facilityManagers: {
        id: number;
        name: string;
        email: string;
        profile_photo_url: string;
        is_active: boolean;
        facilities_managed: number;
    }[];
    users: {
        id: number;
        name: string;
        email: string;
        profile_photo_url: string;
        is_active: boolean;
        facilities_managed: number;
        inspections_last_week: number;
        upcoming_todos: number;
        requests_submitted: number;
        roles: string[];
    }[];
    todoSummary: {
        pending: number;
        overdue: number;
        total: number;
    };
    inspectionSummary: {
        total: number;
        last_7_days: number;
        latest_date?: string | null;
    };
}

interface AdminData {
    totals: {
        facilities: number;
        facilityTypes: number;
        requestTypes: number;
        users: number;
        inspections: number;
        maintenanceRequests: number;
        vendors: number;
        workOrders: number;
        payments: number;
    };
    pending: {
        maintenanceRequests: number;
        workOrders: number;
        payments: number;
        todos: number;
    };
    health: {
        inactiveUsers: number;
        overdueWorkOrders: number;
        staleMaintenanceRequests: number;
        staleThresholdDays: number;
    };
        users: {
            id: number;
            name: string;
            email: string;
            profile_photo_url: string;
            is_active: boolean;
            facilities_managed: number;
            inspections_last_week: number;
            upcoming_todos: number;
            requests_submitted: number;
            roles: string[];
        }[];
    facilityManagers: {
        id: number;
        name: string;
        email: string;
        profile_photo_url: string;
        is_active: boolean;
        facilities_managed: number;
    }[];
}

interface Props {
    data: {
        facilityManager?: FacilityManagerData;
        maintenanceManager?: MaintenanceManagerData;
        manager?: ManagerData;
        admin?: AdminData;
    };
    permissions: string[];
    routes: Record<string, string>;
    meta: Record<string, string>;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-8 p-6 lg:p-8">
            <PageHeader title="Dashboard" subtitle="Overview of your assigned work and metrics." />

            <!-- Role-Specific Dashboards -->
            <div class="space-y-12">
                <AdminDashboard v-if="data.admin" :data="data.admin" />

                <ManagerDashboard v-if="data.manager" :data="data.manager" :permissions="permissions" />

                <MaintenanceManagerDashboard v-if="data.maintenanceManager" :data="data.maintenanceManager" />

                <FacilityManagerDashboard v-if="data.facilityManager" :data="data.facilityManager" />
            </div>

            <!-- Empty State if no role data found -->
            <div v-if="!data.admin && !data.manager && !data.maintenanceManager && !data.facilityManager"
                class="flex flex-col items-center justify-center py-20 text-center">
                <div class="rounded-full bg-muted p-6 mb-4">
                    <HeadsetIcon class="h-10 w-10 text-muted-foreground" />
                </div>
                <h2 class="text-xl font-bold font-display">No Dashboard Access</h2>
                <p class="text-muted-foreground mt-2 max-w-xs">
                    Your account does not have any active manager roles assigned. Please contact the administrator for
                    access.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
