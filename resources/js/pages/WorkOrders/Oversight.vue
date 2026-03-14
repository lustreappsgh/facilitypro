<script setup lang="ts">
import DataTable from '@/components/data-table/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import TableTotalsBar from '@/components/TableTotalsBar.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ButtonGroup } from '@/components/ui/button-group';
import { DatePicker } from '@/components/ui/date-picker';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import { createCurrencyFormatter } from '@/lib/currency';
import { sumByNumber } from '@/lib/totals';
import { show as maintenanceShow } from '@/routes/maintenance';
import { show, oversight as workOrdersOversight } from '@/routes/work-orders';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { Eye, Search } from 'lucide-vue-next';
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
        title: 'Work Order Oversight',
        href: workOrdersOversight().url,
    },
];

const currencyFormat = createCurrencyFormatter();

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

const workOrderTotals = computed(() => ({
    estimated: sumByNumber(
        props.workOrders.data,
        (workOrder) => workOrder.estimated_cost,
    ),
    actual: sumByNumber(
        props.workOrders.data,
        (workOrder) => workOrder.actual_cost,
    ),
}));

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

const columns: ColumnDef<WorkOrder>[] = [
    {
        id: 'maintenanceRequest',
        accessorFn: (row) => row.maintenanceRequest?.id ?? '',
        header: 'Maintenance request',
        cell: ({ row }) => {
            const requestId = row.original.maintenanceRequest?.id;
            if (!requestId) {
                return '--';
            }
            return h(
                Button,
                { variant: 'link', class: 'px-0', asChild: true },
                () =>
                    h(
                        Link,
                        {
                            href: maintenanceShow(
                                row.original.maintenanceRequest!,
                            ).url,
                        },
                        () => `Request #${requestId}`,
                    ),
            );
        },
        enableHiding: false,
    },
    {
        id: 'facility',
        accessorFn: (row) => row.maintenanceRequest?.facility?.name ?? '',
        header: 'Facility',
        cell: ({ row }) =>
            row.original.maintenanceRequest?.facility?.name ?? '--',
    },
    {
        id: 'vendor',
        accessorFn: (row) => row.vendor?.name ?? '',
        header: 'Vendor',
        cell: ({ row }) => row.original.vendor?.name ?? '--',
    },
    {
        id: 'assigned',
        accessorFn: (row) => row.assigned_date ?? '',
        header: 'Assigned',
        cell: ({ row }) => row.original.assigned_date ?? '--',
    },
    {
        id: 'scheduled',
        accessorFn: (row) => row.scheduled_date ?? '',
        header: 'Scheduled',
        cell: ({ row }) => row.original.scheduled_date ?? '--',
    },
    {
        id: 'completed',
        accessorFn: (row) => row.completed_date ?? '',
        header: 'Completed',
        cell: ({ row }) => row.original.completed_date ?? '--',
    },
    {
        id: 'status',
        accessorFn: (row) => row.status ?? '',
        header: 'Status',
        cell: ({ row }) =>
            h(
                Badge,
                {
                    variant: 'secondary',
                    class: statusBadgeClass(row.original.status ?? ''),
                },
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
                        : '--',
                ]),
                h('div', {}, [
                    'Act: ',
                    row.original.actual_cost !== null &&
                    row.original.actual_cost !== undefined
                        ? currencyFormat.format(row.original.actual_cost)
                        : '--',
                ]),
            ]),
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) =>
            h(ButtonGroup, {}, () => [
                h(Tooltip, {}, () => [
                    h(TooltipTrigger, { asChild: true }, () =>
                        h(
                            Button,
                            {
                                variant: 'ghost',
                                size: 'icon-sm',
                                class: 'rounded-none border-0 shadow-none',
                                asChild: true,
                            },
                            () =>
                                h(
                                    Link,
                                    { href: show(row.original.id).url },
                                    () => h(Eye, { class: 'h-3.5 w-3.5' }),
                                ),
                        ),
                    ),
                    h(TooltipContent, { side: 'top' }, () => 'View'),
                ]),
            ]),
        enableSorting: false,
        enableHiding: false,
    },
];
</script>

<template>
    <Head title="Work order overview" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader
                title="Work order overview"
                subtitle="Read-only visibility into work order progress."
                :show-filters-toggle="true"
                :filters-visible="filtersVisible"
                @toggle-filters="filtersVisible = !filtersVisible"
            />

            <DataTable
                :data="workOrders.data"
                :columns="columns"
                :show-search="false"
                :show-selection-summary="false"
            >
                <template v-if="filtersVisible" #filters>
                    <form
                        :action="workOrdersOversight().url"
                        method="get"
                        class="flex flex-wrap items-center gap-3 rounded-xl border border-border/60 bg-card p-4"
                    >
                        <div class="relative min-w-[220px] flex-1">
                            <Search
                                class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                v-model="searchFilter"
                                name="search"
                                class="pl-9"
                                placeholder="Search vendor, facility, request"
                            />
                        </div>

                        <input
                            type="hidden"
                            name="status"
                            :value="statusFilter"
                        />
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="min-w-[150px] justify-between"
                                >
                                    {{
                                        statusFilter
                                            ? statusFilter.replace('_', ' ')
                                            : 'All statuses'
                                    }}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-56">
                                <DropdownMenuLabel>Status</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @click="statusFilter = ''">
                                    All statuses
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    @click="statusFilter = 'in_progress'"
                                >
                                    In progress
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    @click="statusFilter = 'completed'"
                                >
                                    Completed
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    @click="statusFilter = 'cancelled'"
                                >
                                    Cancelled
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <input
                            type="hidden"
                            name="vendor"
                            :value="vendorFilter"
                        />
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="min-w-[160px] justify-between"
                                >
                                    {{ selectedVendorLabel }}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-64">
                                <DropdownMenuLabel>Vendor</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @click="vendorFilter = ''">
                                    All vendors
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    v-for="vendor in vendors"
                                    :key="vendor.id"
                                    @click="vendorFilter = String(vendor.id)"
                                >
                                    {{ vendor.name }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <input
                            type="hidden"
                            name="facility"
                            :value="facilityFilter"
                        />
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="min-w-[170px] justify-between"
                                >
                                    {{ selectedFacilityLabel }}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-72">
                                <DropdownMenuLabel>Facility</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @click="facilityFilter = ''">
                                    All facilities
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    v-for="facility in facilities"
                                    :key="facility.id"
                                    @click="
                                        facilityFilter = String(facility.id)
                                    "
                                >
                                    {{ facility.name }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <DatePicker
                            v-model="startDateFilter"
                            name="start_date"
                            class="min-w-[150px]"
                        />
                        <DatePicker
                            v-model="endDateFilter"
                            name="end_date"
                            class="min-w-[150px]"
                        />

                        <div class="flex items-center gap-2">
                            <Button type="submit">Apply filters</Button>
                            <Button variant="ghost" as-child>
                                <Link :href="workOrdersOversight().url"
                                    >Reset</Link
                                >
                            </Button>
                        </div>
                    </form>
                </template>
                <template #footer>
                    <TableTotalsBar
                        :items="[
                            {
                                label: 'Estimated',
                                value: currencyFormat.format(
                                    workOrderTotals.estimated,
                                ),
                            },
                            {
                                label: 'Actual',
                                value: currencyFormat.format(
                                    workOrderTotals.actual,
                                ),
                            },
                        ]"
                    />
                </template>
            </DataTable>

            <PaginationLinks :links="workOrders.links" />
        </div>
    </AppLayout>
</template>
