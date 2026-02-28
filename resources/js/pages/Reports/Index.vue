<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as reportsIndex } from '@/routes/reports';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

interface Props {
    data: {
        facilities: number;
        inspections: number;
        maintenanceRequests: number;
        workOrders: number;
        vendors: number;
        paymentsPending: number;
    };
    permissions: string[];
    routes: Record<string, string>;
    meta: Record<string, string>;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reports',
        href: reportsIndex().url,
    },
];
</script>

<template>
    <Head title="Reports" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader title="Reports" subtitle="High-level operational summary." />

            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle>Facilities</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold">
                            {{ data.facilities }}
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Inspections</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold">
                            {{ data.inspections }}
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Maintenance requests</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold">
                            {{ data.maintenanceRequests }}
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Work orders</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold">
                            {{ data.workOrders }}
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Vendors</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold">
                            {{ data.vendors }}
                        </p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Pending payments</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold">
                            {{ data.paymentsPending }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
