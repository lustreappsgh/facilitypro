<script setup lang="ts">
import StatsCard from '@/components/StatsCard.vue';
import NotificationHighlights from '@/components/Dashboard/NotificationHighlights.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useInitials } from '@/composables/useInitials';
import { createCurrencyFormatter } from '@/lib/currency';
import { governance as auditGovernance } from '@/routes/audit-logs';
import { index as inspectionsIndex } from '@/routes/inspections';
import {
    index as maintenanceIndex,
    oversight as maintenanceOversight,
} from '@/routes/maintenance';
import { index as reportsIndex } from '@/routes/reports';
import { index as todosIndex } from '@/routes/todos';
import { Link, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ClipboardCheck,
    CreditCard,
    ListTodo,
    ShieldCheck,
    Timer,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface FacilityManager {
    id: number;
    name: string;
    email: string;
    profile_photo_url: string;
    is_active: boolean;
    facilities_managed: number;
}

interface Props {
    data: {
        pendingApprovals: number;
        pendingApprovalCost: number;
        oldestPendingDate?: string | null;
        oldestPendingDays?: number | null;
        highCostThreshold?: number | null;
        highCostPendingCount?: number | null;
        highCostPendingTotal?: number | null;
        facilityManagers: FacilityManager[];
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
        todoSummary: {
            pending: number;
            overdue: number;
            total: number;
        };
        inspectionSummary: {
            total: number;
            last_7_days: number;
            latest_date?: string | null;
        };
        ownRequestSummary?: {
            total: number;
            pending: number;
            rejected: number;
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
    };
    permissions?: string[];
}

const props = defineProps<Props>();
const paymentApprovalsUrl = '/payment-approvals';
const { getInitials } = useInitials();
const page = usePage();
const users = computed(() => props.data.users ?? []);
const canApprovePayments = computed(() =>
    (props.permissions ?? []).includes('users.manage'),
);

const currencyFormat = createCurrencyFormatter();

const todoSummary = computed(
    () => props.data.todoSummary ?? { pending: 0, overdue: 0, total: 0 },
);
const inspectionSummary = computed(
    () =>
        props.data.inspectionSummary ?? {
            total: 0,
            last_7_days: 0,
            latest_date: null,
        },
);
const ownRequestSummary = computed(
    () =>
        props.data.ownRequestSummary ?? {
            total: 0,
            pending: 0,
            rejected: 0,
        },
);
const actionCenterItems = computed(() => {
    const items = [];

    if (canApprovePayments.value) {
        items.push(
            {
                key: 'pending-approvals',
                label: 'Pending approvals',
                value: props.data.pendingApprovals,
                helper: 'Awaiting payment decisions',
                href: paymentApprovalsUrl,
            },
            {
                key: 'queue-age',
                label: 'Oldest approval',
                value: props.data.oldestPendingDays
                    ? `${props.data.oldestPendingDays}d`
                    : '--',
                helper: props.data.oldestPendingDate ?? 'No delayed queue',
                href: paymentApprovalsUrl,
            },
            {
                key: 'high-cost',
                label: 'High-cost alerts',
                value: props.data.highCostPendingCount || 0,
                helper: `Threshold ${currencyFormat.format(props.data.highCostThreshold || 0)}`,
                href: paymentApprovalsUrl,
            },
        );
    } else {
        items.push(
            {
                key: 'pending-payments',
                label: 'Pending payments',
                value: props.data.pendingApprovals,
                helper: 'Read-only approval queue',
                href: paymentApprovalsUrl,
            },
            {
                key: 'queue-age',
                label: 'Oldest payment',
                value: props.data.oldestPendingDays
                    ? `${props.data.oldestPendingDays}d`
                    : '--',
                helper: props.data.oldestPendingDate ?? 'No delayed queue',
                href: paymentApprovalsUrl,
            },
            {
                key: 'high-cost',
                label: 'High-cost payments',
                value: props.data.highCostPendingCount || 0,
                helper: `Threshold ${currencyFormat.format(props.data.highCostThreshold || 0)}`,
                href: paymentApprovalsUrl,
            },
        );
    }

    items.push({
        key: 'overdue-todos',
        label: 'Overdue todos',
        value: todoSummary.value.overdue,
        helper: 'Needs manager follow-up',
        href: todosIndex().url,
    });

    return items;
});
</script>

<template>
    <div class="space-y-6">
        <Card v-if="users.length" class="border-border/60 bg-card/70">
            <CardHeader class="pb-3">
                <CardTitle class="font-display text-lg"
                    >Users Overview</CardTitle
                >
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
                                    <AvatarImage
                                        :src="user.profile_photo_url"
                                        :alt="user.name"
                                    />
                                    <AvatarFallback>{{
                                        getInitials(user.name)
                                    }}</AvatarFallback>
                                </Avatar>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold">
                                        {{ user.name }}
                                    </p>
                                    <p
                                        class="truncate text-xs text-muted-foreground"
                                    >
                                        {{ user.email }}
                                    </p>
                                </div>
                            </div>
                            <Badge
                                variant="outline"
                                :class="
                                    user.is_active
                                        ? 'text-success bg-success-subtle border-success/20'
                                        : ''
                                "
                            >
                                {{
                                    user.id === page.props.auth?.user?.id
                                        ? 'You'
                                        : user.is_active
                                          ? 'Active'
                                          : 'Inactive'
                                }}
                            </Badge>
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-2">
                            <Link
                                :href="
                                    inspectionsIndex({
                                        query: { user_id: user.id },
                                    }).url
                                "
                                class="rounded-md border border-border/50 bg-muted/30 p-2 transition-colors hover:border-primary/40 hover:bg-primary/5"
                            >
                                <p
                                    class="text-[10px] tracking-wide text-muted-foreground uppercase"
                                >
                                    Inspections
                                </p>
                                <p class="font-display text-lg font-semibold">
                                    {{ user.inspections_last_week }}
                                </p>
                            </Link>
                            <Link
                                :href="
                                    todosIndex({ query: { user_id: user.id } })
                                        .url
                                "
                                class="rounded-md border border-border/50 bg-muted/30 p-2 transition-colors hover:border-primary/40 hover:bg-primary/5"
                            >
                                <p
                                    class="text-[10px] tracking-wide text-muted-foreground uppercase"
                                >
                                    Upcoming Todos
                                </p>
                                <p class="font-display text-lg font-semibold">
                                    {{ user.upcoming_todos }}
                                </p>
                            </Link>
                            <Link
                                :href="
                                    maintenanceIndex({
                                        query: { user_id: user.id },
                                    }).url
                                "
                                class="rounded-md border border-border/50 bg-muted/30 p-2 transition-colors hover:border-primary/40 hover:bg-primary/5"
                            >
                                <p
                                    class="text-[10px] tracking-wide text-muted-foreground uppercase"
                                >
                                    Requests
                                </p>
                                <p class="font-display text-lg font-semibold">
                                    {{ user.requests_submitted }}
                                </p>
                            </Link>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
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
                description="Needs correction"
            />
            <StatsCard
                title="Pending Approvals"
                :value="props.data.pendingApprovals"
                :icon="CreditCard"
                accent-color="amber"
                description="Awaiting manager decision"
            />
            <StatsCard
                title="Pending Amount"
                :value="
                    currencyFormat.format(props.data.pendingApprovalCost || 0)
                "
                :icon="ShieldCheck"
                accent-color="rose"
                description="Current approval exposure"
            />
            <StatsCard
                title="Queue Age"
                :value="
                    props.data.oldestPendingDays
                        ? `${props.data.oldestPendingDays}d`
                        : '--'
                "
                :icon="Timer"
                accent-color="blue"
                :description="
                    props.data.oldestPendingDate ?? 'No delayed queue'
                "
            />
            <StatsCard
                title="Pending Todos"
                :value="todoSummary.pending"
                :icon="ListTodo"
                accent-color="blue"
                :description="`Overdue: ${todoSummary.overdue}`"
            />
            <StatsCard
                title="Inspections 7 Days"
                :value="inspectionSummary.last_7_days"
                :icon="ClipboardCheck"
                accent-color="emerald"
                :description="`Total: ${inspectionSummary.total}`"
            />
            <StatsCard
                title="High Cost Alerts"
                :value="props.data.highCostPendingCount || 0"
                :icon="AlertTriangle"
                accent-color="amber"
                :description="`Threshold: ${currencyFormat.format(props.data.highCostThreshold || 0)}`"
            />
        </div>

        <Card class="border-border/60 bg-card/70">
            <CardHeader class="pb-3">
                <CardTitle class="font-display text-lg"
                    >Action Center</CardTitle
                >
            </CardHeader>
            <CardContent>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <Link
                        v-for="item in actionCenterItems"
                        :key="item.key"
                        :href="item.href"
                        class="rounded-lg border border-border/60 bg-card p-3 transition-colors hover:border-primary/40 hover:bg-primary/5"
                    >
                        <p
                            class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            {{ item.label }}
                        </p>
                        <p
                            class="font-display mt-2 text-2xl leading-none font-semibold"
                        >
                            {{ item.value }}
                        </p>
                        <p class="mt-2 text-xs text-muted-foreground">
                            {{ item.helper }}
                        </p>
                    </Link>
                </div>
            </CardContent>
        </Card>

        <Card class="border-border/60 bg-card/70">
            <CardHeader class="pb-3">
                <CardTitle class="font-display text-lg"
                    >Quick Actions</CardTitle
                >
            </CardHeader>
            <CardContent>
                <div class="flex flex-wrap gap-2">
                    <Button size="sm" variant="outline" as-child>
                        <Link
                            :href="
                                canApprovePayments
                                    ? paymentApprovalsUrl
                                    : '/payments'
                            "
                        >
                            {{
                                canApprovePayments
                                    ? 'Payment Approvals'
                                    : 'Payments'
                            }}
                        </Link>
                    </Button>
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="maintenanceOversight().url"
                            >Maintenance Oversight</Link
                        >
                    </Button>
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="todosIndex().url">Todos</Link>
                    </Button>
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="inspectionsIndex().url">Inspections</Link>
                    </Button>
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="reportsIndex().url">Reports</Link>
                    </Button>
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="auditGovernance().url"
                            >Audit Governance</Link
                        >
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
