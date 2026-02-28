<script setup lang="ts">
interface Change {
    field: string;
    before: unknown;
    after: unknown;
}

defineProps<{
    changes?: Change[] | null;
}>();

const formatValue = (value: unknown) => {
    if (value === null || value === undefined) {
        return '-';
    }
    if (typeof value === 'object') {
        return JSON.stringify(value);
    }
    return String(value);
};
</script>

<template>
    <details class="rounded-lg border border-border/60 bg-muted/30 p-2">
        <summary class="cursor-pointer text-sm">View changes</summary>
        <div class="mt-3 space-y-2 text-xs text-muted-foreground">
            <div v-for="change in changes ?? []" :key="change.field" class="grid gap-1">
                <div class="text-foreground">{{ change.field }}</div>
                <div class="grid gap-1 md:grid-cols-2">
                    <div>
                        <span class="font-medium">Before:</span>
                        {{ formatValue(change.before) }}
                    </div>
                    <div>
                        <span class="font-medium">After:</span>
                        {{ formatValue(change.after) }}
                    </div>
                </div>
            </div>
            <p v-if="!changes?.length">No field-level changes recorded.</p>
        </div>
    </details>
</template>
