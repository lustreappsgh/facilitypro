<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <th
        data-slot="table-head"
        :class="
            cn(
                'h-10 px-4 text-left align-middle text-xs font-semibold uppercase text-muted-foreground',
                $props.class,
            )
        "
    >
        <slot />
    </th>
</template>
