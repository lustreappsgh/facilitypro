<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <thead
        data-slot="table-header"
        :class="cn('border-b text-muted-foreground', $props.class)"
    >
        <slot />
    </thead>
</template>
