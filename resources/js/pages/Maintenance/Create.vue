<script setup lang="ts">
import MaintenanceRequestController from '@/actions/App/Http/Controllers/MaintenanceRequestController';
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
import { index as maintenanceIndex } from '@/routes/maintenance';
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

interface RequestType {
    id: number;
    name: string;
}

type Priority = 'low' | 'medium' | 'high';

interface Props {
    facilities: Facility[];
    facilityTypes: FacilityType[];
    requestTypes: RequestType[];
    priorities: Priority[];
    selectedFacilityId?: number | null;
}

const props = defineProps<Props>();

interface BulkRow {
    facility_id: number;
    facility_name: string;
    request_type_id: string;
    priority: Priority;
    description: string;
    cost: string;
    selected: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Maintenance Requests',
        href: maintenanceIndex().url,
    },
    {
        title: 'Create',
        href: '#',
    },
];

const textAreaClass =
    'border-input bg-transparent text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] min-h-[76px] w-full rounded-md border px-3 py-2';

const hasPrefilledFacility = computed(() => Boolean(props.selectedFacilityId));
const prefilledFacility = computed(() =>
    props.facilities.find(
        (facility) => facility.id === props.selectedFacilityId,
    ),
);

const selectedFacilityTypeId = ref<string | null>(
    prefilledFacility.value?.facility_type_id
        ? String(prefilledFacility.value.facility_type_id)
        : props.facilityTypes[0]
          ? String(props.facilityTypes[0].id)
          : null,
);
const defaultRequestTypeId = ref<string | null>(
    props.requestTypes[0] ? String(props.requestTypes[0].id) : null,
);
const defaultPriority = ref<Priority>('medium');
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
const hasInvalidSelectedRows = computed(() =>
    selectedRows.value.some((row) => !row.request_type_id),
);
const allSelected = computed({
    get: () => rows.value.length > 0 && rows.value.every((row) => row.selected),
    set: (value: boolean) => {
        rows.value = rows.value.map((row) => ({ ...row, selected: value }));
    },
});
const facilityErrorMessage = computed(
    () =>
        'Select at least one facility and provide request type for selected rows.',
);

const initializeRows = () => {
    rows.value = filteredFacilities.value.map((facility) => ({
        facility_id: facility.id,
        facility_name: facility.name,
        request_type_id: defaultRequestTypeId.value ?? '',
        priority: defaultPriority.value,
        description: '',
        cost: '',
        selected: hasPrefilledFacility.value
            ? facility.id === props.selectedFacilityId
            : true,
    }));
};

watch(selectedFacilityTypeId, initializeRows, { immediate: true });
</script>

<template>
    <Head title="Create maintenance request" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader
                title="Create maintenance request"
                subtitle="Requests are assigned automatically to the Sunday-start week in which they are submitted."
            />

            <div
                v-if="hasPrefilledFacility && prefilledFacility"
                class="rounded-lg border border-border/60 bg-muted/20 px-3 py-2 text-xs text-muted-foreground"
            >
                Prefilled facility context:
                <span class="font-semibold text-foreground">{{
                    prefilledFacility.name
                }}</span>
            </div>

            <Form
                v-bind="MaintenanceRequestController.store.form()"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div
                    class="grid gap-4 rounded-xl border border-border/50 bg-card/50 p-4 md:grid-cols-2"
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
                        <Label for="default_request_type"
                            >Default request type</Label
                        >
                        <Select v-model="defaultRequestTypeId">
                            <SelectTrigger
                                id="default_request_type"
                                class="w-full"
                            >
                                <SelectValue placeholder="Select a type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="type in requestTypes"
                                    :key="type.id"
                                    :value="String(type.id)"
                                >
                                    {{ type.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="grid gap-2">
                        <Label for="default_priority">Default priority</Label>
                        <Select v-model="defaultPriority">
                            <SelectTrigger id="default_priority" class="w-full">
                                <SelectValue placeholder="Select priority" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="priority in priorities"
                                    :key="priority"
                                    :value="priority"
                                >
                                    {{ priority }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
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
                                    Request type
                                </th>
                                <th class="px-3 py-2 text-left font-semibold">
                                    Priority
                                </th>
                                <th class="px-3 py-2 text-left font-semibold">
                                    Description
                                </th>
                                <th class="px-3 py-2 text-left font-semibold">
                                    Estimated cost
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/50">
                            <tr
                                v-for="(row, index) in rows"
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
                                    <Select v-model="row.request_type_id">
                                        <SelectTrigger class="w-full">
                                            <SelectValue
                                                placeholder="Select type"
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="type in requestTypes"
                                                :key="type.id"
                                                :value="String(type.id)"
                                            >
                                                {{ type.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        :message="
                                            errors[
                                                `bulk_requests.${index}.request_type_id`
                                            ]
                                        "
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    <Select v-model="row.priority">
                                        <SelectTrigger class="w-full">
                                            <SelectValue placeholder="Select priority" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="priority in priorities"
                                                :key="priority"
                                                :value="priority"
                                            >
                                                {{ priority }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </td>
                                <td class="px-3 py-2">
                                    <textarea
                                        v-model="row.description"
                                        :class="textAreaClass"
                                        placeholder="Optional details"
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    <Input
                                        v-model="row.cost"
                                        type="number"
                                        step="1"
                                        placeholder="0"
                                    />
                                </td>
                            </tr>
                            <tr v-if="rows.length === 0">
                                <td
                                    colspan="6"
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
                        :name="`bulk_requests[${index}][facility_id]`"
                        :value="row.facility_id"
                    />
                    <input
                        type="hidden"
                        :name="`bulk_requests[${index}][request_type_id]`"
                        :value="row.request_type_id"
                    />
                    <input
                        type="hidden"
                        :name="`bulk_requests[${index}][priority]`"
                        :value="row.priority"
                    />
                    <input
                        type="hidden"
                        :name="`bulk_requests[${index}][description]`"
                        :value="row.description"
                    />
                    <input
                        type="hidden"
                        :name="`bulk_requests[${index}][cost]`"
                        :value="row.cost"
                    />
                </div>

                <InputError
                    :message="
                        errors.bulk_requests ||
                        errors.facility_id ||
                        errors.facility_ids ||
                        (selectedRows.length === 0 ? facilityErrorMessage : '')
                    "
                />

                <div class="flex items-center gap-4">
                    <Button
                        type="submit"
                        name="submission_route"
                        value="maintenance_manager"
                        :disabled="
                            processing ||
                            selectedRows.length === 0 ||
                            hasInvalidSelectedRows
                        "
                    >
                        Submit to Maintenance Manager
                    </Button>
                    <Button
                        type="submit"
                        variant="secondary"
                        name="submission_route"
                        value="admin"
                        :disabled="
                            processing ||
                            selectedRows.length === 0 ||
                            hasInvalidSelectedRows
                        "
                    >
                        Submit to Administrator
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link :href="maintenanceIndex().url">Cancel</Link>
                    </Button>
                </div>
                <InputError :message="errors.submission_route" />
            </Form>
        </div>
    </AppLayout>
</template>
