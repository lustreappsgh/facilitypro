<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { ButtonGroup } from '@/components/ui/button-group';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as todosIndex, update } from '@/routes/todos';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Facility {
    id: number;
    name: string;
}

interface Todo {
    id: number;
    facility_id: number;
    description: string;
    week_start: string | null;
}

interface Props {
    data: {
        todo: Todo;
        facilities: Facility[];
    };
    permissions: string[];
    routes: Record<string, string>;
    meta: Record<string, string>;
}

const props = defineProps<Props>();

const description = ref(props.data.todo.description);
const facilityId = ref(String(props.data.todo.facility_id));

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Weekly Todos',
        href: todosIndex().url,
    },
    {
        title: 'Edit',
        href: '#',
    },
];

const textAreaClass =
    'border-input bg-transparent text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] min-h-[120px] w-full rounded-md border px-3 py-2';
</script>

<template>
    <Head title="Edit todo" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader title="Edit todo" subtitle="Update the task details. The original Sunday-start submission week stays fixed." />

            <Form
                v-bind="update.form(data.todo)"
                class="max-w-3xl space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="facility_id">Facility</Label>
                    <input type="hidden" name="facility_id" :value="facilityId" />
                    <Select v-model="facilityId">
                        <SelectTrigger id="facility_id" class="w-full">
                            <SelectValue placeholder="Select a facility" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="facility in data.facilities"
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
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        name="description"
                        :class="textAreaClass"
                        required
                        v-model="description"
                    />
                    <InputError :message="errors.description" />
                </div>

                <ButtonGroup class="border-border/60 bg-background/80 dark:bg-muted/30">
                    <Button class="rounded-none" :disabled="processing">Save changes</Button>
                    <Button variant="ghost" class="rounded-none" as-child>
                        <Link :href="todosIndex().url">Cancel</Link>
                    </Button>
                </ButtonGroup>
            </Form>
        </div>
    </AppLayout>
</template>

