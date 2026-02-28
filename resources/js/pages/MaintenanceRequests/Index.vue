<script setup lang="ts">
import StatsCard from '@/components/StatsCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import DataTable from '@/components/data-table/DataTable.vue';
import { DatePicker } from '@/components/ui/date-picker';
import AppLayout from '@/layouts/AppLayout.vue';
import { approve, close, create, edit, index as maintenanceIndex, reject, show } from '@/routes/maintenance';
import { create as workOrderCreate, show as workOrderShow } from '@/routes/work-orders';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';
import type { ColumnDef } from '@tanstack/vue-table';
import { computed, h, ref } from 'vue';
import { CheckCircle2, ClipboardCheck, ClipboardList, Plus, Timer, Wrench } from 'lucide-vue-next';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

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

const clearFilters = () => {
    router.get(maintenanceIndex().url, {}, { preserveState: true, preserveScroll: true });
};

const statusLabel = (status: string) => {
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
            cell: ({ row }) => row.original.created_at ?? '—',
            enableHiding: false,
        },
        {
            id: 'facility',
            accessorFn: (row) => row.facility?.name ?? '',
            header: 'Facility',
            cell: ({ row }) => h('span', { class: 'font-medium text-[11px]' }, row.original.facility?.name ?? '—'),
        },
        {
            id: 'request_type',
            accessorFn: (row) => row.request_type_name ?? '',
            header: 'Request type',
            cell: ({ row }) => row.original.request_type_name ?? '—',
        },
        {
            id: 'description',
            accessorFn: (row) => row.description ?? '',
            header: 'Description',
            cell: ({ row }) => h('span', { class: 'text-[11px] text-muted-foreground' }, row.original.description ?? '—'),
        },
        {
            id: 'status',
            accessorFn: (row) => row.status ?? '',
            header: 'Status',
            cell: ({ row }) =>
                h(
                    Badge,
                    {
                        variant: 'outline',
                        class: `rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider ${statusClass(row.original.status)}`,
                    },
                    () => statusLabel(row.original.status),
                ),
        },
        {
            id: 'cost',
            accessorFn: (row) => row.cost ?? 0,
            header: 'Cost',
            cell: ({ row }) =>
                row.original.cost !== null && row.original.cost !== undefined
                    ? currencyFormat.format(Number(row.original.cost))
                    : '—',
        },
    ];

    if (props.data.show_facility_manager_name) {
        base.splice(2, 0, {
            id: 'manager',
            accessorFn: (row) => row.facility_manager_name ?? '',
            header: 'Facility manager',
            cell: ({ row }) => row.original.facility_manager_name ?? '—',
        });
    }

    if (props.data.show_requester_name) {
        base.splice(3, 0, {
            id: 'requested_by',
            accessorFn: (row) => row.requested_by_name ?? '',
            header: 'Requested by',
            cell: ({ row }) => row.original.requested_by_name ?? '—',
        });
    }

    base.push({
        id: 'actions',
        header: '',
        cell: ({ row }) =>
            h('div', { class: 'flex flex-wrap justify-end gap-2' }, [
                h(Button, { variant: 'outline', size: 'sm', class: 'h-7 px-3 text-[10px] font-bold uppercase', asChild: true }, () =>
                    h(Link, { href: show(row.original.id).url }, () => 'View'),
                ),
                (can('maintenance.update') || can('maintenance_requests.update')) && ['submitted', 'pending'].includes(row.original.status)
                    ? h(Button, { variant: 'outline', size: 'sm', class: 'h-7 px-3 text-[10px] font-bold uppercase', asChild: true }, () =>
                        h(Link, { href: edit(row.original.id).url }, () => 'Edit'),
                    )
                    : null,
                can('maintenance.review') && ['submitted', 'pending'].includes(row.original.status)
                    ? h(Button, { variant: 'secondary', size: 'sm', class: 'h-7 px-3 text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/20', asChild: true }, () =>
                        h(Link, { href: approve(row.original.id).url, method: 'post', as: 'button' }, () => 'Approve'),
                    )
                    : null,
                can('maintenance.review') && ['submitted', 'pending'].includes(row.original.status)
                    ? h(Button, { variant: 'secondary', size: 'sm', class: 'h-7 px-3 text-[10px] font-bold uppercase bg-rose-500/10 text-rose-600 hover:bg-rose-500/20', asChild: true }, () =>
                        h(Link, { href: reject(row.original.id).url, method: 'post', as: 'button' }, () => 'Reject'),
                    )
                    : null,
                can('work_orders.create') && !row.original.latest_work_order_id && ['approved', 'assigned', 'work_order_created', 'in_progress'].includes(row.original.status)
                    ? h(Button, { size: 'sm', class: 'h-7 px-3 text-[10px] font-bold uppercase', asChild: true }, () =>
                        h(
                            Link,
                            {
                                href: workOrderCreate({
                                    query: { maintenance_request_id: row.original.id },
                                }).url,
                            },
                            () => 'Create work order',
                        ),
                    )
                    : null,
                can('work_orders.view') && row.original.latest_work_order_id
                    ? h(Button, { variant: 'outline', size: 'sm', class: 'h-7 px-3 text-[10px] font-bold uppercase', asChild: true }, () =>
                        h(Link, { href: workOrderShow(row.original.latest_work_order_id as number).url }, () => 'View work order'),
                    )
                    : null,
                can('maintenance.close') && row.original.status === 'paid'
                    ? h(Button, { variant: 'secondary', size: 'sm', class: 'h-7 px-3 text-[10px] font-bold uppercase bg-slate-500/10 text-slate-700 hover:bg-slate-500/20', asChild: true }, () =>
                        h(Link, { href: close(row.original.id).url, method: 'post', as: 'button' }, () => 'Close'),
                    )
                    : null,
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
                <Button
                    v-if="can('maintenance.create') || can('maintenance_requests.create')"
                    size="sm"
                    as-child
                    class="h-9 rounded-lg px-3 text-[11px] font-semibold uppercase tracking-wide"
                >
                    <Link :href="create().url">
                        <Plus class="mr-1.5 h-3.5 w-3.5" />
                        New request
                    </Link>
                </Button>
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
                        <Button size="sm" class="h-9 px-4" @click="applyFilters">Apply filters</Button>
                        <Button size="sm" variant="ghost" class="h-9 px-3" @click="clearFilters">Reset</Button>
                    </div>
                </div>
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
                                :enable-row-selection="false"
                            />
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
