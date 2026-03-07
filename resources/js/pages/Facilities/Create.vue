<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as facilitiesIndex, store as facilitiesStore } from '@/routes/facilities';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';

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

interface FacilityRow {
    name: string;
    facility_type_id: string;
    parent_id: string;
    condition: string;
    managed_by: string;
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

const conditions = ['Good', 'Bad', 'OutOfOrder'];

const createDefaultRow = (): FacilityRow => ({
    name: '',
    facility_type_id: '',
    parent_id: '',
    condition: 'Good',
    managed_by: '',
});

const form = useForm({
    facilities: [createDefaultRow()],
});

const addRow = () => {
    form.facilities.push(createDefaultRow());
};

const removeRow = (index: number) => {
    if (form.facilities.length === 1) {
        return;
    }

    form.facilities.splice(index, 1);
};

const rowError = (index: number, field: keyof FacilityRow) => {
    const rowKey = `facilities.${index}.${field}`;
    const fallbackKey = index === 0 ? field : null;

    return form.errors[rowKey] || (fallbackKey ? form.errors[fallbackKey] : undefined);
};

const submit = () => {
    form.post(facilitiesStore().url, {
        preserveScroll: true,
        transform: (data) => ({
            facilities: data.facilities.map((facility) => ({
                ...facility,
                parent_id: facility.parent_id || null,
                managed_by: facility.managed_by || null,
            })),
        }),
    });
};
</script>

<template>
    <Head title="Create facilities" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader title="Create facilities" subtitle="Add one or more facilities in a single submit." />

            <div class="relative rounded-2xl border border-sidebar-border/70 bg-muted/30 p-6">
                <div class="mx-auto w-full max-w-5xl rounded-2xl border border-border bg-card p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold">Bulk facility create</h2>
                            <p class="text-sm text-muted-foreground">
                                Add multiple facilities dynamically and save in one action.
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button type="button" variant="outline" class="gap-2" @click="addRow">
                                <Plus class="h-4 w-4" />
                                Add row
                            </Button>
                            <Button variant="ghost" as-child>
                                <Link :href="facilitiesIndex().url">Cancel</Link>
                            </Button>
                        </div>
                    </div>

                    <form class="mt-6 space-y-6" @submit.prevent="submit">
                        <div class="space-y-4">
                            <div
                                v-for="(facility, index) in form.facilities"
                                :key="index"
                                class="rounded-xl border border-border/70 p-4"
                            >
                                <div class="mb-3 flex items-center justify-between">
                                    <h3 class="text-sm font-semibold">Facility {{ index + 1 }}</h3>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive"
                                        :disabled="form.facilities.length === 1"
                                        @click="removeRow(index)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="grid gap-2 md:col-span-2">
                                        <Label :for="`name-${index}`">Facility name</Label>
                                        <Input
                                            :id="`name-${index}`"
                                            v-model="facility.name"
                                            placeholder="North Campus Gym"
                                            required
                                        />
                                        <InputError :message="rowError(index, 'name')" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label :for="`facility-type-${index}`">Facility type</Label>
                                        <Select v-model="facility.facility_type_id" required>
                                            <SelectTrigger :id="`facility-type-${index}`" class="w-full">
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
                                        <InputError :message="rowError(index, 'facility_type_id')" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label :for="`condition-${index}`">Condition</Label>
                                        <Select v-model="facility.condition" required>
                                            <SelectTrigger :id="`condition-${index}`" class="w-full">
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
                                        <InputError :message="rowError(index, 'condition')" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label :for="`parent-${index}`">Parent facility</Label>
                                        <Select
                                            :model-value="facility.parent_id || 'none'"
                                            @update:model-value="(value) => { facility.parent_id = value === 'none' ? '' : value; }"
                                        >
                                            <SelectTrigger :id="`parent-${index}`" class="w-full">
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
                                        <InputError :message="rowError(index, 'parent_id')" />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label :for="`manager-${index}`">Facility manager</Label>
                                        <Select
                                            :model-value="facility.managed_by || 'none'"
                                            @update:model-value="(value) => { facility.managed_by = value === 'none' ? '' : value; }"
                                        >
                                            <SelectTrigger :id="`manager-${index}`" class="w-full">
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
                                        <InputError :message="rowError(index, 'managed_by')" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <Button type="submit" :disabled="form.processing">
                                Create {{ form.facilities.length > 1 ? `${form.facilities.length} facilities` : 'facility' }}
                            </Button>
                            <Button type="button" variant="outline" class="gap-2" @click="addRow">
                                <Plus class="h-4 w-4" />
                                Add another
                            </Button>
                            <Button variant="secondary" as-child>
                                <Link :href="facilitiesIndex().url">Back</Link>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
