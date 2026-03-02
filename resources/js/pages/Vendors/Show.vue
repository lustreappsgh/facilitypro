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
import { index as vendorsIndex } from '@/routes/vendors';
import { create as workOrderCreate, show as workOrderShow } from '@/routes/work-orders';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';
import type { ColumnDef } from '@tanstack/vue-table';
import { ArrowLeft, ClipboardList, Eye } from 'lucide-vue-next';
import { h } from 'vue';

interface MaintenanceRequest {
    id: number;
}

interface WorkOrderSummary {
    id: number;
    status?: string | null;
    scheduled_date?: string | null;
    maintenanceRequest?: MaintenanceRequest | null;
}

interface Vendor {
    id: number;
    name: string;
    email?: string | null;
    phone?: string | null;
    service_type?: string | null;
    status?: string | null;
    notes?: string | null;
}

interface Props {
    vendor: Vendor;
    recentWorkOrders: WorkOrderSummary[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Vendors',
        href: vendorsIndex().url,
    },
    {
        title: props.vendor.name,
        href: '#',
    },
];

const { can } = usePermissions();

const statusBadgeClass = (status: string) => {
    if (status === 'active') {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (status === 'inactive') {
        return 'bg-slate-500/10 text-slate-700 dark:text-slate-300';
    }

    return 'bg-muted text-muted-foreground';
};

const recentWorkOrderColumns: ColumnDef<WorkOrderSummary>[] = [
    {
        id: 'order',
        accessorFn: (row) => row.id,
        header: 'Work order',
        cell: ({ row }) => h('span', { class: 'font-medium' }, `#${row.original.id}`),
    },
    {
        id: 'status',
        accessorFn: (row) => row.status ?? '',
        header: 'Status',
        cell: ({ row }) => row.original.status ?? '-',
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
            h(Button, { variant: 'ghost', size: 'icon', class: 'h-8 w-8', asChild: true }, () =>
                h(Link, { href: workOrderShow(row.original.id).url, 'aria-label': 'View work order' }, () =>
                    h(Eye, { class: 'h-4 w-4' }),
                ),
            ),
        enableSorting: false,
        enableHiding: false,
    },
];
</script>

<template>
    <Head title="Vendor profile" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader :title="vendor.name" subtitle="Vendor profile and assigned work.">
                <template #actions>
                    <div class="flex flex-wrap gap-2">
                        <Button variant="outline" size="icon" class="h-9 w-9" as-child>
                            <Link :href="vendorsIndex().url" aria-label="Back to list">
                                <ArrowLeft class="h-4 w-4" />
                            </Link>
                        </Button>
                        <Button v-if="can('work_orders.create')" size="icon" class="h-9 w-9" as-child>
                            <Link
                                :href="
                                    workOrderCreate({
                                        query: { vendor_id: vendor.id },
                                    }).url
                                "
                                aria-label="Create work order with vendor"
                            >
                                <ClipboardList class="h-4 w-4" />
                            </Link>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-[1.1fr_1fr]">
                <Card>
                    <CardHeader>
                        <CardTitle>Contact & service</CardTitle>
                        <CardDescription>
                            Primary details for assignment decisions.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-xs uppercase text-muted-foreground">
                                Email
                            </p>
                            <p class="text-sm font-medium">
                                {{ vendor.email ?? '-' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-muted-foreground">
                                Phone
                            </p>
                            <p class="text-sm font-medium">
                                {{ vendor.phone ?? '-' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-muted-foreground">
                                Service type
                            </p>
                            <p class="text-sm font-medium">
                                {{ vendor.service_type ?? '-' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-muted-foreground">
                                Status
                            </p>
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="statusBadgeClass(vendor.status ?? '')"
                            >
                                {{ vendor.status ?? 'unknown' }}
                            </span>
                        </div>
                        <div v-if="vendor.notes">
                            <p class="text-xs uppercase text-muted-foreground">
                                Notes
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ vendor.notes }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent work orders</CardTitle>
                        <CardDescription>
                            Last five assignments for this vendor.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <DataTable
                            :data="recentWorkOrders"
                            :columns="recentWorkOrderColumns"
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
