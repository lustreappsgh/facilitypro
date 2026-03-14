<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import ReportFilters from '@/components/ReportFilters.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { createCurrencyFormatter } from '@/lib/currency';
import { dashboard as reportsDashboard } from '@/routes/reports';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const filtersVisible = ref(false);

interface Summary {
    facilities: number;
    inspections: number;
    maintenanceRequests: number;
    workOrders: number;
    vendors: number;
    requestTypes: number;
}

interface Approvals {
    pending: number;
    pendingCost: number;
}

interface BreakdownItem {
    id: number;
    name: string;
    total_cost: number;
}

interface TrendItem {
    period: string;
    count: number;
    total_cost: number;
}

interface Props {
    data: {
        summary: Summary;
        approvals: Approvals;
        breakdowns: {
            facilities: BreakdownItem[];
            vendors: BreakdownItem[];
            types: BreakdownItem[];
        };
        trend: TrendItem[];
    };
    filters: {
        start_date?: string | null;
        end_date?: string | null;
    };
    permissions: string[];
    routes: {
        index: string;
        export: string;
    };
    meta: Record<string, string>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reports',
        href: reportsDashboard().url,
    },
];

const currencyFormat = createCurrencyFormatter();

const maxTrendCost = computed(() => {
    return Math.max(...props.data.trend.map((item) => item.total_cost), 1);
});

const exportUrl = computed(() => {
    const url = new URL(props.routes.export);
    url.searchParams.set('format', 'csv');
    if (props.filters.start_date) {
        url.searchParams.set('start_date', props.filters.start_date);
    }
    if (props.filters.end_date) {
        url.searchParams.set('end_date', props.filters.end_date);
    }
    return url.pathname + url.search;
});
</script>

<template>
    <Head title="Reports dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader
                title="Reports dashboard"
                subtitle="Track high-level performance trends."
                :show-filters-toggle="true"
                :filters-visible="filtersVisible"
                @toggle-filters="filtersVisible = !filtersVisible"
            >
                <template #actions>
                    <div class="flex flex-wrap gap-2">
                        <Button variant="outline" as-child>
                            <a :href="exportUrl">Export CSV</a>
                        </Button>
                        <Button variant="ghost" as-child>
                            <Link :href="routes.index">Summary view</Link>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <ReportFilters
                v-if="filtersVisible"
                :action="reportsDashboard().url"
                :start-date="filters.start_date"
                :end-date="filters.end_date"
            />

            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle>Facilities</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold">
                            {{ data.summary.facilities }}
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Inspections</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold">
                            {{ data.summary.inspections }}
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Maintenance requests</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold">
                            {{ data.summary.maintenanceRequests }}
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Work orders</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold">
                            {{ data.summary.workOrders }}
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Vendors</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold">
                            {{ data.summary.vendors }}
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Request types</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold">
                            {{ data.summary.requestTypes }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Pending approvals</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <p class="text-3xl font-semibold">
                            {{ data.approvals.pending }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            Pending value:
                            {{
                                currencyFormat.format(
                                    data.approvals.pendingCost,
                                )
                            }}
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Spend trend</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-end gap-3">
                            <div
                                v-for="item in data.trend"
                                :key="item.period"
                                class="flex flex-1 flex-col items-center gap-2"
                            >
                                <div
                                    class="w-full rounded-md bg-slate-900/10"
                                    :style="{
                                        height: `${Math.max(
                                            (item.total_cost / maxTrendCost) *
                                                140,
                                            12,
                                        )}px`,
                                    }"
                                />
                                <p class="text-xs text-muted-foreground">
                                    {{ item.period }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle>Top facilities</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="!data.breakdowns.facilities.length"
                            class="text-sm text-muted-foreground"
                        >
                            No facility spend data yet.
                        </div>
                        <div v-else class="space-y-2 text-sm">
                            <div
                                v-for="facility in data.breakdowns.facilities"
                                :key="facility.id"
                                class="flex items-center justify-between"
                            >
                                <span>{{ facility.name }}</span>
                                <span class="font-medium">
                                    {{
                                        currencyFormat.format(
                                            facility.total_cost,
                                        )
                                    }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Top vendors</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="!data.breakdowns.vendors.length"
                            class="text-sm text-muted-foreground"
                        >
                            No vendor spend data yet.
                        </div>
                        <div v-else class="space-y-2 text-sm">
                            <div
                                v-for="vendor in data.breakdowns.vendors"
                                :key="vendor.id"
                                class="flex items-center justify-between"
                            >
                                <span>{{ vendor.name }}</span>
                                <span class="font-medium">
                                    {{
                                        currencyFormat.format(vendor.total_cost)
                                    }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Top request types</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="!data.breakdowns.types.length"
                            class="text-sm text-muted-foreground"
                        >
                            No request type spend data yet.
                        </div>
                        <div v-else class="space-y-2 text-sm">
                            <div
                                v-for="type in data.breakdowns.types"
                                :key="type.id"
                                class="flex items-center justify-between"
                            >
                                <span>{{ type.name }}</span>
                                <span class="font-medium">
                                    {{ currencyFormat.format(type.total_cost) }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
