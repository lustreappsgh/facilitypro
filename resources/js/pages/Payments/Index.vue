<script setup lang="ts">
import DataTable from '@/components/data-table/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import TableTotalsBar from '@/components/TableTotalsBar.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { createCurrencyFormatter } from '@/lib/currency';
import { sumByNumber } from '@/lib/totals';
import { show as maintenanceShow } from '@/routes/maintenance';
import { show as paymentShow, index as paymentsIndex } from '@/routes/payments';
import { show as workOrderShow } from '@/routes/work-orders';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { Eye, Search, Send } from 'lucide-vue-next';
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

const currencyFormat = createCurrencyFormatter();

const searchFilter = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? 'all');
const facilityFilter = ref(props.filters.facility ?? 'all');
const vendorFilter = ref(props.filters.vendor ?? 'all');
const startDateFilter = ref(props.filters.start_date ?? '');
const endDateFilter = ref(props.filters.end_date ?? '');

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

const paymentTotals = computed(() => ({
    cost: sumByNumber(props.data.payments.data, (payment) => payment.cost),
    amountPaid: sumByNumber(
        props.data.payments.data,
        (payment) => payment.amount_payed,
    ),
}));

const columns: ColumnDef<Payment>[] = [
    {
        id: 'requestDetails',
        accessorFn: (row) => row.maintenanceRequest?.id ?? '',
        header: 'Request details',
        cell: ({ row }) => {
            const maintenanceRequest = row.original.maintenanceRequest;
            if (!maintenanceRequest?.id) {
                return '-';
            }

            return h('div', { class: 'space-y-1' }, [
                h(
                    Button,
                    {
                        variant: 'link',
                        class: 'h-auto px-0 py-0',
                        asChild: true,
                    },
                    () =>
                        h(
                            Link,
                            { href: maintenanceShow(maintenanceRequest).url },
                            () => `Request #${maintenanceRequest.id}`,
                        ),
                ),
                h(
                    'div',
                    { class: 'text-xs text-muted-foreground' },
                    maintenanceRequest.requestType?.name ?? 'General request',
                ),
                h(
                    'div',
                    { class: 'text-xs text-muted-foreground' },
                    maintenanceRequest.facility?.name ?? 'No facility linked',
                ),
            ]);
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
            row.original.amount_payed !== null &&
            row.original.amount_payed !== undefined
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
                {
                    variant: 'secondary',
                    class: statusBadgeClass(row.original.status ?? ''),
                },
                () => row.original.status ?? 'unknown',
            ),
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) =>
            h('div', { class: 'flex items-center justify-end gap-1' }, [
                h(Tooltip, {}, () => [
                    h(TooltipTrigger, { asChild: true }, () =>
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
                    ),
                    h(TooltipContent, { side: 'top' }, () => 'View payment'),
                ]),
                row.original.status === 'pending' &&
                can('payments.submit') &&
                !can('payments.approve')
                    ? h(Tooltip, {}, () => [
                          h(TooltipTrigger, { asChild: true }, () =>
                              h(
                                  Button,
                                  {
                                      size: 'icon',
                                      variant: 'secondary',
                                      class: 'h-8 w-8',
                                      'aria-label': 'Submit for approval',
                                  },
                                  () => h(Send, { class: 'h-4 w-4' }),
                              ),
                          ),
                          h(
                              TooltipContent,
                              { side: 'top' },
                              () => 'Submit for approval',
                          ),
                      ])
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
            <PageHeader
                title="Payments"
                subtitle="Monitor payment status and approvals."
                :show-filters-toggle="true"
                :filters-visible="filtersVisible"
                @toggle-filters="filtersVisible = !filtersVisible"
            />

            <DataTable
                :data="data.payments.data"
                :columns="columns"
                :show-search="false"
                :show-selection-summary="false"
                class="portfolio-table"
            >
                <template v-if="filtersVisible" #filters>
                    <form
                        :action="paymentsIndex().url"
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
                                placeholder="Search facility or request"
                            />
                        </div>

                        <input
                            type="hidden"
                            name="status"
                            :value="statusFilter === 'all' ? '' : statusFilter"
                        />
                        <Select v-model="statusFilter">
                            <SelectTrigger class="min-w-[150px]">
                                <SelectValue placeholder="All statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all"
                                    >All statuses</SelectItem
                                >
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="approved"
                                    >Approved</SelectItem
                                >
                                <SelectItem value="rejected"
                                    >Rejected</SelectItem
                                >
                                <SelectItem value="paid">Paid</SelectItem>
                            </SelectContent>
                        </Select>

                        <input
                            type="hidden"
                            name="facility"
                            :value="
                                facilityFilter === 'all' ? '' : facilityFilter
                            "
                        />
                        <Select v-model="facilityFilter">
                            <SelectTrigger class="min-w-[170px]">
                                <SelectValue placeholder="All facilities" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all"
                                    >All facilities</SelectItem
                                >
                                <SelectItem
                                    v-for="facility in facilities"
                                    :key="facility.id"
                                    :value="String(facility.id)"
                                >
                                    {{ facility.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <input
                            type="hidden"
                            name="vendor"
                            :value="vendorFilter === 'all' ? '' : vendorFilter"
                        />
                        <Select v-model="vendorFilter">
                            <SelectTrigger class="min-w-[160px]">
                                <SelectValue placeholder="All vendors" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All vendors</SelectItem>
                                <SelectItem
                                    v-for="vendor in vendors"
                                    :key="vendor.id"
                                    :value="String(vendor.id)"
                                >
                                    {{ vendor.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

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
                                <Link :href="paymentsIndex().url">Reset</Link>
                            </Button>
                        </div>
                    </form>
                </template>
                <template #footer>
                    <TableTotalsBar
                        :items="[
                            {
                                label: 'Cost',
                                value: currencyFormat.format(
                                    paymentTotals.cost,
                                ),
                            },
                            {
                                label: 'Amount paid',
                                value: currencyFormat.format(
                                    paymentTotals.amountPaid,
                                ),
                            },
                        ]"
                    />
                </template>
            </DataTable>

            <PaginationLinks :links="data.payments.links" />
        </div>
    </AppLayout>
</template>
