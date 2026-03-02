<script setup lang="ts">
import { computed, ref, h } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger
} from '@/components/ui/accordion';
import DataTable from '@/components/data-table/DataTable.vue';
import {
    Building2, ClipboardCheck, Eye, Wrench,
    List, LayoutGrid
} from 'lucide-vue-next';
import { show as facilitiesShow } from '@/routes/facilities';
import { create as inspectionsCreate } from '@/routes/inspections';
import { create as maintenanceCreate } from '@/routes/maintenance';
import type { ColumnDef } from '@tanstack/vue-table';

interface Facility {
    id: number;
    name: string;
    condition: string;
    open_requests?: number;
    last_inspection?: string;
    parent?: { name: string };
}

interface FacilityGroup {
    facility_type?: { name: string } | null;
    facilities: Facility[];
    count: number;
}

interface Props {
    facilityGroups: FacilityGroup[];
    metrics: {
        totalFacilities: number;
        openRequests: number;
    };
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'My Management', href: '/facilities/my' },
    { title: 'Portfolio', href: '/facilities/my' }
];

const viewMode = ref<'grid' | 'table'>('grid');

// DataTable Columns for Asset view
const columns: ColumnDef<Facility>[] = [
    {
        accessorKey: 'name',
        header: 'Registered Asset',
        cell: ({ row }) => h('div', { class: 'flex flex-col' }, [
            h('span', { class: 'font-black text-[13px] uppercase tracking-tight text-foreground' }, row.original.name),
            h('span', { class: 'text-[10px] font-bold text-muted-foreground/60 uppercase tracking-widest' }, row.original.parent?.name || 'Primary Node')
        ]),
        enableSorting: true,
    },
    {
        accessorKey: 'condition',
        header: 'System Health',
        cell: ({ row }) => {
            const condition = row.original.condition?.toLowerCase();
            const colors: Record<string, string> = {
                good: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
                bad: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
                warning: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
                critical: 'bg-rose-500/10 text-rose-600 border-rose-500/20',
            };
            return h(Badge, {
                variant: 'outline',
                class: `rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider ${colors[condition] || 'bg-muted text-muted-foreground'}`
            }, () => row.original.condition || 'Unknown');
        },
        enableSorting: true,
    },
    {
        accessorKey: 'open_requests',
        header: 'Open Tasks',
        cell: ({ row }) => h('span', { class: 'text-[11px] font-bold text-muted-foreground/80 uppercase tracking-widest' }, row.original.open_requests || '0'),
    },
    {
        id: 'actions',
        header: '',
        cell: ({ row }) => h('div', { class: 'text-right' }, [
            h('div', { class: 'flex items-center justify-end gap-1' }, [
                h(Button, { variant: 'ghost', size: 'icon', class: 'h-8 w-8', asChild: true }, {
                    default: () => h(Link, { href: facilitiesShow(row.original.id).url, 'aria-label': 'View facility' }, () =>
                        h(Eye, { class: 'h-4 w-4' }),
                    )
                }),
                h(Button, { variant: 'ghost', size: 'icon', class: 'h-8 w-8', asChild: true }, {
                    default: () => h(Link, { href: inspectionsCreate({ query: { facility_id: row.original.id } }).url, 'aria-label': 'Create inspection' }, () =>
                        h(ClipboardCheck, { class: 'h-4 w-4' }),
                    )
                }),
                h(Button, { variant: 'ghost', size: 'icon', class: 'h-8 w-8', asChild: true }, {
                    default: () => h(Link, { href: maintenanceCreate({ query: { facility_id: row.original.id } }).url, 'aria-label': 'Create maintenance request' }, () =>
                        h(Wrench, { class: 'h-4 w-4' }),
                    )
                }),
            ]),
        ])
    }
];

const flattenedFacilities = computed(() => props.facilityGroups.flatMap(g => g.facilities));
</script>

<template>

    <Head title="My Facilities" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-8 p-6 lg:p-10">
            <PageHeader title="My facilities" subtitle="Track the facilities you manage.">
                <template #actions>
                    <div class="flex items-center gap-2 rounded-xl border border-border bg-card p-1 shadow-sm">
                        <Button variant="ghost" size="sm"
                            :class="['h-9 rounded-lg px-4 text-[10px] font-black uppercase tracking-widest', viewMode === 'grid' ? 'bg-primary text-primary-foreground shadow-lg' : 'text-muted-foreground']"
                            @click="viewMode = 'grid'">
                            <LayoutGrid class="mr-2 h-4 w-4" /> Grid
                        </Button>
                        <Button variant="ghost" size="sm"
                            :class="['h-9 rounded-lg px-4 text-[10px] font-black uppercase tracking-widest', viewMode === 'table' ? 'bg-primary text-primary-foreground shadow-lg' : 'text-muted-foreground']"
                            @click="viewMode = 'table'">
                            <List class="mr-2 h-4 w-4" /> List
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <!-- Dashboard Body -->
            <div class="flex flex-col gap-10">

                <!-- Metrics Strip -->
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        class="relative overflow-hidden rounded-2xl border border-border bg-card p-6 shadow-sm transition-all hover:shadow-xl hover:border-border/80 group">
                        <div class="flex items-start justify-between">
                            <div class="space-y-1">
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/50">
                                    Total Assets</p>
                                <p class="text-3xl font-black font-display uppercase tabular-nums tracking-tight">{{
                                    metrics.totalFacilities }}</p>
                            </div>
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary border border-primary/20 transition-transform group-hover:scale-110">
                                <Building2 class="h-6 w-6" />
                            </div>
                        </div>
                    </div>
                    <div
                        class="relative overflow-hidden rounded-2xl border border-border bg-card p-6 shadow-sm transition-all hover:shadow-xl hover:border-border/80 group">
                        <div class="flex items-start justify-between">
                            <div class="space-y-1">
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-rose-500/50">Active
                                    Alerts</p>
                                <p
                                    class="text-3xl font-black font-display uppercase tabular-nums tracking-tight text-rose-600">
                                    {{ metrics.openRequests }}</p>
                            </div>
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-500/10 text-rose-500 border border-rose-500/20 transition-transform group-hover:scale-110">
                                <Wrench class="h-6 w-6" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Portfolio Content -->
                <div v-if="viewMode === 'grid'" class="space-y-8">
                    <Accordion type="multiple" class="space-y-6">
                        <AccordionItem v-for="group in facilityGroups" :key="group.facility_type?.name || 'root'"
                            :value="group.facility_type?.name || 'root'" class="border-none">
                            <AccordionTrigger
                                class="flex w-full items-center justify-between rounded-2xl border border-border bg-card/60 px-6 py-4 hover:bg-card hover:no-underline transition-all">
                                <div class="flex items-center gap-5">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-muted/50 border border-border">
                                        <Building2 class="h-5 w-5 text-muted-foreground/40" />
                                    </div>
                                    <div class="text-left">
                                        <h3
                                            class="font-black text-[13px] uppercase tracking-tight text-foreground group-hover:text-primary transition-colors">
                                            {{ group.facility_type?.name || 'Unassigned Cluster' }}
                                        </h3>
                                        <p
                                            class="text-[10px] font-bold text-muted-foreground/60 uppercase tracking-widest leading-none mt-1">
                                            {{ group.count }} assets managed in this sector
                                        </p>
                                    </div>
                                </div>
                            </AccordionTrigger>
                            <AccordionContent class="pt-6">
                                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                                    <div v-for="facility in group.facilities" :key="facility.id"
                                        class="relative group overflow-hidden rounded-2xl border border-border bg-card p-5 transition-all hover:shadow-xl hover:border-primary/20">
                                        <div class="flex flex-col h-full justify-between gap-4">
                                            <div class="space-y-3">
                                                <div class="flex items-start justify-between">
                                                    <Badge variant="outline"
                                                        class="rounded-full px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider bg-primary/5 text-primary border-primary/20 shadow-sm">
                                                        {{ facility.condition }}
                                                    </Badge>
                                                    <div v-if="facility.open_requests"
                                                        class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-600 text-[10px] font-black uppercase animate-pulse">
                                                        <Wrench class="h-3 w-3" /> {{ facility.open_requests }}
                                                    </div>
                                                </div>
                                                <h4
                                                    class="font-black text-[14px] uppercase tracking-tight text-foreground transition-colors group-hover:text-primary">
                                                    {{ facility.name }}
                                                </h4>
                                                <div class="space-y-1.5">
                                                    <div
                                                        class="flex items-center justify-between text-[10px] font-bold uppercase tracking-widest">
                                                        <span class="text-muted-foreground/40 text-[9px]">Last
                                                            Inspection</span>
                                                        <span class="text-foreground/80 tabular-nums">{{
                                                            facility.last_inspection || 'MISSING' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <Button size="sm"
                                                class="w-full h-10 rounded-xl bg-muted/30 border border-border group-hover:bg-primary group-hover:text-primary-foreground group-hover:border-primary transition-all text-[10px] font-black uppercase tracking-widest shadow-none"
                                                as-child>
                                                <Link :href="facilitiesShow(facility.id).url">Inspect Details
                                                </Link>
                                            </Button>
                                            <div class="grid grid-cols-2 gap-2">
                                            <Button size="icon" variant="outline" class="h-9 w-9 rounded-xl" as-child>
                                                <Link :href="inspectionsCreate({ query: { facility_id: facility.id } }).url" aria-label="New inspection">
                                                    <ClipboardCheck class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button size="icon" variant="outline" class="h-9 w-9 rounded-xl" as-child>
                                                <Link :href="maintenanceCreate({ query: { facility_id: facility.id } }).url" aria-label="New request">
                                                    <Wrench class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                </div>

                <div v-else class="flex flex-col gap-6">
                    <div class="flex items-center justify-between px-1">
                        <h3 class="text-[11px] font-black uppercase tracking-[0.25em] text-muted-foreground/50">Registry
                            View</h3>
                    </div>
                    <DataTable :data="flattenedFacilities" :columns="columns" :show-search="false" class="manager-table">
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.manager-table :deep(.overflow-hidden) {
    border-radius: 1rem;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    border: 1px solid hsl(var(--border));
    background-color: hsl(var(--card));
}

.manager-table :deep(thead tr) {
    background-color: hsl(var(--muted) / 0.2);
    border-bottom: 1px solid hsl(var(--border) / 0.6);
}

.manager-table :deep(th) {
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    padding: 1rem 1.5rem;
    color: hsl(var(--muted-foreground) / 0.6);
}

.manager-table :deep(td) {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid hsl(var(--border) / 0.4);
}

.manager-table :deep(tbody tr:last-child td) {
    border-bottom: none;
}
</style>
