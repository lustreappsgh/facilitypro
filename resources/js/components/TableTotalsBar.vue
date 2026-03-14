<script setup lang="ts">
interface TotalItem {
    label: string;
    value: string;
}

defineProps<{
    items: TotalItem[];
    heading?: string;
}>();
</script>

<template>
    <div
        class="flex flex-wrap items-center justify-end gap-3 bg-muted/20 px-4 py-3 text-sm"
    >
        <span
            class="text-xs font-bold tracking-widest text-muted-foreground/70 uppercase"
        >
            {{ heading ?? 'Totals' }}
        </span>
        <div
            v-for="item in items"
            :key="item.label"
            class="flex items-center gap-2 rounded-lg border border-border/60 bg-background px-3 py-1.5"
        >
            <span
                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
            >
                {{ item.label }}
            </span>
            <span class="font-display text-sm font-semibold text-foreground">
                {{ item.value }}
            </span>
        </div>
    </div>
</template>
