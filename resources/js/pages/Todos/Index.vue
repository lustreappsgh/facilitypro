<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import StatsCard from '@/components/StatsCard.vue';
import { Card, CardContent } from '@/components/ui/card';
import DataTable from '@/components/data-table/DataTable.vue';
import { DatePicker } from '@/components/ui/date-picker';
import AppLayout from '@/layouts/AppLayout.vue';
import { complete, edit, index as todosIndex } from '@/routes/todos';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';
import type { ColumnDef } from '@tanstack/vue-table';
import { computed, h, ref } from 'vue';
import { parseISO } from 'date-fns';
import { AlertTriangle, CheckCircle2, ClipboardCheck, ClipboardList, Plus, TrendingUp } from 'lucide-vue-next';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import TodoModal from '@/components/TodoModal.vue';

interface Facility {
    id: number;
    name: string;
}

interface Todo {
    id: number;
    description: string;
    status: string;
    week_start: string | null;
    completed_at: string | null;
    facility?: Facility | null;
    facility_manager_name?: string | null;
}

interface TodoGroup {
    week_start: string;
    week_label: string;
    todos: Todo[];
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
        groups: TodoGroup[];
        weeks_by_year_month: WeeksByYearMonth[];
        facilities: Facility[];
        show_manager_name: boolean;
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
        title: 'Weekly Todos',
        href: todosIndex().url,
    },
];

const { can } = usePermissions();

const todoModalOpen = ref(false);
const filterStartDate = ref(props.data.filters.start_date || '');
const filterEndDate = ref(props.data.filters.end_date || '');
const filterFacilityId = ref(props.data.filters.facility_id ? String(props.data.filters.facility_id) : 'all');
const selectedTodoIds = ref<number[]>([]);

const allTodos = computed(() => props.data.groups.flatMap((group) => group.todos));
const completableTodoIds = computed(() =>
    allTodos.value
        .filter((todo) => ['pending', 'overdue'].includes(todo.status))
        .map((todo) => todo.id),
);
const selectedCompletableCount = computed(() =>
    selectedTodoIds.value.filter((id) => completableTodoIds.value.includes(id)).length,
);

const metrics = computed(() => {
    const todos = allTodos.value;
    const total = todos.length;
    const pending = todos.filter((todo) => todo.status === 'pending').length;
    const completed = todos.filter((todo) => todo.status === 'completed').length;
    const today = new Date();
    const overdue = todos.filter((todo) => {
        if (todo.completed_at || !todo.week_start) {
            return false;
        }

        return today > parseISO(todo.week_start);
    }).length;

    return {
        total,
        pending,
        completed,
        overdue,
    };
});

const applyFilters = () => {
    router.get(
        todosIndex().url,
        {
            start_date: filterStartDate.value || undefined,
            end_date: filterEndDate.value || undefined,
            facility_id: filterFacilityId.value === 'all' ? undefined : filterFacilityId.value,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const clearFilters = () => {
    router.get(todosIndex().url, {}, { preserveState: true, preserveScroll: true });
};

const toggleTodoSelection = (todoId: number, checked: boolean) => {
    if (checked) {
        if (!selectedTodoIds.value.includes(todoId)) {
            selectedTodoIds.value = [...selectedTodoIds.value, todoId];
        }
        return;
    }

    selectedTodoIds.value = selectedTodoIds.value.filter((id) => id !== todoId);
};

const isSelected = (todoId: number) => selectedTodoIds.value.includes(todoId);

const groupCompletableIds = (todos: Todo[]) =>
    todos.filter((todo) => ['pending', 'overdue'].includes(todo.status)).map((todo) => todo.id);

const isGroupSelected = (todos: Todo[]) => {
    const ids = groupCompletableIds(todos);
    return ids.length > 0 && ids.every((id) => selectedTodoIds.value.includes(id));
};

const toggleGroupSelection = (todos: Todo[], checked: boolean) => {
    const ids = groupCompletableIds(todos);
    if (!ids.length) return;

    if (checked) {
        const next = new Set(selectedTodoIds.value);
        ids.forEach((id) => next.add(id));
        selectedTodoIds.value = Array.from(next);
        return;
    }

    selectedTodoIds.value = selectedTodoIds.value.filter((id) => !ids.includes(id));
};

const toggleSelectAll = (checked: boolean) => {
    if (checked) {
        selectedTodoIds.value = [...completableTodoIds.value];
        return;
    }
    selectedTodoIds.value = [];
};

const submitBulkComplete = () => {
    if (!selectedCompletableCount.value || !props.routes.bulkComplete) {
        return;
    }

    router.post(
        props.routes.bulkComplete,
        { todo_ids: selectedTodoIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedTodoIds.value = [];
            },
        },
    );
};

const statusLabels: Record<string, string> = {
    pending: 'Pending',
    completed: 'Completed',
    overdue: 'Overdue',
};

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        pending: 'bg-muted text-muted-foreground border-border',
        completed: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
        overdue: 'bg-rose-500/10 text-rose-600 border-rose-500/20',
    };

    return colors[status] || 'bg-muted text-muted-foreground';
};

const columns = computed<ColumnDef<Todo>[]>(() => {
    const base: ColumnDef<Todo>[] = [
        {
            id: 'select',
            header: '',
            cell: ({ row }) => {
                const canSelect = can('todos.complete') && ['pending', 'overdue'].includes(row.original.status);
                if (!canSelect) {
                    return h('span', { class: 'text-[10px] text-muted-foreground/50' }, '—');
                }

                return h(Checkbox, {
                    modelValue: isSelected(row.original.id),
                    'onUpdate:modelValue': (value: boolean | 'indeterminate') =>
                        toggleTodoSelection(row.original.id, value === true),
                    'aria-label': `Select todo ${row.original.id}`,
                });
            },
            enableSorting: false,
            enableHiding: false,
        },
        {
            id: 'facility',
            accessorFn: (row) => row.facility?.name ?? '',
            header: 'Facility',
            cell: ({ row }) => h('span', { class: 'font-medium text-[11px]' }, row.original.facility?.name ?? '—'),
            enableHiding: false,
        },
        {
            id: 'description',
            accessorFn: (row) => row.description ?? '',
            header: 'Description',
            cell: ({ row }) => h('span', { class: 'text-[11px] font-medium text-foreground/80' }, row.original.description),
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
                        class: `rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider ${getStatusColor(row.original.status)}`,
                    },
                    () => statusLabels[row.original.status] ?? row.original.status,
                ),
        },
    ];

    if (props.data.show_manager_name) {
        base.splice(1, 0, {
            id: 'manager',
            accessorFn: (row) => row.facility_manager_name ?? '',
            header: 'Facility Manager',
            cell: ({ row }) => h('span', { class: 'text-[11px] text-muted-foreground' }, row.original.facility_manager_name ?? '—'),
        });
    }

    base.push({
        id: 'actions',
        header: '',
        cell: ({ row }) =>
            h('div', { class: 'flex flex-wrap justify-end gap-2' }, [
                can('todos.update') && ['pending'].includes(row.original.status)
                    ? h(Button, { variant: 'outline', size: 'sm', class: 'h-7 px-3 text-[10px] font-bold uppercase', asChild: true }, () =>
                        h(Link, { href: edit(row.original.id).url }, () => 'Edit'),
                    )
                    : null,
                can('todos.complete') && ['pending', 'overdue'].includes(row.original.status)
                    ? h(Button, { variant: 'secondary', size: 'sm', class: 'h-7 px-3 text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/20', asChild: true }, () =>
                        h(Link, { href: complete(row.original.id).url, method: 'post', as: 'button' }, () => 'Complete'),
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
    <Head title="Weekly Todos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-6 lg:p-8">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="font-display text-3xl font-semibold tracking-tight text-foreground">Weekly todos</h1>
                    <p class="text-sm text-muted-foreground">Plan and manage to-do items by week.</p>
                </div>
                <Button
                    v-if="can('todos.create')"
                    size="sm"
                    class="h-9 rounded-lg px-3 text-[11px] font-semibold uppercase tracking-wide"
                    @click="todoModalOpen = true"
                >
                    <Plus class="mr-1.5 h-3.5 w-3.5" />
                    New todo
                </Button>
            </div>

            <div class="rounded-xl border border-border/60 bg-card/60 p-3 backdrop-blur">
                <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_auto] lg:items-end">
                    <div class="grid gap-2 sm:grid-cols-2">
                        <DatePicker
                            v-model="filterStartDate"
                            class="h-9 w-full"
                            placeholder="Start date"
                        />
                        <DatePicker
                            v-model="filterEndDate"
                            class="h-9 w-full"
                            placeholder="End date"
                        />
                    </div>
                    <Select v-model="filterFacilityId">
                        <SelectTrigger class="h-9 w-full">
                            <SelectValue placeholder="All facilities" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All facilities</SelectItem>
                            <SelectItem
                                v-for="facility in data.facilities"
                                :key="facility.id"
                                :value="String(facility.id)"
                            >
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

            <div
                v-if="can('todos.complete') && selectedCompletableCount > 1"
                class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-border/50 bg-muted/20 px-3 py-2"
            >
                <div class="flex items-center gap-3 text-xs text-muted-foreground">
                    <div class="flex items-center gap-2">
                        <Checkbox
                            :model-value="selectedCompletableCount > 0 && selectedCompletableCount === completableTodoIds.length"
                            :aria-label="'Select all completable todos'"
                            @update:model-value="(value) => toggleSelectAll(value === true)"
                        />
                        <span>Select all completable</span>
                    </div>
                    <span>{{ selectedCompletableCount }} selected</span>
                </div>
                <Button
                    size="sm"
                    class="h-8 px-3 text-xs"
                    :disabled="selectedCompletableCount === 0"
                    @click="submitBulkComplete"
                >
                    Complete selected
                </Button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StatsCard title="Total todos" :value="metrics.total" :icon="ClipboardCheck" accent-color="amber" description="All in range" />
                <StatsCard title="Pending" :value="metrics.pending" :icon="TrendingUp" accent-color="blue" description="Open work" />
                <StatsCard title="Completed" :value="metrics.completed" :icon="CheckCircle2" accent-color="emerald" description="Done" />
                <StatsCard title="Overdue" :value="metrics.overdue" :icon="AlertTriangle" accent-color="rose" description="Needs action" />
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
                                <Checkbox
                                    v-if="can('todos.complete')"
                                    :model-value="isGroupSelected(group.todos)"
                                    :aria-label="`Select todos for ${group.week_label}`"
                                    @update:model-value="(value) => toggleGroupSelection(group.todos, value === true)"
                                    @click.stop
                                />
                                <p class="font-display text-sm font-semibold tracking-wide">{{ group.week_label }}</p>
                                <Badge variant="outline" class="text-[10px]">{{ group.todos.length }} todos</Badge>
                            </div>
                        </AccordionTrigger>
                        <AccordionContent class="px-4 pb-4">
                            <DataTable
                                :data="group.todos"
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
                        <p class="text-sm font-bold text-muted-foreground">No todos found</p>
                        <p class="mt-1 text-xs text-muted-foreground/60">Try adjusting your filters.</p>
                    </CardContent>
                </Card>
            </div>
        </div>

        <TodoModal v-model:open="todoModalOpen" :facilities="data.facilities" @success="router.reload()" />
    </AppLayout>
</template>
