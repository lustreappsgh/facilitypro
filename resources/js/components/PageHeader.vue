<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { Filter } from 'lucide-vue-next';
import { computed } from 'vue';
import type { Component } from 'vue';

interface Props {
    title: string;
    subtitle?: string;
    actionLabel?: string;
    actionHref?: string;
    actionIcon?: Component | null;
    showFiltersToggle?: boolean;
    filtersVisible?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    subtitle: '',
    actionLabel: '',
    actionHref: '',
    actionIcon: null,
    showFiltersToggle: false,
    filtersVisible: false,
});

const emit = defineEmits<{
    'toggle-filters': [];
}>();

const hasAction = computed(() => Boolean(props.actionLabel && props.actionHref));
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold font-display">
                {{ title }}
            </h1>
            <p v-if="subtitle" class="text-sm text-muted-foreground mt-1">
                {{ subtitle }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <Button
                v-if="showFiltersToggle"
                variant="outline"
                size="lg"
                @click="emit('toggle-filters')"
            >
                <Filter class="mr-2 h-4 w-4" />
                {{ filtersVisible ? 'Hide' : 'Show' }} Filters
            </Button>
            <Button v-if="hasAction" size="lg" as-child>
                <Link :href="actionHref">
                    <component :is="actionIcon" v-if="actionIcon" class="mr-2 h-4 w-4" />
                    {{ actionLabel }}
                </Link>
            </Button>
            <slot name="actions"></slot>
        </div>
    </div>
</template>
