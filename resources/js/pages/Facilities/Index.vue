<script setup lang="ts">
import { ref, h, watch, computed, onBeforeUnmount } from 'vue';
import { Head, Link, router, useForm, usePage, useRemember } from '@inertiajs/vue3';
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
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import InspectionModal from '@/components/InspectionModal.vue';
import MaintenanceRequestModal from '@/components/MaintenanceRequestModal.vue';
import { usePermissions } from '@/composables/usePermissions';
import {
    Building2, ClipboardCheck, Eye, Pencil, Plus,
    LayoutGrid, List,
    Search, Wrench, Trash2,
} from 'lucide-vue-next';
import { index as facilitiesIndex, show as facilitiesShow, edit as facilitiesEdit, create as facilitiesCreate, destroy as facilitiesDestroy } from '@/routes/facilities';
import type { ColumnDef } from '@tanstack/vue-table';

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

interface ParentOption {
    id: number;
    name: string;
    children_count: number;
}

interface RequestTypeOption {
    id: number;
    name: string;
}

interface Facility {
    id: number;
    name: string;
    condition: string;
    children_count?: number;
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
    routes: {
        bulkAssignManager: string;
        bulkUpdate: string;
        managerAssignments: string;
    };
    formOptions: {
        facilities: FacilityOption[];
        parents: ParentOption[];
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
const appliedManager = ref(props.activeManagerId ? String(props.activeManagerId) : 'all');
const appliedCondition = ref(getQueryParam('condition') ?? 'all');
const appliedFacilityType = ref(getQueryParam('facility_type_id') ?? 'all');
const appliedParent = ref(getQueryParam('parent_id') ?? 'all');
const appliedChildrenScope = ref(getQueryParam('children_scope') ?? 'all');
const selectedManager = ref(appliedManager.value);
const selectedCondition = ref(appliedCondition.value);
const selectedFacilityType = ref(appliedFacilityType.value);
const selectedParent = ref(appliedParent.value);
const selectedChildrenScope = ref(appliedChildrenScope.value);
const facilitiesPerPageFromProps = computed(() => {
    const facilitiesAny = props.facilities as any;
    const perPage = facilitiesAny.meta?.per_page ?? facilitiesAny.per_page;
    return Number(perPage) || 50;
});
const selectedPerPage = ref(getQueryParam('per_page') ?? String(facilitiesPerPageFromProps.value));

const facilitiesPaginationMeta = computed(() => {
    const facilitiesAny = props.facilities as any;
    return facilitiesAny.meta ?? facilitiesAny;
});

const facilitiesPagination = computed(() => {
    const links = props.facilities.links ?? [];
    const previous = links.find((link: any) => typeof link?.label === 'string' && link.label.toLowerCase().includes('previous'))?.url ?? null;
    const next = links.find((link: any) => typeof link?.label === 'string' && link.label.toLowerCase().includes('next'))?.url ?? null;

    return {
        currentPage: Number(facilitiesPaginationMeta.value?.current_page ?? 1),
        lastPage: Number(facilitiesPaginationMeta.value?.last_page ?? 1),
        perPage: Number(facilitiesPaginationMeta.value?.per_page ?? facilitiesPerPageFromProps.value),
        prevUrl: previous,
        nextUrl: next,
        only: ['facilities'],
        onPageSizeChange: handlePerPageChange,
    };
});

const canInspect = computed(() => can('inspections.create'));
const canRequest = computed(() => can('maintenance.create') || can('maintenance_requests.create'));
const canViewFacility = computed(() => can('facilities.view'));
const canEditFacility = computed(() => can('facilities.update'));
const canDeleteFacility = computed(() => can('facilities.delete'));
const canAssignManager = computed(() => can('facilities.assign_manager'));
const canBulkEdit = computed(() => can('facilities.update'));
const managerOptions = computed(() =>
    props.managers.filter((manager) => !['self', 'unassigned'].includes(String(manager.id))),
);

const inspectionModalOpen = ref(false);
const requestModalOpen = ref(false);
const assignDialogOpen = ref(false);
const bulkEditDialogOpen = ref(false);
const selectedFacilityIds = useRemember<number[]>([], 'facilities.selection');
const modalFacilityIds = ref<number[]>([]);

const assignManagerForm = useForm({
    facility_ids: [] as number[],
    manager_id: 'unassigned' as string,
});
const bulkEditForm = useForm({
    facility_ids: [] as number[],
    condition: '__no_change__' as string,
    facility_type_id: '__no_change__' as string,
    parent_id: '__no_change__' as string,
    managed_by: '__no_change__' as string,
});
let searchDebounceTimer: number | null = null;

const normalizeManagerId = (value: string) => {
    if (!value || value === 'all') return undefined;
    return value;
};

function handlePerPageChange(pageSize: number) {
    selectedPerPage.value = String(pageSize);
    applyFilters({ replace: true, only: ['facilities'] });
}

const applyFilters = (options: Record<string, unknown> = {}) => {
    const parsedPerPage = Number(selectedPerPage.value);
    router.get(facilitiesIndex().url, {
        manager_id: normalizeManagerId(appliedManager.value),
        search: search.value || undefined,
        condition: appliedCondition.value === 'all' ? undefined : appliedCondition.value,
        facility_type_id: appliedFacilityType.value === 'all' ? undefined : appliedFacilityType.value,
        parent_id: appliedParent.value === 'all' ? undefined : appliedParent.value,
        children_scope: appliedChildrenScope.value === 'all' ? undefined : appliedChildrenScope.value,
        per_page: Number.isFinite(parsedPerPage) && parsedPerPage > 0 ? parsedPerPage : undefined,
        view: viewMode.value
    }, {
        preserveState: true,
        preserveScroll: true,
        ...options
    });
};

const applySelectedFilters = () => {
    appliedManager.value = selectedManager.value;
    appliedCondition.value = selectedCondition.value;
    appliedFacilityType.value = selectedFacilityType.value;
    appliedParent.value = selectedParent.value;
    appliedChildrenScope.value = selectedChildrenScope.value;
    applyFilters({ replace: true, only: ['facilities'] });
};

const resetFilters = () => {
    search.value = '';
    appliedManager.value = 'all';
    appliedCondition.value = 'all';
    appliedFacilityType.value = 'all';
    appliedParent.value = 'all';
    appliedChildrenScope.value = 'all';
    selectedManager.value = 'all';
    selectedCondition.value = 'all';
    selectedFacilityType.value = 'all';
    selectedParent.value = 'all';
    selectedChildrenScope.value = 'all';
    applyFilters({ replace: true, only: ['facilities'] });
};

watch(() => props.activeManagerId, (value) => {
    const manager = value ? String(value) : 'all';
    appliedManager.value = manager;
    selectedManager.value = manager;
});

watch(() => facilitiesPerPageFromProps.value, (value) => {
    if (value > 0) {
        selectedPerPage.value = String(value);
    }
});

watch(viewMode, () => {
    applyFilters({ replace: true, only: ['facilities'] });
});

watch(search, () => {
    if (searchDebounceTimer !== null) {
        window.clearTimeout(searchDebounceTimer);
    }

    searchDebounceTimer = window.setTimeout(() => {
        applyFilters({ replace: true, only: ['facilities'] });
    }, 350);
});

onBeforeUnmount(() => {
    if (searchDebounceTimer !== null) {
        window.clearTimeout(searchDebounceTimer);
    }
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
    manageableFacilityIds.value.has(facilityId) && (canInspect.value || canRequest.value || canAssignManager.value || canBulkEdit.value);

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

const openAssignDialog = () => {
    if (!selectionCount.value) {
        return;
    }

    assignManagerForm.reset();
    assignManagerForm.clearErrors();
    assignManagerForm.facility_ids = [...selectedFacilityIds.value];
    assignManagerForm.manager_id = 'unassigned';
    assignDialogOpen.value = true;
};

const openBulkEditDialog = () => {
    if (!selectionCount.value || !canBulkEdit.value) {
        return;
    }

    bulkEditForm.reset();
    bulkEditForm.clearErrors();
    bulkEditForm.facility_ids = [...selectedFacilityIds.value];
    bulkEditForm.condition = '__no_change__';
    bulkEditForm.facility_type_id = '__no_change__';
    bulkEditForm.parent_id = '__no_change__';
    bulkEditForm.managed_by = '__no_change__';
    bulkEditDialogOpen.value = true;
};

const deleteFacility = (facilityId: number) => {
    if (!canDeleteFacility.value) {
        return;
    }

    if (!window.confirm('Delete this facility? This action cannot be undone.')) {
        return;
    }

    router.delete(facilitiesDestroy(facilityId).url, {
        preserveScroll: true,
    });
};

const submitAssignManager = () => {
    assignManagerForm.facility_ids = [...selectedFacilityIds.value];
    assignManagerForm.manager_id = assignManagerForm.manager_id || 'unassigned';
    assignManagerForm.post(props.routes.bulkAssignManager, {
        transform: (data) => ({
            ...data,
            manager_id: data.manager_id === 'unassigned' ? null : Number(data.manager_id),
        }),
        preserveScroll: true,
        onSuccess: () => {
            assignDialogOpen.value = false;
            selectedFacilityIds.value = [];
        },
    });
};

const submitBulkEdit = () => {
    bulkEditForm.facility_ids = [...selectedFacilityIds.value];
    bulkEditForm.post(props.routes.bulkUpdate, {
        transform: (data) => ({
            facility_ids: data.facility_ids,
            condition: data.condition === '__no_change__' ? undefined : data.condition,
            facility_type_id: data.facility_type_id === '__no_change__' ? undefined : Number(data.facility_type_id),
            parent_id: data.parent_id === '__no_change__'
                ? undefined
                : data.parent_id === '__none__'
                    ? null
                    : Number(data.parent_id),
            managed_by: data.managed_by === '__no_change__'
                ? undefined
                : data.managed_by === 'unassigned'
                    ? null
                    : Number(data.managed_by),
        }),
        preserveScroll: true,
        onSuccess: () => {
            bulkEditDialogOpen.value = false;
            selectedFacilityIds.value = [];
        },
    });
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
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-[11px] font-bold text-muted-foreground/80 uppercase tracking-widest' },
                row.original.parent?.name
                    ? row.original.parent.name
                    : `Root (${row.original.children_count ?? 0} child${(row.original.children_count ?? 0) === 1 ? '' : 'ren'})`,
            ),
        enableSorting: true,
    },
    {
        accessorKey: 'manager.name',
        header: 'Manager',
        cell: ({ row }) =>
            h(
                'span',
                { class: 'text-[11px] font-bold text-muted-foreground/80 uppercase tracking-widest' },
                row.original.manager?.name || 'Unassigned',
            ),
        enableSorting: true,
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) => h('div', { class: 'flex justify-end' }, [
            h('div', { class: 'flex items-center gap-1' }, [
                canViewFacility.value
                    ? h(Button, { variant: 'ghost', size: 'icon', class: 'h-8 w-8', onClick: () => router.get(facilitiesShow(row.original.id).url), 'aria-label': 'View facility' }, () =>
                        h(Eye, { class: 'h-4 w-4' }),
                    )
                    : null,
                canInspect.value && canSelectFacility(row.original.id)
                    ? h(Button, { variant: 'ghost', size: 'icon', class: 'h-8 w-8', onClick: () => openInspectionModal([row.original.id]), 'aria-label': 'Inspect facility' }, () =>
                        h(ClipboardCheck, { class: 'h-4 w-4' }),
                    )
                    : null,
                canRequest.value && canSelectFacility(row.original.id)
                    ? h(Button, { variant: 'ghost', size: 'icon', class: 'h-8 w-8', onClick: () => openRequestModal([row.original.id]), 'aria-label': 'Create request' }, () =>
                        h(Wrench, { class: 'h-4 w-4' }),
                    )
                    : null,
                canEditFacility.value
                    ? h(Button, { variant: 'ghost', size: 'icon', class: 'h-8 w-8', onClick: () => router.get(facilitiesEdit(row.original.id).url), 'aria-label': 'Edit facility' }, () =>
                        h(Pencil, { class: 'h-4 w-4' }),
                    )
                    : null,
                canDeleteFacility.value
                    ? h(Button, { variant: 'ghost', size: 'icon', class: 'h-8 w-8 text-destructive hover:text-destructive', onClick: () => deleteFacility(row.original.id), 'aria-label': 'Delete facility' }, () =>
                        h(Trash2, { class: 'h-4 w-4' }),
                    )
                    : null,
            ]),
        ])
    }
];
</script>

<template>

    <Head title="Facilities Portfolio" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-8 p-6 lg:p-10">
            <PageHeader title="Facilities" subtitle="Oversee your portfolio of properties.">
                <template #actions>
                    <Button
                        v-if="canAssignManager"
                        size="sm"
                        variant="outline"
                        class="h-9 px-3 text-[10px] font-bold uppercase tracking-widest"
                        as-child
                    >
                        <Link :href="routes.managerAssignments">
                            Manage Assignments
                        </Link>
                    </Button>
                    <Button
                        v-if="can('facilities.create')"
                        size="icon"
                        class="h-9 w-9"
                        as-child
                    >
                        <Link :href="facilitiesCreate().url" aria-label="New facility">
                            <Plus class="h-4 w-4" />
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="rounded-xl border border-border/60 bg-card/60 p-3 backdrop-blur">
                <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_170px_170px_220px_170px_auto] lg:items-end">
                    <div class="relative min-w-[220px] flex-1">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="search" class="h-9 pl-9" placeholder="Search facilities..." />
                    </div>

                    <Select v-model="selectedManager">
                        <SelectTrigger class="h-9 min-w-[160px]">
                            <SelectValue placeholder="All managers" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All managers</SelectItem>
                            <SelectItem v-for="manager in managers" :key="manager.id" :value="String(manager.id)">
                                {{ manager.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="selectedFacilityType">
                        <SelectTrigger class="h-9 min-w-[160px]">
                            <SelectValue placeholder="All types" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All types</SelectItem>
                            <SelectItem v-for="type in formOptions.facilityTypes" :key="type.id" :value="String(type.id)">
                                {{ type.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="selectedCondition">
                        <SelectTrigger class="h-9 min-w-[160px]">
                            <SelectValue placeholder="All conditions" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All conditions</SelectItem>
                            <SelectItem v-for="condition in formOptions.conditions" :key="condition" :value="condition">
                                {{ condition }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="selectedParent">
                        <SelectTrigger class="h-9 min-w-[200px]">
                            <SelectValue placeholder="Any parent node" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Any parent node</SelectItem>
                            <SelectItem value="root">Root nodes only</SelectItem>
                            <SelectItem v-for="parent in formOptions.parents" :key="parent.id" :value="String(parent.id)">
                                {{ parent.name }} ({{ parent.children_count }} child{{ parent.children_count === 1 ? '' : 'ren' }})
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="selectedChildrenScope">
                        <SelectTrigger class="h-9 min-w-[160px]">
                            <SelectValue placeholder="Any child count" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Any child count</SelectItem>
                            <SelectItem value="with">With children</SelectItem>
                            <SelectItem value="without">Without children</SelectItem>
                        </SelectContent>
                    </Select>

                    <div class="flex items-center gap-2">
                        <Button size="sm" class="h-9 px-4" @click="applySelectedFilters">Apply filters</Button>
                        <Button size="sm" variant="ghost" class="h-9 px-3" @click="resetFilters">Reset</Button>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <div class="text-sm text-muted-foreground">
                    {{ facilities.total }} facilities
                </div>
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex-1" />

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
                    :show-selection-summary="false" :show-pagination="true" :enable-row-selection="false"
                    :server-pagination="facilitiesPagination" class="portfolio-table">
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
                            <Button v-if="canAssignManager" variant="outline" size="sm" :disabled="!selectionCount"
                                class="rounded-lg h-8 px-4 text-[10px] font-bold uppercase tracking-widest border-border hover:bg-primary/5 hover:text-primary transition-colors"
                                @click="openAssignDialog">
                                Assign Manager
                            </Button>
                            <Button v-if="canBulkEdit" variant="outline" size="sm" :disabled="!selectionCount"
                                class="rounded-lg h-8 px-4 text-[10px] font-bold uppercase tracking-widest border-border hover:bg-primary/5 hover:text-primary transition-colors"
                                @click="openBulkEditDialog">
                                Bulk Edit
                            </Button>
                            <Button variant="ghost" size="sm" :disabled="!selectionCount"
                                class="rounded-lg h-8 px-4 text-[10px] font-bold uppercase tracking-widest"
                                @click="selectedFacilityIds = []">
                                Clear Selection
                            </Button>
                        </div>
                    </template>
                </DataTable>

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
                        <Button v-if="canAssignManager" variant="outline" size="sm" :disabled="!selectionCount"
                            class="rounded-lg h-8 px-4 text-[10px] font-bold uppercase tracking-widest border-border hover:bg-primary/5 hover:text-primary transition-colors"
                            @click="openAssignDialog">
                            Assign Manager
                        </Button>
                        <Button v-if="canBulkEdit" variant="outline" size="sm" :disabled="!selectionCount"
                            class="rounded-lg h-8 px-4 text-[10px] font-bold uppercase tracking-widest border-border hover:bg-primary/5 hover:text-primary transition-colors"
                            @click="openBulkEditDialog">
                            Bulk Edit
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
                                <Button
                                    v-if="canDeleteFacility"
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8 text-destructive hover:text-destructive"
                                    @click="deleteFacility(facility.id)"
                                >
                                    <Trash2 class="h-4 w-4" />
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
                                    <span class="text-foreground/80">
                                        {{
                                            facility.parent?.name
                                                ? facility.parent.name
                                                : `Root (${facility.children_count ?? 0} child${(facility.children_count ?? 0) === 1 ? '' : 'ren'})`
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-auto flex items-center justify-between gap-2">
                                <div class="flex w-full items-center justify-end gap-1">
                                    <Button size="icon" variant="ghost" class="h-9 w-9" :disabled="!canViewFacility"
                                        aria-label="View facility"
                                        @click="canViewFacility && router.get(facilitiesShow(facility.id).url)">
                                        <Eye class="h-4 w-4" />
                                    </Button>
                                    <Button size="icon" variant="ghost" class="h-9 w-9"
                                        :disabled="!canInspect || !manageableFacilityIds.has(facility.id)"
                                        aria-label="Inspect facility"
                                        @click="canInspect && manageableFacilityIds.has(facility.id) && openInspectionModal([facility.id])">
                                        <ClipboardCheck class="h-4 w-4" />
                                    </Button>
                                    <Button size="icon" variant="ghost" class="h-9 w-9"
                                        :disabled="!canRequest || !manageableFacilityIds.has(facility.id)"
                                        aria-label="Create request"
                                        @click="canRequest && manageableFacilityIds.has(facility.id) && openRequestModal([facility.id])">
                                        <Wrench class="h-4 w-4" />
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

                <PaginationLinks :links="facilities.links" :only="['facilities']" />
            </div>
        </div>


        <Dialog v-model:open="assignDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Bulk Assign Manager</DialogTitle>
                    <DialogDescription>
                        Assign {{ selectionCount }} selected facilities to a facility manager.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-3">
                    <Select v-model="assignManagerForm.manager_id">
                        <SelectTrigger class="h-10 w-full">
                            <SelectValue placeholder="Select manager or unassign" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="unassigned">Unassigned</SelectItem>
                            <SelectItem v-for="manager in managerOptions" :key="manager.id" :value="String(manager.id)">
                                {{ manager.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="assignManagerForm.errors.manager_id" class="text-xs text-red-600">{{ assignManagerForm.errors.manager_id }}</p>
                    <p v-if="assignManagerForm.errors.facility_ids" class="text-xs text-red-600">{{ assignManagerForm.errors.facility_ids }}</p>
                </div>
                <DialogFooter>
                    <Button variant="ghost" @click="assignDialogOpen = false">Cancel</Button>
                    <Button :disabled="assignManagerForm.processing || !selectionCount" @click="submitAssignManager">
                        Save assignment
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="bulkEditDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Bulk Edit Facilities</DialogTitle>
                    <DialogDescription>
                        Update fields for {{ selectionCount }} selected facilities.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-3">
                    <Select v-model="bulkEditForm.condition">
                        <SelectTrigger class="h-10 w-full">
                            <SelectValue placeholder="Condition" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__no_change__">Condition: No change</SelectItem>
                            <SelectItem v-for="condition in formOptions.conditions" :key="condition" :value="condition">
                                Condition: {{ condition }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <template v-if="canAssignManager">
                        <Select v-model="bulkEditForm.facility_type_id">
                            <SelectTrigger class="h-10 w-full">
                                <SelectValue placeholder="Facility type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__no_change__">Facility Type: No change</SelectItem>
                                <SelectItem v-for="type in formOptions.facilityTypes" :key="type.id" :value="String(type.id)">
                                    Facility Type: {{ type.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Select v-model="bulkEditForm.parent_id">
                            <SelectTrigger class="h-10 w-full">
                                <SelectValue placeholder="Parent node" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__no_change__">Parent: No change</SelectItem>
                                <SelectItem value="__none__">Parent: Root (no parent)</SelectItem>
                                <SelectItem v-for="parent in formOptions.parents" :key="parent.id" :value="String(parent.id)">
                                    Parent: {{ parent.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Select v-model="bulkEditForm.managed_by">
                            <SelectTrigger class="h-10 w-full">
                                <SelectValue placeholder="Manager assignment" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__no_change__">Manager: No change</SelectItem>
                                <SelectItem value="unassigned">Manager: Unassigned</SelectItem>
                                <SelectItem v-for="manager in managerOptions" :key="manager.id" :value="String(manager.id)">
                                    Manager: {{ manager.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </template>

                    <p v-if="bulkEditForm.errors.bulk_edit" class="text-xs text-red-600">{{ bulkEditForm.errors.bulk_edit }}</p>
                    <p v-if="bulkEditForm.errors.condition" class="text-xs text-red-600">{{ bulkEditForm.errors.condition }}</p>
                    <p v-if="bulkEditForm.errors.parent_id" class="text-xs text-red-600">{{ bulkEditForm.errors.parent_id }}</p>
                    <p v-if="bulkEditForm.errors.facility_ids" class="text-xs text-red-600">{{ bulkEditForm.errors.facility_ids }}</p>
                </div>
                <DialogFooter>
                    <Button variant="ghost" @click="bulkEditDialogOpen = false">Cancel</Button>
                    <Button :disabled="bulkEditForm.processing || !selectionCount" @click="submitBulkEdit">
                        Save changes
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

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
