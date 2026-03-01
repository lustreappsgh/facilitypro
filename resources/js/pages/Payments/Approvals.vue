<script setup lang="ts">
import ApprovalQueue from '@/components/ApprovalQueue.vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as paymentApprovalsIndex } from '@/routes/payment-approvals/index';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

interface Facility {
    id: number;
    name: string;
}

interface RequestType {
    id: number;
    name: string;
}

interface MaintenanceRequest {
    id: number;
    facility?: Facility | null;
    requestType?: RequestType | null;
}

interface Payment {
    id: number;
    cost?: number | null;
    status?: string | null;
    created_at?: string | null;
    maintenanceRequest?: MaintenanceRequest | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedPayments {
    data: Payment[];
    links: PaginationLink[];
}

interface Filters {
    search?: string | null;
    status?: string | null;
    facility?: string | null;
    start_date?: string | null;
    end_date?: string | null;
    min_amount?: string | null;
    max_amount?: string | null;
}

interface Props {
    data: {
        payments: PaginatedPayments;
    };
    filters: Filters;
    facilities: Facility[];
    permissions: string[];
    routes: Record<string, string>;
    meta: Record<string, string>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Payment approvals',
        href: paymentApprovalsIndex().url,
    },
];
</script>

<template>
    <Head title="Payment approvals" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader title="Payment approvals" subtitle="Approve or reject pending payments." />

            <ApprovalQueue
                :payments="props.data.payments"
                :filters="props.filters"
                :facilities="props.facilities"
            />

            <PaginationLinks :links="props.data.payments.links" />
        </div>
    </AppLayout>
</template>
