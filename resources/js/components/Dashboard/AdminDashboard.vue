<script setup lang="ts">
import { Button } from '@/components/ui/button';
import NotificationHighlights from '@/components/Dashboard/NotificationHighlights.vue';
import StatsCard from '@/components/StatsCard.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { useInitials } from '@/composables/useInitials';
import { admin as facilitiesAdmin } from '@/routes/facilities';
import { index as inspectionsIndex } from '@/routes/inspections';
import { index as todosIndex } from '@/routes/todos';
import { index as maintenanceIndex } from '@/routes/maintenance';
import { index as usersIndex } from '@/routes/users';
import { index as rolesIndex } from '@/routes/roles';
import { admin as reportsAdmin } from '@/routes/reports';
import { index as auditLogsIndex } from '@/routes/audit-logs';
import { index as vendorsIndex } from '@/routes/vendors';
import { admin as paymentsAdmin } from '@/routes/payments';
import { admin as workOrdersAdmin } from '@/routes/work-orders';
import { Link } from '@inertiajs/vue3';
import { AlertTriangle, Building2, ClipboardCheck, Settings, ShieldCheck, Users, Wrench } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    data: {
        totals: {
            facilities: number;
            facilityTypes: number;
            requestTypes: number;
            users: number;
            inspections: number;
            maintenanceRequests: number;
            vendors: number;
            workOrders: number;
            payments: number;
        };
        pending: {
            maintenanceRequests: number;
            workOrders: number;
            payments: number;
            todos: number;
        };
        health: {
            inactiveUsers: number;
            overdueWorkOrders: number;
            staleMaintenanceRequests: number;
            staleThresholdDays: number;
        };
        notifications?: {
            unread_count: number;
            items: {
                id: string;
                title: string | null;
                body: string | null;
                category: string | null;
                severity: string | null;
                action_url?: string | null;
            }[];
        };
        facilityManagers: {
            id: number;
            name: string;
            email: string;
            profile_photo_url: string;
            is_active: boolean;
            facilities_managed: number;
        }[];
        users: {
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
const actionCenterItems = computed(() => [
    {
        key: 'pending-payments',
        label: 'Pending payments',
        count: props.data.pending.payments,
        helper: 'Awaiting decision',
        href: paymentsAdmin().url,
    },
    {
        key: 'stale-requests',
        label: 'Stale requests',
        count: props.data.health.staleMaintenanceRequests,
        helper: `Older than ${props.data.health.staleThresholdDays} days`,
        href: maintenanceIndex().url,
    },
    {
        key: 'overdue-work-orders',
        label: 'Overdue work orders',
        count: props.data.health.overdueWorkOrders,
        helper: 'Scheduled date passed',
        href: workOrdersAdmin().url,
    },
    {
        key: 'inactive-users',
        label: 'Inactive users',
        count: props.data.health.inactiveUsers,
        helper: 'Review account status',
        href: usersIndex({ query: { status: 'inactive' } }).url,
    },
]);
</script>

<template>
    <div class="space-y-6">
        <Card class="border-border/60 bg-card/70">
            <CardHeader class="pb-3">
                <div class="flex items-center justify-between gap-3">
                    <CardTitle class="font-display text-lg">Users Overview</CardTitle>
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="usersIndex().url">Manage Users</Link>
                    </Button>
                </div>
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

        <Card class="border-border/60 bg-card/70">
            <CardHeader class="pb-3">
                <CardTitle class="font-display text-lg">Action Center</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <Link
                        v-for="item in actionCenterItems"
                        :key="item.key"
                        :href="item.href"
                        class="rounded-lg border border-border/60 bg-card p-3 transition-colors hover:border-primary/40 hover:bg-primary/5"
                    >
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{{ item.label }}</p>
                        <p class="mt-2 font-display text-2xl font-semibold leading-none">{{ item.count }}</p>
                        <p class="mt-2 text-xs text-muted-foreground">{{ item.helper }}</p>
                    </Link>
                </div>
            </CardContent>
        </Card>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <StatsCard
                title="Total Users"
                :value="props.data.totals.users"
                :icon="Users"
                accent-color="blue"
                description="Registered accounts"
            />
            <StatsCard
                title="Total Facilities"
                :value="props.data.totals.facilities"
                :icon="Building2"
                accent-color="amber"
                description="Managed facilities"
            />
            <StatsCard
                title="Maintenance Requests"
                :value="props.data.totals.maintenanceRequests"
                :icon="Wrench"
                accent-color="emerald"
                :description="`Pending: ${props.data.pending.maintenanceRequests}`"
            />
            <StatsCard
                title="Work Orders Pending"
                :value="props.data.pending.workOrders"
                :icon="ClipboardCheck"
                accent-color="blue"
                description="Not completed or cancelled"
            />
            <StatsCard
                title="Pending Payments"
                :value="props.data.pending.payments"
                :icon="ShieldCheck"
                accent-color="rose"
                description="Awaiting approval"
            />
            <StatsCard
                title="System Alerts"
                :value="props.data.health.overdueWorkOrders + props.data.health.staleMaintenanceRequests"
                :icon="AlertTriangle"
                accent-color="amber"
                :description="`Inactive users: ${props.data.health.inactiveUsers}`"
            />
        </div>

        <Card class="border-border/60 bg-card/70">
            <CardHeader class="pb-3">
                <CardTitle class="font-display text-lg">Quick Actions</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex flex-wrap gap-2">
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="usersIndex().url">Users</Link>
                    </Button>
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="rolesIndex().url">Roles</Link>
                    </Button>
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="facilitiesAdmin().url">Facilities Admin</Link>
                    </Button>
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="vendorsIndex().url">Vendors</Link>
                    </Button>
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="reportsAdmin().url">Admin Reports</Link>
                    </Button>
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="auditLogsIndex().url">Audit Logs</Link>
                    </Button>
                    <Button size="sm" as-child>
                        <Link :href="rolesIndex().url">
                            <Settings class="mr-1.5 h-3.5 w-3.5" />
                            System Settings
                        </Link>
                    </Button>
                </div>
            </CardContent>
        </Card>

        <NotificationHighlights
            :unread-count="props.data.notifications?.unread_count ?? 0"
            :items="props.data.notifications?.items ?? []"
        />
    </div>
</template>
