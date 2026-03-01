<script setup lang="ts">
import BulkActions from '@/components/Admin/BulkActions.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import DataTable from '@/components/data-table/DataTable.vue';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { create as usersCreate, edit as usersEdit, index as usersIndex } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useInitials } from '@/composables/useInitials';
import { Search, Plus } from 'lucide-vue-next';
import type { ColumnDef } from '@tanstack/vue-table';
import { computed, h, ref } from 'vue';

interface Role {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    profile_photo_url: string;
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
    per_page?: number | null;
}

interface Props {
    data: {
        users: PaginatedUsers;
        pagination: {
            current_page: number;
            last_page: number;
            per_page: number;
            prev_page_url: string | null;
            next_page_url: string | null;
        };
    };
    filters: Filters;
    roles: Role[];
    permissions: string[];
    routes: Record<string, string>;
    meta: Record<string, string>;
}

const props = defineProps<Props>();

const { getInitials } = useInitials();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: usersIndex().url,
    },
];

const searchFilter = ref(props.filters.search ?? '');
const roleFilter = ref(props.filters.role ?? 'all');
const statusFilter = ref(props.filters.status ?? 'all');
const perPage = ref(props.filters.per_page ?? props.data.pagination.per_page ?? 10);

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

const roleQueryValue = computed(() =>
    roleFilter.value === 'all' ? '' : roleFilter.value,
);

const statusQueryValue = computed(() =>
    statusFilter.value === 'all' ? '' : statusFilter.value,
);

const updatePageSize = (pageSize: number) => {
    perPage.value = pageSize;
    router.get(
        usersIndex().url,
        {
            search: props.filters.search ?? undefined,
            role: props.filters.role ?? undefined,
            status: props.filters.status ?? undefined,
            per_page: pageSize,
        },
        { preserveState: true, preserveScroll: true },
    );
};

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
        cell: ({ row }) =>
            h('div', { class: 'flex items-center gap-3' }, [
                h(Avatar, { class: 'h-8 w-8' }, () => [
                    h(AvatarImage, { src: row.original.profile_photo_url, alt: row.original.name }),
                    h(AvatarFallback, { class: 'text-[10px] font-semibold' }, () => getInitials(row.original.name)),
                ]),
                h('span', { class: 'text-[11px] font-semibold text-foreground' }, row.original.name),
            ]),
        enableHiding: false,
    },
    {
        id: 'email',
        accessorFn: (row) => row.email ?? '',
        header: 'Email',
        cell: ({ row }) => h('span', { class: 'text-[11px] text-muted-foreground' }, row.original.email),
    },
    {
        id: 'roles',
        accessorFn: (row) => (row.roles ?? []).map((role) => role.name).join(', '),
        header: 'Roles',
        cell: ({ row }) => {
            const roles = row.original.roles ?? [];
            if (!roles.length) {
                return h('span', { class: 'text-[11px] text-muted-foreground' }, 'None');
            }
            return h(
                'div',
                { class: 'flex flex-wrap gap-1' },
                roles.map((role) =>
                    h(
                        Badge,
                        { variant: 'outline', class: 'rounded-full px-2.5 py-0.5 text-[10px] font-semibold tracking-wide' },
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
                {
                    variant: 'outline',
                    class: `rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider ${
                        row.original.is_active
                            ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20'
                            : 'bg-rose-500/10 text-rose-600 border-rose-500/20'
                    }`,
                },
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
                {
                    variant: 'outline',
                    class: `rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider ${
                        row.original.email_verified_at
                            ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20'
                            : 'bg-amber-500/10 text-amber-600 border-amber-500/20'
                    }`,
                },
                () => (row.original.email_verified_at ? 'Verified' : 'Pending'),
            ),
    },
    {
        id: 'joined',
        accessorFn: (row) => row.created_at ?? '',
        header: 'Joined',
        cell: ({ row }) => h('span', { class: 'text-[11px] text-muted-foreground' }, row.original.created_at),
    },
    {
        id: 'actions',
        header: '',
        cell: ({ row }) =>
            h(Button, { variant: 'outline', size: 'sm', class: 'h-7 px-3 text-[10px] font-bold uppercase', asChild: true }, () =>
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
        <div class="flex h-full flex-col gap-6 p-6 lg:p-8">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="font-display text-3xl font-semibold tracking-tight text-foreground">Users</h1>
                    <p class="text-sm text-muted-foreground">Manage user access, roles, and status.</p>
                </div>
                <Button
                    size="sm"
                    as-child
                    class="h-9 rounded-lg px-3 text-[11px] font-semibold uppercase tracking-wide"
                >
                    <Link :href="usersCreate().url">
                        <Plus class="mr-1.5 h-3.5 w-3.5" />
                        New user
                    </Link>
                </Button>
            </div>

            <div class="rounded-xl border border-border/60 bg-card/60 p-3 backdrop-blur">
                <form
                    :action="usersIndex().url"
                    method="get"
                    class="flex flex-wrap items-center gap-3"
                >
                    <div class="relative min-w-[220px] flex-1">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="searchFilter" name="search" class="pl-9" placeholder="Search by name or email" />
                    </div>

                    <input type="hidden" name="role" :value="roleQueryValue" />
                    <Select v-model="roleFilter">
                        <SelectTrigger class="h-9 min-w-[150px]">
                            <SelectValue placeholder="All roles" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All roles</SelectItem>
                            <SelectItem v-for="role in roles" :key="role.id" :value="role.name">
                                {{ role.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <input type="hidden" name="status" :value="statusQueryValue" />
                    <Select v-model="statusFilter">
                        <SelectTrigger class="h-9 min-w-[150px]">
                            <SelectValue placeholder="All statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="inactive">Inactive</SelectItem>
                        </SelectContent>
                    </Select>

                    <input type="hidden" name="per_page" :value="perPage" />

                    <div class="flex items-center gap-2">
                        <Button type="submit" size="sm" class="h-9 px-4">
                            Apply filters
                        </Button>
                        <Button size="sm" variant="ghost" class="h-9 px-3" as-child>
                            <Link :href="usersIndex().url">Reset</Link>
                        </Button>
                    </div>
                </form>
            </div>

            <BulkActions :selected-count="selectedUserIds.length" :processing="bulkForm.processing"
                @activate="submitBulkAction('activate')" @deactivate="submitBulkAction('deactivate')" />

            <DataTable
                :data="data.users.data"
                :columns="columns"
                :show-search="false"
                :show-selection-summary="false"
                :enable-row-selection="false"
                :server-pagination="{
                    currentPage: data.pagination.current_page,
                    lastPage: data.pagination.last_page,
                    perPage: data.pagination.per_page,
                    prevUrl: data.pagination.prev_page_url,
                    nextUrl: data.pagination.next_page_url,
                    onPageSizeChange: updatePageSize,
                }"
            />
        </div>
    </AppLayout>
</template>
