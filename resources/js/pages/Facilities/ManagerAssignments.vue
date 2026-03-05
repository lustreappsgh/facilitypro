<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Head, Link, router, useForm, useRemember } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { usePermissions } from '@/composables/usePermissions';
import { create as facilitiesCreate, edit as facilitiesEdit, show as facilitiesShow } from '@/routes/facilities';
import { Building2, Eye, Pencil, Plus, Search, Trash2, UserRoundCog } from 'lucide-vue-next';

interface ManagerOption {
    id: number;
    name: string;
    facilities_count: number;
}

interface FacilityItem {
    id: number;
    name: string;
    condition: string;
    children_count: number;
    manager?: { id: number; name: string } | null;
    manager_name?: string | null;
    facilityType?: { name: string } | null;
    facility_type?: { name: string } | null;
    parent?: { id: number; name: string } | null;
}

interface QueuedFacility {
    id: number;
    name: string;
    condition: string;
    children_count: number;
    manager_name: string;
    type_name: string;
}

interface Props {
    facilities: {
        data: FacilityItem[];
        links: any[];
        total: number;
    };
    managers: ManagerOption[];
    filters: {
        search?: string;
        currentManagerId?: string;
    };
    routes: {
        index: string;
        facilitiesIndex: string;
        bulkAssignManager: string;
    };
}

const props = defineProps<Props>();
const { can } = usePermissions();

const breadcrumbs = [
    { title: 'Facilities', href: props.routes.facilitiesIndex },
    { title: 'Manager Assignments', href: props.routes.index },
];

const search = ref(props.filters.search ?? '');
const currentManagerId = ref(props.filters.currentManagerId ?? 'all');
const selectedManagerId = ref<string>('none');
const queuedFacilities = useRemember<QueuedFacility[]>([], 'facilities.manager-assignments.queue');
const localError = ref<string | null>(null);
let searchDebounceTimer: number | null = null;

const assignmentForm = useForm({
    facility_ids: [] as number[],
    manager_id: null as number | null,
});

const queuedFacilityIds = computed(() => new Set(queuedFacilities.value.map((facility) => facility.id)));
const canAssign = computed(() => queuedFacilities.value.length > 0 && selectedManagerId.value !== 'none');
const canUnassign = computed(() => queuedFacilities.value.length > 0);
const canViewFacility = computed(() => can('facilities.view'));
const canEditFacility = computed(() => can('facilities.update'));

const facilityTypeName = (facility: FacilityItem | QueuedFacility) => {
    if ('type_name' in facility) {
        return facility.type_name || 'Unassigned Type';
    }

    return facility.facilityType?.name
        || facility.facility_type?.name
        || 'Unassigned Type';
};

const facilityManagerName = (facility: FacilityItem) =>
    facility.manager?.name || facility.manager_name || 'Unassigned';

const refreshFacilities = (options: Record<string, unknown> = {}) => {
    router.get(props.routes.index, {
        search: search.value || undefined,
        current_manager_id: currentManagerId.value === 'all' ? undefined : currentManagerId.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['facilities', 'filters'],
        replace: true,
        ...options,
    });
};

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

const addFacility = (facility: FacilityItem) => {
    if (queuedFacilityIds.value.has(facility.id)) {
        return;
    }

    queuedFacilities.value = [
        ...queuedFacilities.value,
        {
            id: facility.id,
            name: facility.name,
            condition: facility.condition,
            children_count: facility.children_count ?? 0,
            manager_name: facility.manager?.name ?? 'Unassigned',
            type_name: facilityTypeName(facility),
        },
    ];
};

const removeQueuedFacility = (facilityId: number) => {
    queuedFacilities.value = queuedFacilities.value.filter((facility) => facility.id !== facilityId);
};

const clearQueuedFacilities = () => {
    queuedFacilities.value = [];
};

const submitAssignment = (managerId: number | null) => {
    localError.value = null;
    assignmentForm.clearErrors();

    if (managerId !== null && selectedManagerId.value === 'none') {
        localError.value = 'Select a manager before assigning facilities.';
        return;
    }

    assignmentForm.facility_ids = queuedFacilities.value.map((facility) => facility.id);
    assignmentForm.manager_id = managerId;

    assignmentForm.post(props.routes.bulkAssignManager, {
        preserveScroll: true,
        onSuccess: () => {
            clearQueuedFacilities();
            refreshFacilities();
        },
    });
};

watch(search, () => {
    if (searchDebounceTimer !== null) {
        window.clearTimeout(searchDebounceTimer);
    }

    searchDebounceTimer = window.setTimeout(() => {
        refreshFacilities();
    }, 350);
});

watch(currentManagerId, () => {
    refreshFacilities();
});

onBeforeUnmount(() => {
    if (searchDebounceTimer !== null) {
        window.clearTimeout(searchDebounceTimer);
    }
});
</script>

<template>
    <Head title="Facility Manager Assignments" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-6 lg:p-10">
            <PageHeader
                title="Facility Manager Assignments"
                subtitle="Search facilities, build a working list, and assign or unassign in bulk."
            >
                <template #actions>
                    <Button variant="outline" size="sm" class="h-9 px-4 text-[10px] font-bold uppercase tracking-widest" as-child>
                        <Link :href="facilitiesCreate().url">
                            <Plus class="mr-1.5 h-3.5 w-3.5" />
                            Add Facility
                        </Link>
                    </Button>
                    <Button variant="outline" size="sm" class="h-9 px-4 text-[10px] font-bold uppercase tracking-widest" as-child>
                        <Link :href="routes.facilitiesIndex">Back to Facilities</Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-4 lg:grid-cols-[minmax(0,1.7fr)_minmax(0,1fr)]">
                <Card class="border-border/70 bg-card/70">
                    <CardHeader class="space-y-3">
                        <CardTitle class="text-sm font-black uppercase tracking-wider">Facility Search</CardTitle>
                        <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_220px]">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input v-model="search" class="h-9 pl-9" placeholder="Search by facility, manager, or type..." />
                            </div>

                            <Select v-model="currentManagerId">
                                <SelectTrigger class="h-9">
                                    <SelectValue placeholder="Current manager" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All current managers</SelectItem>
                                    <SelectItem value="unassigned">Currently unassigned</SelectItem>
                                    <SelectItem v-for="manager in managers" :key="manager.id" :value="String(manager.id)">
                                        {{ manager.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </CardHeader>

                    <CardContent class="space-y-3">
                        <div class="text-sm text-muted-foreground">{{ facilities.total }} facilities found</div>

                        <div v-if="facilities.data.length" class="space-y-2">
                            <div
                                v-for="facility in facilities.data"
                                :key="facility.id"
                                class="flex items-center justify-between gap-3 rounded-xl border border-border/60 bg-background/70 p-3"
                            >
                                <div class="min-w-0 space-y-1">
                                    <p class="truncate text-sm font-black uppercase tracking-tight text-foreground">{{ facility.name }}</p>
                                    <div class="flex flex-wrap items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/70">
                                        <span>{{ facilityTypeName(facility) }}</span>
                                        <span>•</span>
                                        <span>{{ facilityManagerName(facility) }}</span>
                                        <span>•</span>
                                        <span>{{ facility.children_count ?? 0 }} child{{ (facility.children_count ?? 0) === 1 ? '' : 'ren' }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge variant="outline" :class="`text-[10px] font-black uppercase tracking-wider ${conditionClass(facility.condition)}`">
                                        {{ facility.condition || 'Unknown' }}
                                    </Badge>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        class="h-8 px-3 text-[10px] font-bold uppercase tracking-widest"
                                        :disabled="queuedFacilityIds.has(facility.id)"
                                        @click="addFacility(facility)"
                                    >
                                        <Plus class="mr-1 h-3.5 w-3.5" />
                                        {{ queuedFacilityIds.has(facility.id) ? 'Added' : 'Add' }}
                                    </Button>
                                    <Button
                                        v-if="canViewFacility"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8"
                                        as-child
                                    >
                                        <Link :href="facilitiesShow(facility.id).url" aria-label="View facility">
                                            <Eye class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="canEditFacility"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8"
                                        as-child
                                    >
                                        <Link :href="facilitiesEdit(facility.id).url" aria-label="Edit facility">
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="rounded-xl border border-dashed border-border/60 p-8 text-center">
                            <Building2 class="mx-auto mb-2 h-10 w-10 text-muted-foreground/25" />
                            <p class="text-sm font-bold text-muted-foreground">No facilities match your search</p>
                        </div>

                        <PaginationLinks :links="facilities.links" :only="['facilities', 'filters']" />
                    </CardContent>
                </Card>

                <Card class="border-border/70 bg-card/70">
                    <CardHeader class="space-y-3">
                        <CardTitle class="text-sm font-black uppercase tracking-wider">Bulk Assignment Queue</CardTitle>
                        <Select v-model="selectedManagerId">
                            <SelectTrigger class="h-9">
                                <SelectValue placeholder="Select target manager" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">Select target manager</SelectItem>
                                <SelectItem v-for="manager in managers" :key="manager.id" :value="String(manager.id)">
                                    {{ manager.name }} ({{ manager.facilities_count }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="localError" class="text-xs text-destructive">{{ localError }}</p>
                    </CardHeader>

                    <CardContent class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-widest text-muted-foreground/70">
                                {{ queuedFacilities.length }} selected
                            </span>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="h-8 px-3 text-[10px] font-bold uppercase tracking-widest"
                                :disabled="queuedFacilities.length === 0"
                                @click="clearQueuedFacilities"
                            >
                                Clear list
                            </Button>
                        </div>

                        <div v-if="queuedFacilities.length" class="max-h-[420px] space-y-2 overflow-y-auto pr-1">
                            <div
                                v-for="facility in queuedFacilities"
                                :key="facility.id"
                                class="rounded-xl border border-border/60 bg-background/70 p-3"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 space-y-1">
                                        <p class="truncate text-xs font-black uppercase tracking-tight">{{ facility.name }}</p>
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/70">
                                            {{ facilityTypeName(facility) }} • {{ facility.manager_name }}
                                        </p>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-7 w-7 text-destructive hover:text-destructive"
                                        @click="removeQueuedFacility(facility.id)"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="rounded-xl border border-dashed border-border/60 p-6 text-center text-xs font-bold uppercase tracking-widest text-muted-foreground/70">
                            Add facilities from the left list
                        </div>

                        <div class="space-y-2">
                            <Button
                                class="h-9 w-full text-[10px] font-bold uppercase tracking-widest"
                                :disabled="assignmentForm.processing || !canAssign"
                                @click="submitAssignment(Number(selectedManagerId))"
                            >
                                <UserRoundCog class="mr-2 h-4 w-4" />
                                Assign to Selected Manager
                            </Button>
                            <Button
                                variant="outline"
                                class="h-9 w-full text-[10px] font-bold uppercase tracking-widest"
                                :disabled="assignmentForm.processing || !canUnassign"
                                @click="submitAssignment(null)"
                            >
                                Unassign Selected Facilities
                            </Button>
                            <p v-if="assignmentForm.errors.facility_ids" class="text-xs text-destructive">
                                {{ assignmentForm.errors.facility_ids }}
                            </p>
                            <p v-if="assignmentForm.errors.manager_id" class="text-xs text-destructive">
                                {{ assignmentForm.errors.manager_id }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
