<script setup lang="ts">
import StatsCard from '@/components/StatsCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ButtonGroup } from '@/components/ui/button-group';
import { Card, CardContent } from '@/components/ui/card';
import DataTable from '@/components/data-table/DataTable.vue';
import { DatePicker } from '@/components/ui/date-picker';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import { close, create, edit, index as maintenanceIndex, show } from '@/routes/maintenance';
import { show as workOrderShow } from '@/routes/work-orders';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';
import type { ColumnDef } from '@tanstack/vue-table';
import { computed, h, ref } from 'vue';
import { CheckCircle2, ClipboardCheck, ClipboardList, Eye, Pencil, Plus, Timer, Wrench } from 'lucide-vue-next';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { bulkCreate as workOrdersBulkCreate } from '@/routes/work-orders';

interface Facility {
    id: number;
    name: string;
}

interface MaintenanceRequest {
    id: number;
    status: string;
    description?: string | null;
    cost?: number | null;
    created_at?: string | null;
    facility?: Facility | null;
    facility_manager_name?: string | null;
    requested_by_name?: string | null;
    request_type_name?: string | null;
    latest_work_order_id?: number | null;
    has_work_order?: boolean;
}

interface MaintenanceRequestGroup {
    week_start: string;
    week_label: string;
    requests: MaintenanceRequest[];
}

interface WeekOption {
    week_start: string;
    label: string;
}

interface WeeksByYearMonth {
    year: number;
    month: string;
    month_key: string;
    weeks: WeekOption[];
}

interface Props {
    data: {
        groups: MaintenanceRequestGroup[];
        weeks_by_year_month: WeeksByYearMonth[];
        facilities: Facility[];
        show_requester_name: boolean;
        show_facility_manager_name: boolean;
        is_facility_manager: boolean;
        filters: {
            start_date: string;
            end_date: string;
            facility_id: string | null;
        };
    };
    permissions: string[];
    routes: Record<string, string>;
    meta: Record<string, string>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Maintenance Requests',
        href: maintenanceIndex().url,
    },
];

const { can } = usePermissions();

const filterStartDate = ref(props.data.filters.start_date || '');
const filterEndDate = ref(props.data.filters.end_date || '');
const filterFacilityId = ref(props.data.filters.facility_id ? String(props.data.filters.facility_id) : 'all');

const currencyFormat = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0,
});

const allRequests = computed(() => props.data.groups.flatMap((group) => group.requests));

const metrics = computed(() => {
    const requests = allRequests.value;

    return {
        total: requests.length,
        submitted: requests.filter((request) => ['submitted', 'pending'].includes(request.status)).length,
        approved: requests.filter((request) => ['approved', 'assigned', 'work_order_created'].includes(request.status)).length,
        inProgress: requests.filter((request) => request.status === 'in_progress').length,
        completedPendingPayment: requests.filter((request) => request.status === 'completed_pending_payment').length,
        paid: requests.filter((request) => request.status === 'paid').length,
        closed: requests.filter((request) => ['closed', 'completed'].includes(request.status)).length,
    };
});

const applyFilters = () => {
    router.get(
        maintenanceIndex().url,
        {
            start_date: filterStartDate.value || undefined,
            end_date: filterEndDate.value || undefined,
            facility_id: filterFacilityId.value === 'all' ? undefined : filterFacilityId.value,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const toDateString = (value: Date) => {
    const year = value.getFullYear();
    const month = String(value.getMonth() + 1).padStart(2, '0');
    const day = String(value.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const getCurrentAndUpcomingWeekRange = () => {
    const today = new Date();
    const dayOfWeek = today.getDay();
    const mondayOffset = (dayOfWeek + 6) % 7;

    const start = new Date(today);
    start.setHours(0, 0, 0, 0);
    start.setDate(start.getDate() - mondayOffset);

    const end = new Date(start);
    end.setDate(end.getDate() + 13);

    return {
        start: toDateString(start),
        end: toDateString(end),
    };
};

const applyNeedsActionNow = () => {
    const range = getCurrentAndUpcomingWeekRange();

    filterStartDate.value = range.start;
    filterEndDate.value = range.end;

    router.get(
        maintenanceIndex().url,
        {
            start_date: range.start,
            end_date: range.end,
            facility_id: filterFacilityId.value === 'all' ? undefined : filterFacilityId.value,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const clearFilters = () => {
    router.get(maintenanceIndex().url, {}, { preserveState: true, preserveScroll: true });
};

const selectedEligibleRequestIds = (table: any) => {
    const selectedRows = table?.getSelectedRowModel?.().rows ?? [];

    return selectedRows
        .map((row: { original: MaintenanceRequest }) => row.original)
        .filter((request: MaintenanceRequest) => ['submitted', 'pending'].includes(request.status) && !request.has_work_order)
        .map((request: MaintenanceRequest) => request.id);
};

const openBulkReview = (table: any) => {
    const requestIds = selectedEligibleRequestIds(table);
    if (!requestIds.length) {
        return;
    }

    router.get(
        workOrdersBulkCreate().url,
        { request_ids: requestIds, intent: 'review' },
        { preserveState: false, preserveScroll: true },
    );
};

const openBulkCreateWorkOrders = (table: any) => {
    const requestIds = selectedEligibleRequestIds(table);
    if (!requestIds.length) {
        return;
    }

    router.get(
        workOrdersBulkCreate().url,
        { request_ids: requestIds, intent: 'create' },
        { preserveState: false, preserveScroll: true },
    );
};

const dateRangeLabel = computed(() => `${filterStartDate.value} to ${filterEndDate.value}`);

const statusLabel = (status: string) => {
    if (props.data.is_facility_manager) {
        if (['submitted', 'pending'].includes(status)) return 'Needs Review';
        if (['approved', 'assigned', 'work_order_created', 'in_progress'].includes(status)) return 'In Progress';
        if (['completed_pending_payment', 'paid', 'closed', 'completed'].includes(status)) return 'Done';
        if (status === 'rejected') return 'Rejected';
        if (status === 'cancelled') return 'Cancelled';
    }

    if (status === 'submitted') return 'Submitted';
    if (status === 'approved') return 'Approved';
    if (status === 'rejected') return 'Rejected';
    if (status === 'assigned') return 'Assigned';
    if (status === 'work_order_created') return 'Work Order Created';
    if (status === 'in_progress') return 'In Progress';
    if (status === 'completed_pending_payment') return 'Completed - Pending Payment';
    if (status === 'paid') return 'Paid';
    if (status === 'closed') return 'Closed';
    if (status === 'completed') return 'Completed (Legacy)';
    if (status === 'cancelled') return 'Cancelled';
    return 'Pending (Legacy)';
};

const statusClass = (status: string) => {
    if (props.data.is_facility_manager) {
        if (['submitted', 'pending'].includes(status)) return 'bg-amber-500/10 text-amber-700 border-amber-500/20';
        if (['approved', 'assigned', 'work_order_created', 'in_progress'].includes(status)) return 'bg-blue-500/10 text-blue-600 border-blue-500/20';
        if (['completed_pending_payment', 'paid', 'closed', 'completed'].includes(status)) return 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20';
        if (status === 'rejected' || status === 'cancelled') return 'bg-rose-500/10 text-rose-600 border-rose-500/20';
    }

    if (status === 'rejected' || status === 'cancelled') return 'bg-rose-500/10 text-rose-600 border-rose-500/20';
    if (status === 'paid') return 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20';
    if (status === 'closed' || status === 'completed') return 'bg-slate-500/10 text-slate-700 border-slate-500/20';
    if (status === 'completed_pending_payment') return 'bg-orange-500/10 text-orange-600 border-orange-500/20';
    if (status === 'in_progress') return 'bg-blue-500/10 text-blue-600 border-blue-500/20';
    if (status === 'approved' || status === 'assigned' || status === 'work_order_created') return 'bg-indigo-500/10 text-indigo-600 border-indigo-500/20';
    return 'bg-amber-500/10 text-amber-700 border-amber-500/20';
};

const columns = computed<ColumnDef<MaintenanceRequest>[]>(() => {
    const base: ColumnDef<MaintenanceRequest>[] = [
        {
            id: 'created_at',
            accessorFn: (row) => row.created_at ?? '',
            header: 'Date',
            cell: ({ row }) => row.original.created_at ?? '-',
            enableHiding: false,
        },
        {
            id: 'facility',
            accessorFn: (row) => row.facility?.name ?? '',
            header: 'Facility',
            cell: ({ row }) => h('span', { class: 'font-medium text-[11px]' }, row.original.facility?.name ?? '-'),
        },
        {
            id: 'request_type',
            accessorFn: (row) => row.request_type_name ?? '',
            header: 'Request type',
            cell: ({ row }) => row.original.request_type_name ?? '-',
        },
        {
            id: 'description',
            accessorFn: (row) => row.description ?? '',
            header: 'Description',
            cell: ({ row }) => h('span', { class: 'text-[11px] text-muted-foreground' }, row.original.description ?? '-'),
        },
        {
            id: 'status',
            accessorFn: (row) => row.status ?? '',
            header: 'Status',
            cell: ({ row }) => {
                const chips = [
                    h(
                        Badge,
                        {
                            variant: 'outline',
                            class: `rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider ${statusClass(row.original.status)}`,
                        },
                        () => statusLabel(row.original.status),
                    ),
                ];

                if (can('work_orders.view')) {
                    chips.push(
                        h(
                            Badge,
                            {
                                variant: 'outline',
                                class: row.original.has_work_order
                                    ? 'rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-600 border-emerald-500/20'
                                    : 'rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider bg-amber-500/10 text-amber-700 border-amber-500/20',
                            },
                            () => (row.original.has_work_order ? 'WO linked' : 'WO needed'),
                        ),
                    );
                }

                return h('div', { class: 'flex flex-wrap items-center gap-1.5' }, chips);
            },
        },
        {
            id: 'cost',
            accessorFn: (row) => row.cost ?? 0,
            header: 'Cost',
            cell: ({ row }) =>
                row.original.cost !== null && row.original.cost !== undefined
                    ? currencyFormat.format(Number(row.original.cost))
                    : '-',
        },
    ];

    if (props.data.show_facility_manager_name) {
        base.splice(2, 0, {
            id: 'manager',
            accessorFn: (row) => row.facility_manager_name ?? '',
            header: 'Facility manager',
            cell: ({ row }) => row.original.facility_manager_name ?? '-',
        });
    }

    if (props.data.show_requester_name) {
        base.splice(3, 0, {
            id: 'requested_by',
            accessorFn: (row) => row.requested_by_name ?? '',
            header: 'Requested by',
            cell: ({ row }) => row.original.requested_by_name ?? '-',
        });
    }

    base.push({
        id: 'actions',
        header: '',
        cell: ({ row }) =>
            h('div', { class: 'flex justify-end' }, [
                h(ButtonGroup, { class: 'shadow-sm' }, () => [
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
                                () => h(Link, { href: show(row.original.id).url }, () => h(Eye, { class: 'h-3.5 w-3.5' })),
                            ),
                        ),
                        h(TooltipContent, { side: 'top' }, () => 'View'),
                    ]),
                    (can('maintenance.update') || can('maintenance_requests.update')) && ['submitted', 'pending'].includes(row.original.status)
                        ? h(Tooltip, {}, () => [
                            h(TooltipTrigger, { asChild: true }, () =>
                                h(
                                    Button,
                                    {
                                        variant: 'ghost',
                                        size: 'icon-sm',
                                        class: 'rounded-none border-l border-border/60 shadow-none',
                                        asChild: true,
                                    },
                                    () => h(Link, { href: edit(row.original.id).url }, () => h(Pencil, { class: 'h-3.5 w-3.5' })),
                                ),
                            ),
                            h(TooltipContent, { side: 'top' }, () => 'Edit'),
                        ])
                        : null,
                    (can('maintenance.review') || can('work_orders.create')) && ['submitted', 'pending'].includes(row.original.status)
                        ? h(Tooltip, {}, () => [
                            h(TooltipTrigger, { asChild: true }, () =>
                                h(
                                    Button,
                                    {
                                        variant: 'ghost',
                                        size: 'icon-sm',
                                        class: 'rounded-none border-l border-border/60 text-amber-600 hover:text-amber-700 hover:bg-amber-500/10 shadow-none',
                                        asChild: true,
                                    },
                                    () => h(Link, { href: show(row.original.id).url }, () => h(ClipboardCheck, { class: 'h-3.5 w-3.5' })),
                                ),
                            ),
                            h(TooltipContent, { side: 'top' }, () => 'Review'),
                        ])
                        : null,
                    can('work_orders.view') && row.original.latest_work_order_id
                        ? h(Tooltip, {}, () => [
                            h(TooltipTrigger, { asChild: true }, () =>
                                h(
                                    Button,
                                    {
                                        variant: 'ghost',
                                        size: 'icon-sm',
                                        class: 'rounded-none border-l border-border/60 shadow-none',
                                        asChild: true,
                                    },
                                    () => h(Link, { href: workOrderShow(row.original.latest_work_order_id as number).url }, () => h(ClipboardList, { class: 'h-3.5 w-3.5' })),
                                ),
                            ),
                            h(TooltipContent, { side: 'top' }, () => 'Work order'),
                        ])
                        : null,
                    can('maintenance.close') && row.original.status === 'paid'
                        ? h(Tooltip, {}, () => [
                            h(TooltipTrigger, { asChild: true }, () =>
                                h(
                                    Button,
                                    {
                                        variant: 'ghost',
                                        size: 'icon-sm',
                                        class: 'rounded-none border-l border-border/60 text-slate-600 hover:text-slate-700 hover:bg-slate-500/10 shadow-none',
                                        asChild: true,
                                    },
                                    () => h(Link, { href: close(row.original.id).url, method: 'post', as: 'button' }, () => h(CheckCircle2, { class: 'h-3.5 w-3.5' })),
                                ),
                            ),
                            h(TooltipContent, { side: 'top' }, () => 'Close'),
                        ])
                        : null,
                ]),
            ]),
        enableSorting: false,
        enableHiding: false,
    });

    return base;
});
</script>

<template>
    <Head title="Maintenance Requests" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-6 lg:p-8">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="font-display text-3xl font-semibold tracking-tight text-foreground">Maintenance requests</h1>
                    <p class="text-sm text-muted-foreground">Track and resolve maintenance requests by week.</p>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        v-if="can('maintenance.create') || can('maintenance_requests.create')"
                        size="icon"
                        as-child
                        class="h-9 w-9 rounded-lg"
                    >
                        <Link :href="create().url" aria-label="New request">
                            <Plus class="h-4 w-4" />
                        </Link>
                    </Button>
                </div>
            </div>

            <div class="rounded-xl border border-border/60 bg-card/60 p-3 backdrop-blur">
                <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_auto] lg:items-end">
                    <div class="grid gap-2 sm:grid-cols-2">
                        <DatePicker v-model="filterStartDate" class="h-9 w-full" placeholder="Start date" />
                        <DatePicker v-model="filterEndDate" class="h-9 w-full" placeholder="End date" />
                    </div>
                    <Select v-model="filterFacilityId">
                        <SelectTrigger class="h-9 w-full">
                            <SelectValue placeholder="All facilities" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All facilities</SelectItem>
                            <SelectItem v-for="facility in data.facilities" :key="facility.id" :value="String(facility.id)">
                                {{ facility.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <div class="flex items-center gap-2">
                        <Button size="sm" variant="secondary" class="h-9 px-4" @click="applyNeedsActionNow">Needs action now</Button>
                        <Button size="sm" class="h-9 px-4" @click="applyFilters">Apply filters</Button>
                        <Button size="sm" variant="ghost" class="h-9 px-3" @click="clearFilters">Reset</Button>
                    </div>
                </div>
                <p class="mt-3 text-xs text-muted-foreground">
                    Default range shows the current and upcoming week: {{ dateRangeLabel }}.
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Priority order surfaces pending/submitted requests without work orders first.
                </p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StatsCard title="Total requests" :value="metrics.total" :icon="ClipboardCheck" accent-color="amber" description="All in range" />
                <StatsCard title="Submitted" :value="metrics.submitted" :icon="Timer" accent-color="amber" description="Awaiting approval" />
                <StatsCard title="Approved Queue" :value="metrics.approved" :icon="ClipboardList" accent-color="blue" description="Ready for work order" />
                <StatsCard title="In progress" :value="metrics.inProgress" :icon="Wrench" accent-color="blue" description="Being resolved" />
                <StatsCard title="Pending payment" :value="metrics.completedPendingPayment" :icon="CheckCircle2" accent-color="amber" description="Completed work, unpaid" />
                <StatsCard title="Paid" :value="metrics.paid" :icon="CheckCircle2" accent-color="emerald" description="Ready to close" />
                <StatsCard title="Closed" :value="metrics.closed" :icon="CheckCircle2" accent-color="emerald" description="Finalized" />
            </div>

            <div class="space-y-4">
                <Accordion v-if="data.groups.length > 0" type="multiple" :default-value="data.groups.map((g) => g.week_start)">
                    <AccordionItem
                        v-for="group in data.groups"
                        :key="group.week_start"
                        :value="group.week_start"
                        class="mb-3 overflow-hidden rounded-xl border border-border/70 bg-card"
                    >
                        <AccordionTrigger class="px-4 py-3 hover:no-underline">
                            <div class="flex items-center gap-3">
                                <p class="font-display text-sm font-semibold tracking-wide">{{ group.week_label }}</p>
                                <Badge variant="outline" class="text-[10px]">{{ group.requests.length }} requests</Badge>
                            </div>
                        </AccordionTrigger>
                        <AccordionContent class="px-4 pb-4">
                            <DataTable
                                :data="group.requests"
                                :columns="columns"
                                :show-search="false"
                                :show-selection-summary="false"
                                :enable-row-selection="can('work_orders.create')"
                            >
                                <template v-if="can('work_orders.create')" #actions="{ table }">
                                    <div class="flex items-center gap-2">
                                        <Button
                                            size="sm"
                                            variant="secondary"
                                            class="h-8 px-3 text-[11px] font-semibold uppercase tracking-wide"
                                            :disabled="selectedEligibleRequestIds(table).length === 0"
                                            @click="openBulkReview(table)"
                                        >
                                            Bulk review selected
                                        </Button>
                                        <Button
                                            size="sm"
                                            class="h-8 px-3 text-[11px] font-semibold uppercase tracking-wide"
                                            :disabled="selectedEligibleRequestIds(table).length === 0"
                                            @click="openBulkCreateWorkOrders(table)"
                                        >
                                            Bulk create work orders
                                        </Button>
                                    </div>
                                </template>
                            </DataTable>
                        </AccordionContent>
                    </AccordionItem>
                </Accordion>

                <Card v-else class="border-border bg-card shadow-sm">
                    <CardContent class="py-12 text-center">
                        <ClipboardList class="mx-auto mb-4 h-12 w-12 text-muted-foreground/20" />
                        <p class="text-sm font-bold text-muted-foreground">No maintenance requests found</p>
                        <p class="mt-1 text-xs text-muted-foreground/60">Try adjusting your filters.</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
