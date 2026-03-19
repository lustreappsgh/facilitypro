<script setup lang="ts">
import DataTable from '@/components/data-table/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
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
import { useDateFormat } from '@/composables/useDateFormat';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatLocaleDateRange } from '@/lib/locale';
import {
    admin as inspectionsAdmin,
    show as inspectionsShow,
} from '@/routes/inspections';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { endOfWeek, format, startOfWeek, subWeeks } from 'date-fns';
import { Eye, Search } from 'lucide-vue-next';
import { computed, h, onMounted, ref } from 'vue';

const filtersVisible = ref(false);

interface Facility {
    id: number;
    name: string;
}

interface Inspector {
    id: number;
    name: string;
}

interface Inspection {
    id: number;
    condition: string;
    inspection_date?: string | null;
    created_at?: string | null;
    facility?: Facility | null;
    addedBy?: Inspector | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedInspections {
    data: Inspection[];
    links: PaginationLink[];
}

interface ConditionOption {
    value: string;
    label: string;
}

interface Filters {
    search?: string | null;
    condition?: string | null;
    facility?: string | null;
    inspector?: string | null;
    start_date?: string | null;
    end_date?: string | null;
}

interface Props {
    inspections: PaginatedInspections;
    facilities: Facility[];
    inspectors: Inspector[];
    conditions: ConditionOption[];
    filters: Filters;
}

const props = defineProps<Props>();

const { parseDate } = useDateFormat();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inspections',
        href: inspectionsAdmin().url,
    },
];

const searchFilter = ref(props.filters.search ?? '');
const conditionFilter = ref(props.filters.condition ?? '');
const facilityFilter = ref(props.filters.facility ?? '');
const inspectorFilter = ref(props.filters.inspector ?? '');
const startDateFilter = ref(props.filters.start_date ?? '');
const endDateFilter = ref(props.filters.end_date ?? '');

const selectedConditionLabel = computed(() => {
    const match = props.conditions.find(
        (condition) => condition.value === conditionFilter.value,
    );
    return match?.label ?? 'All conditions';
});

const selectedInspectorLabel = computed(() => {
    const match = props.inspectors.find(
        (inspector) => String(inspector.id) === inspectorFilter.value,
    );
    return match?.name ?? 'All inspectors';
});

const conditionBadgeClass = (condition: string) => {
    if (condition === 'Good') {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (condition === 'Bad') {
        return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
    }

    return 'bg-rose-500/10 text-rose-700 dark:text-rose-300';
};

const groupedByWeek = computed(() => {
    const groups = new Map<string, Inspection[]>();

    props.inspections.data.forEach((inspection) => {
        const dateValue = parseDate(inspection.inspection_date);
        const weekStart = dateValue
            ? startOfWeek(dateValue, { weekStartsOn: 1 })
            : null;
        const weekEnd = dateValue
            ? endOfWeek(dateValue, { weekStartsOn: 1 })
            : null;
        const label = dateValue
            ? formatLocaleDateRange(weekStart, weekEnd)
            : 'Unknown week';

        if (!groups.has(label)) {
            groups.set(label, []);
        }
        groups.get(label)!.push(inspection);
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
    const now = new Date();
    const lastWeekStart = startOfWeek(subWeeks(now, 1), { weekStartsOn: 1 });
    const lastWeekEnd = endOfWeek(subWeeks(now, 1), { weekStartsOn: 1 });
    startDateFilter.value = format(lastWeekStart, 'yyyy-MM-dd');
    endDateFilter.value = format(lastWeekEnd, 'yyyy-MM-dd');

    router.get(
        inspectionsAdmin().url,
        {
            search: searchFilter.value || undefined,
            condition: conditionFilter.value || undefined,
            facility: facilityFilter.value || undefined,
            inspector: inspectorFilter.value || undefined,
            start_date: startDateFilter.value,
            end_date: endDateFilter.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
});

const columns: ColumnDef<Inspection>[] = [
    {
        id: 'facility',
        accessorFn: (row) => row.facility?.name ?? '',
        header: 'Facility',
        cell: ({ row }) =>
            h(
                'span',
                { class: 'font-medium' },
                row.original.facility?.name ?? '-',
            ),
        enableHiding: false,
    },
    {
        id: 'inspector',
        accessorFn: (row) => row.addedBy?.name ?? '',
        header: 'Inspector',
        cell: ({ row }) => row.original.addedBy?.name ?? '-',
    },
    {
        id: 'condition',
        accessorFn: (row) => row.condition ?? '',
        header: 'Condition',
        cell: ({ row }) =>
            h(
                Badge,
                {
                    variant: 'secondary',
                    class: conditionBadgeClass(row.original.condition),
                },
                () => row.original.condition,
            ),
    },
    {
        id: 'inspectionDate',
        accessorFn: (row) => row.inspection_date ?? '',
        header: 'Inspection date',
        cell: ({ row }) => row.original.inspection_date ?? '-',
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
                            href: inspectionsShow(row.original).url,
                            'aria-label': 'View inspection',
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
    <Head title="Inspections overview" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader
                title="Inspections overview"
                subtitle="Review inspection activity across facilities."
                :show-filters-toggle="true"
                :filters-visible="filtersVisible"
                @toggle-filters="filtersVisible = !filtersVisible"
            />

            <div class="space-y-4">
                <form
                    v-if="filtersVisible"
                    :action="inspectionsAdmin().url"
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
                            placeholder="Search facilities or inspectors"
                        />
                    </div>

                    <input
                        type="hidden"
                        name="condition"
                        :value="conditionFilter"
                    />
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="outline"
                                class="min-w-[150px] justify-between"
                            >
                                {{ selectedConditionLabel }}
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <DropdownMenuLabel>Condition</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem @click="conditionFilter = ''">
                                All conditions
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-for="condition in conditions"
                                :key="condition.value"
                                @click="conditionFilter = condition.value"
                            >
                                {{ condition.label }}
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
                        name="inspector"
                        :value="inspectorFilter"
                    />
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="outline"
                                class="min-w-[180px] justify-between"
                            >
                                {{ selectedInspectorLabel }}
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-64">
                            <DropdownMenuLabel>Inspector</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem @click="inspectorFilter = ''">
                                All inspectors
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-for="inspector in inspectors"
                                :key="inspector.id"
                                @click="inspectorFilter = String(inspector.id)"
                            >
                                {{ inspector.name }}
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
                            <Link :href="inspectionsAdmin().url">Reset</Link>
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
                            class="flex w-full items-center justify-between rounded-2xl border border-border bg-card/60 px-6 py-4 transition-all hover:bg-card hover:no-underline"
                        >
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

            <PaginationLinks :links="inspections.links" />
        </div>
    </AppLayout>
</template>
