<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { createCurrencyFormatter } from '@/lib/currency';
import { computed } from 'vue';

interface Props {
    estimated?: number | null;
    actual?: number | null;
}

const props = defineProps<Props>();

const currencyFormat = createCurrencyFormatter();

const variance = computed(() => {
    if (props.estimated === null || props.estimated === undefined) {
        return null;
    }

    if (props.actual === null || props.actual === undefined) {
        return null;
    }

    return props.actual - props.estimated;
});

const varianceRatio = computed(() => {
    if (variance.value === null || !props.estimated) {
        return null;
    }

    return variance.value / props.estimated;
});

const varianceBadgeClass = computed(() => {
    if (varianceRatio.value === null) {
        return 'bg-muted text-muted-foreground';
    }

    if (Math.abs(varianceRatio.value) >= 0.15) {
        return 'bg-rose-500/10 text-rose-700 dark:text-rose-300';
    }

    return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
});

const varianceLabel = computed(() => {
    if (variance.value === null || varianceRatio.value === null) {
        return 'Variance pending';
    }

    const percent = Math.round(Math.abs(varianceRatio.value) * 100);
    const direction = variance.value >= 0 ? 'over' : 'under';
    return `${percent}% ${direction} estimate`;
});
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-3">
        <div>
            <p class="text-xs text-muted-foreground uppercase">Estimated</p>
            <p class="text-sm font-medium">
                {{
                    estimated !== null && estimated !== undefined
                        ? currencyFormat.format(estimated)
                        : '—'
                }}
            </p>
        </div>
        <div>
            <p class="text-xs text-muted-foreground uppercase">Actual</p>
            <p class="text-sm font-medium">
                {{
                    actual !== null && actual !== undefined
                        ? currencyFormat.format(actual)
                        : '—'
                }}
            </p>
        </div>
        <div>
            <p class="text-xs text-muted-foreground uppercase">Variance</p>
            <Badge variant="secondary" :class="varianceBadgeClass">
                {{ varianceLabel }}
            </Badge>
        </div>
    </div>
</template>
