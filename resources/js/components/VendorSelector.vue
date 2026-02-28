<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { computed } from 'vue';

interface Vendor {
    id: number;
    name: string;
    service_type?: string | null;
}

interface Props {
    vendors: Vendor[];
    modelValue?: string | number | null;
    serviceType?: string | null;
    error?: string | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (event: 'update:modelValue', value: string | number | null): void;
    (event: 'update:serviceType', value: string | null): void;
}>();

const selectClass =
    'border-input bg-transparent text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] h-9 w-full rounded-md border px-3';

const serviceTypes = computed(() => {
    const unique = new Set(
        props.vendors
            .map((vendor) => vendor.service_type)
            .filter((type): type is string => Boolean(type)),
    );

    return Array.from(unique).sort((a, b) => a.localeCompare(b));
});

const filteredVendors = computed(() => {
    if (!props.serviceType) {
        return props.vendors;
    }

    return props.vendors.filter(
        (vendor) => vendor.service_type === props.serviceType,
    );
});
</script>

<template>
    <div class="space-y-4">
        <div class="grid gap-2">
            <Label for="service_type">Service type</Label>
            <select
                id="service_type"
                name="service_type"
                :class="selectClass"
                :value="serviceType ?? ''"
                @change="
                    emit(
                        'update:serviceType',
                        ($event.target as HTMLSelectElement).value || null,
                    )
                "
            >
                <option value="">All service types</option>
                <option v-for="type in serviceTypes" :key="type" :value="type">
                    {{ type }}
                </option>
            </select>
        </div>

        <div class="grid gap-2">
            <Label for="vendor_id">Vendor</Label>
            <select
                id="vendor_id"
                name="vendor_id"
                :class="selectClass"
                required
                :value="modelValue ?? ''"
                @change="
                    emit(
                        'update:modelValue',
                        ($event.target as HTMLSelectElement).value,
                    )
                "
            >
                <option value="" disabled>Select a vendor</option>
                <option
                    v-for="vendor in filteredVendors"
                    :key="vendor.id"
                    :value="vendor.id"
                >
                    {{ vendor.name }}
                </option>
            </select>
            <InputError :message="error" />
        </div>
    </div>
</template>
