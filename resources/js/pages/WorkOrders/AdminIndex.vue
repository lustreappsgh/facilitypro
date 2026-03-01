<script setup lang="ts">
import { DatePicker } from '@/components/ui/date-picker';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ButtonGroup } from '@/components/ui/button-group';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import DataTable from '@/components/data-table/DataTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { admin as workOrdersAdmin, show as workOrdersShow } from '@/routes/work-orders';
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
    status: string;
    scheduled_date?: string | null;
    created_at?: string | null;
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
        title: 'Work Orders',
        href: workOrdersAdmin().url,
    },
];

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

const selectedStatusLabel = computed(() => {
    if (!statusFilter.value) {
        return 'All statuses';
    }
    return statusFilter.value.replace('_', ' ');
});

const statusBadgeClass = (status: string) => {
    if (status === 'completed') {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (status === 'in_progress') {
        return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
    }

    return 'bg-slate-500/10 text-slate-700 dark:text-slate-300';
};

const columns: ColumnDef<WorkOrder>[] = [
    {
        id: 'facility',
        accessorFn: (row) => row.maintenanceRequest?.facility?.name ?? '',
        header: 'Facility',
        cell: ({ row }) =>
            h('span', { class: 'font-medium' }, row.original.maintenanceRequest?.facility?.name ?? '-'),
        enableHiding: false,
    },
    {
        id: 'vendor',
        accessorFn: (row) => row.vendor?.name ?? '',
        header: 'Vendor',
        cell: ({ row }) => row.original.vendor?.name ?? '-',
    },
    {
        id: 'status',
        accessorFn: (row) => row.status ?? '',
        header: 'Status',
        cell: ({ row }) =>
            h(
                Badge,
                { variant: 'secondary', class: statusBadgeClass(row.original.status) },
                () =>
                    row.original.status
                        ? row.original.status.replace('_', ' ')
                        : 'unknown',
            ),
    },
    {
        id: 'scheduledDate',
        accessorFn: (row) => row.scheduled_date ?? '',
        header: 'Scheduled date',
        cell: ({ row }) => row.original.scheduled_date ?? '-',
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
                            { variant: 'ghost', size: 'icon-sm', class: 'rounded-none border-0 shadow-none', asChild: true },
                            () => h(Link, { href: workOrdersShow(row.original).url }, () => h(Eye, { class: 'h-3.5 w-3.5' })),
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
    <Head title="Work orders overview" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader
                title="Work orders overview"
                subtitle="Monitor work orders across facilities."
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
                        :action="workOrdersAdmin().url"
                        method="get"
                        class="flex flex-wrap items-center gap-3 rounded-xl border border-border/60 bg-card p-4"
                    >
                <div class="relative min-w-[220px] flex-1">
                    <Search
                        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="searchFilter"
                        name="search"
                        class="pl-9"
                        placeholder="Search by facility or vendor"
                    />
                </div>

                <input type="hidden" name="status" :value="statusFilter" />
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" class="min-w-[160px] justify-between">
                            {{ selectedStatusLabel }}
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
                        <Button variant="outline" class="min-w-[180px] justify-between">
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

                <input type="hidden" name="facility" :value="facilityFilter" />
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" class="min-w-[180px] justify-between">
                            {{ selectedFacilityLabel }}
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-64">
                        <DropdownMenuLabel>Facility</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem @click="facilityFilter = ''">
                            All facilities
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            v-for="facility in facilities"
                            :key="facility.id"
                            @click="facilityFilter = String(facility.id)"
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
                    <Button type="submit" class="whitespace-nowrap">
                        Apply filters
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link :href="workOrdersAdmin().url">Reset</Link>
                    </Button>
                </div>
                    </form>
                </template>
            </DataTable>


            <PaginationLinks :links="workOrders.links" />
        </div>
    </AppLayout>
</template>
