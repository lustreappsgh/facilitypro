<script setup lang="ts">
import { Checkbox } from '@/components/ui/checkbox';

interface Permission {
    id: number;
    name: string;
}

const props = defineProps<{
    permissions: Permission[];
    modelValue: string[];
}>();

const emit = defineEmits<{
    (event: 'update:modelValue', value: string[]): void;
}>();

const togglePermission = (permissionName: string, checked: boolean) => {
    const next = new Set(props.modelValue);

    if (checked) {
        next.add(permissionName);
    } else {
        next.delete(permissionName);
    }

    emit('update:modelValue', Array.from(next));
};
</script>

<template>
    <div class="grid gap-2 rounded-lg border border-border/60 p-4">
        <label
            v-for="permission in permissions"
            :key="permission.id"
            class="flex items-center gap-3 text-sm"
        >
            <Checkbox
                :model-value="props.modelValue.includes(permission.name)"
                @update:model-value="(value) => togglePermission(permission.name, !!value)"
            />
            <span>{{ permission.name }}</span>
        </label>
    </div>
</template>
