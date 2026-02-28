<script setup lang="ts">
import FacilityController from '@/actions/App/Http/Controllers/FacilityController';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as facilitiesIndex } from '@/routes/facilities';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';
import { computed } from 'vue';

interface FacilityType {
    id: number;
    name: string;
}

interface FacilityManager {
    id: number;
    name: string;
}

interface FacilityParent {
    id: number;
    name: string;
}

interface Facility {
    id: number;
    name: string;
    facility_type_id: number | null;
    parent_id: number | null;
    managed_by: number | null;
    condition: string | null;
}

interface Props {
    facility: Facility;
    facilityTypes: FacilityType[];
    parents: FacilityParent[];
    users: FacilityManager[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Facilities',
        href: facilitiesIndex().url,
    },
    {
        title: 'Details',
        href: '#',
    },
];

const selectClass =
    'h-10 w-full rounded-lg border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';

const conditions = ['Good', 'Bad', 'OutOfOrder'];

const { can } = usePermissions();

const isFacilityManagerView = computed(
    () =>
        can('facilities.update') &&
        !can('facilities.assign_manager') &&
        !can('facilities.create'),
);

const facilityTypeName = computed(
    () =>
        props.facilityTypes.find(
            (type) => type.id === props.facility.facility_type_id,
        )?.name ?? 'Unassigned',
);

const parentName = computed(
    () =>
        props.parents.find((parent) => parent.id === props.facility.parent_id)
            ?.name ?? 'No parent',
);

const managerName = computed(
    () =>
        props.users.find((manager) => manager.id === props.facility.managed_by)
            ?.name ?? 'Unassigned',
);
</script>

<template>
    <Head title="Facility details" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader title="Facility details" subtitle="Review and update facility information.">
                <template #actions>
                    <Button variant="secondary" as-child>
                        <Link :href="facilitiesIndex().url">Back to list</Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="rounded-2xl border border-sidebar-border/70 bg-card p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">
                            {{ facility.name }}
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            {{ facilityTypeName }}
                        </p>
                    </div>
                    <Badge variant="secondary" class="capitalize">
                        {{ facility.condition ?? 'unknown' }}
                    </Badge>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-4">
                    <div class="rounded-xl border border-border/60 bg-muted/30 p-4">
                        <p class="text-xs font-semibold uppercase text-muted-foreground">
                            Type
                        </p>
                        <p class="mt-2 text-sm font-semibold">
                            {{ facilityTypeName }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-border/60 bg-muted/30 p-4">
                        <p class="text-xs font-semibold uppercase text-muted-foreground">
                            Manager
                        </p>
                        <p class="mt-2 text-sm font-semibold">
                            {{ managerName }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-border/60 bg-muted/30 p-4">
                        <p class="text-xs font-semibold uppercase text-muted-foreground">
                            Parent
                        </p>
                        <p class="mt-2 text-sm font-semibold">
                            {{ parentName }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-border/60 bg-muted/30 p-4">
                        <p class="text-xs font-semibold uppercase text-muted-foreground">
                            Condition
                        </p>
                        <p class="mt-2 text-sm font-semibold">
                            {{ facility.condition ?? 'unknown' }}
                        </p>
                    </div>
                </div>

                <Form
                    v-bind="FacilityController.update.form(facility)"
                    class="mt-8 space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <div v-if="isFacilityManagerView" class="grid gap-6 md:grid-cols-2">
                        <input type="hidden" name="name" :value="facility.name" />
                        <input
                            type="hidden"
                            name="facility_type_id"
                            :value="facility.facility_type_id ?? ''"
                        />
                        <input type="hidden" name="parent_id" :value="facility.parent_id ?? ''" />
                        <input type="hidden" name="managed_by" :value="facility.managed_by ?? ''" />

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="name">Facility name</Label>
                            <Input id="name" :default-value="facility.name" readonly />
                        </div>

                        <div class="grid gap-2">
                            <Label>Facility type</Label>
                            <Input :default-value="facilityTypeName" readonly />
                        </div>

                        <div class="grid gap-2">
                            <Label>Parent facility</Label>
                            <Input :default-value="parentName" readonly />
                        </div>

                        <div class="grid gap-2">
                            <Label>Facility manager</Label>
                            <Input :default-value="managerName" readonly />
                        </div>

                        <div class="grid gap-2">
                            <Label for="condition">Condition</Label>
                            <select
                                id="condition"
                                name="condition"
                                :class="selectClass"
                                :value="facility.condition ?? ''"
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
                    </div>

                    <div v-else class="grid gap-6 md:grid-cols-2">
                        <div class="grid gap-2 md:col-span-2">
                            <Label for="name">Facility name</Label>
                            <Input
                                id="name"
                                name="name"
                                :default-value="facility.name"
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
                                :value="facility.facility_type_id ?? ''"
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
                                :value="facility.condition ?? ''"
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
                                :value="facility.parent_id ?? ''"
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
                                :value="facility.managed_by ?? ''"
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
                        <Button :disabled="processing">Save changes</Button>
                        <Button variant="secondary" as-child>
                            <Link :href="facilitiesIndex().url">Cancel</Link>
                        </Button>
                    </div>
                </Form>
            </div>
        </div>
    </AppLayout>
</template>
