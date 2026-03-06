<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as todosIndex } from '@/routes/todos';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Facility {
    id: number;
    name: string;
    facility_type_id?: number | null;
}

interface FacilityType {
    id: number;
    name: string;
}

interface Props {
    data: {
        facilities: Facility[];
        facilityTypes: FacilityType[];
        selectedFacilityId?: number | null;
        selectedFacilityIds?: number[];
    };
    permissions: string[];
    routes: Record<string, string>;
    meta: Record<string, string>;
}

const props = defineProps<Props>();

interface BulkRow {
    facility_id: number;
    facility_name: string;
    description: string;
    week: string;
    selected: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Weekly Todos',
        href: todosIndex().url,
    },
    {
        title: 'Create',
        href: '#',
    },
];

const textAreaClass =
    'border-input bg-transparent text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] min-h-[120px] w-full rounded-md border px-3 py-2';

const getUpcomingMonday = () => {
    const date = new Date();
    const day = date.getDay();
    const diffToNextMonday = ((8 - day) % 7) || 7;
    date.setDate(date.getDate() + diffToNextMonday);
    return date.toISOString().slice(0, 10);
};

const hasPrefilledFacilities = computed(
    () => (props.data.selectedFacilityIds?.length ?? 0) > 0 || Boolean(props.data.selectedFacilityId),
);
const prefilledFacilityIds = computed(() => {
    const selected = new Set<number>((props.data.selectedFacilityIds ?? []).map((id) => Number(id)));
    if (props.data.selectedFacilityId) {
        selected.add(props.data.selectedFacilityId);
    }
    return [...selected];
});
const selectedFacilityTypeId = ref<string | null>(
    (() => {
        const selectedFacilities = props.data.facilities.filter((facility) =>
            prefilledFacilityIds.value.includes(facility.id),
        );
        const selectedType = selectedFacilities[0]?.facility_type_id;
        if (selectedType) {
            return String(selectedType);
        }

        return props.data.facilityTypes[0] ? String(props.data.facilityTypes[0].id) : null;
    })(),
);
const defaultWeekStart = ref(getUpcomingMonday());
const rows = ref<BulkRow[]>([]);

const filteredFacilities = computed(() => {
    if (!selectedFacilityTypeId.value) {
        return [] as Facility[];
    }

    return props.data.facilities.filter(
        (facility) => String(facility.facility_type_id ?? '') === selectedFacilityTypeId.value,
    );
});
const selectedRows = computed(() => rows.value.filter((row) => row.selected));
const selectedRowIndexById = computed<Record<number, number>>(() => {
    const indexMap: Record<number, number> = {};
    selectedRows.value.forEach((row, index) => {
        indexMap[row.facility_id] = index;
    });
    return indexMap;
});
const hasInvalidSelectedRows = computed(() =>
    selectedRows.value.some((row) => !row.description.trim()),
);
const allSelected = computed({
    get: () => rows.value.length > 0 && rows.value.every((row) => row.selected),
    set: (value: boolean) => {
        rows.value = rows.value.map((row) => ({ ...row, selected: value }));
    },
});

const initializeRows = () => {
    rows.value = filteredFacilities.value.map((facility) => ({
        facility_id: facility.id,
        facility_name: facility.name,
        description: '',
        week: defaultWeekStart.value,
        selected: hasPrefilledFacilities.value
            ? prefilledFacilityIds.value.includes(facility.id)
            : true,
    }));
};

watch(selectedFacilityTypeId, initializeRows, { immediate: true });
</script>

<template>
    <Head title="Create todo" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader title="Create todo" subtitle="Create todos in bulk by facility type." />

            <Form
                action="/todos"
                method="post"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-4 rounded-xl border border-border/50 bg-card/50 p-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="facility_type_id">Facility type</Label>
                        <Select v-model="selectedFacilityTypeId">
                            <SelectTrigger id="facility_type_id" class="w-full">
                                <SelectValue placeholder="Select facility type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="facilityType in data.facilityTypes"
                                    :key="facilityType.id"
                                    :value="String(facilityType.id)"
                                >
                                    {{ facilityType.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="grid gap-2">
                        <Label for="default_week_start">Default week start</Label>
                        <Input
                            id="default_week_start"
                            v-model="defaultWeekStart"
                            type="date"
                        />
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-border/50 bg-card/50">
                    <table class="min-w-full divide-y divide-border/60 text-sm">
                        <thead class="bg-muted/30">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold">
                                    <input
                                        type="checkbox"
                                        v-model="allSelected"
                                        :disabled="rows.length === 0"
                                    />
                                </th>
                                <th class="px-3 py-2 text-left font-semibold">Facility</th>
                                <th class="px-3 py-2 text-left font-semibold">Week start</th>
                                <th class="px-3 py-2 text-left font-semibold">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/50">
                            <tr
                                v-for="(row, index) in rows"
                                :key="row.facility_id"
                                class="align-top"
                            >
                                <td class="px-3 py-2">
                                    <input type="checkbox" v-model="row.selected" />
                                </td>
                                <td class="px-3 py-2 font-medium">
                                    {{ row.facility_name }}
                                </td>
                                <td class="px-3 py-2">
                                    <Input v-model="row.week" type="date" />
                                    <InputError
                                        :message="
                                            row.selected &&
                                            selectedRowIndexById[row.facility_id] !== undefined
                                                ? errors[
                                                      `bulk_todos.${selectedRowIndexById[row.facility_id]}.week`
                                                  ]
                                                : null
                                        "
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    <textarea
                                        v-model="row.description"
                                        :class="textAreaClass"
                                        placeholder="Describe the weekly action item"
                                    />
                                    <InputError
                                        :message="
                                            row.selected &&
                                            selectedRowIndexById[row.facility_id] !== undefined
                                                ? errors[
                                                      `bulk_todos.${selectedRowIndexById[row.facility_id]}.description`
                                                  ]
                                                : null
                                        "
                                    />
                                </td>
                            </tr>
                            <tr v-if="rows.length === 0">
                                <td colspan="4" class="px-3 py-8 text-center text-sm text-muted-foreground">
                                    Select a facility type to load facilities.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-for="(row, index) in selectedRows"
                    :key="`payload-${row.facility_id}`"
                >
                    <input type="hidden" :name="`bulk_todos[${index}][facility_id]`" :value="row.facility_id" />
                    <input type="hidden" :name="`bulk_todos[${index}][description]`" :value="row.description" />
                    <input type="hidden" :name="`bulk_todos[${index}][week]`" :value="row.week" />
                </div>

                <InputError
                    :message="
                        errors.bulk_todos ||
                        errors.facility_id ||
                        errors.facility_ids ||
                        (selectedRows.length === 0 ? 'Select at least one facility row.' : '')
                    "
                />

                <div class="flex items-center gap-4">
                    <Button :disabled="processing || selectedRows.length === 0 || hasInvalidSelectedRows">
                        Create bulk todos
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link :href="todosIndex().url">Cancel</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
