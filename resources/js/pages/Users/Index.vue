<script setup lang="ts">
import BulkActions from '@/components/Admin/BulkActions.vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import DataTable from '@/components/data-table/DataTable.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/AppLayout.vue';
import { create as usersCreate, edit as usersEdit, index as usersIndex } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Search, Plus } from 'lucide-vue-next';
import type { ColumnDef } from '@tanstack/vue-table';
import { computed, h, ref } from 'vue';

const filtersVisible = ref(false);

interface Role {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    is_active: boolean;
    roles?: Role[];
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedUsers {
    data: User[];
    links: PaginationLink[];
}

interface Filters {
    search?: string | null;
    role?: string | null;
    status?: string | null;
}

interface Props {
    data: {
        users: PaginatedUsers;
    };
    filters: Filters;
    roles: Role[];
    permissions: string[];
    routes: Record<string, string>;
    meta: Record<string, string>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: usersIndex().url,
    },
];

const searchFilter = ref(props.filters.search ?? '');
const roleFilter = ref(props.filters.role ?? '');
const statusFilter = ref(props.filters.status ?? '');

const selectedUserIds = ref<number[]>([]);

const bulkForm = useForm({
    action: 'activate',
    user_ids: [] as number[],
});

const isAllSelected = computed(() => {
    const ids = props.data.users.data.map((user) => user.id);
    return ids.length > 0 && ids.every((id) => selectedUserIds.value.includes(id));
});

const isSomeSelected = computed(
    () => selectedUserIds.value.length > 0 && !isAllSelected.value,
);

const toggleAll = (value: boolean | 'indeterminate') => {
    if (value === true) {
        selectedUserIds.value = props.data.users.data.map((user) => user.id);
        return;
    }

    selectedUserIds.value = [];
};

const toggleUserSelection = (userId: number, checked: boolean) => {
    if (checked) {
        if (!selectedUserIds.value.includes(userId)) {
            selectedUserIds.value.push(userId);
        }
        return;
    }

    selectedUserIds.value = selectedUserIds.value.filter(
        (id) => id !== userId,
    );
};

const submitBulkAction = (action: 'activate' | 'deactivate') => {
    if (!selectedUserIds.value.length) {
        return;
    }

    bulkForm.action = action;
    bulkForm.user_ids = selectedUserIds.value;
    bulkForm.post(props.routes.bulkStatus, {
        preserveScroll: true,
        onSuccess: () => {
            selectedUserIds.value = [];
        },
    });
};

const selectedRoleLabel = computed(() => {
    const match = props.roles.find((role) => role.name === roleFilter.value);
    return match?.name ?? 'All roles';
});

const selectedStatusLabel = computed(() => {
    if (statusFilter.value === 'active') {
        return 'Active';
    }
    if (statusFilter.value === 'inactive') {
        return 'Inactive';
    }
    return 'All statuses';
});

const columns: ColumnDef<User>[] = [
    {
        id: 'select',
        header: () =>
            h(Checkbox, {
                modelValue:
                    isAllSelected.value ||
                    (isSomeSelected.value && 'indeterminate'),
                'onUpdate:modelValue': toggleAll,
                'aria-label': 'Select all users',
            }),
        cell: ({ row }) =>
            h(Checkbox, {
                modelValue: selectedUserIds.value.includes(row.original.id),
                'onUpdate:modelValue': (value: boolean | 'indeterminate') =>
                    toggleUserSelection(row.original.id, value === true),
                'aria-label': 'Select user',
            }),
        enableSorting: false,
        enableHiding: false,
    },
    {
        id: 'name',
        accessorFn: (row) => row.name ?? '',
        header: 'Name',
        cell: ({ row }) => h('span', { class: 'font-medium' }, row.original.name),
        enableHiding: false,
    },
    {
        id: 'email',
        accessorFn: (row) => row.email ?? '',
        header: 'Email',
        cell: ({ row }) => row.original.email,
    },
    {
        id: 'roles',
        accessorFn: (row) => (row.roles ?? []).map((role) => role.name).join(', '),
        header: 'Roles',
        cell: ({ row }) => {
            const roles = row.original.roles ?? [];
            if (!roles.length) {
                return h('span', { class: 'text-muted-foreground' }, 'None');
            }
            return h(
                'div',
                { class: 'flex flex-wrap gap-1' },
                roles.map((role) =>
                    h(
                        Badge,
                        { variant: 'secondary' },
                        () => role.name,
                    ),
                ),
            );
        },
    },
    {
        id: 'status',
        accessorFn: (row) => (row.is_active ? 'Active' : 'Inactive'),
        header: 'Status',
        cell: ({ row }) =>
            h(
                Badge,
                { variant: row.original.is_active ? 'secondary' : 'outline' },
                () => (row.original.is_active ? 'Active' : 'Inactive'),
            ),
    },
    {
        id: 'verification',
        accessorFn: (row) => (row.email_verified_at ? 'Verified' : 'Pending'),
        header: 'Verification',
        cell: ({ row }) =>
            h(
                Badge,
                { variant: row.original.email_verified_at ? 'secondary' : 'outline' },
                () => (row.original.email_verified_at ? 'Verified' : 'Pending'),
            ),
    },
    {
        id: 'joined',
        accessorFn: (row) => row.created_at ?? '',
        header: 'Joined',
        cell: ({ row }) => row.original.created_at,
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) =>
            h(Button, { variant: 'outline', size: 'sm', asChild: true }, () =>
                h(Link, { href: usersEdit(row.original).url }, () => 'Edit'),
            ),
        enableSorting: false,
        enableHiding: false,
    },
];
</script>

<template>

    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-8 p-6 lg:p-10">
            <PageHeader title="Users" subtitle="Manage user access, roles, and status." :action-label="'New user'"
                :action-href="usersCreate().url" :action-icon="Plus" :show-filters-toggle="true"
                :filters-visible="filtersVisible" @toggle-filters="filtersVisible = !filtersVisible" />

            <BulkActions :selected-count="selectedUserIds.length" :processing="bulkForm.processing"
                @activate="submitBulkAction('activate')" @deactivate="submitBulkAction('deactivate')" />

            <DataTable :data="data.users.data" :columns="columns" :show-search="false" :show-selection-summary="false"
                :enable-row-selection="false" class="portfolio-table">
                <template v-if="filtersVisible" #filters>
                    <form :action="usersIndex().url" method="get"
                        class="flex flex-wrap items-center gap-3 rounded-xl border border-border/60 bg-card p-4">
                        <div class="relative min-w-[220px] flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input v-model="searchFilter" name="search" class="pl-9"
                                placeholder="Search by name or email" />
                        </div>

                        <input type="hidden" name="role" :value="roleFilter" />
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" class="min-w-[150px] justify-between">
                                    {{ selectedRoleLabel }}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-56">
                                <DropdownMenuLabel>Role</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @click="roleFilter = ''">
                                    All roles
                                </DropdownMenuItem>
                                <DropdownMenuItem v-for="role in roles" :key="role.id" @click="roleFilter = role.name">
                                    {{ role.name }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <input type="hidden" name="status" :value="statusFilter" />
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" class="min-w-[150px] justify-between">
                                    {{ selectedStatusLabel }}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-40">
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

                        <div class="flex items-center gap-2">
                            <Button type="submit" class="whitespace-nowrap">
                                Apply filters
                            </Button>
                            <Button variant="ghost" as-child>
                                <Link :href="usersIndex().url">Reset</Link>
                            </Button>
                        </div>
                    </form>
                </template>
            </DataTable>

            <PaginationLinks :links="data.users.links" />
        </div>
    </AppLayout>
</template>
