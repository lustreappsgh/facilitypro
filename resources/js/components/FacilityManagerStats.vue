<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { createNumberFormatter } from '@/lib/locale';
import { my as facilitiesMy } from '@/routes/facilities';
import { create as inspectionsCreate } from '@/routes/inspections';
import { create as maintenanceCreate } from '@/routes/maintenance';
import { weekly as todosWeekly } from '@/routes/todos';
import { Link } from '@inertiajs/vue3';
import {
    Building2,
    ClipboardCheck,
    ClipboardList,
    Wrench,
} from 'lucide-vue-next';

interface Props {
    inspectionsSubmitted: number;
    openMaintenanceRequests: number;
    facilitiesManaged: number;
}

defineProps<Props>();

const numberFormat = createNumberFormatter();
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

        <div class="flex flex-wrap items-center gap-2">
            <Button size="icon" class="h-9 w-9" as-child>
                <Link
                    :href="inspectionsCreate().url"
                    aria-label="New inspection"
                >
                    <ClipboardCheck class="h-4 w-4" />
                </Link>
            </Button>
            <Button size="icon" variant="secondary" class="h-9 w-9" as-child>
                <Link :href="maintenanceCreate().url" aria-label="New request">
                    <Wrench class="h-4 w-4" />
                </Link>
            </Button>
            <Button size="icon" variant="outline" class="h-9 w-9" as-child>
                <Link :href="todosWeekly().url" aria-label="Plan weekly todos">
                    <ClipboardList class="h-4 w-4" />
                </Link>
            </Button>
            <Button size="icon" variant="ghost" class="h-9 w-9" as-child>
                <Link
                    :href="facilitiesMy().url"
                    aria-label="View my facilities"
                >
                    <Building2 class="h-4 w-4" />
                </Link>
            </Button>
        </div>
    </div>
</template>
