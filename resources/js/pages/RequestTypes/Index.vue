<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
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
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    destroy as requestTypesDestroy,
    index as requestTypesIndex,
    store as requestTypesStore,
    update as requestTypesUpdate,
} from '@/routes/request-types';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import type { ColumnDef } from '@tanstack/vue-table';
import { h, ref } from 'vue';

const filtersVisible = ref(false);

interface RequestType {
    id: number;
    name: string;
    maintenance_requests_count?: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedRequestTypes {
    data: RequestType[];
    links: PaginationLink[];
}

interface Filters {
    search?: string | null;
}

interface Props {
    data: {
        requestTypes: PaginatedRequestTypes;
    };
    filters: Filters;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Request Types',
        href: requestTypesIndex().url,
    },
];

const searchFilter = ref(props.filters.search ?? '');

const createForm = useForm({
    name: '',
});

const updateForm = useForm({
    name: '',
});

const deleteForm = useForm({});
const typeToDelete = ref<RequestType | null>(null);

const editNames = ref<Record<number, string>>({});
props.data.requestTypes.data.forEach((type) => {
    editNames.value[type.id] = type.name;
});

const submitCreate = () => {
    createForm.post(requestTypesStore().url, {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
        },
    });
};

const submitUpdate = (type: RequestType) => {
    updateForm.name = editNames.value[type.id] ?? '';
    updateForm.put(requestTypesUpdate(type).url, {
        preserveScroll: true,
    });
};

const submitDelete = (type: RequestType) => {
    typeToDelete.value = type;
};

const closeDelete = () => {
    typeToDelete.value = null;
};

const handleDialogChange = (open: boolean) => {
    if (!open) {
        closeDelete();
    }
};

const confirmDelete = () => {
    if (!typeToDelete.value) {
        return;
    }

    deleteForm.delete(requestTypesDestroy(typeToDelete.value).url, {
        preserveScroll: true,
        onFinish: () => {
            typeToDelete.value = null;
        },
    });
};

const columns: ColumnDef<RequestType>[] = [
    {
        id: 'name',
        accessorFn: (row) => row.name ?? '',
        header: 'Name',
        cell: ({ row }) =>
            h(Input, {
                modelValue: editNames.value[row.original.id] ?? '',
                'onUpdate:modelValue': (value: string) => {
                    editNames.value[row.original.id] = value;
                },
                class: 'max-w-[260px]',
            }),
        enableHiding: false,
    },
    {
        id: 'requests',
        accessorFn: (row) => row.maintenance_requests_count ?? 0,
        header: 'Requests',
        cell: ({ row }) => String(row.original.maintenance_requests_count ?? 0),
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) =>
            h('div', { class: 'flex flex-wrap gap-2' }, [
                h(
                    Button,
                    {
                        size: 'sm',
                        variant: 'outline',
                        disabled: updateForm.processing,
                        onClick: () => submitUpdate(row.original),
                    },
                    () => 'Update',
                ),
                h(
                    Button,
                    {
                        size: 'sm',
                        variant: 'ghost',
                        disabled: deleteForm.processing,
                        onClick: () => submitDelete(row.original),
                    },
                    () => 'Delete',
                ),
            ]),
        enableSorting: false,
        enableHiding: false,
    },
];
</script>

<template>

    <Head title="Request types" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-8 p-6 lg:p-10">
            <PageHeader title="Request types" subtitle="Configure types of maintenance requests."
                :show-filters-toggle="true" :filters-visible="filtersVisible"
                @toggle-filters="filtersVisible = !filtersVisible" />

            <div class="rounded-xl border border-border/60 bg-card p-4">
                <form class="flex flex-wrap items-end gap-3" @submit.prevent="submitCreate">
                    <div class="grid flex-1 gap-2">
                        <Label for="name">New request type</Label>
                        <Input id="name" v-model="createForm.name" placeholder="Electrical" />
                        <InputError :message="createForm.errors.name" />
                    </div>
                    <Button :disabled="createForm.processing">Add</Button>
                </form>
            </div>
            <DataTable :data="data.requestTypes.data" :columns="columns" :show-search="false"
                :show-selection-summary="false" class="portfolio-table">
                <template v-if="filtersVisible" #filters>
                    <form :action="requestTypesIndex().url" method="get"
                        class="flex flex-wrap items-center gap-3 rounded-xl border border-border/60 bg-card p-4">
                        <div class="relative min-w-[220px] flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input v-model="searchFilter" name="search" class="pl-9"
                                placeholder="Search request types" />
                        </div>
                        <div class="flex items-center gap-2">
                            <Button type="submit" class="whitespace-nowrap">
                                Apply filters
                            </Button>
                            <Button variant="ghost" as-child>
                                <Link :href="requestTypesIndex().url">Reset</Link>
                            </Button>
                        </div>
                    </form>
                </template>
            </DataTable>

            <PaginationLinks :links="data.requestTypes.links" />
        </div>

        <Dialog :open="Boolean(typeToDelete)" @update:open="handleDialogChange">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete request type</DialogTitle>
                    <DialogDescription>
                        Request types in use cannot be deleted.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="ghost" @click="closeDelete">Cancel</Button>
                    <Button variant="destructive" :disabled="deleteForm.processing" @click="confirmDelete">
                        Confirm delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
