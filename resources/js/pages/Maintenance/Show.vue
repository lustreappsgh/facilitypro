<script setup lang="ts">
import DataTable from '@/components/data-table/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit, index as maintenanceIndex } from '@/routes/maintenance';
import { create as workOrderCreate, show as workOrderShow } from '@/routes/work-orders';
import { index as vendorsIndex } from '@/routes/vendors';
import { show as paymentShow } from '@/routes/payments';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';
import type { ColumnDef } from '@tanstack/vue-table';
import { h } from 'vue';

interface FacilityType {
    id: number;
    name: string;
}

interface FacilityManager {
    id: number;
    name: string;
}

interface Facility {
    id: number;
    name: string;
    condition?: string | null;
    facilityType?: FacilityType | null;
    manager?: FacilityManager | null;
}

interface RequestedBy {
    id: number;
    name: string;
}

interface RequestType {
    id: number;
    name: string;
}

interface MaintenanceRequest {
    id: number;
    status: string;
    cost?: number | null;
    description?: string | null;
    facility?: Facility | null;
    requestedBy?: RequestedBy | null;
    requested_by?: RequestedBy | null;
    requestType?: RequestType | null;
    request_type?: RequestType | null;
}

interface VendorSummary {
    id: number;
    name: string;
}

interface WorkOrder {
    id: number;
    status?: string | null;
    scheduled_date?: string | null;
    estimated_cost?: number | null;
    vendor?: VendorSummary | null;
}

interface Payment {
    id: number;
    status?: string | null;
    cost?: number | null;
    amount_payed?: number | null;
}

interface Props {
    request: MaintenanceRequest;
    workOrders: WorkOrder[];
    payments: Payment[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Maintenance Requests',
        href: maintenanceIndex().url,
    },
    {
        title: 'Details',
        href: '#',
    },
];

const { can } = usePermissions();

const currencyFormat = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
});

const statusSteps = [
    { value: 'pending', label: 'Pending' },
    { value: 'in_progress', label: 'In progress' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
];

const statusBadgeClass = (status: string) => {
    if (status === 'completed') {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (status === 'in_progress') {
        return 'bg-sky-500/10 text-sky-700 dark:text-sky-300';
    }

    if (status === 'cancelled') {
        return 'bg-rose-500/10 text-rose-700 dark:text-rose-300';
    }

    return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
};

const workOrderColumns: ColumnDef<WorkOrder>[] = [
    {
        id: 'vendor',
        accessorFn: (row) => row.vendor?.name ?? '',
        header: 'Vendor',
        cell: ({ row }) => row.original.vendor?.name ?? '-',
    },
    {
        id: 'scheduledDate',
        accessorFn: (row) => row.scheduled_date ?? '',
        header: 'Scheduled',
        cell: ({ row }) => row.original.scheduled_date ?? '-',
    },
    {
        id: 'cost',
        accessorFn: (row) => row.estimated_cost ?? 0,
        header: 'Cost',
        cell: ({ row }) =>
            row.original.estimated_cost !== null &&
            row.original.estimated_cost !== undefined
                ? currencyFormat.format(row.original.estimated_cost)
                : '-',
    },
    {
        id: 'status',
        accessorFn: (row) => row.status ?? '',
        header: 'Status',
        cell: ({ row }) =>
            h(
                Badge,
                { variant: 'secondary', class: statusBadgeClass(row.original.status ?? '') },
                () => row.original.status ? row.original.status.replace('_', ' ') : 'unknown',
            ),
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

const paymentColumns: ColumnDef<Payment>[] = [
    {
        id: 'cost',
        accessorFn: (row) => row.cost ?? 0,
        header: 'Cost',
        cell: ({ row }) =>
            row.original.cost !== null && row.original.cost !== undefined
                ? currencyFormat.format(row.original.cost)
                : '-',
    },
    {
        id: 'amountPaid',
        accessorFn: (row) => row.amount_payed ?? 0,
        header: 'Amount paid',
        cell: ({ row }) =>
            row.original.amount_payed !== null && row.original.amount_payed !== undefined
                ? currencyFormat.format(row.original.amount_payed)
                : '-',
    },
    {
        id: 'status',
        accessorFn: (row) => row.status ?? '',
        header: 'Status',
        cell: ({ row }) =>
            h(
                Badge,
                { variant: 'secondary', class: statusBadgeClass(row.original.status ?? '') },
                () => row.original.status ?? 'unknown',
            ),
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
    <Head title="Maintenance request" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader
                title="Maintenance request"
                subtitle="Review request context and advance maintenance work."
            >
                <template #actions>
                    <div class="flex flex-wrap gap-2">
                        <Button variant="outline" as-child>
                            <Link :href="maintenanceIndex().url">Back to list</Link>
                        </Button>
                        <Button
                            v-if="can('work_orders.create') && workOrders.length === 0"
                            as-child
                        >
                            <Link
                                :href="
                                    workOrderCreate({
                                        query: {
                                            maintenance_request_id: request.id,
                                        },
                                    }).url
                                "
                            >
                                Create work order
                            </Link>
                        </Button>
                        <Button
                            v-else-if="can('work_orders.view') && workOrders.length > 0"
                            variant="outline"
                            as-child
                        >
                            <Link :href="workOrderShow(workOrders[0].id).url">
                                View work order
                            </Link>
                        </Button>
                        <Button
                            v-if="can('maintenance.update') && request.status === 'pending'"
                            variant="secondary"
                            as-child
                        >
                            <Link :href="edit(request).url">Edit request</Link>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Facility details</CardTitle>
                        <CardDescription>
                            Location context and operational ownership.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-xs uppercase text-muted-foreground">
                                Facility
                            </p>
                            <p class="text-base font-medium">
                                {{ request.facility?.name ?? '-' }}
                            </p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs uppercase text-muted-foreground">
                                    Type
                                </p>
                                <p class="text-sm font-medium">
                                    {{ request.facility?.facilityType?.name ?? '-' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-muted-foreground">
                                    Manager
                                </p>
                                <p class="text-sm font-medium">
                                    {{ request.facility?.manager?.name ?? '-' }}
                                </p>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-muted-foreground">
                                Condition
                            </p>
                            <p class="text-sm font-medium">
                                {{ request.facility?.condition ?? '-' }}
                            </p>
                        </div>
                        <Button variant="ghost" class="px-0" as-child>
                            <Link :href="vendorsIndex().url">
                                Browse vendors
                            </Link>
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Request details</CardTitle>
                        <CardDescription>
                            Scope, cost, and status tracking.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-xs uppercase text-muted-foreground">
                                Request type
                            </p>
                            <p class="text-base font-medium">
                                {{ request.requestType?.name ?? request.request_type?.name ?? '-' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-muted-foreground">
                                Requested by
                            </p>
                            <p class="text-sm font-medium">
                                {{ request.requestedBy?.name ?? request.requested_by?.name ?? '-' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-muted-foreground">
                                Estimated cost
                            </p>
                            <p class="text-sm font-medium">
                                {{
                                    request.cost !== null &&
                                    request.cost !== undefined
                                        ? currencyFormat.format(request.cost)
                                        : '-'
                                }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-muted-foreground">
                                Description
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{
                                    request.description ??
                                    'No description provided.'
                                }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <p class="text-xs uppercase text-muted-foreground">
                                Status progress
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <Badge
                                    v-for="step in statusSteps"
                                    :key="step.value"
                                    variant="secondary"
                                    :class="
                                        request.status === step.value
                                            ? 'bg-primary/10 text-primary'
                                            : 'bg-muted text-muted-foreground'
                                    "
                                >
                                    {{ step.label }}
                                </Badge>
                            </div>
                            <Badge
                                variant="secondary"
                                :class="statusBadgeClass(request.status)"
                            >
                                Current: {{ request.status.replace('_', ' ') }}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Linked work orders</CardTitle>
                        <CardDescription>
                            Assignments created from this request.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <DataTable
                            :data="workOrders"
                            :columns="workOrderColumns"
                            :show-search="false"
                            :enable-column-toggle="false"
                            :show-selection-summary="false"

                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Linked payments</CardTitle>
                        <CardDescription>
                            Payment readiness and approvals.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <DataTable
                            :data="payments"
                            :columns="paymentColumns"
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
