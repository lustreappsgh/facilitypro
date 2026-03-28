<script setup lang="ts">
import StatsCard from '@/components/StatsCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import DataTable from '@/components/data-table/DataTable.vue';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import {
    NativeSelect,
    NativeSelectOption,
} from '@/components/ui/native-select';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import { create, index as inspectionsIndex, show } from '@/routes/inspections';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';
import { useDateFormat } from '@/composables/useDateFormat';
import type { ColumnDef } from '@tanstack/vue-table';
import { computed, h, onMounted, ref, watch } from 'vue';
import { AlertTriangle, CheckCircle2, ClipboardCheck, ClipboardList, Eye, Plus, TrendingUp } from 'lucide-vue-next';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { isSameMonth } from 'date-fns';

interface Facility {
    id: number;
    name: string;
}

interface UserOption {
    id: number;
    name: string;
    email?: string | null;
}

interface Inspection {
    id: number;
    inspection_date: string | null;
    condition: string;
    comments?: string | null;
    facility?: Facility | null;
    inspector_name?: string | null;
}

interface InspectionGroup {
    week_start: string;
    week_label: string;
    inspections: Inspection[];
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
        groups: InspectionGroup[];
        weeks_by_year_month: WeeksByYearMonth[];
        facilities: Facility[];
        show_inspector: boolean;
        users?: UserOption[];
        filters: {
            start_date: string;
            end_date: string;
            search?: string | null;
            facility_id: string | null;
            user_id?: string | null;
        };
    };
    permissions: string[];
    routes: Record<string, string>;
    meta: Record<string, string>;
}

const props = defineProps<Props>();

const { parseDate } = useDateFormat();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inspections',
        href: inspectionsIndex().url,
    },
];

const { can } = usePermissions();

const toDateString = (value: Date) => {
    const year = value.getFullYear();
    const month = String(value.getMonth() + 1).padStart(2, '0');
    const day = String(value.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const getCurrentWeekPlusLastMonthRange = () => {
    const today = new Date();
    const dayOfWeek = today.getDay();
    const sundayOffset = dayOfWeek;

    const start = new Date(today);
    start.setHours(0, 0, 0, 0);
    start.setDate(start.getDate() - sundayOffset);

    const end = new Date(start);
    end.setDate(end.getDate() + 13);

    return {
        start: toDateString(start),
        end: toDateString(end),
    };
};

const defaultRange = getCurrentWeekPlusLastMonthRange();

const filterStartDate = ref(props.data.filters.start_date || defaultRange.start);
const filterEndDate = ref(props.data.filters.end_date || defaultRange.end);
const searchFilter = ref(props.data.filters.search ?? '');
const filterFacilityId = ref(
    props.data.filters.facility_id ? String(props.data.filters.facility_id) : '',
);
const filterUserId = ref(props.data.filters.user_id ? String(props.data.filters.user_id) : 'all');
let searchDebounceTimer: number | null = null;

const allInspections = computed(() => props.data.groups.flatMap((group) => group.inspections));

const metrics = computed(() => {
    const inspections = allInspections.value;
    const total = inspections.length;
    const thisMonth = inspections.filter((inspection) => {
        const dateValue = parseDate(inspection.inspection_date);
        return dateValue ? isSameMonth(dateValue, new Date()) : false;
    }).length;
    const goodCondition = inspections.filter((inspection) => inspection.condition === 'Good').length;
    const needsAttention = inspections.filter((inspection) => ['Bad', 'Warning', 'Critical'].includes(inspection.condition)).length;

    return {
        total,
        thisMonth,
        goodCondition,
        needsAttention,
    };
});

const applyFilters = () => {
    router.get(
        inspectionsIndex().url,
        {
            start_date: filterStartDate.value || undefined,
            end_date: filterEndDate.value || undefined,
            search: searchFilter.value || undefined,
            facility_id: filterFacilityId.value || undefined,
            user_id: filterUserId.value === 'all' ? undefined : filterUserId.value,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const clearFilters = () => {
    filterStartDate.value = defaultRange.start;
    filterEndDate.value = defaultRange.end;
    searchFilter.value = '';
    filterFacilityId.value = '';
    filterUserId.value = 'all';

    router.get(
        inspectionsIndex().url,
        {
            start_date: defaultRange.start,
            end_date: defaultRange.end,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const dateRangeLabel = computed(() => `${filterStartDate.value} to ${filterEndDate.value}`);

watch(searchFilter, () => {
    if (searchDebounceTimer !== null) {
        window.clearTimeout(searchDebounceTimer);
    }

    searchDebounceTimer = window.setTimeout(() => {
        applyFilters();
    }, 350);
});

const conditionClass = (condition: string) => {
    if (condition === 'Good') return 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20';
    if (condition === 'Warning') return 'bg-amber-500/10 text-amber-600 border-amber-500/20';
    if (condition === 'Critical' || condition === 'Bad') return 'bg-rose-500/10 text-rose-600 border-rose-500/20';
    return 'bg-muted text-muted-foreground border-border';
};

const columns = computed<ColumnDef<Inspection>[]>(() => {
    const base: ColumnDef<Inspection>[] = [
        {
            id: 'date',
            accessorFn: (row) => row.inspection_date ?? '',
            header: 'Date',
            cell: ({ row }) => row.original.inspection_date ?? '-',
            enableHiding: false,
        },
        {
            id: 'facility',
            accessorFn: (row) => row.facility?.name ?? '',
            header: 'Facility',
            cell: ({ row }) => row.original.facility?.name ?? '-',
        },
        {
            id: 'condition',
            accessorFn: (row) => row.condition ?? '',
            header: 'Condition',
            cell: ({ row }) =>
                h(
                    Badge,
                    {
                        variant: 'outline',
                        class: `rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider ${conditionClass(row.original.condition)}`,
                    },
                    () => row.original.condition,
                ),
        },
    ];

    if (props.data.show_inspector) {
        base.splice(2, 0, {
            id: 'inspector',
            accessorFn: (row) => row.inspector_name ?? '',
            header: 'Inspector',
            cell: ({ row }) => row.original.inspector_name ?? '-',
        });
    }

    base.push({
        id: 'actions',
        header: '',
        cell: ({ row }) =>
            h(Tooltip, {}, () => [
                h(TooltipTrigger, { asChild: true }, () =>
                    h(Button, { variant: 'ghost', size: 'icon', class: 'h-8 w-8', asChild: true }, () =>
                        h(Link, { href: show(row.original.id).url, 'aria-label': 'View inspection' }, () =>
                            h(Eye, { class: 'h-4 w-4' }),
                        ),
                    ),
                ),
                h(TooltipContent, { side: 'top' }, () => 'View inspection'),
            ]),
        enableSorting: false,
        enableHiding: false,
    });

    return base;
});

onMounted(() => {
    if (props.data.filters.start_date || props.data.filters.end_date) {
        return;
    }

    router.get(
        inspectionsIndex().url,
        {
            start_date: defaultRange.start,
            end_date: defaultRange.end,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
});
</script>

<template>
    <Head title="Inspections" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-6 lg:p-8">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="font-display text-3xl font-semibold tracking-tight text-foreground">Inspections</h1>
                    <p class="text-sm text-muted-foreground">Review inspections by Sunday-start submission week.</p>
                </div>
                <Button
                    v-if="can('inspections.create')"
                    size="sm"
                    as-child
                    class="h-9 rounded-lg px-4"
                >
                    <Link :href="create().url" aria-label="New inspection">
                        <Plus class="mr-2 h-4 w-4" />
                        Add inspection
                    </Link>
                </Button>
            </div>

            <div class="rounded-xl border border-border/60 bg-card/60 p-3 backdrop-blur">
                <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_220px_220px_auto] lg:items-end">
                    <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_repeat(2,minmax(0,220px))]">
                        <Input
                            v-model="searchFilter"
                            class="h-9 w-full"
                            placeholder="Search facility, inspector, condition"
                        />
                        <DatePicker v-model="filterStartDate" class="h-9 w-full" placeholder="Start date" />
                        <DatePicker v-model="filterEndDate" class="h-9 w-full" placeholder="End date" />
                    </div>
                    <NativeSelect v-model="filterFacilityId" class="h-9 w-full">
                        <NativeSelectOption value="">
                            All facilities
                        </NativeSelectOption>
                        <NativeSelectOption
                            v-for="facility in data.facilities"
                            :key="facility.id"
                            :value="String(facility.id)"
                        >
                            {{ facility.name }}
                        </NativeSelectOption>
                    </NativeSelect>
                    <Select v-if="data.users && data.users.length" v-model="filterUserId">
                        <SelectTrigger class="h-9 w-full">
                            <SelectValue placeholder="All users" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All users</SelectItem>
                            <SelectItem v-for="user in data.users" :key="user.id" :value="String(user.id)">
                                {{ user.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <div class="flex items-center gap-2">
                        <Button size="sm" class="h-9 px-4" @click="applyFilters">Apply filters</Button>
                        <Button size="sm" variant="ghost" class="h-9 px-3" @click="clearFilters">Reset</Button>
                    </div>
                </div>
                <p class="mt-3 text-xs text-muted-foreground">
                    Default range shows the current week plus the last month: {{ dateRangeLabel }}.
                </p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StatsCard title="Total inspections" :value="metrics.total" :icon="ClipboardCheck" accent-color="amber" description="All time" />
                <StatsCard title="This month" :value="metrics.thisMonth" :icon="TrendingUp" accent-color="emerald" description="Recent activity" />
                <StatsCard title="Good condition" :value="metrics.goodCondition" :icon="CheckCircle2" accent-color="emerald" description="Operational" />
                <StatsCard title="Needs attention" :value="metrics.needsAttention" :icon="AlertTriangle" accent-color="amber" description="Requires action" />
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
                                <Badge variant="outline" class="text-[10px]">{{ group.inspections.length }} inspections</Badge>
                            </div>
                        </AccordionTrigger>
                        <AccordionContent class="px-4 pb-4">
                            <DataTable
                                :data="group.inspections"
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
                        <p class="text-sm font-bold text-muted-foreground">No inspections found</p>
                        <p class="mt-1 text-xs text-muted-foreground/60">Try adjusting your filters.</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>


