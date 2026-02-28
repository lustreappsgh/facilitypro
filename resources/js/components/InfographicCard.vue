<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

interface Props {
  title: string;
  description?: string;
  class?: string;
  contentClass?: string;
}

const props = defineProps<Props>();
</script>

<template>
  <Card :class="cn('overflow-hidden border-border bg-card shadow-sm transition-all hover:shadow-md', props.class)">
    <CardHeader class="border-b border-border/50 py-4 relative">
      <div class="flex items-center justify-between gap-4 relative z-10">
        <div class="space-y-0.5">
          <CardTitle class="text-[13px] font-black font-display tracking-[0.05em] text-card-foreground uppercase">
            {{ title }}
          </CardTitle>
          <CardDescription v-if="description"
            class="text-[11px] font-bold text-muted-foreground/60 uppercase tracking-widest">
            {{ description }}
          </CardDescription>
        </div>
        <div class="flex items-center gap-2">
          <slot name="actions" />
        </div>
      </div>
    </CardHeader>
    <CardContent :class="cn('p-6 relative z-10', props.contentClass)">
      <slot />
    </CardContent>
    <div v-if="$slots.footer" class="border-t border-border/40 bg-muted/5 px-6 py-4 relative z-10">
      <slot name="footer" />
    </div>
  </Card>
</template>
