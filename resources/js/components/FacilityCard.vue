<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ButtonGroup } from '@/components/ui/button-group';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { create as inspectionsCreate } from '@/routes/inspections';
import { create as maintenanceCreate } from '@/routes/maintenance';
import { show as facilitiesShow } from '@/routes/facilities';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ClipboardCheck, Eye, Wrench } from 'lucide-vue-next';

interface FacilityType {
    name: string;
}

interface Inspection {
    inspection_date: string | null;
}

interface Facility {
    id: number;
    name: string;
    condition?: string | null;
    facilityType?: FacilityType | null;
    inspections?: Inspection[];
    open_maintenance_requests_count?: number;
}

const props = defineProps<{
    facility: Facility;
}>();

const conditionKey = (condition?: string | null) =>
    (condition ?? '').toLowerCase();

const conditionConfig = computed(() => {
    const normalized = conditionKey(props.facility.condition);

    if (normalized === 'good') {
        return 'bg-emerald-500/10 text-emerald-700';
    }

    if (['bad', 'warning'].includes(normalized)) {
        return 'bg-amber-500/10 text-amber-700';
    }

    if (['outoforder', 'critical'].includes(normalized)) {
        return 'bg-red-500/10 text-red-600';
    }

    return 'bg-muted text-muted-foreground';
});

const lastInspectionDate = computed(() => {
    return props.facility.inspections?.[0]?.inspection_date ?? 'Not yet';
});
</script>

<template>
    <Card class="flex h-full flex-col">
        <CardHeader class="space-y-2">
            <div class="flex items-start justify-between gap-2">
                <CardTitle class="text-base">
                    {{ facility.name }}
                </CardTitle>
                <span
                    class="rounded-full px-2 py-1 text-[11px] font-semibold uppercase"
                    :class="conditionConfig"
                >
                    {{ facility.condition ?? 'unknown' }}
                </span>
            </div>
            <p class="text-xs text-muted-foreground">
                {{ facility.facilityType?.name ?? 'Unassigned type' }}
            </p>
        </CardHeader>

        <CardContent class="space-y-3 text-xs text-muted-foreground">
            <div class="flex items-center justify-between">
                <span>Last inspection</span>
                <span class="text-foreground">{{ lastInspectionDate }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span>Open requests</span>
                <span class="text-foreground">
                    {{ facility.open_maintenance_requests_count ?? 0 }}
                </span>
            </div>
        </CardContent>

        <CardFooter class="mt-auto">
            <ButtonGroup class="w-full border-border/60 bg-background/80 dark:bg-muted/30">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            size="icon"
                            variant="ghost"
                            class="h-9 flex-1 rounded-none text-foreground hover:bg-accent/80 dark:text-foreground"
                            as-child
                        >
                            <Link :href="facilitiesShow(facility.id).url" aria-label="View facility">
                                <Eye class="h-4 w-4" />
                            </Link>
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent side="top">View facility</TooltipContent>
                </Tooltip>
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            size="icon"
                            variant="ghost"
                            class="h-9 flex-1 rounded-none text-foreground hover:bg-accent/80 dark:text-foreground"
                            as-child
                        >
                            <Link
                                :href="inspectionsCreate({ query: { facility_id: facility.id } }).url"
                                aria-label="New inspection"
                            >
                                <ClipboardCheck class="h-4 w-4" />
                            </Link>
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent side="top">Add inspection</TooltipContent>
                </Tooltip>
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            size="icon"
                            variant="ghost"
                            class="h-9 flex-1 rounded-none text-foreground hover:bg-accent/80 dark:text-foreground"
                            as-child
                        >
                            <Link
                                :href="maintenanceCreate({ query: { facility_id: facility.id } }).url"
                                aria-label="New request"
                            >
                                <Wrench class="h-4 w-4" />
                            </Link>
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent side="top">Add request</TooltipContent>
                </Tooltip>
            </ButtonGroup>
        </CardFooter>
    </Card>
</template>
