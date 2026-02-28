<script setup lang="ts">
import { DatePicker } from '@/components/ui/date-picker';
import AuditViewer from '@/components/Admin/AuditViewer.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as auditLogsIndex } from '@/routes/audit-logs';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import type { ColumnDef } from '@tanstack/vue-table';
import { h, ref } from 'vue';

const filtersVisible = ref(false);

interface Actor {
    id: number;
    name: string;
}

interface Change {
    field: string;
    before: unknown;
    after: unknown;
}

interface AuditLog {
    id: number;
    action: string;
    auditable_type?: string | null;
    auditable_id?: number | null;
    created_at: string;
    actor?: Actor | null;
    changes?: Change[];
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

interface Filters {
    search?: string | null;
    action?: string | null;
    actor?: string | null;
    auditable_type?: string | null;
    auditable_id?: string | null;
    start_date?: string | null;
    end_date?: string | null;
}

interface Props {
    data: {
        logs: PaginatedLogs;
    };
    filters: Filters;
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

const searchFilter = ref(props.filters.search ?? '');
const actionFilter = ref(props.filters.action ?? '');
const actorFilter = ref(props.filters.actor ?? '');
const typeFilter = ref(props.filters.auditable_type ?? '');
const idFilter = ref(props.filters.auditable_id ?? '');
const startDateFilter = ref(props.filters.start_date ?? '');
const endDateFilter = ref(props.filters.end_date ?? '');

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
        cell: ({ row }) =>
            h(
                'div',
                { class: 'flex items-center gap-2' },
                [
                    h(
                        Badge,
                        { variant: 'secondary' },
                        () => row.original.auditable_type ?? 'none',
                    ),
                    row.original.auditable_id
                        ? `#${row.original.auditable_id}`
                        : null,
                ].filter(Boolean),
            ),
    },
    {
        id: 'changes',
        header: 'Changes',
        cell: ({ row }) => h(AuditViewer, { changes: row.original.changes }),
        enableSorting: false,
        enableHiding: false,
    },
];
</script>

<template>
    <Head title="Audit logs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader
                title="Audit logs"
                subtitle="Review administrative and system actions."
                :show-filters-toggle="true"
                :filters-visible="filtersVisible"
                @toggle-filters="filtersVisible = !filtersVisible"
            />

            <DataTable
                :data="data.logs.data"
                :columns="columns"
                :show-search="false"
                :enable-column-toggle="false"
                :show-selection-summary="false"

            >
                <template v-if="filtersVisible" #filters>
                    <form
                        :action="auditLogsIndex().url"
                        method="get"
                        class="flex flex-wrap items-center gap-3 rounded-xl border border-border/60 bg-card p-4"
                    >
                        <div class="relative min-w-[220px] flex-1">
                            <Search
                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                v-model="searchFilter"
                                name="search"
                                class="pl-9"
                                placeholder="Search action, actor, or type"
                            />
                        </div>
                        <Input
                            v-model="actionFilter"
                            name="action"
                            placeholder="Action key"
                            class="min-w-[180px]"
                        />
                        <Input
                            v-model="actorFilter"
                            name="actor"
                            placeholder="Actor name"
                            class="min-w-[180px]"
                        />
                        <Input
                            v-model="typeFilter"
                            name="auditable_type"
                            placeholder="Auditable type"
                            class="min-w-[180px]"
                        />
                        <Input
                            v-model="idFilter"
                            name="auditable_id"
                            placeholder="Auditable ID"
                            class="min-w-[140px]"
                        />
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
                            <Button type="submit" class="whitespace-nowrap">
                                Apply filters
                            </Button>
                            <Button variant="ghost" as-child>
                                <Link :href="auditLogsIndex().url">Reset</Link>
                            </Button>
                        </div>
                    </form>
                </template>
            </DataTable>

            <PaginationLinks :links="data.logs.links" />
        </div>
    </AppLayout>
</template>
