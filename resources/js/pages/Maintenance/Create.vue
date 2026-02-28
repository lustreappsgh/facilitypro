<script setup lang="ts">
import MaintenanceRequestController from '@/actions/App/Http/Controllers/MaintenanceRequestController';
import MaintenanceRequestForm from '@/components/MaintenanceRequestForm.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as maintenanceIndex } from '@/routes/maintenance';
import type { BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/vue3';

interface Facility {
    id: number;
    name: string;
}

interface RequestType {
    id: number;
    name: string;
}

interface Props {
    facilities: Facility[];
    requestTypes: RequestType[];
}

defineProps<Props>();

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

const cancelHref = maintenanceIndex().url;
</script>

<template>
    <Head title="Create maintenance request" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader title="Create maintenance request" subtitle="Log a new maintenance request." />

            <Form
                v-bind="MaintenanceRequestController.store.form()"
                class="max-w-3xl space-y-6"
                v-slot="{ errors, processing }"
            >
                <MaintenanceRequestForm
                    :facilities="facilities"
                    :request-types="requestTypes"
                    :errors="errors"
                    :processing="processing"
                    :cancel-href="cancelHref"
                />
            </Form>
        </div>
    </AppLayout>
</template>
