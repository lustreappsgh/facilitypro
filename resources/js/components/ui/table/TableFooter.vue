<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <tfoot
        data-slot="table-footer"
        :class="cn('border-t bg-muted/40 font-medium', $props.class)"
    >
        <slot />
    </tfoot>
</template>
