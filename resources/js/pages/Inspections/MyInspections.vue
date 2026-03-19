<script setup lang="ts">
import InspectionModal from '@/components/InspectionModal.vue';
import PageHeader from '@/components/PageHeader.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import {
    NativeSelect,
    NativeSelectOption,
} from '@/components/ui/native-select';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useDateFormat } from '@/composables/useDateFormat';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatLocaleDateRange } from '@/lib/locale';
import {
    my as inspectionsMy,
    show as inspectionsShow,
} from '@/routes/inspections';
import { Head, Link, router } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { endOfWeek, format, startOfWeek, subWeeks } from 'date-fns';
import {
    AlertTriangle,
    Calendar,
    CheckCircle2,
    ClipboardCheck,
    Eye,
    TrendingUp,
} from 'lucide-vue-next';
import { computed, h, onMounted, ref } from 'vue';

const filtersVisible = ref(false);

interface Facility {
    id: number;
    name: string;
}

interface Inspection {
    id: number;
    inspection_date: string;
    condition: string;
    facility?: Facility | null;
    comments?: string | null;
}

interface PaginatedInspections {
    data: Inspection[];
    links: any[];
}

interface Stats {
    total: number;
    this_month: number;
    good_condition: number;
    needs_attention: number;
}

interface Filters {
    start_date: string;
    end_date: string;
    condition?: string | null;
    facility_id?: number | null;
}

interface Props {
    inspections: PaginatedInspections;
    stats: Stats;
    facilities: Facility[];
    conditions: string[];
    filters: Filters;
}

const props = defineProps<Props>();

const breadcrumbs = [{ title: 'My Inspections', href: inspectionsMy().url }];

const { formatTableDate, formatRelative, parseDate } = useDateFormat();

// Modal state
const inspectionModalOpen = ref(false);

// Filter state
const filterStartDate = ref(props.filters.start_date);
const filterEndDate = ref(props.filters.end_date);
const filterCondition = ref(props.filters.condition || '');
const filterFacilityId = ref(
    props.filters.facility_id ? String(props.filters.facility_id) : '',
);

const applyFilters = () => {
    router.get(
        inspectionsMy().url,
        {
            start_date: filterStartDate.value || undefined,
            end_date: filterEndDate.value || undefined,
            condition: filterCondition.value || undefined,
            facility_id: filterFacilityId.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const clearFilters = () => {
    filterStartDate.value = '';
    filterEndDate.value = '';
    filterCondition.value = '';
    filterFacilityId.value = '';
    router.get(inspectionsMy().url);
};

onMounted(() => {
    if (filterStartDate.value || filterEndDate.value) {
        return;
    }
    const now = new Date();
    const lastWeekStart = startOfWeek(subWeeks(now, 1), { weekStartsOn: 1 });
    const lastWeekEnd = endOfWeek(subWeeks(now, 1), { weekStartsOn: 1 });
    filterStartDate.value = format(lastWeekStart, 'yyyy-MM-dd');
    filterEndDate.value = format(lastWeekEnd, 'yyyy-MM-dd');
    applyFilters();
});

// Group inspections by week
const groupedByWeek = computed(() => {
    const groups = new Map<string, Inspection[]>();

    props.inspections.data.forEach((inspection) => {
        const date = parseDate(inspection.inspection_date);
        if (!date) {
            return;
        }
        const weekStart = startOfWeek(date, { weekStartsOn: 1 });
        const weekEnd = endOfWeek(date, { weekStartsOn: 1 });
        const weekKey = formatLocaleDateRange(weekStart, weekEnd);

        if (!groups.has(weekKey)) {
            groups.set(weekKey, []);
        }
        groups.get(weekKey)!.push(inspection);
    });

    return Array.from(groups.entries()).map(([week, inspections]) => ({
        week,
        inspections,
        count: inspections.length,
    }));
});

const getConditionColor = (condition: string) => {
    const normalized = condition.toLowerCase();
    const colors: Record<string, string> = {
        good: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
        bad: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
        warning: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
        critical: 'bg-rose-500/10 text-rose-600 border-rose-500/20',
    };
    return colors[normalized] || 'bg-muted text-muted-foreground';
};

const columns: ColumnDef<Inspection>[] = [
    {
        accessorKey: 'inspection_date',
        header: 'Date',
        cell: ({ row }) =>
            h('div', { class: 'flex flex-col' }, [
                h(
                    'span',
                    { class: 'font-bold text-[11px] text-foreground' },
                    formatTableDate(row.original.inspection_date),
                ),
                h(
                    'span',
                    { class: 'text-[10px] text-muted-foreground/60' },
                    formatRelative(row.original.inspection_date),
                ),
            ]),
    },
    {
        accessorKey: 'facility.name',
        header: 'Facility',
        cell: ({ row }) =>
            h(
                'span',
                { class: 'font-medium text-[11px]' },
                row.original.facility?.name || '-',
            ),
    },
    {
        accessorKey: 'condition',
        header: 'Condition',
        cell: ({ row }) =>
            h(
                Badge,
                {
                    variant: 'outline',
                    class: `rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider ${getConditionColor(row.original.condition)}`,
                },
                () => row.original.condition,
            ),
    },
    {
        id: 'actions',
        header: '',
        cell: ({ row }) =>
            h('div', { class: 'flex justify-end' }, [
                h(
                    Button,
                    {
                        variant: 'ghost',
                        size: 'icon',
                        class: 'h-8 w-8',
                        asChild: true,
                    },
                    {
                        default: () =>
                            h(
                                Link,
                                {
                                    href: inspectionsShow(row.original).url,
                                    'aria-label': 'View inspection',
                                },
                                () => h(Eye, { class: 'h-4 w-4' }),
                            ),
                    },
                ),
            ]),
    },
];
</script>

<template>
    <Head title="My Inspections" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-8 p-6 lg:p-10">
            <PageHeader
                title="My inspections"
                subtitle="Schedule and review your inspections."
                :show-filters-toggle="true"
                :filters-visible="filtersVisible"
                @toggle-filters="filtersVisible = !filtersVisible"
            >
                <template #actions>
                    <Button
                        size="icon"
                        class="h-10 w-10"
                        aria-label="New inspection"
                        @click="inspectionModalOpen = true"
                    >
                        <ClipboardCheck class="h-4 w-4" />
                    </Button>
                </template>
            </PageHeader>
            <div
                v-if="filtersVisible"
                class="rounded-xl border border-border/60 bg-card p-4"
            >
                <div class="flex flex-wrap items-center gap-3">
                    <DatePicker
                        v-model="filterStartDate"
                        class="min-w-[160px]"
                    />
                    <DatePicker v-model="filterEndDate" class="min-w-[160px]" />
                    <Select v-model="filterCondition">
                        <SelectTrigger class="min-w-[160px]">
                            <SelectValue placeholder="All conditions" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="condition in conditions"
                                :key="condition"
                                :value="condition"
                            >
                                {{ condition }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <NativeSelect
                        v-model="filterFacilityId"
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
                    <div class="flex items-center gap-2">
                        <Button @click="applyFilters"> Apply filters </Button>
                        <Button variant="ghost" @click="clearFilters">
                            Reset
                        </Button>
                    </div>
                </div>
            </div>
            <!-- Statistics -->
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <Card class="border-border bg-card shadow-sm">
                    <CardHeader
                        class="flex flex-row items-center justify-between pb-2"
                    >
                        <CardTitle
                            class="text-[10px] font-black tracking-[0.2em] text-muted-foreground/50 uppercase"
                        >
                            Total Inspections
                        </CardTitle>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-primary/20 bg-primary/10"
                        >
                            <ClipboardCheck class="h-5 w-5 text-primary" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-display text-3xl font-black tabular-nums"
                        >
                            {{ stats.total }}
                        </p>
                        <p
                            class="mt-1 text-[10px] font-bold tracking-widest text-muted-foreground/60 uppercase"
                        >
                            All time
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-border bg-card shadow-sm">
                    <CardHeader
                        class="flex flex-row items-center justify-between pb-2"
                    >
                        <CardTitle
                            class="text-[10px] font-black tracking-[0.2em] text-muted-foreground/50 uppercase"
                        >
                            This Month
                        </CardTitle>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-emerald-500/20 bg-emerald-500/10"
                        >
                            <TrendingUp class="h-5 w-5 text-emerald-600" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-display text-3xl font-black text-emerald-600 tabular-nums"
                        >
                            {{ stats.this_month }}
                        </p>
                        <p
                            class="mt-1 text-[10px] font-bold tracking-widest text-muted-foreground/60 uppercase"
                        >
                            Recent activity
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-border bg-card shadow-sm">
                    <CardHeader
                        class="flex flex-row items-center justify-between pb-2"
                    >
                        <CardTitle
                            class="text-[10px] font-black tracking-[0.2em] text-muted-foreground/50 uppercase"
                        >
                            Good Condition
                        </CardTitle>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-emerald-500/20 bg-emerald-500/10"
                        >
                            <CheckCircle2 class="h-5 w-5 text-emerald-600" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-display text-3xl font-black text-emerald-600 tabular-nums"
                        >
                            {{ stats.good_condition }}
                        </p>
                        <p
                            class="mt-1 text-[10px] font-bold tracking-widest text-muted-foreground/60 uppercase"
                        >
                            Operational
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-border bg-card shadow-sm">
                    <CardHeader
                        class="flex flex-row items-center justify-between pb-2"
                    >
                        <CardTitle
                            class="text-[10px] font-black tracking-[0.2em] text-muted-foreground/50 uppercase"
                        >
                            Needs Attention
                        </CardTitle>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-amber-500/20 bg-amber-500/10"
                        >
                            <AlertTriangle class="h-5 w-5 text-amber-600" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p
                            class="font-display text-3xl font-black text-amber-600 tabular-nums"
                        >
                            {{ stats.needs_attention }}
                        </p>
                        <p
                            class="mt-1 text-[10px] font-bold tracking-widest text-muted-foreground/60 uppercase"
                        >
                            Requires action
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Week Grouping -->
            <div class="space-y-4">
                <div class="flex items-center gap-3 px-1">
                    <Calendar class="h-5 w-5 text-muted-foreground/40" />
                    <h3
                        class="text-[11px] font-black tracking-[0.25em] text-muted-foreground/50 uppercase"
                    >
                        Grouped by Week
                    </h3>
                </div>

                <Accordion
                    v-if="groupedByWeek.length > 0"
                    type="multiple"
                    class="space-y-4"
                >
                    <AccordionItem
                        v-for="group in groupedByWeek"
                        :key="group.week"
                        :value="group.week"
                        class="rounded-2xl border border-border bg-card px-4 shadow-sm"
                    >
                        <AccordionTrigger class="hover:no-underline">
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-border bg-muted/50"
                                >
                                    <Calendar
                                        class="h-5 w-5 text-muted-foreground/60"
                                    />
                                </div>
                                <div class="text-left">
                                    <p
                                        class="text-[13px] font-black tracking-tight text-foreground uppercase"
                                    >
                                        {{ group.week }}
                                    </p>
                                    <p
                                        class="text-[10px] font-bold tracking-widest text-muted-foreground/60 uppercase"
                                    >
                                        {{ group.count }}
                                        {{
                                            group.count === 1
                                                ? 'inspection'
                                                : 'inspections'
                                        }}
                                    </p>
                                </div>
                            </div>
                        </AccordionTrigger>
                        <AccordionContent class="pt-4">
                            <DataTable
                                :data="group.inspections"
                                :columns="columns"
                                :show-search="false"
                                :show-selection-summary="false"
                                class="portfolio-table"
                            />
                        </AccordionContent>
                    </AccordionItem>
                </Accordion>

                <Card v-else class="border-border bg-card shadow-sm">
                    <CardContent class="py-12 text-center">
                        <ClipboardCheck
                            class="mx-auto mb-4 h-12 w-12 text-muted-foreground/20"
                        />
                        <p class="text-sm font-bold text-muted-foreground">
                            No inspections found
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground/60">
                            Try adjusting your filters or create a new
                            inspection
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Inspection Modal -->
        <InspectionModal
            v-model:open="inspectionModalOpen"
            :facilities="facilities"
            :conditions="conditions"
            :redirect-to="inspectionsMy().url"
            @success="router.reload()"
        />
    </AppLayout>
</template>
