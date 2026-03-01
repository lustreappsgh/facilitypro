<script setup lang="ts">
import { cn } from "@/lib/utils"

interface ButtonGroupProps {
  class?: string
}

const props = defineProps<ButtonGroupProps>()
</script>

<template>
  <div
    :class="cn(
      'inline-flex items-center overflow-hidden rounded-md border border-border/40 bg-background/70',
      props.class,
    )"
  >
    <slot />
  </div>
</template>
