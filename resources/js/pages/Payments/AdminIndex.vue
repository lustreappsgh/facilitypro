<script setup lang="ts">
import DataTable from '@/components/data-table/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import TableTotalsBar from '@/components/TableTotalsBar.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
    NativeSelect,
    NativeSelectOption,
} from '@/components/ui/native-select';
import AppLayout from '@/layouts/AppLayout.vue';
import { createCurrencyFormatter } from '@/lib/currency';
import { sumByNumber } from '@/lib/totals';
import {
    admin as paymentsAdmin,
    show as paymentsShow,
} from '@/routes/payments';
import { show as workOrderShow } from '@/routes/work-orders';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { Eye, Search } from 'lucide-vue-next';
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

interface Payment {
    id: number;
    status: string;
    cost: number;
    amount_payed?: number;
    created_at?: string | null;
    work_order_id?: number | null;
    maintenanceRequest?: {
        facility?: Facility | null;
    } | null;
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
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Payments',
        href: paymentsAdmin().url,
    },
];

const currencyFormat = createCurrencyFormatter();

const searchFilter = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');
const facilityFilter = ref(props.filters.facility ?? '');
const vendorFilter = ref(props.filters.vendor ?? '');
const startDateFilter = ref(props.filters.start_date ?? '');
const endDateFilter = ref(props.filters.end_date ?? '');

const selectedStatusLabel = computed(() => {
    if (!statusFilter.value) {
        return 'All statuses';
    }
    return statusFilter.value.replace('_', ' ');
});

const selectedVendorLabel = computed(() => {
    const match = props.vendors.find(
        (vendor) => String(vendor.id) === vendorFilter.value,
    );
    return match?.name ?? 'All vendors';
});

const paymentTotals = computed(() => ({
    cost: sumByNumber(props.data.payments.data, (payment) => payment.cost),
    amountPaid: sumByNumber(
        props.data.payments.data,
        (payment) => payment.amount_payed,
    ),
}));

const statusBadgeClass = (status: string) => {
    if (status === 'approved') {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (status === 'paid') {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (status === 'pending') {
        return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
    }

    return 'bg-slate-500/10 text-slate-700 dark:text-slate-300';
};

const columns: ColumnDef<Payment>[] = [
    {
        id: 'facility',
        accessorFn: (row) => row.maintenanceRequest?.facility?.name ?? '',
        header: 'Facility',
        cell: ({ row }) =>
            h(
                'span',
                { class: 'font-medium' },
                row.original.maintenanceRequest?.facility?.name ?? '-',
            ),
        enableHiding: false,
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
                    class: statusBadgeClass(row.original.status),
                },
                () =>
                    row.original.status
                        ? row.original.status.replace('_', ' ')
                        : 'unknown',
            ),
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
        cell: ({ row }) => currencyFormat.format(row.original.cost),
    },
    {
        id: 'paid',
        accessorFn: (row) => row.amount_payed ?? 0,
        header: 'Paid',
        cell: ({ row }) =>
            row.original.amount_payed !== undefined
                ? currencyFormat.format(row.original.amount_payed)
                : '-',
    },
    {
        id: 'createdAt',
        accessorFn: (row) => row.created_at ?? '',
        header: 'Created',
        cell: ({ row }) => row.original.created_at ?? '-',
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
                            href: paymentsShow(row.original).url,
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
    <Head title="Payments overview" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader
                title="Payments overview"
                subtitle="Monitor payments across facilities."
                :show-filters-toggle="true"
                :filters-visible="filtersVisible"
                @toggle-filters="filtersVisible = !filtersVisible"
            />

            <DataTable
                :data="data.payments.data"
                :columns="columns"
                :show-search="false"
                :show-selection-summary="false"
            >
                <template v-if="filtersVisible" #filters>
                    <form
                        :action="paymentsAdmin().url"
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
                                placeholder="Search facility or description"
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
                                    class="min-w-[160px] justify-between"
                                >
                                    {{ selectedStatusLabel }}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-56">
                                <DropdownMenuLabel>Status</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @click="statusFilter = ''">
                                    All statuses
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    @click="statusFilter = 'pending'"
                                >
                                    Pending
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    @click="statusFilter = 'approved'"
                                >
                                    Approved
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    @click="statusFilter = 'rejected'"
                                >
                                    Rejected
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    @click="statusFilter = 'paid'"
                                >
                                    Paid
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <NativeSelect
                            v-model="facilityFilter"
                            name="facility"
                            class="min-w-[180px]"
                        >
                            <NativeSelectOption value="">
                                All facilities
                            </NativeSelectOption>
                            <NativeSelectOption
                                v-for="facility in facilities"
                                :key="facility.id"
                                :value="String(facility.id)"
                            >
                                {{ facility.name }}
                            </NativeSelectOption>
                        </NativeSelect>

                        <input
                            type="hidden"
                            name="vendor"
                            :value="vendorFilter"
                        />
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="min-w-[180px] justify-between"
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
                                <Link :href="paymentsAdmin().url">Reset</Link>
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
