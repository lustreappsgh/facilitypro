<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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
            <Select
                :model-value="serviceType ?? 'all'"
                @update:model-value="(value) => emit('update:serviceType', value === 'all' ? null : value)"
            >
                <SelectTrigger id="service_type" class="w-full">
                    <SelectValue placeholder="All service types" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All service types</SelectItem>
                    <SelectItem v-for="type in serviceTypes" :key="type" :value="type">
                        {{ type }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="grid gap-2">
            <Label for="vendor_id">Vendor</Label>
            <Select
                :model-value="modelValue ? String(modelValue) : ''"
                @update:model-value="(value) => emit('update:modelValue', value)"
            >
                <SelectTrigger id="vendor_id" class="w-full">
                    <SelectValue placeholder="Select a vendor" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="vendor in filteredVendors"
                        :key="vendor.id"
                        :value="String(vendor.id)"
                    >
                        {{ vendor.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="error" />
        </div>
    </div>
</template>
