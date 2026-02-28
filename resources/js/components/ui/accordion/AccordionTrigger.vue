<script setup lang="ts">
import type { AccordionTriggerProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { AccordionTrigger } from "reka-ui"
import { cn } from "@/lib/utils"
import AccordionHeader from "./AccordionHeader.vue"

const props = defineProps<
  AccordionTriggerProps & {
    class?: HTMLAttributes["class"]
  }
>()

const delegatedProps = reactiveOmit(props, "class")
</script>

<template>
  <AccordionHeader>
    <AccordionTrigger
      data-slot="accordion-trigger"
      :class="
        cn(
          'flex w-full flex-1 items-center justify-between py-3 text-sm font-semibold transition-all [&[data-state=open]>svg]:rotate-180',
          props.class,
        )
      "
      v-bind="delegatedProps"
    >
      <slot />
    </AccordionTrigger>
  </AccordionHeader>
</template>
