<script setup lang="ts">
import { Button } from '@/components/ui/button';
import StatsCard from '@/components/StatsCard.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index as paymentApprovalsIndex } from '@/routes/payment-approvals/index';
import { index as todosIndex } from '@/routes/todos';
import { index as inspectionsIndex } from '@/routes/inspections';
import { index as reportsIndex } from '@/routes/reports';
import { governance as auditGovernance } from '@/routes/audit-logs';
import { Link } from '@inertiajs/vue3';
import { AlertTriangle, ClipboardCheck, CreditCard, ListTodo, ShieldCheck, Timer } from 'lucide-vue-next';
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
    };
    permissions?: string[];
}

const props = defineProps<Props>();

const currencyFormat = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0,
});

const todoSummary = computed(() => props.data.todoSummary ?? { pending: 0, overdue: 0, total: 0 });
const inspectionSummary = computed(() => props.data.inspectionSummary ?? { total: 0, last_7_days: 0, latest_date: null });
</script>

<template>
    <div class="space-y-6">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <StatsCard
                title="Pending Approvals"
                :value="props.data.pendingApprovals"
                :icon="CreditCard"
                accent-color="amber"
                description="Awaiting manager decision"
            />
            <StatsCard
                title="Pending Amount"
                :value="currencyFormat.format(props.data.pendingApprovalCost || 0)"
                :icon="ShieldCheck"
                accent-color="rose"
                description="Current approval exposure"
            />
            <StatsCard
                title="Queue Age"
                :value="props.data.oldestPendingDays ? `${props.data.oldestPendingDays}d` : '--'"
                :icon="Timer"
                accent-color="blue"
                :description="props.data.oldestPendingDate ?? 'No delayed queue'"
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
                <CardTitle class="font-display text-lg">Quick Actions</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex flex-wrap gap-2">
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="paymentApprovalsIndex().url">Payment Approvals</Link>
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
                        <Link :href="auditGovernance().url">Audit Governance</Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
