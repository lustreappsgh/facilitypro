<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import RoleEditor from '@/components/Admin/RoleEditor.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as rolesIndex, update as rolesUpdate } from '@/routes/roles';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface Permission {
    id: number;
    name: string;
}

interface Role {
    id: number;
    name: string;
    permissions?: Permission[];
}

interface Props {
    role: Role;
    permissions: Permission[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roles',
        href: rolesIndex().url,
    },
    {
        title: props.role.name,
        href: '#',
    },
];

const form = useForm({
    name: props.role.name,
    permissions: (props.role.permissions ?? []).map((permission) => permission.name),
});

const submit = () => {
    form.put(rolesUpdate(props.role).url);
};
</script>

<template>
    <Head title="Edit role" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader title="Edit role" subtitle="Update role name and permissions." />

            <form class="max-w-3xl space-y-6" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="name">Role name</Label>
                    <Input id="name" v-model="form.name" required />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label>Permissions</Label>
                    <RoleEditor
                        v-model="form.permissions"
                        :permissions="props.permissions"
                    />
                    <InputError :message="form.errors.permissions" />
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="form.processing">Save changes</Button>
                    <Button variant="ghost" as-child>
                        <Link :href="rolesIndex().url">Cancel</Link>
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
