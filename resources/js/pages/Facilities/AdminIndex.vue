<script setup lang="ts">
import { h, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import DataTable from '@/components/data-table/DataTable.vue';
import {
    Layers, Search, Save, ShieldCheck
} from 'lucide-vue-next';
import type { ColumnDef } from '@tanstack/vue-table';

interface Facility {
    id: number;
    name: string;
    condition?: string | null;
    facilityType?: { name: string };
    manager?: { name: string };
    parent?: { id: number; name: string };
}

interface Props {
    facilities: Facility[];
    facilityTypes: any[];
    users: any[];
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Portfolio', href: '/facilities' },
    { title: 'Administrative Oversight', href: '/facilities/admin' }
];

const search = ref('');
const ROOT_PARENT = 'none';
const parentSelections = ref<Record<number, string | null>>({});

// Initialize selections
props.facilities.forEach((f) => {
    parentSelections.value[f.id] = f.parent?.id ? String(f.parent.id) : ROOT_PARENT;
});

const updateHierarchy = (facility: Facility) => {
    const parentId = parentSelections.value[facility.id] ?? ROOT_PARENT;
    router.patch(route('facilities.hierarchy', facility.id), {
        parent_id: parentId === ROOT_PARENT ? null : Number(parentId),
    }, { preserveScroll: true });
};

const columns: ColumnDef<Facility>[] = [
    {
        accessorKey: 'name',
        header: 'Hierarchy Node',
        cell: ({ row }) => h('div', { class: 'flex flex-col' }, [
            h('span', { class: 'font-black text-[13px] uppercase tracking-tight text-foreground' }, row.original.name),
            h('span', { class: 'text-[10px] font-bold text-muted-foreground/60 uppercase tracking-widest' }, row.original.manager?.name || 'Unassigned Manager')
        ]),
        enableSorting: true,
    },
    {
        accessorKey: 'facilityType.name',
        header: 'Entity Type',
        cell: ({ row }) => h('span', { class: 'text-[11px] font-bold text-muted-foreground/80 uppercase tracking-widest' }, row.original.facilityType?.name || '-'),
    },
    {
        id: 'parent',
        header: 'Parent Assignment',
        cell: ({ row }) => h('div', { class: 'flex items-center gap-3' }, [
            h(Select, {
                modelValue: parentSelections.value[row.original.id] ?? ROOT_PARENT,
                'onUpdate:modelValue': (val: string) => parentSelections.value[row.original.id] = val
            }, {
                default: () => [
                    h(SelectTrigger, { class: 'h-9 w-48 bg-card border-border rounded-lg text-[10px] font-bold uppercase tracking-widest' }, {
                        default: () => h(SelectValue, { placeholder: 'Set Cluster' })
                    }),
                    h(SelectContent, {}, {
                        default: () => [
                            h(SelectItem, { value: ROOT_PARENT }, () => 'Independent Root'),
                            ...props.facilities
                                .filter(f => f.id !== row.original.id)
                                .map(f => h(SelectItem, { value: String(f.id) }, () => f.name))
                        ]
                    })
                ]
            }),
            h(Button, {
                size: 'sm',
                variant: 'ghost',
                class: 'h-9 w-9 p-0 hover:bg-primary/10 hover:text-primary transition-colors',
                onClick: () => updateHierarchy(row.original)
            }, () => h(Save, { class: 'h-4 w-4' }))
        ])
    }
];
</script>

<template>

    <Head title="Facility hierarchy" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-8 p-6 lg:p-10">
            <PageHeader title="Facility hierarchy"
                subtitle="Define organizational structure and reporting relationships." :show-filters-toggle="false">
                <template #actions>
                    <Button variant="outline"
                        class="h-12 px-6 border-border rounded-xl font-black uppercase tracking-widest text-[11px] text-muted-foreground"
                        as-child>
                        <Link href="/facilities">
                            Back to facilities
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <!-- Oversight Workspace -->
            <div class="flex flex-col gap-6">
                <div class="flex items-center justify-between px-1">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-muted/50 border border-border">
                            <Layers class="h-5 w-5 text-muted-foreground/60" />
                        </div>
                        <h3 class="text-[11px] font-black uppercase tracking-[0.25em] text-muted-foreground/50">
                            Hierarchy tools</h3>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="relative w-64">
                            <Search
                                class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground/40" />
                            <input v-model="search" type="text" placeholder="Search facilities"
                                class="w-full bg-card border border-border rounded-xl py-2 pl-9 pr-4 text-[11px] font-bold uppercase tracking-widest text-foreground placeholder:text-muted-foreground/30 focus:outline-none focus:ring-1 focus:ring-primary/40 transition-all shadow-sm" />
                        </div>
                    </div>
                </div>

                <DataTable :data="facilities" :columns="columns" :show-search="false" class="oversight-table">
                    <template #actions>
                        <Badge variant="outline"
                            class="h-8 px-4 rounded-lg bg-primary/5 text-primary border-primary/20 font-black uppercase tracking-widest text-[9px]">
                            <ShieldCheck class="mr-2 h-3 w-3" /> System Integrity Validated
                        </Badge>
                    </template>
                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.oversight-table :deep(.overflow-hidden) {
    border-radius: 1rem;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    border: 1px solid hsl(var(--border));
    background-color: hsl(var(--card));
}

.oversight-table :deep(thead tr) {
    background-color: hsl(var(--muted) / 0.2);
    border-bottom: 1px solid hsl(var(--border) / 0.6);
}

.oversight-table :deep(th) {
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    padding: 1rem 1.5rem;
    color: hsl(var(--muted-foreground) / 0.6);
}

.oversight-table :deep(td) {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid hsl(var(--border) / 0.4);
}

.oversight-table :deep(tbody tr:last-child td) {
    border-bottom: none;
}
</style>
