<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { Search } from 'lucide-vue-next';
import { useMainNavigation } from '@/composables/useMainNavigation';
import { useMaintenanceNavigation } from '@/composables/useMaintenanceNavigation';
import { useAdminNavigation } from '@/composables/useAdminNavigation';
import { dashboard } from '@/routes';
import { Link } from '@inertiajs/vue3';
import AppLogo from './AppLogo.vue';

const { mainNavItems } = useMainNavigation();
const { maintenanceNavItems } = useMaintenanceNavigation();
const { adminNavItems } = useAdminNavigation();
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar" class="bg-sidebar border-r border-sidebar-border shadow-2xl">
        <SidebarHeader class="p-4 flex flex-col gap-4">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child class="hover:bg-transparent px-0">
                        <Link :href="dashboard()" class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-[0_0_20px_rgba(255,202,75,0.4)] transition-transform hover:scale-105">
                                <AppLogo class="h-6 w-6" />
                            </div>
                            <div class="flex flex-col gap-0 leading-none">
                                <span
                                    class="text-base font-black uppercase tracking-widest text-sidebar-foreground">Anagkazo</span>
                                <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-primary/80">Systems
                                    Group</span>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>

            <!-- Sidebar Search Placeholder (Image 1 style) -->
            <div class="relative group px-1">
                <Search
                    class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground/60 transition-colors group-focus-within:text-primary" />
                <input type="text" placeholder="Global Search..."
                    class="w-full bg-sidebar-accent/50 border border-sidebar-border rounded-xl py-2 pl-9 pr-4 text-xs font-medium text-sidebar-foreground placeholder:text-muted-foreground/40 focus:outline-none focus:ring-1 focus:ring-primary/40 focus:bg-sidebar-accent transition-all" />
                <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1 opacity-40">
                    <span class="text-[9px] border border-sidebar-border rounded px-1">⌘F</span>
                </div>
            </div>
        </SidebarHeader>

        <SidebarContent class="px-2 py-4 space-y-4">
            <NavMain :items="mainNavItems" label="Platform" />
            <NavMain v-if="maintenanceNavItems.length" :items="maintenanceNavItems" label="Maintenance & Ops" />
            <NavMain v-if="adminNavItems.length" :items="adminNavItems" label="Governance" />
        </SidebarContent>

        <SidebarFooter class="border-t border-sidebar-border/50 p-4 bg-sidebar-background/50">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
