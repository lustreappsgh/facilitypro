<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { TrendingUp, TrendingDown } from 'lucide-vue-next';

interface Props {
    title: string;
    value: string | number;
    icon?: any;
    description?: string;
    trend?: {
        value: number;
        label: string;
        inverse?: boolean;
    };
    subMetrics?: {
        label: string;
        value: string | number;
        color?: string;
    }[];
    accentColor?: 'emerald' | 'blue' | 'amber' | 'purple' | 'rose';
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    accentColor: 'blue'
});

const trendType = computed(() => {
    if (!props.trend) return 'neutral';
    if (props.trend.value > 0) return props.trend.inverse ? 'down' : 'up';
    if (props.trend.value < 0) return props.trend.inverse ? 'up' : 'down';
    return 'neutral';
});

const accentClass = computed(() => {
    const colors = {
        emerald: 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 border-emerald-100 dark:border-emerald-500/20',
        blue: 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-500/10 border-blue-100 dark:border-blue-500/20',
        amber: 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-500/10 border-amber-100 dark:border-amber-500/20',
        purple: 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-500/10 border-purple-100 dark:border-purple-500/20',
        rose: 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-500/10 border-rose-100 dark:border-rose-500/20',
        yellow: 'text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-primary/10 border-yellow-100 dark:border-primary/20',
    };
    return colors[props.accentColor] || colors.yellow;
});
</script>

<template>
    <Card
        :class="cn('group relative overflow-hidden border-border bg-card shadow-sm transition-all hover:shadow-md', props.class)">
        <CardContent class="p-6">
            <div class="flex items-center gap-4">
                <!-- Circular Icon Container (Blueprint Style) -->
                <div v-if="icon"
                    :class="cn('flex h-14 w-14 shrink-0 items-center justify-center rounded-full border transition-transform duration-300 group-hover:scale-110', accentClass)">
                    <component :is="icon" class="h-7 w-7 transition-all group-hover:rotate-12" />
                </div>

                <div class="min-w-0 flex-1 space-y-0.5">
                    <p class="truncate text-[11px] font-black uppercase tracking-[0.15em] text-muted-foreground/80">
                        {{ title }}
                    </p>
                    <div class="flex items-center gap-3">
                        <h3 class="text-3xl font-black font-display tracking-tight text-card-foreground">
                            {{ value }}
                        </h3>

                        <div v-if="trend"
                            :class="cn('flex items-center rounded-full px-2 py-0.5 text-[10px] font-black border',
                                trendType === 'up'
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20'
                                    : 'bg-rose-50 text-rose-700 border-rose-100 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/20')">
                            <TrendingUp v-if="trendType === 'up'" class="mr-1 h-3 w-3" />
                            <TrendingDown v-if="trendType === 'down'" class="mr-1 h-3 w-3" />
                            {{ Math.abs(trend.value) }}%
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="description" class="mt-4 border-t border-border/50 pt-4">
                <p class="text-[11px] font-bold text-muted-foreground/60 leading-relaxed uppercase tracking-wider">
                    {{ description }}
                </p>
            </div>

            <div v-if="subMetrics && subMetrics.length > 0" class="mt-4 flex flex-wrap gap-4 pt-2">
                <div v-for="metric in subMetrics" :key="metric.label" class="space-y-0.5">
                    <p class="text-[9px] font-bold uppercase tracking-widest text-muted-foreground/60">
                        {{ metric.label }}
                    </p>
                    <p :class="cn('text-sm font-black font-display', metric.color || 'text-card-foreground/80')">
                        {{ metric.value }}
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
