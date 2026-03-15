<script setup lang="ts">
import DataTable from '@/components/data-table/DataTable.vue';
import MaintenanceQueue from '@/components/MaintenanceQueue.vue';
import PageHeader from '@/components/PageHeader.vue';
import TableTotalsBar from '@/components/TableTotalsBar.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { createCurrencyFormatter } from '@/lib/currency';
import { createNumberFormatter } from '@/lib/locale';
import { sumByNumber } from '@/lib/totals';
import {
    dashboard as maintenanceDashboardIndex,
    index as maintenanceIndex,
    show as maintenanceShow,
} from '@/routes/maintenance';
import { show as paymentShow, index as paymentsIndex } from '@/routes/payments';
import {
    create as workOrderCreate,
    show as workOrderShow,
    index as workOrdersIndex,
} from '@/routes/work-orders';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { ClipboardList, CreditCard, Eye, Plus } from 'lucide-vue-next';
import { h } from 'vue';

interface QueueMaintenanceRequest {
    id: number;
    facility?: string | null;
    request_type?: string | null;
    description?: string | null;
    priority?: 'low' | 'medium' | 'high' | null;
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
        own_request_total?: number;
        own_request_pending?: number;
        own_request_rejected?: number;
    };
    queues: {
        pendingRequests: QueueMaintenanceRequest[];
        overdueWorkOrders: QueueWorkOrder[];
        pendingPayments: QueuePayment[];
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Maintenance dashboard',
        href: maintenanceDashboardIndex().url,
    },
];

const { can } = usePermissions();

const numberFormat = createNumberFormatter();
const currencyFormat = createCurrencyFormatter();

const pendingPaymentTotal = () =>
    sumByNumber(props.queues.pendingPayments, (payment) => payment.cost);
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
            h(
                Button,
                {
                    variant: 'ghost',
                    size: 'icon',
                    class: 'h-8 w-8',
                    asChild: true,
                },
                () =>
                    h(
                        Link,
                        {
                            href: workOrderShow(row.original.id).url,
                            'aria-label': 'View work order',
                        },
                        () => h(Eye, { class: 'h-4 w-4' }),
                    ),
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
            h(
                Button,
                {
                    variant: 'ghost',
                    size: 'icon',
                    class: 'h-8 w-8',
                    asChild: true,
                },
                () =>
                    h(
                        Link,
                        {
                            href: paymentShow(row.original).url,
                            'aria-label': 'View payment',
                        },
                        () => h(Eye, { class: 'h-4 w-4' }),
                    ),
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
                    <div class="flex flex-wrap items-center gap-2">
                        <Button
                            v-if="can('work_orders.create')"
                            size="icon"
                            class="h-9 w-9"
                            as-child
                        >
                            <Link
                                :href="workOrderCreate().url"
                                aria-label="Create work order"
                            >
                                <Plus class="h-4 w-4" />
                            </Link>
                        </Button>
                        <Button
                            variant="secondary"
                            size="icon"
                            class="h-9 w-9"
                            as-child
                        >
                            <Link
                                :href="paymentsIndex().url"
                                aria-label="View payments"
                            >
                                <CreditCard class="h-4 w-4" />
                            </Link>
                        </Button>
                        <Button
                            variant="outline"
                            size="icon"
                            class="h-9 w-9"
                            as-child
                        >
                            <Link
                                :href="maintenanceIndex().url"
                                aria-label="View maintenance requests"
                            >
                                <ClipboardList class="h-4 w-4" />
                            </Link>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
                <Card v-if="metrics.own_request_total !== undefined">
                    <CardHeader>
                        <CardDescription>My requests</CardDescription>
                        <CardTitle class="text-3xl font-semibold">
                            {{ numberFormat.format(metrics.own_request_total) }}
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card v-if="metrics.own_request_pending !== undefined">
                    <CardHeader>
                        <CardDescription>My pending</CardDescription>
                        <CardTitle class="text-3xl font-semibold">
                            {{
                                numberFormat.format(
                                    metrics.own_request_pending,
                                )
                            }}
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card v-if="metrics.own_request_rejected !== undefined">
                    <CardHeader>
                        <CardDescription>My rejected</CardDescription>
                        <CardTitle class="text-3xl font-semibold">
                            {{
                                numberFormat.format(
                                    metrics.own_request_rejected,
                                )
                            }}
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader>
                        <CardDescription
                            >Open maintenance requests</CardDescription
                        >
                        <CardTitle class="text-3xl font-semibold">
                            {{ numberFormat.format(metrics.open_requests) }}
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader>
                        <CardDescription>Work orders in flight</CardDescription>
                        <CardTitle class="text-3xl font-semibold">
                            {{
                                numberFormat.format(
                                    metrics.work_orders_in_flight,
                                )
                            }}
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
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8"
                                    as-child
                                >
                                    <Link
                                        :href="maintenanceIndex().url"
                                        aria-label="View all maintenance requests"
                                    >
                                        <Eye class="h-4 w-4" />
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
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8"
                                    as-child
                                >
                                    <Link
                                        :href="workOrdersIndex().url"
                                        aria-label="View all work orders"
                                    >
                                        <Eye class="h-4 w-4" />
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
                        >
                            <template #footer>
                                <TableTotalsBar
                                    :items="[
                                        {
                                            label: 'Amount',
                                            value: currencyFormat.format(
                                                pendingPaymentTotal(),
                                            ),
                                        },
                                    ]"
                                />
                            </template>
                        </DataTable>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
