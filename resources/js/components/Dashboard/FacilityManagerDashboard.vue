<script setup lang="ts">
import { Button } from '@/components/ui/button';
import StatsCard from '@/components/StatsCard.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { my as facilitiesMy } from '@/routes/facilities';
import { create as inspectionsCreate, my as inspectionsMy } from '@/routes/inspections';
import { create as maintenanceCreate, my as maintenanceMy } from '@/routes/maintenance';
import { index as todosIndex } from '@/routes/todos';
import { Link } from '@inertiajs/vue3';
import { Building2, ClipboardCheck, ListTodo, Wrench } from 'lucide-vue-next';

interface Props {
    data: {
        inspectionsSubmitted: number;
        openMaintenanceRequests: number;
        facilitiesManaged: number;
        inspectionsThisWeek?: number;
        todosThisWeek?: number;
        pendingTodos?: number;
        pendingRequests?: number;
    };
}

const props = defineProps<Props>();
</script>

<template>
    <div class="space-y-6">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <StatsCard
                title="Facilities"
                :value="props.data.facilitiesManaged"
                :icon="Building2"
                accent-color="amber"
                description="Managed facilities"
            />
            <StatsCard
                title="Todos This Week"
                :value="props.data.todosThisWeek ?? 0"
                :icon="ListTodo"
                accent-color="blue"
                :description="`Pending: ${props.data.pendingTodos ?? 0}`"
            />
            <StatsCard
                title="Inspections This Week"
                :value="props.data.inspectionsThisWeek ?? 0"
                :icon="ClipboardCheck"
                accent-color="emerald"
                :description="`All time: ${props.data.inspectionsSubmitted ?? 0}`"
            />
            <StatsCard
                title="Requests"
                :value="props.data.pendingRequests ?? 0"
                :icon="Wrench"
                accent-color="rose"
                :description="`Open: ${props.data.openMaintenanceRequests ?? 0}`"
            />
        </div>

        <Card class="border-border/60 bg-card/70">
            <CardHeader class="pb-3">
                <CardTitle class="font-display text-lg">Quick Actions</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex flex-wrap gap-2">
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="facilitiesMy().url">Facilities</Link>
                    </Button>
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="todosIndex().url">Todos</Link>
                    </Button>
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="inspectionsMy().url">Inspections</Link>
                    </Button>
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="maintenanceMy().url">Requests</Link>
                    </Button>
                    <Button size="sm" as-child>
                        <Link :href="inspectionsCreate().url">New Inspection</Link>
                    </Button>
                    <Button size="sm" as-child>
                        <Link :href="maintenanceCreate().url">New Request</Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
