<script setup lang="ts">
import { ref, h, watch, computed } from 'vue';
import { Head, Link, router, usePage, useRemember } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import DataTable from '@/components/data-table/DataTable.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import InspectionModal from '@/components/InspectionModal.vue';
import MaintenanceRequestModal from '@/components/MaintenanceRequestModal.vue';
import { usePermissions } from '@/composables/usePermissions';
import {
    Building2, Search, Plus,
    MoreHorizontal, Pencil, Download,
    LayoutGrid, List,
    ClipboardCheck, Wrench,
    EllipsisVertical, Settings
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger
} from '@/components/ui/dropdown-menu';
import { index as facilitiesIndex, show as facilitiesShow, edit as facilitiesEdit, create as facilitiesCreate } from '@/routes/facilities';
import type { ColumnDef } from '@tanstack/vue-table';

const filtersVisible = ref(false);

interface Manager {
    id: number | string;
    name: string;
    facilities_count: number;
}

interface FacilityOption {
    id: number;
    name: string;
}

interface FacilityTypeOption {
    id: number;
    name: string;
}

interface RequestTypeOption {
    id: number;
    name: string;
}

interface Facility {
    id: number;
    name: string;
    condition: string;
    facilityType?: { name: string };
    parent?: { name: string };
    manager?: { name: string };
}

interface Props {
    facilities: {
        data: Facility[];
        links: any[];
        total: number;
        meta?: any;
    };
    managers: Manager[];
    activeManagerId: string | number | null;
    formOptions: {
        facilities: FacilityOption[];
        facilityTypes: FacilityTypeOption[];
        conditions: string[];
        requestTypes: RequestTypeOption[];
    };
}

const props = defineProps<Props>();
const page = usePage();
const { can } = usePermissions();

const getQueryParam = (key: string) => {
    const query = page.url.split('?')[1] || '';
    const params = new URLSearchParams(query);
    return params.get(key);
};

const getViewFromUrl = (url?: string) => {
    if (!url) return null;
    const query = url.split('?')[1] || '';
    const params = new URLSearchParams(query);
    const view = params.get('view');
    return view === 'grid' || view === 'table' ? view : null;
};

const breadcrumbs = [
    { title: 'Facilities', href: facilitiesIndex().url }
];

const search = ref(getQueryParam('search') ?? '');
const viewMode = ref<'table' | 'grid'>(getViewFromUrl(page.url) ?? 'table');
const selectedManager = ref(props.activeManagerId ? String(props.activeManagerId) : 'all');
const selectedCondition = ref(getQueryParam('condition') ?? 'all');
const selectedFacilityType = ref(getQueryParam('facility_type_id') ?? 'all');
const suppressFilters = ref(false);

const canInspect = computed(() => can('inspections.create'));
const canRequest = computed(() => can('maintenance.create') || can('maintenance_requests.create'));
const canViewFacility = computed(() => can('facilities.view'));
const canEditFacility = computed(() => can('facilities.update'));

const inspectionModalOpen = ref(false);
const requestModalOpen = ref(false);
const selectedFacilityIds = useRemember<number[]>([], 'facilities.selection');
const modalFacilityIds = ref<number[]>([]);

const normalizeManagerId = (value: string) => {
    if (!value || value === 'all') return undefined;
    return value;
};

const applyFilters = (options: Record<string, unknown> = {}) => {
    router.get(facilitiesIndex().url, {
        manager_id: normalizeManagerId(selectedManager.value),
        search: search.value || undefined,
        condition: selectedCondition.value === 'all' ? undefined : selectedCondition.value,
        facility_type_id: selectedFacilityType.value === 'all' ? undefined : selectedFacilityType.value,
        view: viewMode.value
    }, {
        preserveState: true,
        preserveScroll: true,
        ...options
    });
};

const resetFilters = () => {
    suppressFilters.value = true;
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    search.value = '';
    selectedManager.value = 'all';
    selectedCondition.value = 'all';
    selectedFacilityType.value = 'all';
    suppressFilters.value = false;
    applyFilters({ replace: true, only: ['facilities'] });
};

// Debounced Search Logic
let searchTimeout: any;
watch(search, () => {
    if (suppressFilters.value) {
        return;
    }
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters({ replace: true, only: ['facilities'] });
    }, 500);
});

watch(selectedManager, () => {
    if (suppressFilters.value) {
        return;
    }
    applyFilters({ replace: true, only: ['facilities'] });
});

watch(selectedCondition, () => {
    if (suppressFilters.value) {
        return;
    }
    applyFilters({ replace: true, only: ['facilities'] });
});

watch(selectedFacilityType, () => {
    if (suppressFilters.value) {
        return;
    }
    applyFilters({ replace: true, only: ['facilities'] });
});

watch(() => props.activeManagerId, (value) => {
    selectedManager.value = value ? String(value) : 'all';
});

watch(viewMode, () => {
    if (suppressFilters.value) {
        return;
    }
    applyFilters({ replace: true, only: ['facilities'] });
});

const conditionClass = (condition?: string) => {
    const status = condition?.toLowerCase();
    const colors: Record<string, string> = {
        good: 'bg-success-subtle text-success border-border/50',
        bad: 'bg-yellow-100 text-yellow-700 border-border/50 dark:bg-yellow-500/10 dark:text-yellow-300',
        warning: 'bg-yellow-100 text-yellow-700 border-border/50 dark:bg-yellow-500/10 dark:text-yellow-300',
        critical: 'bg-red-100 text-red-700 border-border/50 dark:bg-red-500/10 dark:text-red-300',
    };
    return colors[status || ''] || 'bg-muted text-muted-foreground border-border/50';
};

const manageableFacilityIds = computed(() => new Set(props.formOptions.facilities.map((facility) => facility.id)));
const canSelectFacility = (facilityId: number) =>
    manageableFacilityIds.value.has(facilityId) && (canInspect.value || canRequest.value);

const selectableFacilityIdsOnPage = computed(() =>
    props.facilities.data
        .map((facility) => facility.id)
        .filter((id) => canSelectFacility(id)),
);

const selectionCount = computed(() => selectedFacilityIds.value.length);
const allFacilitiesSelected = computed(() => {
    const selectable = selectableFacilityIdsOnPage.value;
    return selectable.length > 0 && selectable.every((id) => selectedFacilityIds.value.includes(id));
});
const someFacilitiesSelected = computed(() => {
    const selectableSet = new Set(selectableFacilityIdsOnPage.value);
    return selectedFacilityIds.value.some((id) => selectableSet.has(id)) && !allFacilitiesSelected.value;
});

const toggleFacilitySelection = (id: number, checked: boolean) => {
    if (!canSelectFacility(id)) {
        return;
    }
    if (checked) {
        if (!selectedFacilityIds.value.includes(id)) {
            selectedFacilityIds.value = [...selectedFacilityIds.value, id];
        }
        return;
    }

    selectedFacilityIds.value = selectedFacilityIds.value.filter((item) => item !== id);
};

const toggleSelectAllFacilities = (checked: boolean) => {
    if (checked) {
        selectedFacilityIds.value = [...selectableFacilityIdsOnPage.value];
        return;
    }
    selectedFacilityIds.value = [];
};

const openInspectionModal = (facilityIds: number[]) => {
    modalFacilityIds.value = facilityIds;
    inspectionModalOpen.value = true;
};

const openRequestModal = (facilityIds: number[]) => {
    modalFacilityIds.value = facilityIds;
    requestModalOpen.value = true;
};

watch(() => props.facilities.data, (rows) => {
    const available = new Set(rows.map((row) => row.id));
    selectedFacilityIds.value = selectedFacilityIds.value.filter((id) => available.has(id));
    modalFacilityIds.value = modalFacilityIds.value.filter((id) => available.has(id));
});

// DataTable Columns
const columns: ColumnDef<Facility>[] = [
    {
        id: 'select',
        header: () => h(Checkbox, {
            modelValue: allFacilitiesSelected.value || (someFacilitiesSelected.value && 'indeterminate'),
            disabled: selectableFacilityIdsOnPage.value.length === 0,
            'onUpdate:modelValue': (value: boolean | 'indeterminate') => toggleSelectAllFacilities(value === true),
            'aria-label': 'Select all',
        }),
        cell: ({ row }) => h(Checkbox, {
            modelValue: selectedFacilityIds.value.includes(row.original.id),
            disabled: !canSelectFacility(row.original.id),
            'onUpdate:modelValue': (value: boolean | 'indeterminate') => toggleFacilitySelection(row.original.id, value === true),
            'aria-label': 'Select row',
        }),
        enableSorting: false,
        enableHiding: false,
    },
    {
        accessorKey: 'name',
        header: 'Facility Name',
        cell: ({ row }) => h('div', { class: 'flex flex-col' }, [
            h('span', { class: 'font-black text-[13px] uppercase tracking-tight text-foreground' }, row.original.name),
            h('span', { class: 'text-[10px] font-bold text-muted-foreground/60 uppercase tracking-widest' }, row.original.facilityType?.name || 'Unassigned Type')
        ]),
        enableSorting: true,
    },
    {
        accessorKey: 'condition',
        header: 'Status',
        cell: ({ row }) => {
            return h(Badge, {
                variant: 'outline',
                class: `rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider ${conditionClass(row.original.condition)}`
            }, () => row.original.condition || 'Unknown');
        },
        enableSorting: true,
    },
    {
        accessorKey: 'parent.name',
        header: 'Parent Node',
        cell: ({ row }) => h('span', { class: 'text-[11px] font-bold text-muted-foreground/80 uppercase tracking-widest' }, row.original.parent?.name || 'Root'),
        enableSorting: true,
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) => h('div', { class: 'flex justify-end' }, [
            (canViewFacility.value || canEditFacility.value || canInspect.value || canRequest.value) ? h(DropdownMenu, {}, {
                default: () => [
                    h(DropdownMenuTrigger, { asChild: true }, {
                        default: () => h(Button, { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0' }, () =>
                            h(MoreHorizontal, { class: 'h-4 w-4' })
                        )
                    }),
                    h(DropdownMenuContent, { align: 'end' }, {
                        default: () => [
                            canViewFacility.value
                                ? h(DropdownMenuItem, { onClick: () => router.get(facilitiesShow(row.original.id).url) }, () => [
                                    h(Building2, { class: 'mr-2 h-4 w-4' }), h('span', 'View Facility')
                                ])
                                : null,
                            canInspect.value && canSelectFacility(row.original.id)
                                ? h(DropdownMenuItem, { onClick: () => openInspectionModal([row.original.id]) }, () => [
                                    h(ClipboardCheck, { class: 'mr-2 h-4 w-4' }), h('span', 'Inspect')
                                ])
                                : null,
                            canRequest.value && canSelectFacility(row.original.id)
                                ? h(DropdownMenuItem, { onClick: () => openRequestModal([row.original.id]) }, () => [
                                    h(Wrench, { class: 'mr-2 h-4 w-4' }), h('span', 'Request')
                                ])
                                : null,
                            canEditFacility.value
                                ? h(DropdownMenuItem, { onClick: () => router.get(facilitiesEdit(row.original.id).url) }, () => [
                                    h(Pencil, { class: 'mr-2 h-4 w-4' }), h('span', 'Edit Entity')
                                ])
                                : null,
                        ].filter(Boolean)
                    })
                ]
            }) : null,
        ])
    }
];
</script>

<template>

    <Head title="Facilities Portfolio" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-8 p-6 lg:p-10">
            <PageHeader title="Facilities" subtitle="Oversee your portfolio of properties."
                :action-label="can('facilities.create') ? 'New facility' : ''"
                :action-href="can('facilities.create') ? facilitiesCreate().url : ''" :action-icon="Plus"
                :show-filters-toggle="true" :filters-visible="filtersVisible"
                @toggle-filters="filtersVisible = !filtersVisible" />

            <div v-if="filtersVisible" class="rounded-xl border border-border/60 bg-card p-4">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="relative min-w-[220px] flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input v-model="search" class="pl-9" placeholder="Search facilities..." />
                        </div>

                        <Select v-model="selectedManagerId">
                            <SelectTrigger class="min-w-[160px]">
                                <SelectValue placeholder="All managers" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All managers</SelectItem>
                                <SelectItem v-for="manager in formOptions.managers" :key="manager.id"
                                    :value="String(manager.id)">
                                    {{ manager.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Select v-model="selectedTypeId">
                            <SelectTrigger class="min-w-[160px]">
                                <SelectValue placeholder="All types" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All types</SelectItem>
                                <SelectItem v-for="type in formOptions.facilityTypes" :key="type.id"
                                    :value="String(type.id)">
                                    {{ type.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Button variant="ghost" @click="resetFilters">
                            Reset
                        </Button>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Button variant="outline" size="sm"
                            class="rounded-xl border-border h-9 text-[10px] font-black uppercase tracking-widest text-muted-foreground hover:bg-muted/50">
                            <Download class="h-3.5 w-3.5 mr-2" /> Export
                        </Button>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" size="sm"
                                    class="rounded-xl border-border h-9 text-[10px] font-black uppercase tracking-widest text-muted-foreground hover:bg-muted/50">
                                    <EllipsisVertical class="h-3.5 w-3.5" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuLabel>View options</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem>
                                    <Settings class="mr-2 h-4 w-4" />
                                    <span>Customize table</span>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <div class="text-sm text-muted-foreground">
                    {{ facilities.total }} facilities
                </div>
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-3">
                            <Select v-model="selectedCondition">
                                <SelectTrigger class="min-w-[160px]">
                                    <SelectValue placeholder="All conditions" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All conditions</SelectItem>
                                    <SelectItem v-for="condition in formOptions.conditions" :key="condition"
                                        :value="condition">
                                        {{ condition }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2 rounded-xl border border-border bg-card p-1 shadow-sm">
                            <Button variant="ghost" size="sm"
                                :class="['h-9 rounded-lg px-4 text-[10px] font-black uppercase tracking-widest', viewMode === 'table' ? 'bg-primary text-primary-foreground shadow-lg' : 'text-muted-foreground']"
                                @click="viewMode = 'table'">
                                <List class="mr-2 h-4 w-4" /> Table
                            </Button>
                            <Button variant="ghost" size="sm"
                                :class="['h-9 rounded-lg px-4 text-[10px] font-black uppercase tracking-widest', viewMode === 'grid' ? 'bg-primary text-primary-foreground shadow-lg' : 'text-muted-foreground']"
                                @click="viewMode = 'grid'">
                                <LayoutGrid class="mr-2 h-4 w-4" /> Grid
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="viewMode === 'table'" class="flex flex-col gap-6 min-w-0">
                <div class="flex items-center justify-between px-1">
                    <h3 class="text-[11px] font-black uppercase tracking-[0.25em] text-muted-foreground/50">
                        Facilities list
                    </h3>
                </div>

                <DataTable :data="facilities.data" :columns="columns" :show-search="false"
                    :show-selection-summary="false" :enable-row-selection="false" class="portfolio-table">
                    <template #actions>
                        <div class="flex items-center gap-3">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60">
                                {{ selectionCount }} selected
                            </span>
                            <Button v-if="canInspect" variant="outline" size="sm" :disabled="!selectionCount"
                                class="rounded-lg h-8 px-4 text-[10px] font-bold uppercase tracking-widest border-border hover:bg-primary/5 hover:text-primary transition-colors"
                                @click="openInspectionModal([...selectedFacilityIds])">
                                Bulk Inspect
                            </Button>
                            <Button v-if="canRequest" variant="outline" size="sm" :disabled="!selectionCount"
                                class="rounded-lg h-8 px-4 text-[10px] font-bold uppercase tracking-widest border-border hover:bg-primary/5 hover:text-primary transition-colors"
                                @click="openRequestModal([...selectedFacilityIds])">
                                Bulk Request
                            </Button>
                            <Button variant="ghost" size="sm" :disabled="!selectionCount"
                                class="rounded-lg h-8 px-4 text-[10px] font-bold uppercase tracking-widest"
                                @click="selectedFacilityIds = []">
                                Clear Selection
                            </Button>
                        </div>
                    </template>
                </DataTable>

                <PaginationLinks :links="facilities.links" />
            </div>

            <div v-else class="space-y-4">
                <div class="flex items-center gap-3 px-1">
                    <Building2 class="h-5 w-5 text-muted-foreground/40" />
                    <h3 class="text-[11px] font-black uppercase tracking-[0.25em] text-muted-foreground/50">Grid
                        Overview</h3>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4 px-1">
                    <div
                        class="flex items-center gap-3 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60">
                        <Checkbox :model-value="allFacilitiesSelected || (someFacilitiesSelected && 'indeterminate')"
                            :disabled="!selectableFacilityIdsOnPage.length"
                            @update:model-value="(value: boolean | 'indeterminate') => toggleSelectAllFacilities(value === true)"
                            aria-label="Select all facilities" />
                        <span>{{ selectionCount }} selected</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button v-if="canInspect" variant="outline" size="sm" :disabled="!selectionCount"
                            class="rounded-lg h-8 px-4 text-[10px] font-bold uppercase tracking-widest border-border hover:bg-primary/5 hover:text-primary transition-colors"
                            @click="openInspectionModal([...selectedFacilityIds])">
                            Bulk Inspect
                        </Button>
                        <Button v-if="canRequest" variant="outline" size="sm" :disabled="!selectionCount"
                            class="rounded-lg h-8 px-4 text-[10px] font-bold uppercase tracking-widest border-border hover:bg-primary/5 hover:text-primary transition-colors"
                            @click="openRequestModal([...selectedFacilityIds])">
                            Bulk Request
                        </Button>
                        <Button variant="ghost" size="sm" :disabled="!selectionCount"
                            class="rounded-lg h-8 px-4 text-[10px] font-bold uppercase tracking-widest"
                            @click="selectedFacilityIds = []">
                            Clear Selection
                        </Button>
                    </div>
                </div>

                <div v-if="facilities.data.length" class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    <Card v-for="facility in facilities.data" :key="facility.id"
                        class="border-border/70 bg-card/70 shadow-sm transition-all hover:shadow-xl hover:border-primary/20">
                        <CardContent class="flex h-full flex-col gap-4 p-5">
                            <div class="flex items-start justify-between">
                                <Checkbox :model-value="selectedFacilityIds.includes(facility.id)"
                                    :disabled="!canSelectFacility(facility.id)"
                                    @update:model-value="(value: boolean | 'indeterminate') => toggleFacilitySelection(facility.id, value === true)"
                                    aria-label="Select facility" />
                                <Button v-if="canEditFacility" variant="ghost" size="icon" class="h-8 w-8"
                                    @click="router.get(facilitiesEdit(facility.id).url)">
                                    <Pencil class="h-4 w-4" />
                                </Button>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <div class="space-y-1">
                                    <p
                                        class="font-black text-[13px] uppercase tracking-tight text-foreground line-clamp-2">
                                        {{ facility.name }}
                                    </p>
                                    <p class="text-[10px] font-bold text-muted-foreground/60 uppercase tracking-widest">
                                        {{ facility.facilityType?.name || 'Unassigned Type' }}
                                    </p>
                                </div>
                                <Badge variant="outline"
                                    :class="`rounded-full px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider ${conditionClass(facility.condition)}`">
                                    {{ facility.condition || 'Unknown' }}
                                </Badge>
                            </div>

                            <div class="space-y-2 text-[10px] font-bold uppercase tracking-widest">
                                <div class="flex items-center justify-between">
                                    <span class="text-muted-foreground/50">Manager</span>
                                    <span class="text-foreground/80">{{ facility.manager?.name || 'Unassigned' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-muted-foreground/50">Parent</span>
                                    <span class="text-foreground/80">{{ facility.parent?.name || 'Root' }}</span>
                                </div>
                            </div>

                            <div class="mt-auto flex items-center justify-between gap-2">
                                <div class="grid w-full grid-cols-3 gap-2">
                                    <Button size="sm" variant="outline" :disabled="!canViewFacility"
                                        class="h-9 text-[10px] font-black uppercase tracking-widest"
                                        @click="canViewFacility && router.get(facilitiesShow(facility.id).url)">
                                        View
                                    </Button>
                                    <Button size="sm" variant="outline"
                                        :disabled="!canInspect || !manageableFacilityIds.has(facility.id)"
                                        class="h-9 text-[10px] font-black uppercase tracking-widest"
                                        @click="canInspect && manageableFacilityIds.has(facility.id) && openInspectionModal([facility.id])">
                                        Inspect
                                    </Button>
                                    <Button size="sm" variant="outline"
                                        :disabled="!canRequest || !manageableFacilityIds.has(facility.id)"
                                        class="h-9 text-[10px] font-black uppercase tracking-widest"
                                        @click="canRequest && manageableFacilityIds.has(facility.id) && openRequestModal([facility.id])">
                                        Request
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card v-else class="border-border/70 bg-card shadow-sm">
                    <CardContent class="py-12 text-center">
                        <Building2 class="mx-auto h-12 w-12 text-muted-foreground/20 mb-4" />
                        <p class="text-sm font-bold text-muted-foreground">No facilities found</p>
                        <p class="text-xs text-muted-foreground/60 mt-1">Try adjusting your filters</p>
                    </CardContent>
                </Card>

                <PaginationLinks :links="facilities.links" />
            </div>
        </div>


        <InspectionModal v-if="canInspect" v-model:open="inspectionModalOpen" :facilities="formOptions.facilities"
            :conditions="formOptions.conditions" :selected-facility-ids="modalFacilityIds" :redirect-to="page.url"
            @success="router.reload()" />

        <MaintenanceRequestModal v-if="canRequest" v-model:open="requestModalOpen" :facilities="formOptions.facilities"
            :request-types="formOptions.requestTypes" :selected-facility-ids="modalFacilityIds" :redirect-to="page.url"
            @success="router.reload()" />
    </AppLayout>
</template>

<style scoped>
.portfolio-table :deep(.overflow-hidden) {
    border-radius: 1rem;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    border: 1px solid hsl(var(--border));
    background-color: hsl(var(--card));
}

.portfolio-table :deep(thead tr) {
    background-color: hsl(var(--muted) / 0.2);
    border-bottom: 1px solid hsl(var(--border) / 0.6);
}

.portfolio-table :deep(th) {
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    padding: 1rem 1.5rem;
    color: hsl(var(--muted-foreground) / 0.6);
}

.portfolio-table :deep(td) {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid hsl(var(--border) / 0.4);
}

.portfolio-table :deep(tbody tr:last-child td) {
    border-bottom: none;
}
</style>
