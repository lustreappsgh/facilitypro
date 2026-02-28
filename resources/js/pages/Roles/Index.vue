<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import DataTable from '@/components/data-table/DataTable.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    create as rolesCreate,
    destroy as rolesDestroy,
    edit as rolesEdit,
    index as rolesIndex,
} from '@/routes/roles';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Search, Plus } from 'lucide-vue-next';
import type { ColumnDef } from '@tanstack/vue-table';
import { h, ref } from 'vue';

const filtersVisible = ref(false);

interface Permission {
    id: number;
    name: string;
}

interface Role {
    id: number;
    name: string;
    permissions?: Permission[];
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedRoles {
    data: Role[];
    links: PaginationLink[];
}

interface Filters {
    search?: string | null;
}

interface Props {
    data: {
        roles: PaginatedRoles;
    };
    filters: Filters;
    permissions: Permission[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roles',
        href: rolesIndex().url,
    },
];

const searchFilter = ref(props.filters.search ?? '');
const deleteForm = useForm({});
const roleToDelete = ref<Role | null>(null);

const confirmDelete = (role: Role) => {
    roleToDelete.value = role;
};

const closeDelete = () => {
    roleToDelete.value = null;
};

const handleDialogChange = (open: boolean) => {
    if (!open) {
        closeDelete();
    }
};

const submitDelete = () => {
    if (!roleToDelete.value) {
        return;
    }

    deleteForm.delete(rolesDestroy(roleToDelete.value).url, {
        preserveScroll: true,
        onFinish: () => {
            roleToDelete.value = null;
        },
    });
};

const columns: ColumnDef<Role>[] = [
    {
        id: 'role',
        accessorFn: (row) => row.name ?? '',
        header: 'Role',
        cell: ({ row }) => h('span', { class: 'font-medium' }, row.original.name),
        enableHiding: false,
    },
    {
        id: 'permissions',
        accessorFn: (row) => (row.permissions ?? []).map((permission) => permission.name).join(', '),
        header: 'Permissions',
        cell: ({ row }) => {
            const permissions = row.original.permissions ?? [];
            if (!permissions.length) {
                return h('span', { class: 'text-muted-foreground' }, 'No permissions assigned.');
            }
            return h(
                'div',
                { class: 'flex flex-wrap gap-2' },
                permissions.map((permission) =>
                    h(
                        Badge,
                        { variant: 'secondary' },
                        () => permission.name,
                    ),
                ),
            );
        },
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) =>
            h('div', { class: 'flex flex-wrap gap-2' }, [
                h(Button, { variant: 'outline', size: 'sm', asChild: true }, () =>
                    h(Link, { href: rolesEdit(row.original).url }, () => 'Edit'),
                ),
                h(
                    Button,
                    { variant: 'ghost', size: 'sm', onClick: () => confirmDelete(row.original) },
                    () => 'Delete',
                ),
            ]),
        enableSorting: false,
        enableHiding: false,
    },
];
</script>

<template>

    <Head title="Roles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-8 p-6 lg:p-10">
            <PageHeader title="Roles" subtitle="Define role permissions and access controls." :action-label="'New role'"
                :action-href="rolesCreate().url" :action-icon="Plus" :show-filters-toggle="true"
                :filters-visible="filtersVisible" @toggle-filters="filtersVisible = !filtersVisible" />

            <DataTable :data="data.roles.data" :columns="columns" :show-search="false" :show-selection-summary="false"
                class="portfolio-table">
                <template v-if="filtersVisible" #filters>
                    <form :action="rolesIndex().url" method="get"
                        class="flex flex-wrap items-center gap-3 rounded-xl border border-border/60 bg-card p-4">
                        <div class="relative min-w-[220px] flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input v-model="searchFilter" name="search" class="pl-9" placeholder="Search roles" />
                        </div>
                        <div class="flex items-center gap-2">
                            <Button type="submit" class="whitespace-nowrap">
                                Apply filters
                            </Button>
                            <Button variant="ghost" as-child>
                                <Link :href="rolesIndex().url">Reset</Link>
                            </Button>
                        </div>
                    </form>
                </template>
            </DataTable>

            <PaginationLinks :links="data.roles.links" />
        </div>

        <Dialog :open="Boolean(roleToDelete)" @update:open="handleDialogChange">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete role</DialogTitle>
                    <DialogDescription>
                        This action removes the role if it is not assigned to any users.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="ghost" @click="closeDelete">Cancel</Button>
                    <Button variant="destructive" :disabled="deleteForm.processing" @click="submitDelete">
                        Confirm delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
