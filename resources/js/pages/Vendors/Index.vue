<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import DataTable from '@/components/data-table/DataTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { create, index as vendorsIndex, show } from '@/routes/vendors';
import { create as workOrderCreate } from '@/routes/work-orders';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';
import type { ColumnDef } from '@tanstack/vue-table';
import { ClipboardList, Eye, Plus, Search } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';

const filtersVisible = ref(false);

interface Vendor {
    id: number;
    name: string;
    email?: string | null;
    phone?: string | null;
    service_type?: string | null;
    status?: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedVendors {
    data: Vendor[];
    links: PaginationLink[];
}

interface Filters {
    search?: string | null;
    status?: string | null;
    service_type?: string | null;
}

interface Props {
    vendors: PaginatedVendors;
    serviceTypes: string[];
    filters: Filters;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Vendors',
        href: vendorsIndex().url,
    },
];

const { can } = usePermissions();

const searchFilter = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');
const serviceTypeFilter = ref(props.filters.service_type ?? '');

const selectedServiceTypeLabel = computed(() => {
    return serviceTypeFilter.value || 'All service types';
});

const statusBadgeClass = (status: string) => {
    if (status === 'active') {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (status === 'inactive') {
        return 'bg-slate-500/10 text-slate-700 dark:text-slate-300';
    }

    return 'bg-muted text-muted-foreground';
};

const columns: ColumnDef<Vendor>[] = [
    {
        id: 'name',
        accessorFn: (row) => row.name ?? '',
        header: 'Name',
        cell: ({ row }) =>
            h('span', { class: 'font-medium' }, row.original.name),
        enableHiding: false,
    },
    {
        id: 'contact',
        accessorFn: (row) => row.email ?? row.phone ?? '',
        header: 'Contact',
        cell: ({ row }) =>
            h('div', { class: 'text-sm text-muted-foreground' }, [
                h('div', {}, row.original.email ?? '-'),
                h('div', {}, row.original.phone ?? '-'),
            ]),
    },
    {
        id: 'serviceType',
        accessorFn: (row) => row.service_type ?? '',
        header: 'Service type',
        cell: ({ row }) => row.original.service_type ?? '-',
    },
    {
        id: 'status',
        accessorFn: (row) => row.status ?? '',
        header: 'Status',
        cell: ({ row }) =>
            h(
                Badge,
                { variant: 'secondary', class: statusBadgeClass(row.original.status ?? '') },
                () => row.original.status ?? 'unknown',
            ),
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) =>
            h('div', { class: 'flex items-center justify-end gap-1' }, [
                h(Button, { variant: 'ghost', size: 'icon', class: 'h-8 w-8', asChild: true }, () =>
                    h(Link, { href: show(row.original.id).url, 'aria-label': 'View vendor' }, () =>
                        h(Eye, { class: 'h-4 w-4' }),
                    ),
                ),
                can('work_orders.create')
                    ? h(Button, { variant: 'ghost', size: 'icon', class: 'h-8 w-8', asChild: true }, () =>
                        h(
                            Link,
                            {
                                href: workOrderCreate({
                                    query: { vendor_id: row.original.id },
                                }).url,
                                'aria-label': 'Create work order',
                            },
                            () => h(ClipboardList, { class: 'h-4 w-4' }),
                        ),
                    )
                    : null,
            ]),
        enableSorting: false,
        enableHiding: false,
    },
];
</script>

<template>

    <Head title="Vendors" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-8 p-6 lg:p-10">
            <PageHeader title="Vendors" subtitle="Manage vendor relationships and service capabilities."
                :show-filters-toggle="true"
                :filters-visible="filtersVisible" @toggle-filters="filtersVisible = !filtersVisible">
                <template #actions>
                    <Button v-if="can('vendors.create')" size="icon" class="h-9 w-9" as-child>
                        <Link :href="create().url" aria-label="New vendor">
                            <Plus class="h-4 w-4" />
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <DataTable :data="vendors.data" :columns="columns" :show-search="false" :show-selection-summary="false">
                <template v-if="filtersVisible" #filters>
                    <form :action="vendorsIndex().url" method="get"
                        class="flex flex-wrap items-center gap-3 rounded-xl border border-border/60 bg-card p-4">
                        <div class="relative min-w-[220px] flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input v-model="searchFilter" name="search" class="pl-9"
                                placeholder="Search name, email, phone" />
                        </div>

                        <input type="hidden" name="status" :value="statusFilter" />
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" class="min-w-[140px] justify-between">
                                    {{ statusFilter || 'All statuses' }}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-52">
                                <DropdownMenuLabel>Status</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @click="statusFilter = ''">
                                    All statuses
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="statusFilter = 'active'">
                                    Active
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="statusFilter = 'inactive'">
                                    Inactive
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <input type="hidden" name="service_type" :value="serviceTypeFilter" />
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" class="min-w-[180px] justify-between">
                                    {{ selectedServiceTypeLabel }}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-64">
                                <DropdownMenuLabel>Service type</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @click="serviceTypeFilter = ''">
                                    All service types
                                </DropdownMenuItem>
                                <DropdownMenuItem v-for="type in serviceTypes" :key="type"
                                    @click="serviceTypeFilter = type">
                                    {{ type }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <div class="flex items-center gap-2">
                            <Button type="submit">Apply filters</Button>
                            <Button variant="ghost" as-child>
                                <Link :href="vendorsIndex().url">Reset</Link>
                            </Button>
                        </div>
                    </form>
                </template>
            </DataTable>


            <PaginationLinks :links="vendors.links" />
        </div>
    </AppLayout>
</template>
