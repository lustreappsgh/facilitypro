<script setup lang="ts">
import { Button } from '@/components/ui/button';

defineProps<{
    selectedCount: number;
}>();

const emit = defineEmits<{
    (event: 'activate'): void;
    (event: 'deactivate'): void;
}>();
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="text-sm text-muted-foreground">
            Selected {{ selectedCount }} user(s)
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <Button
                variant="secondary"
                size="sm"
                :disabled="selectedCount === 0"
                @click="emit('activate')"
            >
                Activate selected
            </Button>
            <Button
                variant="outline"
                size="sm"
                :disabled="selectedCount === 0"
                @click="emit('deactivate')"
            >
                Deactivate selected
            </Button>
        </div>
    </div>
</template>
