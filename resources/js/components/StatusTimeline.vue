<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { computed } from 'vue';

interface Step {
    value: string;
    label: string;
}

interface Props {
    steps: Step[];
    current?: string | null;
}

const props = defineProps<Props>();

const currentIndex = computed(() => {
    if (!props.current) {
        return -1;
    }

    return props.steps.findIndex((step) => step.value === props.current);
});

const badgeClass = (index: number) => {
    if (index < currentIndex.value) {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (index === currentIndex.value) {
        return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
    }

    return 'bg-muted text-muted-foreground';
};
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <Badge
            v-for="(step, index) in steps"
            :key="step.value"
            variant="secondary"
            :class="badgeClass(index)"
        >
            {{ step.label }}
        </Badge>
    </div>
</template>
