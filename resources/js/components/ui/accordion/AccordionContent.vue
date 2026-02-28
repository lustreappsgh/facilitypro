<script setup lang="ts">
import type { AccordionContentProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { AccordionContent } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<
  AccordionContentProps & {
    class?: HTMLAttributes["class"]
  }
>()

const delegatedProps = reactiveOmit(props, "class")
</script>

<template>
  <AccordionContent
    data-slot="accordion-content"
    :class="cn('overflow-hidden text-sm', props.class)"
    v-bind="delegatedProps"
  >
    <div class="pb-4 pt-0">
      <slot />
    </div>
  </AccordionContent>
</template>
