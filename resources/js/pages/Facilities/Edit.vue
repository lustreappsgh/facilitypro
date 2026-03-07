<script setup lang="ts">
import FacilityController from '@/actions/App/Http/Controllers/FacilityController';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as facilitiesIndex } from '@/routes/facilities';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';
import { ArrowLeft } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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

const selectedFacilityTypeId = ref(
    props.facility.facility_type_id ? String(props.facility.facility_type_id) : '',
);
const selectedCondition = ref(props.facility.condition ?? '');
const selectedParentId = ref(
    props.facility.parent_id ? String(props.facility.parent_id) : '',
);
const selectedManagerId = ref(
    props.facility.managed_by ? String(props.facility.managed_by) : '',
);
</script>

<template>
    <Head title="Facility details" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader title="Facility details" subtitle="Review and update facility information.">
                <template #actions>
                    <Button variant="secondary" size="icon" class="h-9 w-9" as-child>
                        <Link :href="facilitiesIndex().url" aria-label="Back to list">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
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
                    <input type="hidden" name="facility_type_id" :value="selectedFacilityTypeId" />
                    <input type="hidden" name="condition" :value="selectedCondition" />
                    <input type="hidden" name="parent_id" :value="selectedParentId === 'none' ? '' : selectedParentId" />
                    <input type="hidden" name="managed_by" :value="selectedManagerId === 'none' ? '' : selectedManagerId" />
                    <div v-if="isFacilityManagerView" class="grid gap-6 md:grid-cols-2">
                        <input type="hidden" name="name" :value="facility.name" />

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
                            <Select v-model="selectedCondition" required>
                                <SelectTrigger id="condition" class="w-full">
                                    <SelectValue placeholder="Select condition" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="condition in conditions"
                                        :key="condition"
                                        :value="condition"
                                    >
                                        {{ condition }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
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
                            <Select v-model="selectedFacilityTypeId" required>
                                <SelectTrigger id="facility_type_id" class="w-full">
                                    <SelectValue placeholder="Select a type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="type in facilityTypes"
                                        :key="type.id"
                                        :value="String(type.id)"
                                    >
                                        {{ type.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.facility_type_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="condition">Condition</Label>
                            <Select v-model="selectedCondition" required>
                                <SelectTrigger id="condition" class="w-full">
                                    <SelectValue placeholder="Select condition" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="condition in conditions"
                                        :key="condition"
                                        :value="condition"
                                    >
                                        {{ condition }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.condition" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="parent_id">Parent facility</Label>
                            <Select v-model="selectedParentId">
                                <SelectTrigger id="parent_id" class="w-full">
                                    <SelectValue placeholder="No parent" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">No parent</SelectItem>
                                    <SelectItem
                                        v-for="parent in parents"
                                        :key="parent.id"
                                        :value="String(parent.id)"
                                    >
                                        {{ parent.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.parent_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="managed_by">Facility manager</Label>
                            <Select v-model="selectedManagerId">
                                <SelectTrigger id="managed_by" class="w-full">
                                    <SelectValue placeholder="Unassigned" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">Unassigned</SelectItem>
                                    <SelectItem
                                        v-for="manager in users"
                                        :key="manager.id"
                                        :value="String(manager.id)"
                                    >
                                        {{ manager.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
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
