<script setup lang="ts">
import { DatePicker } from '@/components/ui/date-picker';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Facility {
    id: number;
    name: string;
}

interface Props {
    facilities: Facility[];
    conditions: string[];
    errors: Record<string, string>;
    processing: boolean;
    submitLabel?: string;
    cancelHref: string;
    facilitySelectionMode?: 'single' | 'multiple';
    selectedFacilityId?: number | null;
    selectedFacilityIds?: number[];
    showCancel?: boolean;
    onCancel?: () => void;
}

const props = defineProps<Props>();

const textAreaClass =
    'border-input bg-transparent text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] min-h-[120px] w-full rounded-md border px-3 py-2';

const submitText = computed(() => props.submitLabel ?? 'Submit inspection');
const isMultiple = computed(() => props.facilitySelectionMode === 'multiple');
const facilityErrorMessage = computed(
    () =>
        props.errors.facility_id ||
        props.errors.facility_ids ||
        props.errors['facility_ids.0'],
);
const showCancel = computed(() => props.showCancel ?? true);
const isFacilitySelectionLocked = computed(() => {
    if (props.selectedFacilityId !== null && props.selectedFacilityId !== undefined) {
        return true;
    }
    return (props.selectedFacilityIds ?? []).length > 0;
});
const lockedFacilityIds = computed<number[]>(() => {
    if (!isFacilitySelectionLocked.value) {
        return [];
    }

    if (isMultiple.value) {
        return props.selectedFacilityIds ?? [];
    }

    if (props.selectedFacilityId !== null && props.selectedFacilityId !== undefined) {
        return [props.selectedFacilityId];
    }

    if ((props.selectedFacilityIds ?? []).length === 1) {
        return [props.selectedFacilityIds![0]];
    }

    return [];
});
const lockedFacilities = computed(() => {
    const byId = new Map(props.facilities.map((facility) => [facility.id, facility]));
    return lockedFacilityIds.value.map((id) => ({
        id,
        name: byId.get(id)?.name ?? `Facility #${id}`,
    }));
});

const resolveSingleSelection = (single?: number | null, list?: number[]) => {
    if (single !== null && single !== undefined) {
        return String(single);
    }
    if (list && list.length === 1) {
        return String(list[0]);
    }
    return null;
};

const facilitySelection = ref<string | null>(
    resolveSingleSelection(props.selectedFacilityId, props.selectedFacilityIds),
);
const facilitySelections = ref<string[]>(
    (props.selectedFacilityIds ?? []).map((id) => String(id)),
);
const conditionSelection = ref<string | null>(null);

const toggleFacilitySelection = (facilityId: string, checked: boolean) => {
    if (checked) {
        facilitySelections.value = [...new Set([...facilitySelections.value, facilityId])];

        return;
    }

    facilitySelections.value = facilitySelections.value.filter((id) => id !== facilityId);
};

watch(
    () => [props.selectedFacilityId, props.selectedFacilityIds] as const,
    ([single, list]) => {
        facilitySelection.value = resolveSingleSelection(single, list);
    },
);

watch(
    () => props.selectedFacilityIds,
    (value) => {
        facilitySelections.value = (value ?? []).map((id) => String(id));
    },
    { deep: true },
);
</script>

<template>
    <div class="space-y-6">
        <div class="grid gap-2">
            <Label for="inspection_date">Inspection date</Label>
            <DatePicker id="inspection_date" name="inspection_date" required  />
            <InputError :message="errors.inspection_date" />
        </div>

        <div class="grid gap-2">
            <Label :for="isMultiple ? 'facility_ids' : 'facility_id'">Facility</Label>
            <div
                v-if="isFacilitySelectionLocked"
                class="space-y-2 rounded-lg border border-border/60 bg-muted/20 p-3"
            >
                <template v-if="isMultiple">
                    <input
                        v-for="facility in lockedFacilities"
                        :key="facility.id"
                        type="hidden"
                        name="facility_ids[]"
                        :value="String(facility.id)"
                    />
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="facility in lockedFacilities"
                            :key="facility.id"
                            class="inline-flex items-center rounded-md border border-border/60 bg-background px-2 py-1 text-[10px] font-bold uppercase tracking-widest text-foreground/80"
                        >
                            {{ facility.name }}
                        </span>
                    </div>
                </template>
                <template v-else>
                    <input
                        v-if="lockedFacilities[0]"
                        type="hidden"
                        name="facility_id"
                        :value="String(lockedFacilities[0].id)"
                    />
                    <p class="text-sm font-semibold text-foreground">
                        {{ lockedFacilities[0]?.name ?? 'Selected facility' }}
                    </p>
                </template>
                <p class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/70">
                    Selection locked
                </p>
            </div>
            <template v-else>
                <div v-if="isMultiple" class="space-y-2">
                    <div class="grid max-h-56 gap-2 overflow-y-auto rounded-lg border border-border/60 bg-muted/20 p-3">
                        <label
                            v-for="facility in facilities"
                            :key="facility.id"
                            class="flex items-center gap-3 rounded-md border border-transparent bg-background/80 px-3 py-2 text-sm transition-colors hover:border-border/60 dark:bg-background/40"
                        >
                            <Checkbox
                                :model-value="facilitySelections.includes(String(facility.id))"
                                @update:model-value="(checked) => toggleFacilitySelection(String(facility.id), checked === true)"
                            />
                            <span>{{ facility.name }}</span>
                        </label>
                    </div>
                    <div v-for="facilityId in facilitySelections" :key="facilityId">
                        <input type="hidden" name="facility_ids[]" :value="facilityId" />
                    </div>
                    <p class="text-[10px] text-muted-foreground">Select one or more facilities</p>
                </div>
                <Select v-else v-model="facilitySelection" name="facility_id" required>
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
            </template>
            <InputError :message="facilityErrorMessage" />
        </div>

        <div class="grid gap-2">
            <Label for="condition">Condition</Label>
            <Select v-model="conditionSelection" name="condition" required>
                <SelectTrigger id="condition" class="w-full">
                    <SelectValue placeholder="Select condition" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="condition in conditions" :key="condition" :value="condition">
                        {{ condition }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="errors.condition" />
        </div>

        <div class="grid gap-2">
            <Label for="comments">Comments</Label>
            <textarea
                id="comments"
                name="comments"
                :class="textAreaClass"
                placeholder="Add any notes from the inspection"
            />
            <InputError :message="errors.comments" />
        </div>

        <div class="grid gap-2">
            <Label for="image">Image URL</Label>
            <Input id="image" name="image" placeholder="https://" />
            <InputError :message="errors.image" />
        </div>

        <div class="flex items-center gap-4">
            <Button :disabled="processing">{{ submitText }}</Button>
            <Button
                v-if="showCancel && onCancel"
                variant="ghost"
                type="button"
                @click="onCancel"
            >
                Cancel
            </Button>
            <Button v-else-if="showCancel" variant="ghost" as-child>
                <Link :href="cancelHref">Cancel</Link>
            </Button>
        </div>
    </div>
</template>
