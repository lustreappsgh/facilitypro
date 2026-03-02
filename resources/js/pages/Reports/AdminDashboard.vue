<script setup lang="ts">
import { DatePicker } from '@/components/ui/date-picker';
import ExportTools from '@/components/Admin/ExportTools.vue';
import SystemMetrics from '@/components/Admin/SystemMetrics.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { admin as reportsAdmin } from '@/routes/reports';
import adminExports from '@/routes/reports/admin';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const filtersVisible = ref(false);

interface TrendEntry {
    period: string;
    count?: number;
    total_cost?: number;
}

interface Props {
    data: {
        summary: Record<string, number>;
        statusBreakdown: Record<string, Record<string, number>>;
        costs: {
            total: number;
            average: number;
        };
        aging: {
            openMaintenanceOver14Days: number;
            overdueWorkOrders: number;
            pendingPaymentsOver30Days: number;
        };
        trends: {
            payments: TrendEntry[];
            maintenanceRequests: TrendEntry[];
            workOrders: TrendEntry[];
        };
    };
    filters: {
        start_date?: string | null;
        end_date?: string | null;
    };
    permissions: string[];
    routes: Record<string, string>;
    meta: Record<string, string>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Reports',
        href: reportsAdmin().url,
    },
];

const startDateFilter = ref(props.filters.start_date ?? '');
const endDateFilter = ref(props.filters.end_date ?? '');

const currencyFormat = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0,
});

const buildExportUrl = (format: 'csv' | 'pdf') =>
    adminExports.export({
        query: {
            format,
            start_date: startDateFilter.value || undefined,
            end_date: endDateFilter.value || undefined,
        },
    }).url;

const summaryEntries = computed(() => Object.entries(props.data.summary));
const statusEntries = computed(() => Object.entries(props.data.statusBreakdown));
const costMetrics = computed(() => [
    {
        label: 'Total cost',
        value: currencyFormat.format(props.data.costs.total),
    },
    {
        label: 'Average cost',
        value: currencyFormat.format(props.data.costs.average),
    },
    {
        label: 'Open maintenance > 14 days',
        value: props.data.aging.openMaintenanceOver14Days,
    },
    {
        label: 'Overdue work orders',
        value: props.data.aging.overdueWorkOrders,
    },
    {
        label: 'Pending payments > 30 days',
        value: props.data.aging.pendingPaymentsOver30Days,
    },
]);
</script>

<template>
    <Head title="Admin reports" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader
                title="Admin reports"
                subtitle="Track system-wide performance and costs."
                :show-filters-toggle="true"
                :filters-visible="filtersVisible"
                @toggle-filters="filtersVisible = !filtersVisible"
            >
                <template #actions>
                    <ExportTools
                        :csv-url="buildExportUrl('csv')"
                        :pdf-url="buildExportUrl('pdf')"
                    />
                </template>
            </PageHeader>

            <form
                v-if="filtersVisible"
                :action="reportsAdmin().url"
                method="get"
                class="flex flex-wrap items-center gap-3 rounded-xl border border-border/60 bg-card p-4"
            >
                <DatePicker
                    v-model="startDateFilter"
                    name="start_date"
                    class="min-w-[150px]"
                 />
                <DatePicker
                    v-model="endDateFilter"
                    name="end_date"
                    class="min-w-[150px]"
                 />
                <div class="flex items-center gap-2">
                    <Button type="submit">Apply</Button>
                    <Button variant="ghost" as-child>
                        <Link :href="reportsAdmin().url">Reset</Link>
                    </Button>
                </div>
            </form>

            <SystemMetrics title="Cost and aging" :items="costMetrics" />

            <section class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>System totals</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-2 text-sm">
                            <div
                                v-for="[label, value] in summaryEntries"
                                :key="label"
                                class="flex items-center justify-between"
                            >
                                <span class="capitalize text-muted-foreground">
                                    {{ label.replace(/([A-Z])/g, ' $1') }}
                                </span>
                                <span class="font-medium">{{ value }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Status breakdown</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4 text-sm">
                            <div
                                v-for="[group, breakdown] in statusEntries"
                                :key="group"
                                class="rounded-lg border border-border/60 p-3"
                            >
                                <div class="mb-2 font-medium capitalize">
                                    {{ group.replace(/([A-Z])/g, ' $1') }}
                                </div>
                                <div class="grid gap-2">
                                    <div
                                        v-for="(count, status) in breakdown"
                                        :key="status"
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-muted-foreground">
                                            {{ status.replace('_', ' ') }}
                                        </span>
                                        <span class="font-medium">{{ count }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </section>

            <section class="grid gap-4 lg:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle>Payment trend</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2 text-sm">
                            <div
                                v-for="entry in data.trends.payments"
                                :key="entry.period"
                                class="flex items-center justify-between"
                            >
                                <span class="text-muted-foreground">
                                    {{ entry.period }}
                                </span>
                                <span class="font-medium">
                                    {{ entry.count ?? 0 }} /
                                    {{ currencyFormat.format(entry.total_cost ?? 0) }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Maintenance requests trend</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2 text-sm">
                            <div
                                v-for="entry in data.trends.maintenanceRequests"
                                :key="entry.period"
                                class="flex items-center justify-between"
                            >
                                <span class="text-muted-foreground">
                                    {{ entry.period }}
                                </span>
                                <span class="font-medium">{{ entry.count ?? 0 }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Work orders trend</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2 text-sm">
                            <div
                                v-for="entry in data.trends.workOrders"
                                :key="entry.period"
                                class="flex items-center justify-between"
                            >
                                <span class="text-muted-foreground">
                                    {{ entry.period }}
                                </span>
                                <span class="font-medium">{{ entry.count ?? 0 }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </section>
        </div>
    </AppLayout>
</template>
