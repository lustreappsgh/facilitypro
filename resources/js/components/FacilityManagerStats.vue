<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { my as facilitiesMy } from '@/routes/facilities';
import { create as inspectionsCreate } from '@/routes/inspections';
import { create as maintenanceCreate } from '@/routes/maintenance';
import { weekly as todosWeekly } from '@/routes/todos';
import { Link } from '@inertiajs/vue3';

interface Props {
    inspectionsSubmitted: number;
    openMaintenanceRequests: number;
    facilitiesManaged: number;
}

defineProps<Props>();

const numberFormat = new Intl.NumberFormat();
</script>

<template>
    <div class="space-y-4">
        <div class="grid gap-4 md:grid-cols-3">
            <Card>
                <CardHeader>
                    <CardTitle>Inspections submitted</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-semibold">
                        {{ numberFormat.format(inspectionsSubmitted) }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle>Open maintenance requests</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-semibold">
                        {{ numberFormat.format(openMaintenanceRequests) }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle>Facilities managed</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-semibold">
                        {{ numberFormat.format(facilitiesManaged) }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="flex flex-wrap gap-3">
            <Button as-child>
                <Link :href="inspectionsCreate().url">New inspection</Link>
            </Button>
            <Button variant="secondary" as-child>
                <Link :href="maintenanceCreate().url">New request</Link>
            </Button>
            <Button variant="outline" as-child>
                <Link :href="todosWeekly().url">Plan weekly todos</Link>
            </Button>
            <Button variant="ghost" as-child>
                <Link :href="facilitiesMy().url">View my facilities</Link>
            </Button>
        </div>
    </div>
</template>
