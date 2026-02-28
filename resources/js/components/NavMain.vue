<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useActiveUrl } from '@/composables/useActiveUrl';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';

defineProps<{
    items: NavItem[];
    label?: string;
}>();

const { urlIsActive } = useActiveUrl();

const isDisabled = (item: NavItem) => Boolean(item.disabled);
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>{{ label ?? 'Platform' }}</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton as-child :is-active="isDisabled(item) ? false : urlIsActive(item.href)"
                    :tooltip="item.title"
                    class="relative h-10 transition-all duration-200 hover:bg-sidebar-accent/50 data-[active=true]:bg-primary/10 group/item">
                    <Link :href="item.href" :aria-disabled="isDisabled(item)" :tabindex="isDisabled(item) ? -1 : 0"
                        :class="[
                            'flex w-full items-center gap-3.5 px-3',
                            isDisabled(item) ? 'pointer-events-none opacity-40' : '',
                            urlIsActive(item.href) ? 'text-primary' : 'text-sidebar-foreground/60'
                        ]">
                        <!-- Active Indicator Glow (Image 1 style) -->
                        <div v-if="urlIsActive(item.href)"
                            class="absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-5 bg-primary rounded-r shadow-[0_0_10px_rgba(255,202,75,1)]">
                        </div>

                        <component :is="item.icon"
                            :class="['h-4.5 w-4.5 transition-all', urlIsActive(item.href) ? 'text-primary scale-110' : 'group-hover/item:text-sidebar-foreground group-hover/item:scale-105']" />
                        <span
                            class="text-xs font-bold tracking-tight uppercase group-hover/item:text-sidebar-foreground">{{
                            item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
