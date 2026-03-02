<script setup lang="ts">
import { DatePicker } from '@/components/ui/date-picker';
import WorkOrderController from '@/actions/App/Http/Controllers/WorkOrderController';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as workOrdersIndex, show as workOrdersShow } from '@/routes/work-orders';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

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

const selectClass =
    'border-input bg-transparent text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] h-9 w-full rounded-md border px-3';

const paymentStatus = computed(() => props.workOrder.payment?.status ?? 'pending');
const executionUnlocked = computed(() =>
    ['approved', 'paid'].includes(paymentStatus.value),
);
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
                <div class="grid gap-2">
                    <Label for="maintenance_request_id">
                        Maintenance request
                    </Label>
                    <select
                        id="maintenance_request_id"
                        name="maintenance_request_id"
                        :class="selectClass"
                    >
                        <option value="" disabled>
                            Select a request
                        </option>
                        <option
                            v-for="request in maintenanceRequests"
                            :key="request.id"
                            :value="request.id"
                            :selected="request.id === workOrder.maintenance_request_id"
                        >
                            Request #{{ request.id }}
                        </option>
                    </select>
                    <InputError :message="errors.maintenance_request_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="vendor_id">Vendor</Label>
                    <select
                        id="vendor_id"
                        name="vendor_id"
                        :class="selectClass"
                        :disabled="!executionUnlocked"
                    >
                        <option value="" disabled>
                            Select a vendor
                        </option>
                        <option
                            v-for="vendor in vendors"
                            :key="vendor.id"
                            :value="vendor.id"
                            :selected="vendor.id === workOrder.vendor_id"
                        >
                            {{ vendor.name }}
                        </option>
                    </select>
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
                    <select
                        id="status"
                        name="status"
                        :class="selectClass"
                        :disabled="!executionUnlocked"
                    >
                        <option value="" disabled>Select status</option>
                        <option
                            value="in_progress"
                            :selected="workOrder.status === 'in_progress'"
                        >
                            In progress
                        </option>
                        <option
                            value="completed"
                            :selected="workOrder.status === 'completed'"
                        >
                            Completed
                        </option>
                        <option
                            value="cancelled"
                            :selected="workOrder.status === 'cancelled'"
                        >
                            Cancelled
                        </option>
                    </select>
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
