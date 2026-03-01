<script setup lang="ts">
import InspectionController from '@/actions/App/Http/Controllers/InspectionController';
import InspectionForm from '@/components/InspectionForm.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as inspectionsIndex } from '@/routes/inspections';
import type { BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/vue3';

interface Facility {
    id: number;
    name: string;
}

interface Props {
    facilities: Facility[];
    conditions: string[];
    selectedFacilityId?: number | null;
}

const props = defineProps<Props>();

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

const cancelHref = inspectionsIndex().url;
</script>

<template>
    <Head title="Create inspection" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader title="Create inspection" subtitle="Record a new facility inspection." />

            <Form
                v-bind="InspectionController.store.form()"
                class="max-w-3xl space-y-6"
                v-slot="{ errors, processing }"
            >
            <InspectionForm
                :facilities="props.facilities"
                :conditions="props.conditions"
                :errors="errors"
                :processing="processing"
                :cancel-href="cancelHref"
                :selected-facility-id="props.selectedFacilityId ?? null"
            />
            </Form>
        </div>
    </AppLayout>
</template>
