<script setup lang="ts">
import type { AcceptableValue } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit, useVModel } from "@vueuse/core"
import { ChevronDownIcon } from "lucide-vue-next"
import { cn } from "@/lib/utils"
import { computed, useAttrs } from "vue"

defineOptions({
  inheritAttrs: false,
})

const props = defineProps<{
  modelValue?: AcceptableValue | AcceptableValue[]
  class?: HTMLAttributes["class"]
  containerClass?: HTMLAttributes["class"]
}>()

const emit = defineEmits<{
  "update:modelValue": AcceptableValue | AcceptableValue[]
}>()

const modelValue = useVModel(props, "modelValue", emit, {
  passive: true,
  defaultValue: "",
})

const delegatedProps = reactiveOmit(props, "class", "containerClass")

const attrs = useAttrs()
const isMultiple = computed(() => {
  const value = (attrs as Record<string, unknown>).multiple
  return value === "" || value === true || value === "multiple"
})
</script>

<template>
  <div
    :class="cn(
      'group/native-select relative has-[select:disabled]:opacity-50',
      isMultiple ? 'w-full' : 'w-fit',
      props.containerClass,
    )"
    data-slot="native-select-wrapper"
  >
    <select
      v-bind="{ ...$attrs, ...delegatedProps }"
      v-model="modelValue"
      data-slot="native-select"
      :class="cn(
        'border-input placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 dark:hover:bg-input/50 w-full min-w-0 appearance-none rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed',
        isMultiple ? 'h-auto min-h-24 pr-3' : 'h-9 pr-9',
        'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
        'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
        props.class,
      )"
    >
      <slot />
    </select>
    <ChevronDownIcon
      v-if="!isMultiple"
      class="text-muted-foreground pointer-events-none absolute top-1/2 right-3.5 size-4 -translate-y-1/2 opacity-50 select-none"
      aria-hidden="true"
      data-slot="native-select-icon"
    />
  </div>
</template>
