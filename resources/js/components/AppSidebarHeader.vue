<script setup lang="ts">
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import NotificationsBell from '@/components/NotificationsBell.vue';
import { Switch } from '@/components/ui/switch';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useAppearance } from '@/composables/useAppearance';
import type { BreadcrumbItemType } from '@/types';
import { Moon, Sun } from 'lucide-vue-next';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { resolvedAppearance, updateAppearance } = useAppearance();

const isDark = computed(() => resolvedAppearance.value === 'dark');

const handleToggle = (checked: boolean) => {
    updateAppearance(checked ? 'dark' : 'light');
};
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="flex items-center gap-2">
            <NotificationsBell />
            <Sun class="h-4 w-4 text-muted-foreground/60 transition-colors" :class="{ 'text-primary': !isDark }" />
            <Switch :checked="isDark" @update:checked="handleToggle" aria-label="Toggle dark mode" />
            <Moon class="h-4 w-4 text-muted-foreground/60 transition-colors" :class="{ 'text-primary': isDark }" />
        </div>
    </header>
</template>
