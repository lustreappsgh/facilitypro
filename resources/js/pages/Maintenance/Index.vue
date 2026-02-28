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
import {
    complete,
    create,
    edit,
    index as maintenanceIndex,
    show,
} from '@/routes/maintenance';
import { create as workOrderCreate, show as workOrderShow } from '@/routes/work-orders';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';
import { Search, Plus } from 'lucide-vue-next';
import type { ColumnDef } from '@tanstack/vue-table';
import { computed, h, onMounted, ref } from 'vue';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { endOfWeek, format, parseISO, startOfWeek, subMonths } from 'date-fns';

const filtersVisible = ref(false);

interface Facility {
    id: number;
    name: string;
}

interface RequestedBy {
    id: number;
    name: string;
}

interface RequestType {
    id: number;
    name: string;
}

interface WorkOrderSummary {
    id: number;
}

interface MaintenanceRequest {
    id: number;
    status: string;
    cost?: number | null;
    description?: string | null;
    created_at?: string | null;
    facility?: Facility | null;
    requestedBy?: RequestedBy | null;
    requested_by?: RequestedBy | null;
    requestType?: RequestType | null;
    request_type?: RequestType | null;
    workOrders?: WorkOrderSummary[] | null;
    work_orders?: WorkOrderSummary[] | null;
}

interface StatusOption {
    value: string;
    label: string;
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

interface Filters {
    search?: string | null;
    status?: string | null;
    request_type?: string | null;
    start_date?: string | null;
    end_date?: string | null;
}

interface Props {
    requests: PaginatedRequests;
    statuses: StatusOption[];
    requestTypes: RequestType[];
    filters: Filters;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Maintenance Requests',
        href: maintenanceIndex().url,
    },
];

const { can } = usePermissions();

const currencyFormat = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
});

const searchFilter = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');
const requestTypeFilter = ref(props.filters.request_type ?? '');
const startDateFilter = ref(props.filters.start_date ?? '');
const endDateFilter = ref(props.filters.end_date ?? '');

const allowedStatuses = computed(() =>
    props.statuses.filter((status) =>
        ['pending', 'in_progress', 'completed', 'cancelled'].includes(status.value),
    ),
);

const selectedStatusLabel = computed(() => {
    const match = allowedStatuses.value.find(
        (status) => status.value === statusFilter.value,
    );
    return match?.label ?? 'All statuses';
});

const selectedRequestTypeLabel = computed(() => {
    const match = props.requestTypes.find(
        (type) => String(type.id) === requestTypeFilter.value,
    );
    return match?.name ?? 'All request types';
});

const statusBadgeClass = (status: string) => {
    if (status === 'completed') {
        return 'bg-success-subtle text-success';
    }

    if (status === 'in_progress') {
        return 'bg-primary/10 text-primary';
    }

    if (status === 'cancelled') {
        return 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300';
    }

    return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-300';
};

const requestTypeName = (request: MaintenanceRequest) =>
    request.requestType?.name ?? request.request_type?.name ?? '-';

const requestedByName = (request: MaintenanceRequest) =>
    request.requestedBy?.name ?? request.requested_by?.name ?? '-';

const requestWorkOrders = (request: MaintenanceRequest) =>
    request.workOrders ?? request.work_orders ?? [];

const latestWorkOrderId = (request: MaintenanceRequest) =>
    requestWorkOrders(request)?.[0]?.id ?? null;

const groupedByWeek = computed(() => {
    const groups = new Map<string, MaintenanceRequest[]>();

    props.requests.data.forEach((request) => {
        const dateValue = request.created_at ? parseISO(request.created_at) : null;
        const weekStart = dateValue ? startOfWeek(dateValue, { weekStartsOn: 1 }) : null;
        const weekEnd = dateValue ? endOfWeek(dateValue, { weekStartsOn: 1 }) : null;
        const label = dateValue
            ? `${format(weekStart, 'MMM d')} - ${format(weekEnd, 'MMM d, yyyy')}`
            : 'Unknown week';

        if (!groups.has(label)) {
            groups.set(label, []);
        }
        groups.get(label)!.push(request);
    });

    return Array.from(groups.entries()).map(([week, items]) => ({
        week,
        items,
        count: items.length,
    }));
});

onMounted(() => {
    if (startDateFilter.value || endDateFilter.value) {
        return;
    }
    if (!statusFilter.value) {
        statusFilter.value = 'pending';
    }
    const now = new Date();
    const monthStart = subMonths(now, 1);
    startDateFilter.value = format(monthStart, 'yyyy-MM-dd');
    endDateFilter.value = format(now, 'yyyy-MM-dd');

    router.get(maintenanceIndex().url, {
        search: searchFilter.value || undefined,
        status: statusFilter.value || undefined,
        request_type: requestTypeFilter.value || undefined,
        start_date: startDateFilter.value,
        end_date: endDateFilter.value,
    }, { preserveState: true, preserveScroll: true, replace: true });
});

const columns: ColumnDef<MaintenanceRequest>[] = [
    {
        id: 'facility',
        accessorFn: (row) => row.facility?.name ?? '',
        header: 'Facility',
        cell: ({ row }) =>
            h('span', { class: 'font-medium' }, row.original.facility?.name ?? '-'),
        enableHiding: false,
    },
    {
        id: 'requestType',
        accessorFn: (row) => row.requestType?.name ?? row.request_type?.name ?? '',
        header: 'Request type',
        cell: ({ row }) => requestTypeName(row.original),
    },
    {
        id: 'description',
        accessorFn: (row) => row.description ?? '',
        header: 'Description',
        cell: ({ row }) =>
            h(
                'p',
                { class: 'max-w-[240px] truncate text-sm text-muted-foreground' },
                row.original.description ?? 'No description provided.',
            ),
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
        id: 'requestedBy',
        accessorFn: (row) => row.requestedBy?.name ?? row.requested_by?.name ?? '',
        header: 'Requested by',
        cell: ({ row }) => requestedByName(row.original),
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
            h('div', { class: 'flex flex-wrap gap-2' }, [
                h(Button, { variant: 'outline', size: 'sm', asChild: true }, () =>
                    h(Link, { href: show(row.original).url }, () => 'View'),
                ),
                can('maintenance.update') && row.original.status === 'pending'
                    ? h(Button, { variant: 'secondary', size: 'sm', asChild: true }, () =>
                        h(Link, { href: edit(row.original).url }, () => 'Edit'),
                    )
                    : null,
                can('work_orders.create') && !latestWorkOrderId(row.original)
                    ? h(Button, { size: 'sm', asChild: true }, () =>
                        h(
                            Link,
                            {
                                href: workOrderCreate({
                                    query: {
                                        maintenance_request_id: row.original.id,
                                    },
                                }).url,
                            },
                            () => 'Create work order',
                        ),
                    )
                    : null,
                can('work_orders.view') && latestWorkOrderId(row.original)
                    ? h(Button, { variant: 'outline', size: 'sm', asChild: true }, () =>
                        h(
                            Link,
                            { href: workOrderShow(latestWorkOrderId(row.original)!).url },
                            () => 'View work order',
                        ),
                    )
                    : null,
                can('maintenance.complete') && row.original.status === 'in_progress'
                    ? h(Button, { variant: 'secondary', size: 'sm', asChild: true }, () =>
                        h(Link, { href: complete(row.original).url, method: 'post', as: 'button' }, () => 'Complete'),
                    )
                    : null,
            ]),
        enableSorting: false,
        enableHiding: false,
    },
];
</script>

<template>

    <Head title="Maintenance Requests" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-8 p-6 lg:p-10">
            <PageHeader title="Maintenance requests" subtitle="Review, prioritize, and resolve maintenance requests."
                :action-label="can('maintenance.create') ? 'New request' : ''"
                :action-href="can('maintenance.create') ? create().url : ''" :action-icon="Plus"
                :show-filters-toggle="true" :filters-visible="filtersVisible"
                @toggle-filters="filtersVisible = !filtersVisible" />

            <div class="space-y-4">
                <form v-if="filtersVisible" :action="maintenanceIndex().url" method="get"
                    class="flex flex-wrap items-center gap-3 rounded-xl border border-border/60 bg-card p-4">
                            <div class="relative min-w-[220px] flex-1">
                                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input v-model="searchFilter" name="search" class="pl-9"
                                    placeholder="Search facility or description" />
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
                                    <DropdownMenuItem v-for="status in allowedStatuses" :key="status.value"
                                        @click="statusFilter = status.value">
                                        {{ status.label }}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>

                            <input type="hidden" name="request_type" :value="requestTypeFilter" />
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="outline" class="min-w-[180px] justify-between">
                                        {{ selectedRequestTypeLabel }}
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-64">
                                    <DropdownMenuLabel>Request type</DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem @click="requestTypeFilter = ''">
                                        All types
                                    </DropdownMenuItem>
                                    <DropdownMenuItem v-for="type in requestTypes" :key="type.id"
                                        @click="requestTypeFilter = String(type.id)">
                                        {{ type.name }}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>

                            <DatePicker v-model="startDateFilter" name="start_date" class="min-w-[150px]"  />
                            <DatePicker v-model="endDateFilter" name="end_date" class="min-w-[150px]"  />

                            <div class="flex items-center gap-2">
                                <Button type="submit" class="whitespace-nowrap">
                                    Apply filters
                                </Button>
                                <Button variant="ghost" as-child>
                                    <Link :href="maintenanceIndex().url">Reset</Link>
                                </Button>
                            </div>
                </form>

                <Accordion type="multiple" class="space-y-4">
                    <AccordionItem
                        v-for="group in groupedByWeek"
                        :key="group.week"
                        :value="group.week"
                        class="border-none"
                    >
                        <AccordionTrigger
                            class="flex w-full items-center justify-between rounded-2xl border border-border bg-card/60 px-6 py-4 hover:bg-card hover:no-underline transition-all"
                        >
                            <div class="text-left">
                                <p class="font-black text-[13px] uppercase tracking-tight text-foreground">
                                    {{ group.week }}
                                </p>
                                <p class="text-[10px] font-bold text-muted-foreground/60 uppercase tracking-widest">
                                    {{ group.count }} {{ group.count === 1 ? 'request' : 'requests' }}
                                </p>
                            </div>
                        </AccordionTrigger>
                        <AccordionContent class="pb-6">
                            <DataTable
                                :data="group.items"
                                :columns="columns"
                                :show-search="false"
                                :show-selection-summary="false"
                                class="portfolio-table"
                            />
                        </AccordionContent>
                    </AccordionItem>
                </Accordion>
            </div>


            <PaginationLinks :links="requests.links" />
        </div>
    </AppLayout>
</template>
