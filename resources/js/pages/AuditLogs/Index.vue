<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as auditLogsIndex } from '@/routes/audit-logs';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { h } from 'vue';

interface Actor {
    id: number;
    name: string;
}

interface AuditLog {
    id: number;
    action: string;
    auditable_type?: string | null;
    auditable_id?: number | null;
    before?: Record<string, unknown> | null;
    after?: Record<string, unknown> | null;
    created_at: string;
    actor?: Actor | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedLogs {
    data: AuditLog[];
    links: PaginationLink[];
}

interface Props {
    data: {
        logs: PaginatedLogs;
    };
    permissions: string[];
    routes: Record<string, string>;
    meta: Record<string, string>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Audit Logs',
        href: auditLogsIndex().url,
    },
];

const columns: ColumnDef<AuditLog>[] = [
    {
        id: 'timestamp',
        accessorFn: (row) => row.created_at,
        header: 'Timestamp',
        cell: ({ row }) => row.original.created_at,
    },
    {
        id: 'actor',
        accessorFn: (row) => row.actor?.name ?? '',
        header: 'Actor',
        cell: ({ row }) => row.original.actor?.name ?? 'System',
    },
    {
        id: 'action',
        accessorFn: (row) => row.action,
        header: 'Action',
        cell: ({ row }) => row.original.action,
    },
    {
        id: 'target',
        accessorFn: (row) => row.auditable_type ?? '',
        header: 'Target',
        cell: ({ row }) => {
            const type = row.original.auditable_type ?? '-';
            const id = row.original.auditable_id;
            return h(
                'span',
                { class: 'inline-flex items-center gap-1' },
                id ? `${type} #${id}` : type,
            );
        },
    },
];
</script>

<template>

    <Head title="Audit Logs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-8 p-6 lg:p-10">
            <PageHeader title="Audit logs" subtitle="Review system activity and user actions."
                :show-filters-toggle="false" />

            <DataTable :data="props.data.logs.data" :columns="columns" :show-search="false"
                :enable-column-toggle="false" :show-selection-summary="false" class="portfolio-table" />

            <PaginationLinks :links="data.logs.links" />
        </div>
    </AppLayout>
</template>
