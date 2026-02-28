<script setup lang="ts">
import { Button } from '@/components/ui/button';
import StatsCard from '@/components/StatsCard.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index as maintenanceIndex } from '@/routes/maintenance';
import { dashboard as maintenanceDashboard } from '@/routes/maintenance';
import { index as paymentsIndex } from '@/routes/payments';
import { index as workOrdersIndex, create as workOrdersCreate } from '@/routes/work-orders';
import { index as vendorsIndex } from '@/routes/vendors';
import { Link } from '@inertiajs/vue3';
import { ClipboardCheck, CreditCard, Hammer, Wrench } from 'lucide-vue-next';

interface Props {
    data: {
        openRequests: number;
        pendingRequests?: number;
        requestsThisWeek?: number;
        workOrdersInFlight: number;
        workOrdersThisWeek?: number;
        pendingPayments: number;
    };
}

const props = defineProps<Props>();
</script>

<template>
    <div class="space-y-6">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <StatsCard
                title="Requests This Week"
                :value="props.data.requestsThisWeek ?? 0"
                :icon="Wrench"
                accent-color="amber"
                :description="`Pending: ${props.data.pendingRequests ?? 0}`"
            />
            <StatsCard
                title="Open Requests"
                :value="props.data.openRequests"
                :icon="ClipboardCheck"
                accent-color="blue"
                description="Pending + in progress"
            />
            <StatsCard
                title="Work Orders"
                :value="props.data.workOrdersInFlight"
                :icon="Hammer"
                accent-color="emerald"
                :description="`This week: ${props.data.workOrdersThisWeek ?? 0}`"
            />
            <StatsCard
                title="Pending Payments"
                :value="props.data.pendingPayments"
                :icon="CreditCard"
                accent-color="rose"
                description="Awaiting approval"
            />
        </div>

        <Card class="border-border/60 bg-card/70">
            <CardHeader class="pb-3">
                <CardTitle class="font-display text-lg">Quick Actions</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex flex-wrap gap-2">
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="maintenanceIndex().url">Maintenance Requests</Link>
                    </Button>
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="workOrdersIndex().url">Work Orders</Link>
                    </Button>
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="paymentsIndex().url">Payments</Link>
                    </Button>
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="vendorsIndex().url">Vendors</Link>
                    </Button>
                    <Button size="sm" as-child>
                        <Link :href="workOrdersCreate().url">New Work Order</Link>
                    </Button>
                    <Button size="sm" as-child>
                        <Link :href="maintenanceDashboard().url">Maintenance Dashboard</Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
