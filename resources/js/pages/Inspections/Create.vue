<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as inspectionsIndex } from '@/routes/inspections';
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
    facilities: Facility[];
    facilityTypes: FacilityType[];
    conditions: string[];
    selectedFacilityId?: number | null;
    selectedFacilityIds?: number[];
}

const props = defineProps<Props>();

interface BulkRow {
    facility_id: number;
    facility_name: string;
    inspection_date: string;
    condition: string;
    comments: string;
    selected: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inspections',
        href: inspectionsIndex().url,
    },
    {
        title: 'Create',
        href: '#',
    },
];

const inputClass =
    'border-input bg-transparent text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] h-9 w-full rounded-md border px-3';
const textAreaClass =
    'border-input bg-transparent text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] min-h-[76px] w-full rounded-md border px-3 py-2';
const todayDate = new Date().toISOString().slice(0, 10);

const hasPrefilledFacilities = computed(
    () =>
        (props.selectedFacilityIds?.length ?? 0) > 0 ||
        Boolean(props.selectedFacilityId),
);
const prefilledFacilityIds = computed(() => {
    const selected = new Set<number>(
        (props.selectedFacilityIds ?? []).map((id) => Number(id)),
    );
    if (props.selectedFacilityId) {
        selected.add(props.selectedFacilityId);
    }
    return [...selected];
});
const selectedFacilityTypeId = ref<string | null>(
    (() => {
        const selectedFacilities = props.facilities.filter((facility) =>
            prefilledFacilityIds.value.includes(facility.id),
        );
        const selectedType = selectedFacilities[0]?.facility_type_id;
        if (selectedType) {
            return String(selectedType);
        }

        return props.facilityTypes[0]
            ? String(props.facilityTypes[0].id)
            : null;
    })(),
);
const defaultCondition = ref<string | null>(props.conditions[0] ?? null);
const defaultInspectionDate = ref(todayDate);
const rows = ref<BulkRow[]>([]);

const filteredFacilities = computed(() => {
    if (!selectedFacilityTypeId.value) {
        return [] as Facility[];
    }

    return props.facilities.filter(
        (facility) =>
            String(facility.facility_type_id ?? '') ===
            selectedFacilityTypeId.value,
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
    selectedRows.value.some((row) => !row.condition || !row.inspection_date),
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
        inspection_date: defaultInspectionDate.value,
        condition: defaultCondition.value ?? '',
        comments: '',
        selected: hasPrefilledFacilities.value
            ? prefilledFacilityIds.value.includes(facility.id)
            : true,
    }));
};

watch(selectedFacilityTypeId, initializeRows, { immediate: true });
</script>

<template>
    <Head title="Create inspection" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader
                title="Create inspection"
                subtitle="Create inspections in bulk by facility type."
            />

            <Form
                action="/inspections"
                method="post"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div
                    class="grid gap-4 rounded-xl border border-border/50 bg-card/50 p-4 md:grid-cols-3"
                >
                    <div class="grid gap-2">
                        <Label for="facility_type_id">Facility type</Label>
                        <Select v-model="selectedFacilityTypeId">
                            <SelectTrigger id="facility_type_id" class="w-full">
                                <SelectValue
                                    placeholder="Select facility type"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="facilityType in facilityTypes"
                                    :key="facilityType.id"
                                    :value="String(facilityType.id)"
                                >
                                    {{ facilityType.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="grid gap-2">
                        <Label for="default_condition">Default condition</Label>
                        <Select v-model="defaultCondition">
                            <SelectTrigger
                                id="default_condition"
                                :class="inputClass"
                            >
                                <SelectValue placeholder="Select condition" />
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
                    </div>

                    <div class="grid gap-2">
                        <Label for="default_inspection_date"
                            >Default inspection date</Label
                        >
                        <Input
                            id="default_inspection_date"
                            v-model="defaultInspectionDate"
                            type="date"
                            :max="todayDate"
                        />
                    </div>
                </div>

                <div
                    class="overflow-x-auto rounded-xl border border-border/50 bg-card/50"
                >
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
                                <th class="px-3 py-2 text-left font-semibold">
                                    Facility
                                </th>
                                <th class="px-3 py-2 text-left font-semibold">
                                    Inspection Date
                                </th>
                                <th class="px-3 py-2 text-left font-semibold">
                                    Condition
                                </th>
                                <th class="px-3 py-2 text-left font-semibold">
                                    Comments
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/50">
                            <tr
                                v-for="row in rows"
                                :key="row.facility_id"
                                class="align-top"
                            >
                                <td class="px-3 py-2">
                                    <input
                                        type="checkbox"
                                        v-model="row.selected"
                                    />
                                </td>
                                <td class="px-3 py-2 font-medium">
                                    {{ row.facility_name }}
                                </td>
                                <td class="px-3 py-2">
                                    <Input
                                        v-model="row.inspection_date"
                                        type="date"
                                        :max="todayDate"
                                    />
                                    <InputError
                                        :message="
                                            row.selected &&
                                            selectedRowIndexById[
                                                row.facility_id
                                            ] !== undefined
                                                ? errors[
                                                      `bulk_inspections.${selectedRowIndexById[row.facility_id]}.inspection_date`
                                                  ]
                                                : null
                                        "
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    <Select v-model="row.condition">
                                        <SelectTrigger :class="inputClass">
                                            <SelectValue
                                                placeholder="Select condition"
                                            />
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
                                    <InputError
                                        :message="
                                            row.selected &&
                                            selectedRowIndexById[
                                                row.facility_id
                                            ] !== undefined
                                                ? errors[
                                                      `bulk_inspections.${selectedRowIndexById[row.facility_id]}.condition`
                                                  ]
                                                : null
                                        "
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    <textarea
                                        v-model="row.comments"
                                        :class="textAreaClass"
                                        placeholder="Optional notes"
                                    ></textarea>
                                </td>
                            </tr>
                            <tr v-if="rows.length === 0">
                                <td
                                    colspan="5"
                                    class="px-3 py-8 text-center text-sm text-muted-foreground"
                                >
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
                    <input
                        type="hidden"
                        :name="`bulk_inspections[${index}][facility_id]`"
                        :value="row.facility_id"
                    />
                    <input
                        type="hidden"
                        :name="`bulk_inspections[${index}][inspection_date]`"
                        :value="row.inspection_date"
                    />
                    <input
                        type="hidden"
                        :name="`bulk_inspections[${index}][condition]`"
                        :value="row.condition"
                    />
                    <input
                        type="hidden"
                        :name="`bulk_inspections[${index}][comments]`"
                        :value="row.comments"
                    />
                </div>

                <InputError
                    :message="
                        errors.bulk_inspections ||
                        (selectedRows.length === 0
                            ? 'Select at least one facility row.'
                            : '')
                    "
                />

                <div class="flex items-center gap-4">
                    <Button
                        :disabled="
                            processing ||
                            selectedRows.length === 0 ||
                            hasInvalidSelectedRows
                        "
                    >
                        Submit bulk inspections
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link :href="inspectionsIndex().url">Cancel</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
