<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { bulkStore as workOrdersBulkStore, index as workOrdersIndex } from '@/routes/work-orders';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface FacilityManager {
    id: number;
    name: string;
}

interface MaintenanceRequestRow {
    id: number;
    description?: string | null;
    cost?: number | null;
    facility?: {
        id: number;
        name: string;
        managed_by?: number | null;
    } | null;
    requestType?: {
        id: number;
        name: string;
    } | null;
}

interface EditableRow {
    maintenance_request_id: number;
    facility_name: string;
    request_type_name: string;
    description: string;
    scheduled_date: string;
    estimated_cost: string;
    actual_cost: string;
    selected: boolean;
    facility_manager_id: string;
}

interface Props {
    facilityManagers: FacilityManager[];
    maintenanceRequests: MaintenanceRequestRow[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Work Orders',
        href: workOrdersIndex().url,
    },
    {
        title: 'Bulk Create',
        href: '#',
    },
];

const inputClass =
    'border-input bg-transparent text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] h-9 w-full rounded-md border px-3';
const textAreaClass =
    'border-input bg-transparent text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] min-h-[76px] w-full rounded-md border px-3 py-2';

const selectedFacilityManagerId = ref<string | null>(
    props.facilityManagers[0] ? String(props.facilityManagers[0].id) : null,
);
const rows = ref<EditableRow[]>([]);

const filteredRequests = computed(() => {
    if (!selectedFacilityManagerId.value) {
        return [] as MaintenanceRequestRow[];
    }

    return props.maintenanceRequests.filter(
        (request) =>
            String(request.facility?.managed_by ?? '') === selectedFacilityManagerId.value,
    );
});

const selectedRows = computed(() => rows.value.filter((row) => row.selected));
const allSelected = computed({
    get: () => rows.value.length > 0 && rows.value.every((row) => row.selected),
    set: (value: boolean) => {
        rows.value = rows.value.map((row) => ({ ...row, selected: value }));
    },
});

const initializeRows = () => {
    rows.value = filteredRequests.value.map((request) => ({
        maintenance_request_id: request.id,
        facility_name: request.facility?.name ?? `Facility for request #${request.id}`,
        request_type_name: request.requestType?.name ?? 'N/A',
        description: request.description ?? '',
        scheduled_date: '',
        estimated_cost:
            request.cost !== null && request.cost !== undefined
                ? String(request.cost)
                : '',
        actual_cost: '',
        selected: true,
        facility_manager_id: String(request.facility?.managed_by ?? ''),
    }));
};

watch(selectedFacilityManagerId, initializeRows, { immediate: true });
</script>

<template>
    <Head title="Bulk create work orders" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader title="Bulk create work orders" subtitle="Select request group by facility manager and create at once." />

            <Form
                :action="workOrdersBulkStore().url"
                method="post"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-4 rounded-xl border border-border/50 bg-card/50 p-4">
                    <div class="grid gap-2">
                        <Label for="facility_manager_id">Facility manager</Label>
                        <Select v-model="selectedFacilityManagerId">
                            <SelectTrigger id="facility_manager_id" class="w-full">
                                <SelectValue placeholder="Select facility manager" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="manager in facilityManagers"
                                    :key="manager.id"
                                    :value="String(manager.id)"
                                >
                                    {{ manager.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
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
                                <th class="px-3 py-2 text-left font-semibold">Request</th>
                                <th class="px-3 py-2 text-left font-semibold">Facility</th>
                                <th class="px-3 py-2 text-left font-semibold">Request type</th>
                                <th class="px-3 py-2 text-left font-semibold">Description</th>
                                <th class="px-3 py-2 text-left font-semibold">Scheduled date</th>
                                <th class="px-3 py-2 text-left font-semibold">Estimated cost</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/50">
                            <tr
                                v-for="row in rows"
                                :key="row.maintenance_request_id"
                                class="align-top"
                            >
                                <td class="px-3 py-2">
                                    <input type="checkbox" v-model="row.selected" />
                                </td>
                                <td class="px-3 py-2 font-medium">
                                    #{{ row.maintenance_request_id }}
                                </td>
                                <td class="px-3 py-2">{{ row.facility_name }}</td>
                                <td class="px-3 py-2">{{ row.request_type_name }}</td>
                                <td class="px-3 py-2">
                                    <textarea
                                        v-model="row.description"
                                        :class="textAreaClass"
                                        placeholder="Optional notes"
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    <Input
                                        v-model="row.scheduled_date"
                                        type="date"
                                        :class="inputClass"
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    <Input
                                        v-model="row.estimated_cost"
                                        type="number"
                                        step="1"
                                        placeholder="0"
                                    />
                                </td>
                            </tr>
                            <tr v-if="rows.length === 0">
                                <td
                                    colspan="7"
                                    class="px-3 py-8 text-center text-sm text-muted-foreground"
                                >
                                    No pending maintenance requests found for this facility manager.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-for="(row, index) in selectedRows"
                    :key="`bulk-order-${row.maintenance_request_id}`"
                >
                    <input
                        type="hidden"
                        :name="`bulk_orders[${index}][maintenance_request_id]`"
                        :value="row.maintenance_request_id"
                    />
                    <input
                        type="hidden"
                        :name="`bulk_orders[${index}][scheduled_date]`"
                        :value="row.scheduled_date"
                    />
                    <input
                        type="hidden"
                        :name="`bulk_orders[${index}][estimated_cost]`"
                        :value="row.estimated_cost"
                    />
                    <input
                        type="hidden"
                        :name="`bulk_orders[${index}][actual_cost]`"
                        :value="row.actual_cost"
                    />
                </div>

                <InputError
                    :message="
                        errors.bulk_orders ||
                        (selectedRows.length === 0
                            ? 'Select at least one request to create work orders.'
                            : '')
                    "
                />

                <div class="flex items-center gap-4">
                    <Button
                        :disabled="processing || selectedRows.length === 0"
                    >
                        Create bulk work orders
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link :href="workOrdersIndex().url">Cancel</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
