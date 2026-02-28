<script setup lang="ts">
import FacilityController from '@/actions/App/Http/Controllers/FacilityController';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as facilitiesIndex } from '@/routes/facilities';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';

interface FacilityType {
    id: number;
    name: string;
}

interface FacilityParent {
    id: number;
    name: string;
}

interface FacilityManager {
    id: number;
    name: string;
}

interface Props {
    facilityTypes: FacilityType[];
    parents: FacilityParent[];
    users: FacilityManager[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Facilities',
        href: facilitiesIndex().url,
    },
    {
        title: 'Create',
        href: '#',
    },
];

const selectClass =
    'h-10 w-full rounded-lg border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';

const conditions = ['Good', 'Bad', 'OutOfOrder'];
</script>

<template>
    <Head title="Create facility" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader title="Create facility" subtitle="Add facility details and ownership." />

            <div class="relative rounded-2xl border border-sidebar-border/70 bg-muted/30 p-6">
                <div
                    class="mx-auto w-full max-w-3xl rounded-2xl border border-border bg-card p-6 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold">Facility create</h2>
                            <p class="text-sm text-muted-foreground">
                                Provide the core details used for reporting.
                            </p>
                        </div>
                        <Button variant="ghost" as-child>
                            <Link :href="facilitiesIndex().url">Cancel</Link>
                        </Button>
                    </div>

                    <Form
                        v-bind="FacilityController.store.form()"
                        class="mt-6 space-y-6"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="grid gap-2 md:col-span-2">
                                <Label for="name">Facility name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    placeholder="North Campus Gym"
                                    required
                                />
                                <InputError :message="errors.name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="facility_type_id">Facility type</Label>
                                <select
                                    id="facility_type_id"
                                    name="facility_type_id"
                                    :class="selectClass"
                                    required
                                >
                                    <option value="" disabled>Select a type</option>
                                    <option
                                        v-for="type in facilityTypes"
                                        :key="type.id"
                                        :value="type.id"
                                    >
                                        {{ type.name }}
                                    </option>
                                </select>
                                <InputError :message="errors.facility_type_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="condition">Condition</Label>
                                <select
                                    id="condition"
                                    name="condition"
                                    :class="selectClass"
                                    required
                                >
                                    <option value="" disabled>Select condition</option>
                                    <option
                                        v-for="condition in conditions"
                                        :key="condition"
                                        :value="condition"
                                    >
                                        {{ condition }}
                                    </option>
                                </select>
                                <InputError :message="errors.condition" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="parent_id">Parent facility</Label>
                                <select
                                    id="parent_id"
                                    name="parent_id"
                                    :class="selectClass"
                                >
                                    <option value="">No parent</option>
                                    <option
                                        v-for="parent in parents"
                                        :key="parent.id"
                                        :value="parent.id"
                                    >
                                        {{ parent.name }}
                                    </option>
                                </select>
                                <InputError :message="errors.parent_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="managed_by">Facility manager</Label>
                                <select
                                    id="managed_by"
                                    name="managed_by"
                                    :class="selectClass"
                                >
                                    <option value="">Unassigned</option>
                                    <option
                                        v-for="manager in users"
                                        :key="manager.id"
                                        :value="manager.id"
                                    >
                                        {{ manager.name }}
                                    </option>
                                </select>
                                <InputError :message="errors.managed_by" />
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <Button :disabled="processing">Create facility</Button>
                            <Button variant="secondary" as-child>
                                <Link :href="facilitiesIndex().url">Back</Link>
                            </Button>
                        </div>
                    </Form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
