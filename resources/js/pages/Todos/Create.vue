<script setup lang="ts">
import { DatePicker } from '@/components/ui/date-picker';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as todosIndex, store } from '@/routes/todos';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';

interface Facility {
    id: number;
    name: string;
}

interface Props {
    data: {
        facilities: Facility[];
    };
    permissions: string[];
    routes: Record<string, string>;
    meta: Record<string, string>;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Weekly Todos',
        href: todosIndex().url,
    },
    {
        title: 'Create',
        href: '#',
    },
];

const selectClass =
    'border-input bg-transparent text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] h-9 w-full rounded-md border px-3';

const textAreaClass =
    'border-input bg-transparent text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] min-h-[120px] w-full rounded-md border px-3 py-2';
</script>

<template>
    <Head title="Create todo" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader title="Create todo" subtitle="Plan a weekly facility task." />

            <Form
                v-bind="store.form()"
                class="max-w-3xl space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="facility_id">Facility</Label>
                    <select
                        id="facility_id"
                        name="facility_id"
                        :class="selectClass"
                        required
                    >
                        <option value="" disabled>Select a facility</option>
                        <option
                            v-for="facility in data.facilities"
                            :key="facility.id"
                            :value="facility.id"
                        >
                            {{ facility.name }}
                        </option>
                    </select>
                    <InputError :message="errors.facility_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="week">Week start</Label>
                    <DatePicker id="week" name="week" required  />
                    <InputError :message="errors.week" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        name="description"
                        :class="textAreaClass"
                        placeholder="Describe the weekly action item"
                        required
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="processing">Create todo</Button>
                    <Button variant="ghost" as-child>
                        <Link :href="todosIndex().url">Cancel</Link>
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
