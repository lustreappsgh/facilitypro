<script setup lang="ts">
import { Button } from '@/components/ui/button';
import StatsCard from '@/components/StatsCard.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { useInitials } from '@/composables/useInitials';
import { index as maintenanceIndex } from '@/routes/maintenance';
import { dashboard as maintenanceDashboard } from '@/routes/maintenance';
import { index as inspectionsIndex } from '@/routes/inspections';
import { index as todosIndex } from '@/routes/todos';
import { index as paymentsIndex } from '@/routes/payments';
import { index as workOrdersIndex, create as workOrdersCreate } from '@/routes/work-orders';
import { index as vendorsIndex } from '@/routes/vendors';
import { Link } from '@inertiajs/vue3';
import { AlertTriangle, ClipboardCheck, CreditCard, Hammer, Wrench } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    data: {
        openRequests: number;
        pendingRequests?: number;
        requestsThisWeek?: number;
        workOrdersInFlight: number;
        workOrdersThisWeek?: number;
        pendingPayments: number;
        ownRequestSummary?: {
            total: number;
            pending: number;
            rejected: number;
        };
        users?: {
            id: number;
            name: string;
            email: string;
            profile_photo_url: string;
            is_active: boolean;
            facilities_managed: number;
            inspections_last_week: number;
            upcoming_todos: number;
            requests_submitted: number;
            roles: string[];
        }[];
    };
}

const props = defineProps<Props>();
const { getInitials } = useInitials();
const users = computed(() => props.data.users ?? []);
const ownRequestSummary = computed(
    () =>
        props.data.ownRequestSummary ?? {
            total: 0,
            pending: 0,
            rejected: 0,
        },
);
</script>

<template>
    <div class="space-y-6">
        <Card v-if="users.length" class="border-border/60 bg-card/70">
            <CardHeader class="pb-3">
                <CardTitle class="font-display text-lg">Users Overview</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <div
                        v-for="user in users"
                        :key="user.id"
                        class="rounded-lg border border-border/60 bg-card p-4"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <Avatar class="h-10 w-10">
                                    <AvatarImage :src="user.profile_photo_url" :alt="user.name" />
                                    <AvatarFallback>{{ getInitials(user.name) }}</AvatarFallback>
                                </Avatar>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold">{{ user.name }}</p>
                                    <p class="truncate text-xs text-muted-foreground">{{ user.email }}</p>
                                </div>
                            </div>
                            <Badge
                                variant="outline"
                                :class="user.is_active ? 'text-success bg-success-subtle border-success/20' : ''"
                            >
                                {{ user.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-2">
                            <Link
                                :href="inspectionsIndex({ query: { user_id: user.id } }).url"
                                class="rounded-md border border-border/50 bg-muted/30 p-2 transition-colors hover:border-primary/40 hover:bg-primary/5"
                            >
                                <p class="text-[10px] uppercase tracking-wide text-muted-foreground">Inspections</p>
                                <p class="font-display text-lg font-semibold">{{ user.inspections_last_week }}</p>
                            </Link>
                            <Link
                                :href="todosIndex({ query: { user_id: user.id } }).url"
                                class="rounded-md border border-border/50 bg-muted/30 p-2 transition-colors hover:border-primary/40 hover:bg-primary/5"
                            >
                                <p class="text-[10px] uppercase tracking-wide text-muted-foreground">Upcoming Todos</p>
                                <p class="font-display text-lg font-semibold">{{ user.upcoming_todos }}</p>
                            </Link>
                            <Link
                                :href="maintenanceIndex({ query: { user_id: user.id } }).url"
                                class="rounded-md border border-border/50 bg-muted/30 p-2 transition-colors hover:border-primary/40 hover:bg-primary/5"
                            >
                                <p class="text-[10px] uppercase tracking-wide text-muted-foreground">Requests</p>
                                <p class="font-display text-lg font-semibold">{{ user.requests_submitted }}</p>
                            </Link>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <StatsCard
                v-if="props.data.ownRequestSummary"
                title="My Requests"
                :value="ownRequestSummary.total"
                :icon="ClipboardCheck"
                accent-color="amber"
                :description="`Pending: ${ownRequestSummary.pending}`"
            />
            <StatsCard
                v-if="props.data.ownRequestSummary"
                title="My Rejected"
                :value="ownRequestSummary.rejected"
                :icon="AlertTriangle"
                accent-color="rose"
                description="Needs follow-up"
            />
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
