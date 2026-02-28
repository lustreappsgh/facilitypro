<script setup lang="ts">
import DataTable from '@/components/data-table/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import MaintenanceQueue from '@/components/MaintenanceQueue.vue';
import { dashboard as maintenanceDashboardIndex, index as maintenanceIndex, show as maintenanceShow } from '@/routes/maintenance';
import { create as workOrderCreate, index as workOrdersIndex, show as workOrderShow } from '@/routes/work-orders';
import { index as paymentsIndex, show as paymentShow } from '@/routes/payments';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';
import type { ColumnDef } from '@tanstack/vue-table';
import { h } from 'vue';

interface QueueMaintenanceRequest {
    id: number;
    facility?: string | null;
    request_type?: string | null;
    description?: string | null;
    cost?: number | null;
    created_at?: string | null;
}

interface QueueWorkOrder {
    id: number;
    facility?: string | null;
    vendor?: string | null;
    scheduled_date?: string | null;
    estimated_cost?: number | null;
    status?: string | null;
}

interface QueuePayment {
    id: number;
    maintenance_request_id?: number | null;
    facility?: string | null;
    cost?: number | null;
    amount_payed?: number | null;
    status?: string | null;
}

interface Props {
    metrics: {
        open_requests: number;
        work_orders_in_flight: number;
        pending_payments: number;
    };
    queues: {
        pendingRequests: QueueMaintenanceRequest[];
        overdueWorkOrders: QueueWorkOrder[];
        pendingPayments: QueuePayment[];
    };
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Maintenance dashboard',
        href: maintenanceDashboardIndex().url,
    },
];

const { can } = usePermissions();

const numberFormat = new Intl.NumberFormat();
const currencyFormat = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0,
});

const overdueWorkOrderColumns: ColumnDef<QueueWorkOrder>[] = [
    {
        id: 'facility',
        accessorFn: (row) => row.facility ?? '',
        header: 'Facility',
        cell: ({ row }) => row.original.facility ?? '-',
    },
    {
        id: 'vendor',
        accessorFn: (row) => row.vendor ?? '',
        header: 'Vendor',
        cell: ({ row }) => row.original.vendor ?? '-',
    },
    {
        id: 'scheduledDate',
        accessorFn: (row) => row.scheduled_date ?? '',
        header: 'Scheduled',
        cell: ({ row }) => row.original.scheduled_date ?? '-',
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) =>
            h(Button, { variant: 'outline', size: 'sm', asChild: true }, () =>
                h(Link, { href: workOrderShow(row.original.id).url }, () => 'View'),
            ),
        enableSorting: false,
        enableHiding: false,
    },
];

const pendingPaymentColumns: ColumnDef<QueuePayment>[] = [
    {
        id: 'facility',
        accessorFn: (row) => row.facility ?? '',
        header: 'Facility',
        cell: ({ row }) => row.original.facility ?? '-',
    },
    {
        id: 'amount',
        accessorFn: (row) => row.cost ?? 0,
        header: 'Amount',
        cell: ({ row }) =>
            row.original.cost !== null && row.original.cost !== undefined
                ? currencyFormat.format(row.original.cost)
                : '-',
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) =>
            h(Button, { variant: 'outline', size: 'sm', asChild: true }, () =>
                h(Link, { href: paymentShow(row.original).url }, () => 'View'),
            ),
        enableSorting: false,
        enableHiding: false,
    },
];
</script>

<template>
    <Head title="Maintenance Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader
                title="Maintenance dashboard"
                subtitle="Monitor maintenance activity and priorities."
            >
                <template #actions>
                    <div class="flex flex-wrap gap-2">
                        <Button v-if="can('work_orders.create')" as-child>
                            <Link :href="workOrderCreate().url">
                                Create work order
                            </Link>
                        </Button>
                        <Button variant="secondary" as-child>
                            <Link :href="paymentsIndex().url">View payments</Link>
                        </Button>
                        <Button variant="outline" as-child>
                            <Link :href="maintenanceIndex().url">
                                View maintenance requests
                            </Link>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardDescription>Open maintenance requests</CardDescription>
                        <CardTitle class="text-3xl font-semibold">
                            {{ numberFormat.format(metrics.open_requests) }}
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader>
                        <CardDescription>Work orders in flight</CardDescription>
                        <CardTitle class="text-3xl font-semibold">
                            {{ numberFormat.format(metrics.work_orders_in_flight) }}
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader>
                        <CardDescription>Pending payments</CardDescription>
                        <CardTitle class="text-3xl font-semibold">
                            {{ numberFormat.format(metrics.pending_payments) }}
                        </CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>My tasks</CardTitle>
                        <CardDescription>
                            Top requests and overdue work orders needing action.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold">
                                    Pending maintenance requests
                                </h3>
                                <Button variant="link" class="px-0" as-child>
                                    <Link :href="maintenanceIndex().url">
                                        View all
                                    </Link>
                                </Button>
                            </div>
                            <MaintenanceQueue
                                :requests="queues.pendingRequests"
                                :show-route="maintenanceShow"
                            />
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold">
                                    Overdue work orders
                                </h3>
                                <Button variant="link" class="px-0" as-child>
                                    <Link :href="workOrdersIndex().url">
                                        View all
                                    </Link>
                                </Button>
                            </div>
                            <DataTable
                                :data="queues.overdueWorkOrders"
                                :columns="overdueWorkOrderColumns"
                                :show-search="false"
                                :enable-column-toggle="false"
                                :show-selection-summary="false"

                            />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Pending payments</CardTitle>
                        <CardDescription>
                            Payments waiting on completion or approvals.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <DataTable
                            :data="queues.pendingPayments"
                            :columns="pendingPaymentColumns"
                            :show-search="false"
                            :enable-column-toggle="false"
                            :show-selection-summary="false"

                        />
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
