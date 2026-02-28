<script setup lang="ts">
import InfographicCard from '@/components/InfographicCard.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatsCard from '@/components/StatsCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as reportsIndex } from '@/routes/reports';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Activity,
    AlertTriangle,
    Building2,
    ClipboardCheck,
    DollarSign,
    ReceiptText,
    UserCog,
    Wrench,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface SummaryData {
    users?: number;
    facilities?: number;
    facilityTypes?: number;
    requestTypes?: number;
    inspections?: number;
    maintenanceRequests?: number;
    workOrders?: number;
    vendors?: number;
    payments?: number;
    paymentsPending?: number;
}

interface BreakdownItem {
    id: number;
    name: string;
    total_cost: number;
}

interface TrendItem {
    period: string;
    count?: number;
    total_cost?: number;
}

interface PeriodTrendItem {
    period_start: string;
    label: string;
    count: number;
    total_cost?: number;
}

interface Props {
    data: {
        summary: SummaryData;
        adminInsights?: {
            costs: {
                total: number;
                average: number;
            };
            aging: {
                openMaintenanceOver14Days: number;
                overdueWorkOrders: number;
                pendingPaymentsOver30Days: number;
            };
            statusBreakdown: Record<string, Record<string, number>>;
            trends: {
                payments: TrendItem[];
                maintenanceRequests: TrendItem[];
                workOrders: TrendItem[];
            };
            periodicTrends: {
                weekly: {
                    payments: PeriodTrendItem[];
                    requests: PeriodTrendItem[];
                };
                monthly: {
                    payments: PeriodTrendItem[];
                    requests: PeriodTrendItem[];
                };
            };
            approvals: {
                pending: number;
                pendingCost: number;
            };
            spendBreakdowns: {
                facilities: BreakdownItem[];
                vendors: BreakdownItem[];
                types: BreakdownItem[];
            };
        } | null;
    };
    filters: {
        start_date?: string | null;
        end_date?: string | null;
    };
    isAdminScope: boolean;
    permissions: string[];
    routes: {
        dashboard: string;
        adminExport: string;
    };
    meta: Record<string, string>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reports',
        href: reportsIndex().url,
    },
];

const currencyFormat = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0,
});

const startDateFilter = ref(props.filters.start_date ?? '');
const endDateFilter = ref(props.filters.end_date ?? '');

const applyFilters = () => {
    router.get(
        reportsIndex().url,
        {
            start_date: startDateFilter.value || undefined,
            end_date: endDateFilter.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const clearFilters = () => {
    startDateFilter.value = '';
    endDateFilter.value = '';
    router.get(reportsIndex().url, {}, { preserveState: true, preserveScroll: true });
};

const buildExportUrl = (format: 'csv' | 'pdf') => {
    const url = new URL(props.routes.adminExport, window.location.origin);
    url.searchParams.set('format', format);

    if (startDateFilter.value) {
        url.searchParams.set('start_date', startDateFilter.value);
    }

    if (endDateFilter.value) {
        url.searchParams.set('end_date', endDateFilter.value);
    }

    return `${url.pathname}${url.search}`;
};

const summaryCards = computed(() => {
    const summary = props.data.summary;

    if (props.isAdminScope) {
        return [
            { key: 'users', title: 'Users', value: summary.users ?? 0, icon: UserCog, accentColor: 'blue' as const },
            { key: 'facilities', title: 'Facilities', value: summary.facilities ?? 0, icon: Building2, accentColor: 'amber' as const },
            { key: 'inspections', title: 'Inspections', value: summary.inspections ?? 0, icon: ClipboardCheck, accentColor: 'emerald' as const },
            { key: 'maintenance', title: 'Maintenance', value: summary.maintenanceRequests ?? 0, icon: Wrench, accentColor: 'rose' as const },
            { key: 'workOrders', title: 'Work Orders', value: summary.workOrders ?? 0, icon: Activity, accentColor: 'blue' as const },
            { key: 'payments', title: 'Payments', value: summary.payments ?? 0, icon: ReceiptText, accentColor: 'emerald' as const },
        ];
    }

    return [
        { key: 'facilities', title: 'Facilities', value: summary.facilities ?? 0, icon: Building2, accentColor: 'amber' as const },
        { key: 'inspections', title: 'Inspections', value: summary.inspections ?? 0, icon: ClipboardCheck, accentColor: 'emerald' as const },
        { key: 'maintenance', title: 'Maintenance Requests', value: summary.maintenanceRequests ?? 0, icon: Wrench, accentColor: 'rose' as const },
        { key: 'workOrders', title: 'Work Orders', value: summary.workOrders ?? 0, icon: Activity, accentColor: 'blue' as const },
        { key: 'vendors', title: 'Vendors', value: summary.vendors ?? 0, icon: UserCog, accentColor: 'blue' as const },
        { key: 'pendingPayments', title: 'Pending Payments', value: summary.paymentsPending ?? 0, icon: ReceiptText, accentColor: 'amber' as const },
    ];
});

const adminInsights = computed(() => props.data.adminInsights);
const paymentWeeklySpendSeries = computed(() =>
    (adminInsights.value?.periodicTrends?.weekly?.payments ?? []).map((item) => ({
        ...item,
        value: Number(item.total_cost ?? 0),
    })),
);
const paymentMonthlySpendSeries = computed(() =>
    (adminInsights.value?.periodicTrends?.monthly?.payments ?? []).map((item) => ({
        ...item,
        value: Number(item.total_cost ?? 0),
    })),
);
const requestWeeklyCountSeries = computed(() =>
    (adminInsights.value?.periodicTrends?.weekly?.requests ?? []).map((item) => ({
        ...item,
        value: Number(item.count ?? 0),
    })),
);
const requestMonthlyCountSeries = computed(() =>
    (adminInsights.value?.periodicTrends?.monthly?.requests ?? []).map((item) => ({
        ...item,
        value: Number(item.count ?? 0),
    })),
);
const paymentWeeklyMax = computed(() => Math.max(1, ...paymentWeeklySpendSeries.value.map((point) => point.value)));
const paymentMonthlyMax = computed(() => Math.max(1, ...paymentMonthlySpendSeries.value.map((point) => point.value)));
const requestWeeklyMax = computed(() => Math.max(1, ...requestWeeklyCountSeries.value.map((point) => point.value)));
const requestMonthlyMax = computed(() => Math.max(1, ...requestMonthlyCountSeries.value.map((point) => point.value)));

const labelize = (value: string) =>
    value
        .replace(/([A-Z])/g, ' $1')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())
        .trim();
</script>

<template>
    <Head title="Reports" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-6 lg:p-8">
            <PageHeader title="Reports" subtitle="Performance, workload, and cost visibility for operations.">
                <template v-if="isAdminScope" #actions>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" size="sm" as-child>
                            <a :href="buildExportUrl('csv')">Export CSV</a>
                        </Button>
                        <Button variant="outline" size="sm" as-child>
                            <a :href="buildExportUrl('pdf')">Export PDF</a>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div v-if="isAdminScope" class="rounded-xl border border-border/60 bg-card/60 p-3 backdrop-blur">
                <div class="grid gap-3 lg:grid-cols-[220px_220px_auto] lg:items-end">
                    <DatePicker v-model="startDateFilter" class="h-9 w-full" placeholder="Start date" />
                    <DatePicker v-model="endDateFilter" class="h-9 w-full" placeholder="End date" />
                    <div class="flex items-center gap-2">
                        <Button size="sm" class="h-9 px-4" @click="applyFilters">Apply filters</Button>
                        <Button size="sm" variant="ghost" class="h-9 px-3" @click="clearFilters">Reset</Button>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <StatsCard
                    v-for="card in summaryCards"
                    :key="card.key"
                    :title="card.title"
                    :value="card.value"
                    :icon="card.icon"
                    :accent-color="card.accentColor"
                />
            </div>

            <template v-if="isAdminScope && adminInsights">
                <div class="grid gap-4 lg:grid-cols-3">
                    <StatsCard
                        title="Total Spend"
                        :value="currencyFormat.format(adminInsights.costs.total)"
                        :icon="DollarSign"
                        accent-color="emerald"
                        description="Payments in current filter window"
                    />
                    <StatsCard
                        title="Average Payment"
                        :value="currencyFormat.format(adminInsights.costs.average)"
                        :icon="ReceiptText"
                        accent-color="blue"
                        description="Average maintenance payment size"
                    />
                    <StatsCard
                        title="Pending Approvals"
                        :value="adminInsights.approvals.pending"
                        :icon="AlertTriangle"
                        accent-color="amber"
                        :description="`Pending value ${currencyFormat.format(adminInsights.approvals.pendingCost)}`"
                    />
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    <InfographicCard title="Maintenance Aging" description="Open and overdue workload">
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between rounded-lg border border-border/50 px-3 py-2">
                                <span class="text-muted-foreground">Open requests > 14 days</span>
                                <Badge variant="outline">{{ adminInsights.aging.openMaintenanceOver14Days }}</Badge>
                            </div>
                            <div class="flex items-center justify-between rounded-lg border border-border/50 px-3 py-2">
                                <span class="text-muted-foreground">Overdue work orders</span>
                                <Badge variant="outline">{{ adminInsights.aging.overdueWorkOrders }}</Badge>
                            </div>
                            <div class="flex items-center justify-between rounded-lg border border-border/50 px-3 py-2">
                                <span class="text-muted-foreground">Pending payments > 30 days</span>
                                <Badge variant="outline">{{ adminInsights.aging.pendingPaymentsOver30Days }}</Badge>
                            </div>
                        </div>
                    </InfographicCard>

                    <InfographicCard title="Payments Breakdown (Weekly + Monthly)" description="Payment spend trends by period" class="lg:col-span-2">
                        <div class="grid gap-4 lg:grid-cols-2">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <p class="text-[11px] font-black uppercase tracking-widest text-muted-foreground/70">Weekly spend</p>
                                    <Badge variant="outline">{{ paymentWeeklySpendSeries.length }} points</Badge>
                                </div>
                                <div class="h-[180px] rounded-lg border border-border/60 bg-muted/20 px-2 py-1">
                                    <div class="flex h-full items-end gap-1 overflow-hidden pb-1">
                                        <div
                                            v-for="point in paymentWeeklySpendSeries"
                                            :key="`pay-week-${point.period_start}`"
                                            class="flex min-w-0 flex-1 flex-col items-center gap-1"
                                        >
                                            <div
                                                class="w-full rounded-sm bg-primary/80"
                                                :style="{ height: `${Math.max((point.value / paymentWeeklyMax) * 140, 4)}px` }"
                                                :title="`${point.label}: ${currencyFormat.format(point.value)}`"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <p class="text-[11px] font-black uppercase tracking-widest text-muted-foreground/70">Monthly spend</p>
                                    <Badge variant="outline">{{ paymentMonthlySpendSeries.length }} points</Badge>
                                </div>
                                <div class="h-[180px] rounded-lg border border-border/60 bg-muted/20 px-2 py-1">
                                    <div class="flex h-full items-end gap-1 overflow-hidden pb-1">
                                        <div
                                            v-for="point in paymentMonthlySpendSeries"
                                            :key="`pay-month-${point.period_start}`"
                                            class="flex min-w-0 flex-1 flex-col items-center gap-1"
                                        >
                                            <div
                                                class="w-full rounded-sm bg-foreground/80"
                                                :style="{ height: `${Math.max((point.value / paymentMonthlyMax) * 140, 4)}px` }"
                                                :title="`${point.label}: ${currencyFormat.format(point.value)}`"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </InfographicCard>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <InfographicCard title="Status Breakdown" description="Current lifecycle distribution">
                        <div class="space-y-4">
                            <div v-for="(breakdown, group) in adminInsights.statusBreakdown" :key="group" class="space-y-2">
                                <p class="text-[11px] font-black uppercase tracking-widest text-muted-foreground/70">{{ labelize(group) }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <Badge
                                        v-for="(count, status) in breakdown"
                                        :key="`${group}-${status}`"
                                        variant="outline"
                                        class="rounded-full"
                                    >
                                        {{ labelize(status) }}: {{ count }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </InfographicCard>

                    <InfographicCard title="Requests Breakdown (Weekly + Monthly)" description="Maintenance request volume trends">
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <p class="text-[11px] font-black uppercase tracking-widest text-muted-foreground/70">Weekly requests</p>
                                    <Badge variant="outline">{{ requestWeeklyCountSeries.length }} points</Badge>
                                </div>
                                <div class="h-[160px] rounded-lg border border-border/60 bg-muted/20 px-2 py-1">
                                    <div class="flex h-full items-end gap-1 overflow-hidden pb-1">
                                        <div
                                            v-for="point in requestWeeklyCountSeries"
                                            :key="`req-week-${point.period_start}`"
                                            class="flex min-w-0 flex-1 flex-col items-center gap-1"
                                        >
                                            <div
                                                class="w-full rounded-sm bg-primary/80"
                                                :style="{ height: `${Math.max((point.value / requestWeeklyMax) * 120, 4)}px` }"
                                                :title="`${point.label}: ${point.value} requests`"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <p class="text-[11px] font-black uppercase tracking-widest text-muted-foreground/70">Monthly requests</p>
                                    <Badge variant="outline">{{ requestMonthlyCountSeries.length }} points</Badge>
                                </div>
                                <div class="h-[160px] rounded-lg border border-border/60 bg-muted/20 px-2 py-1">
                                    <div class="flex h-full items-end gap-1 overflow-hidden pb-1">
                                        <div
                                            v-for="point in requestMonthlyCountSeries"
                                            :key="`req-month-${point.period_start}`"
                                            class="flex min-w-0 flex-1 flex-col items-center gap-1"
                                        >
                                            <div
                                                class="w-full rounded-sm bg-foreground/80"
                                                :style="{ height: `${Math.max((point.value / requestMonthlyMax) * 120, 4)}px` }"
                                                :title="`${point.label}: ${point.value} requests`"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </InfographicCard>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    <InfographicCard title="Top Facilities by Spend" description="Highest maintenance spend entities">
                        <div class="space-y-2 text-sm">
                            <div v-if="!adminInsights.spendBreakdowns.facilities.length" class="text-muted-foreground">
                                No facility spend data.
                            </div>
                            <div
                                v-for="item in adminInsights.spendBreakdowns.facilities"
                                :key="`facility-${item.id}`"
                                class="flex items-center justify-between"
                            >
                                <span class="truncate pr-2">{{ item.name }}</span>
                                <span class="font-semibold">{{ currencyFormat.format(item.total_cost) }}</span>
                            </div>
                        </div>
                    </InfographicCard>

                    <InfographicCard title="Top Vendors by Spend" description="Vendor cost concentration">
                        <div class="space-y-2 text-sm">
                            <div v-if="!adminInsights.spendBreakdowns.vendors.length" class="text-muted-foreground">
                                No vendor spend data.
                            </div>
                            <div
                                v-for="item in adminInsights.spendBreakdowns.vendors"
                                :key="`vendor-${item.id}`"
                                class="flex items-center justify-between"
                            >
                                <span class="truncate pr-2">{{ item.name }}</span>
                                <span class="font-semibold">{{ currencyFormat.format(item.total_cost) }}</span>
                            </div>
                        </div>
                    </InfographicCard>

                    <InfographicCard title="Top Request Types" description="Request categories driving spend">
                        <div class="space-y-2 text-sm">
                            <div v-if="!adminInsights.spendBreakdowns.types.length" class="text-muted-foreground">
                                No request type spend data.
                            </div>
                            <div
                                v-for="item in adminInsights.spendBreakdowns.types"
                                :key="`type-${item.id}`"
                                class="flex items-center justify-between"
                            >
                                <span class="truncate pr-2">{{ item.name }}</span>
                                <span class="font-semibold">{{ currencyFormat.format(item.total_cost) }}</span>
                            </div>
                        </div>
                    </InfographicCard>
                </div>
            </template>

            <div v-else class="rounded-xl border border-border/60 bg-card p-4 text-sm text-muted-foreground">
                Need deeper analytics? Open the dashboard view for trend breakdowns.
                <Button variant="link" class="px-1" as-child>
                    <Link :href="routes.dashboard">Open reports dashboard</Link>
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
