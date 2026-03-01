<script setup lang="ts">
import { DatePicker } from '@/components/ui/date-picker';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import DataTable from '@/components/data-table/DataTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { create, edit, index as workOrdersIndex, show } from '@/routes/work-orders';
import { show as maintenanceShow } from '@/routes/maintenance';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';
import type { ColumnDef } from '@tanstack/vue-table';
import { Search, Plus, Layers } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';

const filtersVisible = ref(false);

interface Vendor {
    id: number;
    name: string;
}

interface Facility {
    id: number;
    name: string;
}

interface MaintenanceRequest {
    id: number;
    facility?: Facility | null;
}

interface WorkOrder {
    id: number;
    status?: string | null;
    assigned_date?: string | null;
    scheduled_date?: string | null;
    completed_date?: string | null;
    estimated_cost?: number | null;
    actual_cost?: number | null;
    vendor?: Vendor | null;
    maintenanceRequest?: MaintenanceRequest | null;
    maintenance_request?: MaintenanceRequest | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedWorkOrders {
    data: WorkOrder[];
    links: PaginationLink[];
}

interface Filters {
    search?: string | null;
    status?: string | null;
    vendor?: string | null;
    facility?: string | null;
    start_date?: string | null;
    end_date?: string | null;
}

interface Props {
    workOrders: PaginatedWorkOrders;
    vendors: Vendor[];
    facilities: Facility[];
    filters: Filters;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Work Orders',
        href: workOrdersIndex().url,
    },
];

const { can } = usePermissions();

const currencyFormat = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
});

const searchFilter = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');
const vendorFilter = ref(props.filters.vendor ?? '');
const facilityFilter = ref(props.filters.facility ?? '');
const startDateFilter = ref(props.filters.start_date ?? '');
const endDateFilter = ref(props.filters.end_date ?? '');

const selectedVendorLabel = computed(() => {
    const match = props.vendors.find(
        (vendor) => String(vendor.id) === vendorFilter.value,
    );
    return match?.name ?? 'All vendors';
});

const selectedFacilityLabel = computed(() => {
    const match = props.facilities.find(
        (facility) => String(facility.id) === facilityFilter.value,
    );
    return match?.name ?? 'All facilities';
});

const statusBadgeClass = (status: string) => {
    if (status === 'completed') {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (status === 'in_progress') {
        return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
    }

    if (status === 'cancelled') {
        return 'bg-rose-500/10 text-rose-700 dark:text-rose-300';
    }

    return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
};

const formatDate = (value?: string | null) => {
    if (!value) {
        return '-';
    }

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
    }).format(parsed);
};

const getMaintenanceRequest = (workOrder: WorkOrder) =>
    workOrder.maintenanceRequest ?? workOrder.maintenance_request ?? null;

const columns: ColumnDef<WorkOrder>[] = [
    {
        id: 'maintenanceRequest',
        accessorFn: (row) => getMaintenanceRequest(row)?.id ?? '',
        header: 'Maintenance request',
        cell: ({ row }) => {
            const requestId = getMaintenanceRequest(row.original)?.id;
            if (!requestId) {
                return '-';
            }
            return h(
                Button,
                { variant: 'link', class: 'px-0', asChild: true },
                () =>
                    h(
                        Link,
                        { href: maintenanceShow(getMaintenanceRequest(row.original)!).url },
                        () => `Request #${requestId}`,
                    ),
            );
        },
        enableHiding: false,
    },
    {
        id: 'facility',
        accessorFn: (row) => getMaintenanceRequest(row)?.facility?.name ?? '',
        header: 'Facility',
        cell: ({ row }) =>
            getMaintenanceRequest(row.original)?.facility?.name ?? '-',
    },
    {
        id: 'vendor',
        accessorFn: (row) => row.vendor?.name ?? '',
        header: 'Vendor',
        cell: ({ row }) => row.original.vendor?.name ?? '-',
    },
    {
        id: 'assigned',
        accessorFn: (row) => row.assigned_date ?? '',
        header: 'Assigned',
        cell: ({ row }) => formatDate(row.original.assigned_date),
    },
    {
        id: 'scheduled',
        accessorFn: (row) => row.scheduled_date ?? '',
        header: 'Scheduled',
        cell: ({ row }) => formatDate(row.original.scheduled_date),
    },
    {
        id: 'completed',
        accessorFn: (row) => row.completed_date ?? '',
        header: 'Completed',
        cell: ({ row }) => formatDate(row.original.completed_date),
    },
    {
        id: 'status',
        accessorFn: (row) => row.status ?? '',
        header: 'Status',
        cell: ({ row }) =>
            h(
                Badge,
                { variant: 'secondary', class: statusBadgeClass(row.original.status ?? '') },
                () =>
                    row.original.status
                        ? row.original.status.replace('_', ' ')
                        : 'unknown',
            ),
    },
    {
        id: 'costs',
        accessorFn: (row) => row.estimated_cost ?? row.actual_cost ?? '',
        header: 'Costs',
        cell: ({ row }) =>
            h('div', { class: 'text-sm text-muted-foreground' }, [
                h('div', {}, [
                    'Est: ',
                    row.original.estimated_cost !== null &&
                        row.original.estimated_cost !== undefined
                        ? currencyFormat.format(row.original.estimated_cost)
                        : '-',
                ]),
                h('div', {}, [
                    'Act: ',
                    row.original.actual_cost !== null &&
                        row.original.actual_cost !== undefined
                        ? currencyFormat.format(row.original.actual_cost)
                        : '-',
                ]),
            ]),
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) => {
            const canEdit =
                can('work_orders.update') && row.original.status !== 'completed';
            const isInProgress = row.original.status === 'in_progress';

            return h('div', { class: 'flex flex-wrap gap-2' }, [
                h(Button, { variant: 'outline', size: 'sm', asChild: true }, () =>
                    h(Link, { href: show(row.original.id).url }, () => 'View'),
                ),
                canEdit
                    ? isInProgress
                        ? h(Button, { size: 'sm', disabled: true }, () => 'Edit')
                        : h(Button, { size: 'sm', asChild: true }, () =>
                            h(Link, { href: edit(row.original.id).url }, () => 'Edit'),
                        )
                    : null,
            ]);
        },
        enableSorting: false,
        enableHiding: false,
    },
];
</script>

<template>

    <Head title="Work Orders" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-8 p-6 lg:p-10">
            <PageHeader title="Work orders" subtitle="Coordinate vendor assignments and track completion."
                :show-filters-toggle="true" :filters-visible="filtersVisible"
                @toggle-filters="filtersVisible = !filtersVisible">
                <template #actions>
                    <Button v-if="can('work_orders.create')" size="lg" variant="outline" as-child>
                        <Link :href="route('work-orders.bulk-create')">
                            <Layers class="mr-2 h-4 w-4" />
                            Bulk create
                        </Link>
                    </Button>
                    <Button v-if="can('work_orders.create')" size="lg" as-child>
                        <Link :href="create().url">
                            <Plus class="mr-2 h-4 w-4" />
                            Create work order
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <DataTable :data="workOrders.data" :columns="columns" :show-search="false" :show-selection-summary="false"
                class="portfolio-table">
                <template v-if="filtersVisible" #filters>
                    <form :action="workOrdersIndex().url" method="get"
                        class="flex flex-wrap items-center gap-3 rounded-xl border border-border/60 bg-card p-4">
                        <div class="relative min-w-[220px] flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input v-model="searchFilter" name="search" class="pl-9"
                                placeholder="Search vendor, facility, request" />
                        </div>

                        <input type="hidden" name="status" :value="statusFilter" />
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" class="min-w-[150px] justify-between">
                                    {{ statusFilter ? statusFilter.replace('_', ' ') : 'All statuses' }}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-56">
                                <DropdownMenuLabel>Status</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @click="statusFilter = ''">
                                    All statuses
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="statusFilter = 'in_progress'">
                                    In progress
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="statusFilter = 'completed'">
                                    Completed
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="statusFilter = 'cancelled'">
                                    Cancelled
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <input type="hidden" name="vendor" :value="vendorFilter" />
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" class="min-w-[160px] justify-between">
                                    {{ selectedVendorLabel }}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-64">
                                <DropdownMenuLabel>Vendor</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @click="vendorFilter = ''">
                                    All vendors
                                </DropdownMenuItem>
                                <DropdownMenuItem v-for="vendor in vendors" :key="vendor.id"
                                    @click="vendorFilter = String(vendor.id)">
                                    {{ vendor.name }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <input type="hidden" name="facility" :value="facilityFilter" />
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" class="min-w-[170px] justify-between">
                                    {{ selectedFacilityLabel }}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-72">
                                <DropdownMenuLabel>Facility</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @click="facilityFilter = ''">
                                    All facilities
                                </DropdownMenuItem>
                                <DropdownMenuItem v-for="facility in facilities" :key="facility.id"
                                    @click="facilityFilter = String(facility.id)">
                                    {{ facility.name }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <DatePicker v-model="startDateFilter" name="start_date" class="min-w-[150px]"  />
                        <DatePicker v-model="endDateFilter" name="end_date" class="min-w-[150px]"  />

                        <div class="flex items-center gap-2">
                            <Button type="submit">Apply filters</Button>
                            <Button variant="ghost" as-child>
                                <Link :href="workOrdersIndex().url">Reset</Link>
                            </Button>
                        </div>
                    </form>
                </template>
            </DataTable>


            <PaginationLinks :links="workOrders.links" />
        </div>
    </AppLayout>
</template>
