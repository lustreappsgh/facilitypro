<script setup lang="ts">
import { DatePicker } from '@/components/ui/date-picker';
import WorkOrderController from '@/actions/App/Http/Controllers/WorkOrderController';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as workOrdersIndex } from '@/routes/work-orders';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Facility {
    id: number;
    name: string;
}

interface RequestType {
    id: number;
    name: string;
}

interface Vendor {
    id: number;
    name: string;
}

interface MaintenanceRequest {
    id: number;
    cost?: number | null;
    facility?: Facility | null;
    requestType?: RequestType | null;
    request_type?: RequestType | null;
    description?: string | null;
}

interface Props {
    maintenanceRequests: MaintenanceRequest[];
    selectedRequestId?: number | null;
    vendors: Vendor[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Work Orders',
        href: workOrdersIndex().url,
    },
    {
        title: 'Create',
        href: '#',
    },
];

const statusMode = ref<'assigned'>('assigned');
const selectedRequestId = ref<string | number | null>(
    props.selectedRequestId ?? null,
);
const estimatedCost = ref('');
const scheduledDate = ref('');
const selectedVendorId = ref<string | number | null>(null);

const selectedRequest = computed(() =>
    props.maintenanceRequests.find(
        (request) => String(request.id) === String(selectedRequestId.value ?? ''),
    ),
);

const selectedRequestType = computed(() =>
    selectedRequest.value?.requestType?.name ??
    selectedRequest.value?.request_type?.name ??
    'Select a maintenance request',
);

const selectedRequestDescription = computed(() =>
    selectedRequest.value?.description?.trim() || 'No description provided.',
);

watch(
    selectedRequest,
    (request) => {
        if (request?.cost !== null && request?.cost !== undefined) {
            estimatedCost.value = String(request.cost);
            return;
        }

        estimatedCost.value = '';
    },
    { immediate: true },
);
</script>

<template>
    <Head title="Create work order" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader title="Create work order" subtitle="Create from a maintenance request, then submit for approval." />

            <Form
                v-bind="WorkOrderController.store.form()"
                class="max-w-3xl space-y-6"
                v-slot="{ errors, processing }"
            >
                <input type="hidden" name="status" :value="statusMode" />
                <input
                    v-if="selectedRequestId !== null && selectedRequestId !== ''"
                    type="hidden"
                    name="maintenance_request_id"
                    :value="selectedRequestId"
                />
                <input
                    v-if="selectedVendorId !== null && selectedVendorId !== ''"
                    type="hidden"
                    name="vendor_id"
                    :value="selectedVendorId"
                />

                <div class="grid gap-2">
                    <Label for="maintenance_request_id">
                        Maintenance request
                    </Label>
                    <Select v-model="selectedRequestId" required>
                        <SelectTrigger id="maintenance_request_id" class="w-full">
                            <SelectValue placeholder="Select a request" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="request in maintenanceRequests"
                                :key="request.id"
                                :value="String(request.id)"
                            >
                                Request #{{ request.id }}{{ request.facility ? ` - ${request.facility.name}` : '' }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.maintenance_request_id" />
                </div>

                <div class="grid gap-4 rounded-xl border border-muted bg-muted/10 p-4">
                    <div class="grid gap-2">
                        <p class="text-xs font-semibold uppercase text-muted-foreground">
                            Request type
                        </p>
                        <p class="text-sm font-medium text-gray-900">
                            {{ selectedRequestType }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <p class="text-xs font-semibold uppercase text-muted-foreground">
                            Description
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ selectedRequestDescription }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="scheduled_date">Scheduled date</Label>
                    <DatePicker
                        id="scheduled_date"
                        name="scheduled_date"
                        v-model="scheduledDate"
                     />
                    <InputError :message="errors.scheduled_date" />
                </div>

                <div class="grid gap-2">
                    <Label for="vendor_id">Vendor</Label>
                    <Select v-model="selectedVendorId" required>
                        <SelectTrigger id="vendor_id" class="w-full">
                            <SelectValue placeholder="Select a vendor" />
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
                    <InputError :message="errors.vendor_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="estimated_cost">Estimated cost</Label>
                    <Input
                        id="estimated_cost"
                        name="estimated_cost"
                        type="number"
                        step="1"
                        placeholder="0"
                        v-model="estimatedCost"
                    />
                    <InputError :message="errors.estimated_cost" />
                </div>

                <div class="grid gap-2">
                    <Label for="actual_cost">Actual cost</Label>
                    <Input
                        id="actual_cost"
                        name="actual_cost"
                        type="number"
                        step="1"
                        placeholder="0"
                    />
                    <InputError :message="errors.actual_cost" />
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <Button type="submit" :disabled="processing">
                        Create work order
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link :href="workOrdersIndex().url">Cancel</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
