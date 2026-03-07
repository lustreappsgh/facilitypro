<script setup lang="ts">
import MaintenanceRequestController from '@/actions/App/Http/Controllers/MaintenanceRequestController';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as maintenanceIndex, show as maintenanceShow } from '@/routes/maintenance';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

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
    facility_id?: number | null;
    request_type_id?: number | null;
    description?: string | null;
    cost?: number | null;
    week_start?: string | null;
}

interface Props {
    request: MaintenanceRequest;
    facilities: Facility[];
    requestTypes: RequestType[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Maintenance Requests',
        href: maintenanceIndex().url,
    },
    {
        title: `Request #${props.request.id}`,
        href: maintenanceShow(props.request).url,
    },
    {
        title: 'Edit',
        href: '#',
    },
];

const textAreaClass =
    'border-input bg-transparent text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] min-h-[120px] w-full rounded-md border px-3 py-2';

const selectedFacilityId = ref(
    props.request.facility_id ? String(props.request.facility_id) : '',
);
const selectedRequestTypeId = ref(
    props.request.request_type_id ? String(props.request.request_type_id) : '',
);
</script>

<template>
    <Head title="Edit maintenance request" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader title="Edit maintenance request" subtitle="Update request details and costs." />

            <Form
                v-bind="MaintenanceRequestController.update.form(request)"
                class="max-w-3xl space-y-6"
                v-slot="{ errors, processing }"
            >
                <input type="hidden" name="facility_id" :value="selectedFacilityId" />
                <input type="hidden" name="request_type_id" :value="selectedRequestTypeId" />
                <div class="grid gap-2">
                    <Label for="facility_id">Facility</Label>
                    <Select v-model="selectedFacilityId" required>
                        <SelectTrigger id="facility_id" class="w-full">
                            <SelectValue placeholder="Select a facility" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="facility in facilities"
                                :key="facility.id"
                                :value="String(facility.id)"
                            >
                                {{ facility.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.facility_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="request_type_id">Request type</Label>
                    <Select v-model="selectedRequestTypeId" required>
                        <SelectTrigger id="request_type_id" class="w-full">
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
                    <InputError :message="errors.request_type_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        name="description"
                        :class="textAreaClass"
                        placeholder="Describe the issue"
                        :value="request.description ?? ''"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="cost">Estimated cost</Label>
                    <Input
                        id="cost"
                        name="cost"
                        type="number"
                        step="1"
                        placeholder="0"
                        :model-value="request.cost ?? ''"
                    />
                    <InputError :message="errors.cost" />
                </div>

                <div class="grid gap-2">
                    <Label for="week_start">Week start</Label>
                    <Input
                        id="week_start"
                        name="week_start"
                        type="date"
                        :model-value="request.week_start ?? ''"
                    />
                    <InputError :message="errors.week_start" />
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="processing">Save changes</Button>
                    <Button variant="ghost" as-child>
                        <Link :href="maintenanceShow(request).url">Cancel</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
