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
import { index as workOrdersIndex, show as workOrdersShow } from '@/routes/work-orders';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Facility {
    id: number;
    name: string;
}

interface RequestType {
    id: number;
    name: string;
}

interface MaintenanceRequest {
    id: number;
    facility?: Facility | null;
    requestType?: RequestType | null;
}

interface Vendor {
    id: number;
    name: string;
}

interface WorkOrder {
    id: number;
    maintenance_request_id?: number | null;
    vendor_id?: number | null;
    scheduled_date?: string | null;
    scheduled_date_raw?: string | null;
    estimated_cost?: number | null;
    actual_cost?: number | null;
    status?: string | null;
    payment?: {
        status?: string | null;
    } | null;
}

interface Props {
    workOrder: WorkOrder;
    maintenanceRequests: MaintenanceRequest[];
    vendors: Vendor[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Work Orders',
        href: workOrdersIndex().url,
    },
    {
        title: `Work Order #${props.workOrder.id}`,
        href: workOrdersShow(props.workOrder.id).url,
    },
    {
        title: 'Edit',
        href: '#',
    },
];

const paymentStatus = computed(() => props.workOrder.payment?.status ?? 'pending');
const executionUnlocked = computed(() =>
    ['approved', 'paid'].includes(paymentStatus.value)
    || ['in_progress', 'completed', 'cancelled'].includes(props.workOrder.status ?? ''),
);
const selectedMaintenanceRequestId = ref(
    props.workOrder.maintenance_request_id ? String(props.workOrder.maintenance_request_id) : '',
);
const selectedVendorId = ref(
    props.workOrder.vendor_id ? String(props.workOrder.vendor_id) : '',
);
const selectedStatus = ref(props.workOrder.status ?? '');
</script>

<template>
    <Head title="Edit work order" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader title="Edit work order" subtitle="Update schedules, vendors, and costs." />

            <Form
                v-bind="WorkOrderController.update.form(workOrder.id)"
                class="max-w-3xl space-y-6"
                v-slot="{ errors, processing }"
            >
                <input
                    type="hidden"
                    name="maintenance_request_id"
                    :value="selectedMaintenanceRequestId"
                />
                <input type="hidden" name="vendor_id" :value="selectedVendorId" />
                <input type="hidden" name="status" :value="selectedStatus" />
                <div class="grid gap-2">
                    <Label for="maintenance_request_id">
                        Maintenance request
                    </Label>
                    <Select v-model="selectedMaintenanceRequestId">
                        <SelectTrigger id="maintenance_request_id" class="w-full">
                            <SelectValue placeholder="Select a request" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="request in maintenanceRequests"
                                :key="request.id"
                                :value="String(request.id)"
                            >
                                Request #{{ request.id }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.maintenance_request_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="vendor_id">Vendor</Label>
                    <Select v-model="selectedVendorId" :disabled="!executionUnlocked">
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
                    <p
                        v-if="!executionUnlocked"
                        class="text-xs text-muted-foreground"
                    >
                        Vendor assignment is available after admin approval.
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="scheduled_date">Scheduled date</Label>
                    <DatePicker
                        id="scheduled_date"
                        name="scheduled_date"
                        :model-value="workOrder.scheduled_date_raw ?? ''"
                     />
                    <InputError :message="errors.scheduled_date" />
                </div>

                <div class="grid gap-2">
                    <Label for="estimated_cost">Estimated cost</Label>
                    <Input
                        id="estimated_cost"
                        name="estimated_cost"
                        type="number"
                        step="1"
                        placeholder="0"
                        :model-value="workOrder.estimated_cost ?? ''"
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
                        :model-value="workOrder.actual_cost ?? ''"
                    />
                    <InputError :message="errors.actual_cost" />
                </div>

                <div class="grid gap-2">
                    <Label for="status">Status</Label>
                    <Select v-model="selectedStatus" :disabled="!executionUnlocked">
                        <SelectTrigger id="status" class="w-full">
                            <SelectValue placeholder="Select status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="in_progress">In progress</SelectItem>
                            <SelectItem value="completed">Completed</SelectItem>
                            <SelectItem value="cancelled">Cancelled</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.status" />
                    <p
                        v-if="!executionUnlocked"
                        class="text-xs text-muted-foreground"
                    >
                        Status updates are available after admin approval.
                    </p>
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="processing">Save changes</Button>
                    <Button variant="ghost" as-child>
                        <Link :href="workOrdersShow(workOrder.id).url">Cancel</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
