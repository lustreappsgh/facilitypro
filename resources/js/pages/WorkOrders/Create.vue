<script setup lang="ts">
import { DatePicker } from '@/components/ui/date-picker';
import WorkOrderController from '@/actions/App/Http/Controllers/WorkOrderController';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { create as workOrdersCreate, index as workOrdersIndex } from '@/routes/work-orders';
import { store as vendorStore } from '@/routes/vendors';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link, useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

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
    cost?: number | null;
    facility?: Facility | null;
    requestType?: RequestType | null;
    request_type?: RequestType | null;
    description?: string | null;
}

interface Vendor {
    id: number;
    name: string;
}

interface Props {
    maintenanceRequests: MaintenanceRequest[];
    vendors: Vendor[];
    selectedRequestId?: number | null;
    selectedVendorId?: number | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Work Orders',
        href: workOrdersIndex().url,
    },
    {
        title: 'Assign vendor',
        href: '#',
    },
];

const selectClass =
    'border-input bg-transparent text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] h-9 w-full rounded-md border px-3';

const statusMode = ref<'in_progress'>('in_progress');
const selectedVendor = ref<string | number | null>(
    props.selectedVendorId ?? null,
);
const selectedRequestId = ref<string | number | null>(
    props.selectedRequestId ?? null,
);
const vendorDialogOpen = ref(false);
const estimatedCost = ref('');
const scheduledDate = ref('');

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

const vendorForm = useForm({
    name: '',
    email: '',
    phone: '',
    status: 'active',
});

const vendorRedirectTo = computed(() => {
    if (!selectedRequestId.value) {
        return workOrdersCreate().url;
    }

    return workOrdersCreate({
        query: { maintenance_request_id: String(selectedRequestId.value) },
    }).url;
});

const submitVendor = () => {
    vendorForm
        .transform((data) => ({
            ...data,
            redirect_to: vendorRedirectTo.value,
        }))
        .post(vendorStore().url, {
            preserveScroll: true,
            onSuccess: () => {
                vendorDialogOpen.value = false;
                vendorForm.reset();
            },
        });
};
</script>

<template>
    <Head title="Assign work order" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader title="Assign work order" subtitle="Assign a vendor and schedule work." />

            <Form
                v-bind="WorkOrderController.store.form()"
                class="max-w-3xl space-y-6"
                v-slot="{ errors, processing }"
            >
                <input type="hidden" name="status" :value="statusMode" />

                <div class="grid gap-2">
                    <Label for="maintenance_request_id">
                        Maintenance request
                    </Label>
                    <select
                        id="maintenance_request_id"
                        name="maintenance_request_id"
                        :class="selectClass"
                        v-model="selectedRequestId"
                        required
                    >
                        <option value="" disabled>
                            Select a request
                        </option>
                        <option
                            v-for="request in maintenanceRequests"
                            :key="request.id"
                            :value="request.id"
                        >
                            Request #{{ request.id }}{{
                                request.facility ? ` — ${request.facility.name}` : ''
                            }}
                        </option>
                    </select>
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
                    <div class="flex items-center justify-between">
                        <Label for="vendor_id">Vendor</Label>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            class="h-8 w-8"
                            @click="vendorDialogOpen = true"
                            aria-label="Create vendor"
                        >
                            <Plus class="h-4 w-4" />
                        </Button>
                    </div>
                    <select
                        id="vendor_id"
                        name="vendor_id"
                        :class="selectClass"
                        required
                        v-model="selectedVendor"
                    >
                        <option value="" disabled>Select a vendor</option>
                        <option
                            v-for="vendor in vendors"
                            :key="vendor.id"
                            :value="vendor.id"
                        >
                            {{ vendor.name }}
                        </option>
                    </select>
                    <InputError :message="errors.vendor_id" />
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
                    <Label for="estimated_cost">Estimated cost</Label>
                    <Input
                        id="estimated_cost"
                        name="estimated_cost"
                        type="number"
                        step="1"
                        placeholder="0"
                        v-model="estimatedCost"
                        readonly
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

            <Dialog v-model:open="vendorDialogOpen">
                <DialogContent class="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Create vendor</DialogTitle>
                        <DialogDescription>
                            Add a vendor without leaving this work order.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-2">
                        <div class="grid gap-2">
                            <Label for="vendor_name">Vendor name</Label>
                            <Input
                                id="vendor_name"
                                v-model="vendorForm.name"
                                placeholder="Acme Services"
                                required
                            />
                            <InputError :message="vendorForm.errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="vendor_email">Email</Label>
                            <Input
                                id="vendor_email"
                                v-model="vendorForm.email"
                                type="email"
                                placeholder="vendor@example.com"
                            />
                            <InputError :message="vendorForm.errors.email" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="vendor_phone">Phone</Label>
                            <Input
                                id="vendor_phone"
                                v-model="vendorForm.phone"
                                placeholder="(555) 000-0000"
                            />
                            <InputError :message="vendorForm.errors.phone" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="vendor_status">Status</Label>
                            <select
                                id="vendor_status"
                                v-model="vendorForm.status"
                                :class="selectClass"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <InputError :message="vendorForm.errors.status" />
                        </div>
                    </div>
                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="vendorDialogOpen = false"
                            :disabled="vendorForm.processing"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="button"
                            :disabled="vendorForm.processing"
                            @click="submitVendor"
                        >
                            Create vendor
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
