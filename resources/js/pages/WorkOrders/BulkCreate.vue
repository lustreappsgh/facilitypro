<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ButtonGroup } from '@/components/ui/button-group';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import { bulkStore as workOrdersBulkStore, index as workOrdersIndex } from '@/routes/work-orders';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface FacilityManager {
    id: number;
    name: string;
}

interface Vendor {
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
    estimated_cost: string;
    selected: boolean;
    facility_manager_id: string;
    review_action: 'approve' | 'reject';
    vendor_id: string;
}

interface Props {
    facilityManagers: FacilityManager[];
    vendors: Vendor[];
    maintenanceRequests: MaintenanceRequestRow[];
    selection?: {
        request_ids?: number[];
        intent?: 'review' | 'create' | null;
    };
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

const textAreaClass =
    'border-input bg-transparent text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] min-h-[76px] w-full rounded-md border px-3 py-2';

const selectedRequestIdSet = new Set((props.selection?.request_ids ?? []).map((id) => Number(id)));
const initiallySelectedRequest = props.maintenanceRequests.find((request) =>
    selectedRequestIdSet.has(request.id),
);
const selectedFacilityManagerId = ref<string | null>(
    initiallySelectedRequest?.facility?.managed_by
        ? String(initiallySelectedRequest.facility.managed_by)
        : (props.facilityManagers[0] ? String(props.facilityManagers[0].id) : null),
);
const selectedVendorId = ref<string | null>(
    props.vendors[0] ? String(props.vendors[0].id) : null,
);
const rows = ref<EditableRow[]>([]);
const pageSubtitle = computed(() =>
    props.selection?.intent === 'create'
        ? 'Finalize approvals and create work orders for the selected requests.'
        : 'Approve or reject requests in bulk for each facility manager.',
);

const filteredRequests = computed(() => {
    if (selectedRequestIdSet.size > 0) {
        return props.maintenanceRequests.filter((request) => selectedRequestIdSet.has(request.id));
    }

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
        estimated_cost:
            request.cost !== null && request.cost !== undefined
                ? String(request.cost)
                : '',
        selected: selectedRequestIdSet.size > 0
            ? selectedRequestIdSet.has(request.id)
            : true,
        facility_manager_id: String(request.facility?.managed_by ?? ''),
        review_action: 'approve',
        vendor_id: selectedVendorId.value ?? '',
    }));
};

const decisionBadgeClass = (row: EditableRow) => {
    if (!row.selected) {
        return 'bg-muted text-muted-foreground';
    }

    if (row.review_action === 'approve') {
        return 'bg-emerald-500/10 text-emerald-700';
    }

    return 'bg-rose-500/10 text-rose-700';
};

const rowHighlightClass = (row: EditableRow) => {
    if (!row.selected) {
        return '';
    }

    return row.review_action === 'approve'
        ? 'bg-emerald-500/5'
        : 'bg-rose-500/5';
};

const setReviewAction = (row: EditableRow, action: 'approve' | 'reject') => {
    row.review_action = action;
    row.selected = true;
};

watch(selectedFacilityManagerId, initializeRows, { immediate: true });
watch(selectedVendorId, () => {
    if (!selectedVendorId.value) {
        return;
    }
    rows.value = rows.value.map((row) => ({
        ...row,
        vendor_id: row.selected ? selectedVendorId.value ?? '' : row.vendor_id,
    }));
});
</script>

<template>
    <Head title="Bulk create work orders" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader title="Review requests" :subtitle="pageSubtitle" />

            <Form
                :action="workOrdersBulkStore().url"
                method="post"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-4 rounded-xl border border-border/50 bg-card/50 p-4">
                    <div class="grid gap-4 md:grid-cols-2">
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
                        <div class="grid gap-2">
                            <Label for="default_vendor_id">Default vendor</Label>
                            <Select v-model="selectedVendorId">
                                <SelectTrigger id="default_vendor_id" class="w-full">
                                    <SelectValue placeholder="Select vendor" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="vendor in vendors"
                                        :key="vendor.id"
                                        :value="String(vendor.id)"
                                    >
                                        {{ vendor.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
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
                                <th class="px-3 py-2 text-left font-semibold">Estimated cost</th>
                                <th class="px-3 py-2 text-left font-semibold">Vendor</th>
                                <th class="px-3 py-2 text-left font-semibold">Decision</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/50">
                            <tr
                                v-for="row in rows"
                                :key="row.maintenance_request_id"
                                class="align-top transition-colors"
                                :class="rowHighlightClass(row)"
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
                                        v-model="row.estimated_cost"
                                        type="number"
                                        step="1"
                                        placeholder="0"
                                        :disabled="row.review_action === 'reject'"
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    <Select v-model="row.vendor_id" :disabled="row.review_action === 'reject'">
                                        <SelectTrigger class="h-9 w-[200px]">
                                            <SelectValue placeholder="Select vendor" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="vendor in vendors"
                                                :key="vendor.id"
                                                :value="String(vendor.id)"
                                            >
                                                {{ vendor.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </td>
                                <td class="px-3 py-2">
                                    <ButtonGroup>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    :variant="row.review_action === 'approve' ? 'secondary' : 'ghost'"
                                                    class="rounded-none border-0 px-3 text-xs font-semibold uppercase tracking-wide text-emerald-700 hover:bg-emerald-500/10"
                                                    @click="setReviewAction(row, 'approve')"
                                                >
                                                    Approved
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent side="top">Approve request</TooltipContent>
                                        </Tooltip>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    :variant="row.review_action === 'reject' ? 'secondary' : 'ghost'"
                                                    class="rounded-none border-l border-border/40 px-3 text-xs font-semibold uppercase tracking-wide text-rose-600 hover:bg-rose-500/10"
                                                    @click="setReviewAction(row, 'reject')"
                                                >
                                                    Rejected
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent side="top">Reject request</TooltipContent>
                                        </Tooltip>
                                    </ButtonGroup>
                                    <div class="mt-2">
                                        <Badge
                                            variant="secondary"
                                            class="rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider"
                                            :class="decisionBadgeClass(row)"
                                        >
                                            {{ row.selected ? row.review_action : 'not selected' }}
                                        </Badge>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="rows.length === 0">
                                <td
                                    colspan="8"
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
                        :name="`bulk_orders[${index}][estimated_cost]`"
                        :value="row.estimated_cost"
                    />
                    <input
                        type="hidden"
                        :name="`bulk_orders[${index}][vendor_id]`"
                        :value="row.vendor_id"
                    />
                    <input
                        type="hidden"
                        :name="`bulk_orders[${index}][review_action]`"
                        :value="row.review_action"
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
                        Submit decisions
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link :href="workOrdersIndex().url">Cancel</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
