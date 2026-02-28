<script setup lang="ts">
import { DatePicker } from '@/components/ui/date-picker';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import {
    edit as maintenanceEdit,
    my as maintenanceMy,
    show as maintenanceShow,
} from '@/routes/maintenance';
import MaintenanceRequestModal from '@/components/MaintenanceRequestModal.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';
import type { ColumnDef } from '@tanstack/vue-table';
import {
    CircleDollarSign, Clock3, MoreHorizontal, Search, Wrench,
    Plus, X, Calendar, ClipboardList
} from 'lucide-vue-next';
import { computed, h, onMounted, ref } from 'vue';
import { startOfWeek, endOfWeek, format, parseISO, subMonths } from 'date-fns';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const filtersVisible = ref(false);

interface Facility {
    id: number;
    name: string;
}

interface RequestType {
    id: number;
    name: string;
}

interface MaintenanceRequest {
    id: number;
    description: string | null;
    status: string;
    cost?: number | null;
    created_at: string | null;
    facility?: Facility | null;
    requestType?: RequestType | null;
    request_type?: RequestType | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedRequests {
    data: MaintenanceRequest[];
    links: PaginationLink[];
}

interface StatusOption {
    value: string;
    label: string;
}

interface Stats {
    active_count: number;
    estimated_cost: number;
    avg_resolution_days: number | null;
}

interface Filters {
    search?: string | null;
    status?: string | null;
    facility?: string | null;
    week?: string | null;
    start_date?: string | null;
    end_date?: string | null;
}

interface Props {
    requests: PaginatedRequests;
    statuses: StatusOption[];
    facilities: Facility[];
    requestTypes: RequestType[];
    filters: Filters;
    stats: Stats;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Maintenance requests',
        href: maintenanceMy().url,
    },
];

const { can } = usePermissions();

const currencyFormat = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
});

// Modal state
const requestModalOpen = ref(false);

const searchFilter = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? 'all');
const facilityFilter = ref(props.filters.facility ?? 'all');
const weekFilter = ref(props.filters.week ?? '');
const startDateFilter = ref(props.filters.start_date ?? '');
const endDateFilter = ref(props.filters.end_date ?? '');

const allowedStatuses = computed(() =>
    props.statuses.filter((status) =>
        ['pending', 'in_progress', 'completed', 'cancelled'].includes(status.value),
    ),
);

const applyFilters = () => {
    router.get(
        maintenanceMy().url,
        {
            search: searchFilter.value || undefined,
            status: statusFilter.value === 'all' ? undefined : statusFilter.value,
            facility: facilityFilter.value === 'all' ? undefined : facilityFilter.value,
            week: weekFilter.value || undefined,
            start_date: startDateFilter.value || undefined,
            end_date: endDateFilter.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const clearFilters = () => {
    searchFilter.value = '';
    statusFilter.value = 'all';
    facilityFilter.value = 'all';
    weekFilter.value = '';
    startDateFilter.value = '';
    endDateFilter.value = '';
    applyFilters();
};

onMounted(() => {
    const hasAnyFilter = Boolean(
        searchFilter.value ||
        (statusFilter.value && statusFilter.value !== 'all') ||
        (facilityFilter.value && facilityFilter.value !== 'all') ||
        weekFilter.value ||
        startDateFilter.value ||
        endDateFilter.value
    );
    if (hasAnyFilter) {
        return;
    }
    statusFilter.value = 'pending';
    const now = new Date();
    const monthStart = subMonths(now, 1);
    startDateFilter.value = format(monthStart, 'yyyy-MM-dd');
    endDateFilter.value = format(now, 'yyyy-MM-dd');
    applyFilters();
});

const groupedByWeek = computed(() => {
    const groups = new Map<string, MaintenanceRequest[]>();

    props.requests.data.forEach((request) => {
        let weekKey = 'Unknown Date';

        if (request.created_at) {
            const date = parseISO(request.created_at);
            const weekStart = startOfWeek(date, { weekStartsOn: 1 });
            const weekEnd = endOfWeek(date, { weekStartsOn: 1 });
            weekKey = `${format(weekStart, 'MMM d')} - ${format(weekEnd, 'MMM d, yyyy')}`;
        }

        if (!groups.has(weekKey)) {
            groups.set(weekKey, []);
        }
        groups.get(weekKey)!.push(request);
    });

    return Array.from(groups.entries()).map(([week, items]) => ({
        week,
        items,
        count: items.length,
    }));
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

    return 'bg-slate-500/10 text-slate-700 dark:text-slate-300';
};

const requestTypeName = (request: MaintenanceRequest) =>
    request.requestType?.name ?? request.request_type?.name ?? '-';

const columns: ColumnDef<MaintenanceRequest>[] = [
    {
        id: 'select',
        header: ({ table }) =>
            h(Checkbox, {
                modelValue:
                    table.getIsAllPageRowsSelected() ||
                    (table.getIsSomePageRowsSelected() && 'indeterminate'),
                'onUpdate:modelValue': (value: boolean | 'indeterminate') =>
                    table.toggleAllPageRowsSelected(value === true),
                'aria-label': 'Select all',
            }),
        cell: ({ row }) =>
            h(Checkbox, {
                modelValue: row.getIsSelected(),
                'onUpdate:modelValue': (value: boolean | 'indeterminate') =>
                    row.toggleSelected(value === true),
                'aria-label': 'Select row',
            }),
        enableSorting: false,
        enableHiding: false,
    },
    {
        id: 'facility',
        accessorFn: (row) => row.facility?.name ?? '',
        header: 'Facility',
        cell: ({ row }) =>
            h('div', { class: 'space-y-1' }, [
                h('p', { class: 'font-semibold text-gray-900' }, row.original.facility?.name ?? '-'),
                h('p', { class: 'text-xs text-muted-foreground' }, `Request #${row.original.id}`),
            ]),
        enableHiding: false,
    },
    {
        id: 'requestType',
        accessorFn: (row) => row.requestType?.name ?? row.request_type?.name ?? '',
        header: 'Request type',
        cell: ({ row }) =>
            h('span', { class: 'text-sm text-muted-foreground' }, requestTypeName(row.original)),
    },
    {
        id: 'cost',
        accessorFn: (row) => row.cost ?? 0,
        header: 'Cost',
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-sm text-muted-foreground' },
                row.original.cost !== null && row.original.cost !== undefined
                    ? currencyFormat.format(row.original.cost)
                    : '-',
            ),
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
                    class: `${statusBadgeClass(row.original.status)} text-[11px] uppercase`,
                },
                () =>
                    row.original.status
                        ? row.original.status.replace('_', ' ')
                        : 'unknown',
            ),
    },
    {
        id: 'createdAt',
        accessorFn: (row) => row.created_at ?? '',
        header: 'Requested',
        cell: ({ row }) =>
            h('span', { class: 'text-sm text-muted-foreground' }, row.original.created_at ?? '-'),
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) =>
            h(DropdownMenu, {}, {
                default: () => [
                    h(DropdownMenuTrigger, { asChild: true }, {
                        default: () =>
                            h(
                                Button,
                                { variant: 'ghost', size: 'icon' },
                                { default: () => h(MoreHorizontal, { class: 'h-4 w-4' }) },
                            ),
                    }),
                    h(DropdownMenuContent, { align: 'end', class: 'w-40' }, {
                        default: () => [
                            h(DropdownMenuLabel, {}, () => 'Actions'),
                            h(DropdownMenuSeparator),
                            (can('maintenance.view') || can('maintenance_requests.view'))
                                ? h(DropdownMenuItem, { asChild: true }, {
                                    default: () =>
                                        h(Link, { href: maintenanceShow(row.original).url }, () => 'View'),
                                })
                                : null,
                            can('maintenance.update') && row.original.status === 'pending'
                                ? h(DropdownMenuItem, { asChild: true }, {
                                    default: () =>
                                        h(Link, { href: maintenanceEdit(row.original).url }, () => 'Edit'),
                                })
                                : null,
                        ],
                    }),
                ],
            }),
        enableSorting: false,
        enableHiding: false,
    },
];
</script>

<template>

    <Head title="Maintenance Requests" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-8 p-6 lg:p-10">
            <PageHeader
                title="Maintenance requests"
                subtitle="Track and resolve maintenance requests."
                :show-filters-toggle="true"
                :filters-visible="filtersVisible"
                @toggle-filters="filtersVisible = !filtersVisible"
            >
                <template #actions>
                    <Button v-if="can('maintenance.create')" size="lg" @click="requestModalOpen = true">
                        <Plus class="mr-2 h-4 w-4" />
                        New request
                    </Button>
                </template>
            </PageHeader>
            <div v-if="filtersVisible" class="rounded-xl border border-border/60 bg-card p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative min-w-[220px] flex-1">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="searchFilter" class="pl-9" placeholder="Search facility or description" />
                    </div>
                    <DatePicker v-model="weekFilter" class="min-w-[160px]"  />
                    <Select v-model="facilityFilter">
                        <SelectTrigger class="min-w-[180px]">
                            <SelectValue placeholder="All facilities" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All facilities</SelectItem>
                            <SelectItem v-for="facility in facilities" :key="facility.id" :value="String(facility.id)">
                                {{ facility.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="statusFilter">
                        <SelectTrigger class="min-w-[160px]">
                            <SelectValue placeholder="All statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            <SelectItem v-for="status in allowedStatuses" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <DatePicker v-model="startDateFilter" class="min-w-[160px]"  />
                    <DatePicker v-model="endDateFilter" class="min-w-[160px]"  />
                    <div class="flex items-center gap-2">
                        <Button @click="applyFilters">
                            Apply filters
                        </Button>
                        <Button variant="ghost" @click="clearFilters">
                            Reset
                        </Button>
                    </div>
                </div>
            </div>
            <!-- Stats -->
            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="border border-border/60 shadow-sm rounded-2xl">
                    <CardContent class="flex items-center justify-between gap-4 p-5">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/60">
                                Total active requests
                            </p>
                            <p class="font-display text-3xl font-black text-foreground uppercase tracking-tight">
                                {{ stats.active_count }}
                            </p>
                            <p class="text-[9px] font-bold text-muted-foreground/40 uppercase tracking-widest mt-1">
                                Open items across your facilities
                            </p>
                        </div>
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">
                            <Wrench class="h-6 w-6" />
                        </div>
                    </CardContent>
                </Card>

                <Card class="border border-border/60 shadow-sm rounded-2xl">
                    <CardContent class="flex items-center justify-between gap-4 p-5">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/60">
                                Estimated cost
                            </p>
                            <p class="font-display text-3xl font-black text-foreground uppercase tracking-tight">
                                {{ stats.estimated_cost ? currencyFormat.format(stats.estimated_cost) :
                                currencyFormat.format(0) }}
                            </p>
                            <p class="text-[9px] font-bold text-muted-foreground/40 uppercase tracking-widest mt-1">
                                Sum of reported estimates
                            </p>
                        </div>
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-500/10 text-blue-600 border border-blue-500/20">
                            <CircleDollarSign class="h-6 w-6" />
                        </div>
                    </CardContent>
                </Card>

                <Card class="border border-border/60 shadow-sm rounded-2xl">
                    <CardContent class="flex items-center justify-between gap-4 p-5">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/60">
                                Avg. resolution time
                            </p>
                            <p class="font-display text-3xl font-black text-foreground uppercase tracking-tight">
                                {{ stats.avg_resolution_days !== null ? `${stats.avg_resolution_days} days` : '--' }}
                            </p>
                            <p class="text-[9px] font-bold text-muted-foreground/40 uppercase tracking-widest mt-1">
                                Completed requests only
                            </p>
                        </div>
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-600 border border-amber-500/20">
                            <Clock3 class="h-6 w-6" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Grouped Requests -->
            <div class="space-y-4">
                <div class="flex items-center gap-3 px-1">
                    <Calendar class="h-5 w-5 text-muted-foreground/40" />
                    <h3 class="text-[11px] font-black uppercase tracking-[0.25em] text-muted-foreground/50">Grouped by
                        Week</h3>
                </div>

                <Accordion v-if="groupedByWeek.length > 0" type="multiple"
                    :default-value="groupedByWeek.map(g => g.week)" class="space-y-4">
                    <AccordionItem v-for="group in groupedByWeek" :key="group.week" :value="group.week"
                        class="rounded-3xl border border-border bg-card shadow-sm px-6 overflow-hidden">
                        <AccordionTrigger class="hover:no-underline py-6">
                            <div class="flex items-center gap-5">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-muted/50 border border-border/60 shadow-inner">
                                    <Calendar class="h-6 w-6 text-muted-foreground/60" />
                                </div>
                                <div class="text-left">
                                    <p class="font-black text-[15px] uppercase tracking-tight text-foreground">{{
                                        group.week }}</p>
                                    <p class="text-[10px] font-bold text-muted-foreground/60 uppercase tracking-widest">
                                        {{ group.count }} {{ group.count === 1 ? 'request' : 'requests' }}
                                    </p>
                                </div>
                            </div>
                        </AccordionTrigger>
                        <AccordionContent class="pb-6">
                            <DataTable :data="group.items" :columns="columns" :show-search="false"
                                :show-selection-summary="false" :enable-row-selection="false" class="portfolio-table" />
                        </AccordionContent>
                    </AccordionItem>
                </Accordion>

                <Card v-else class="border-border bg-card shadow-sm rounded-3xl">
                    <CardContent class="py-20 text-center">
                        <ClipboardList class="mx-auto h-16 w-16 text-muted-foreground/10 mb-6" />
                        <p class="text-lg font-black text-foreground uppercase tracking-tight">No requests found</p>
                        <p class="text-[10px] font-bold text-muted-foreground/40 uppercase tracking-widest mt-2">Try
                            adjusting your filters or log a new maintenance issue
                        </p>
                    </CardContent>
                </Card>
            </div>

            <PaginationLinks :links="requests.links" />
        </div>

        <MaintenanceRequestModal v-model:open="requestModalOpen" :facilities="facilities" :request-types="requestTypes"
            :redirect-to="maintenanceMy().url" @success="router.reload()" />
    </AppLayout>
</template>


