<script setup lang="ts">
import type { AccordionHeaderProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { AccordionHeader } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<
  AccordionHeaderProps & {
    class?: HTMLAttributes["class"]
  }
>()
</script>

<template>
  <AccordionHeader
    data-slot="accordion-header"
    :class="cn('flex', props.class)"
  >
    <slot />
  </AccordionHeader>
</template>
