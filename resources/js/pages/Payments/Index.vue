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
import { show as maintenanceShow } from '@/routes/maintenance';
import { index as paymentsIndex, show as paymentShow } from '@/routes/payments';
import { show as workOrderShow } from '@/routes/work-orders';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';
import type { ColumnDef } from '@tanstack/vue-table';
import { Search, Plus } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';

const filtersVisible = ref(false);

interface Facility {
    id: number;
    name: string;
}

interface Vendor {
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
    amount_payed?: number | null;
    status?: string | null;
    maintenanceRequest?: MaintenanceRequest | null;
    work_order_id?: number | null;
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
    vendor?: string | null;
    start_date?: string | null;
    end_date?: string | null;
}

interface Props {
    data: {
        payments: PaginatedPayments;
    };
    filters: Filters;
    facilities: Facility[];
    vendors: Vendor[];
    permissions: string[];
    routes: Record<string, string>;
    meta: Record<string, string>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Payments',
        href: paymentsIndex().url,
    },
];

const { can } = usePermissions();

const currencyFormat = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
});

const searchFilter = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');
const facilityFilter = ref(props.filters.facility ?? '');
const vendorFilter = ref(props.filters.vendor ?? '');
const startDateFilter = ref(props.filters.start_date ?? '');
const endDateFilter = ref(props.filters.end_date ?? '');

const selectedFacilityLabel = computed(() => {
    const match = props.facilities.find(
        (facility) => String(facility.id) === facilityFilter.value,
    );
    return match?.name ?? 'All facilities';
});

const selectedVendorLabel = computed(() => {
    const match = props.vendors.find(
        (vendor) => String(vendor.id) === vendorFilter.value,
    );
    return match?.name ?? 'All vendors';
});

const statusBadgeClass = (status: string) => {
    if (status === 'paid') {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (status === 'approved') {
        return 'bg-sky-500/10 text-sky-700 dark:text-sky-300';
    }

    if (status === 'rejected') {
        return 'bg-rose-500/10 text-rose-700 dark:text-rose-300';
    }

    return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
};

const columns: ColumnDef<Payment>[] = [
    {
        id: 'maintenanceRequest',
        accessorFn: (row) => row.maintenanceRequest?.id ?? '',
        header: 'Maintenance request',
        cell: ({ row }) => {
            const requestId = row.original.maintenanceRequest?.id;
            if (!requestId) {
                return '-';
            }
            return h(
                Button,
                { variant: 'link', class: 'px-0', asChild: true },
                () =>
                    h(
                        Link,
                        { href: maintenanceShow(row.original.maintenanceRequest!).url },
                        () => `Request #${requestId}`,
                    ),
            );
        },
        enableHiding: false,
    },
    {
        id: 'workOrder',
        accessorFn: (row) => row.work_order_id ?? '',
        header: 'Work order',
        cell: ({ row }) => {
            const workOrderId = row.original.work_order_id;
            if (!workOrderId) {
                return '-';
            }
            return h(
                Button,
                { variant: 'link', class: 'px-0', asChild: true },
                () =>
                    h(
                        Link,
                        { href: workOrderShow(workOrderId).url },
                        () => `Work order #${workOrderId}`,
                    ),
            );
        },
    },
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
            h('div', { class: 'flex flex-wrap gap-2' }, [
                h(Button, { variant: 'outline', size: 'sm', asChild: true }, () =>
                    h(Link, { href: paymentShow(row.original).url }, () => 'View'),
                ),
                row.original.status === 'pending' &&
                    can('payments.submit') &&
                    !can('payments.approve')
                    ? h(Button, { size: 'sm', variant: 'secondary' }, () => 'Submit for approval')
                    : null,
            ]),
        enableSorting: false,
        enableHiding: false,
    },
];
</script>

<template>

    <Head title="Payments" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-8 p-6 lg:p-10">
            <PageHeader title="Payments" subtitle="Monitor payment status and approvals."
                :show-filters-toggle="true" :filters-visible="filtersVisible"
                @toggle-filters="filtersVisible = !filtersVisible" />

            <DataTable :data="data.payments.data" :columns="columns" :show-search="false"
                :show-selection-summary="false" class="portfolio-table">
                <template v-if="filtersVisible" #filters>
                    <form :action="paymentsIndex().url" method="get"
                        class="flex flex-wrap items-center gap-3 rounded-xl border border-border/60 bg-card p-4">
                        <div class="relative min-w-[220px] flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input v-model="searchFilter" name="search" class="pl-9"
                                placeholder="Search facility or request" />
                        </div>

                        <input type="hidden" name="status" :value="statusFilter" />
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" class="min-w-[150px] justify-between">
                                    {{ statusFilter || 'All statuses' }}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-56">
                                <DropdownMenuLabel>Status</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @click="statusFilter = ''">
                                    All statuses
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="statusFilter = 'pending'">
                                    Pending
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="statusFilter = 'approved'">
                                    Approved
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="statusFilter = 'rejected'">
                                    Rejected
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="statusFilter = 'paid'">
                                    Paid
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
                            <DropdownMenuContent align="end" class="w-64">
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

                        <DatePicker v-model="startDateFilter" name="start_date" class="min-w-[150px]"  />
                        <DatePicker v-model="endDateFilter" name="end_date" class="min-w-[150px]"  />

                        <div class="flex items-center gap-2">
                            <Button type="submit">Apply filters</Button>
                            <Button variant="ghost" as-child>
                                <Link :href="paymentsIndex().url">Reset</Link>
                            </Button>
                        </div>
                    </form>
                </template>
            </DataTable>


            <PaginationLinks :links="data.payments.links" />
        </div>
    </AppLayout>
</template>
