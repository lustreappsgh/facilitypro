<script setup lang="ts">
import DataTable from '@/components/data-table/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    edit as facilitiesEdit,
    index as facilitiesIndex,
    show as facilitiesShow,
} from '@/routes/facilities';
import { show as inspectionsShow } from '@/routes/inspections';
import { show as maintenanceShow } from '@/routes/maintenance';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';
import type { ColumnDef } from '@tanstack/vue-table';
import { computed, h, ref } from 'vue';

interface FacilityRelation {
    name: string;
}

interface Facility {
    id: number;
    name: string;
    condition: string | null;
    facilityType?: FacilityRelation | null;
    parent?: FacilityRelation | null;
    manager?: FacilityRelation | null;
}

interface InspectionRow {
    id: number;
    inspection_date: string | null;
    added_by: string | null;
}

interface MaintenanceRequestRow {
    id: number;
    status: string | null;
    created_at: string | null;
    request_type: string | null;
}

interface ChildFacilityRow {
    id: number;
    name: string;
    condition: string | null;
    facility_type: string | null;
}

interface Props {
    facility: Facility;
    inspections: InspectionRow[];
    maintenanceRequests: MaintenanceRequestRow[];
    children: ChildFacilityRow[];
    lastInspection: {
        date: string | null;
        added_by: string | null;
    } | null;
    lastMaintenance: {
        date: string | null;
        status: string | null;
    } | null;
}

const props = defineProps<Props>();

const { can } = usePermissions();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Facilities',
        href: facilitiesIndex().url,
    },
    {
        title: 'Details',
        href: '#',
    },
];

const isFacilityManagerView = computed(
    () =>
        can('facilities.update') &&
        !can('facilities.assign_manager') &&
        !can('facilities.create'),
);

const facilityTypeName = computed(
    () => props.facility.facilityType?.name ?? 'Unassigned',
);

const parentName = computed(
    () => props.facility.parent?.name ?? 'No parent',
);

const managerName = computed(
    () => props.facility.manager?.name ?? 'Unassigned',
);

const tabs = ['inspections', 'maintenance', 'children'] as const;
const activeTab = ref<(typeof tabs)[number]>('inspections');

const tabTitle = (tab: (typeof tabs)[number]) => {
    if (tab === 'inspections') {
        return 'Inspections';
    }

    if (tab === 'maintenance') {
        return 'Maintenance';
    }

    return 'Child facilities';
};

const tabCount = (tab: (typeof tabs)[number]) => {
    if (tab === 'inspections') {
        return props.inspections.length;
    }

    if (tab === 'maintenance') {
        return props.maintenanceRequests.length;
    }

    return props.children.length;
};

const inspectionColumns: ColumnDef<InspectionRow>[] = [
    {
        id: 'date',
        accessorFn: (row) => row.inspection_date ?? '',
        header: 'Date',
        cell: ({ row }) => row.original.inspection_date ?? 'Not dated',
    },
    {
        id: 'inspector',
        accessorFn: (row) => row.added_by ?? '',
        header: 'Inspector',
        cell: ({ row }) => row.original.added_by ?? 'Unknown inspector',
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) =>
            can('inspections.view')
                ? h(Button, { variant: 'outline', size: 'sm', asChild: true }, () =>
                      h(Link, { href: inspectionsShow(row.original.id).url }, () => 'View'),
                  )
                : h('span', { class: 'text-xs text-muted-foreground' }, '-'),
        enableSorting: false,
        enableHiding: false,
    },
];

const maintenanceRequestColumns: ColumnDef<MaintenanceRequestRow>[] = [
    {
        id: 'date',
        accessorFn: (row) => row.created_at ?? '',
        header: 'Date',
        cell: ({ row }) => row.original.created_at ?? 'Not dated',
    },
    {
        id: 'type',
        accessorFn: (row) => row.request_type ?? '',
        header: 'Type',
        cell: ({ row }) => row.original.request_type ?? 'General',
    },
    {
        id: 'status',
        accessorFn: (row) => row.status ?? '',
        header: 'Status',
        cell: ({ row }) => row.original.status ?? 'pending',
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) =>
            can('maintenance_requests.view') || can('maintenance.view')
                ? h(Button, { variant: 'outline', size: 'sm', asChild: true }, () =>
                      h(Link, { href: maintenanceShow(row.original.id).url }, () => 'View'),
                  )
                : h('span', { class: 'text-xs text-muted-foreground' }, '-'),
        enableSorting: false,
        enableHiding: false,
    },
];

const childFacilityColumns: ColumnDef<ChildFacilityRow>[] = [
    {
        id: 'facility',
        accessorFn: (row) => row.name,
        header: 'Facility',
        cell: ({ row }) => row.original.name,
    },
    {
        id: 'type',
        accessorFn: (row) => row.facility_type ?? '',
        header: 'Type',
        cell: ({ row }) => row.original.facility_type ?? 'Unassigned type',
    },
    {
        id: 'condition',
        accessorFn: (row) => row.condition ?? '',
        header: 'Condition',
        cell: ({ row }) => row.original.condition ?? 'unknown',
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) =>
            h(Button, { variant: 'outline', size: 'sm', asChild: true }, () =>
                h(Link, { href: facilitiesShow(row.original.id).url }, () => 'View'),
            ),
        enableSorting: false,
        enableHiding: false,
    },
];
</script>

<template>
    <Head title="Facility details" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader
                :title="facility.name"
                :subtitle="`${facilityTypeName} · Managed by ${managerName}`"
            >
                <template #actions>
                    <div class="flex flex-wrap items-center gap-3">
                        <Badge class="rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-semibold uppercase text-emerald-700">
                            {{ facility.condition ?? 'unknown' }}
                        </Badge>
                        <Button variant="ghost" as-child>
                            <Link :href="facilitiesIndex().url">Back to list</Link>
                        </Button>
                        <Button v-if="can('facilities.update') && !isFacilityManagerView" as-child>
                            <Link :href="facilitiesEdit(facility.id).url">Edit Facility</Link>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-[1fr_1.6fr]">
                <div class="rounded-2xl border border-emerald-100/70 bg-white shadow-sm">
                    <div class="flex items-center justify-between gap-3 border-b border-emerald-100/70 px-6 py-4">
                        <h2 class="text-base font-semibold text-slate-900">Facility Overview</h2>
                        <div class="flex h-8 w-8 items-center justify-center rounded-full border border-emerald-100 text-emerald-700 text-xs font-semibold">
                            i
                        </div>
                    </div>

                    <div class="grid gap-4 px-6 py-5 text-sm">
                        <div class="grid gap-2 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800/70">
                                    Facility type
                                </p>
                                <p class="mt-2 font-medium text-slate-900">
                                    {{ facilityTypeName }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800/70">
                                    Condition
                                </p>
                                <div class="mt-2 flex items-center gap-2 text-slate-900">
                                    <span class="h-2 w-16 rounded-full bg-emerald-100">
                                        <span class="block h-2 w-10 rounded-full bg-emerald-500"></span>
                                    </span>
                                    <span class="text-xs font-semibold uppercase">
                                        {{ facility.condition ?? 'unknown' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800/70">
                                    Manager
                                </p>
                                <p class="mt-2 font-medium text-slate-900">
                                    {{ managerName }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800/70">
                                    Parent facility
                                </p>
                                <p class="mt-2 font-medium text-slate-900">
                                    {{ parentName }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800/70">
                                Last inspected
                            </p>
                            <p class="mt-2 font-medium text-slate-900">
                                {{ lastInspection?.date ?? 'Not yet' }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ lastInspection?.added_by ?? 'Unknown inspector' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800/70">
                                Last maintenance
                            </p>
                            <p class="mt-2 font-medium text-slate-900">
                                {{ lastMaintenance?.date ?? 'Not yet' }}
                            </p>
                            <p class="text-xs text-muted-foreground capitalize">
                                {{ lastMaintenance?.status ?? 'No records' }}
                            </p>
                        </div>
                    </div>
                </div>

                <Tabs
                    v-model="activeTab"
                    class="rounded-2xl border border-emerald-100/70 bg-white shadow-sm"
                >
                    <div class="border-b border-emerald-100/70 px-6 pt-5">
                        <TabsList class="flex flex-wrap items-center gap-6 bg-transparent p-0">
                            <TabsTrigger
                                v-for="tab in tabs"
                                :key="tab"
                                :value="tab"
                                class="rounded-none border-b-2 border-transparent px-0 pb-3 text-xs font-semibold uppercase tracking-wide text-emerald-900/60 hover:text-emerald-800 data-[state=active]:border-emerald-500 data-[state=active]:text-emerald-700"
                            >
                                <span>{{ tabTitle(tab) }}</span>
                                <Badge variant="secondary" class="ml-2 px-2 text-[10px]">
                                    {{ tabCount(tab) }}
                                </Badge>
                            </TabsTrigger>
                        </TabsList>
                    </div>

                    <TabsContent value="inspections" class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-slate-900">Recent Inspections</h3>
                            </div>
                            <DataTable
                                :data="inspections"
                                :columns="inspectionColumns"
                                :show-search="false"
                                :enable-column-toggle="false"
                                :show-selection-summary="false"

                            />
                        </div>
                    </TabsContent>

                    <TabsContent value="maintenance" class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-slate-900">Maintenance Requests</h3>
                            </div>
                            <DataTable
                                :data="maintenanceRequests"
                                :columns="maintenanceRequestColumns"
                                :show-search="false"
                                :enable-column-toggle="false"
                                :show-selection-summary="false"

                            />
                        </div>
                    </TabsContent>

                    <TabsContent value="children" class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-slate-900">Child Facilities</h3>
                            </div>
                            <DataTable
                                :data="children"
                                :columns="childFacilityColumns"
                                :show-search="false"
                                :enable-column-toggle="false"
                                :show-selection-summary="false"

                            />
                        </div>
                    </TabsContent>
                </Tabs>
            </div>
        </div>
    </AppLayout>
</template>
