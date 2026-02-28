<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as usersIndex, store as usersStore } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface Role {
    id: number;
    name: string;
}

interface Props {
    roles: Role[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: usersIndex().url,
    },
    {
        title: 'Create',
        href: '#',
    },
];

const form = useForm({
    name: '',
    email: '',
    password: '',
    roles: [] as string[],
    is_active: true,
});

const toggleRole = (roleName: string, checked: boolean) => {
    if (checked) {
        if (!form.roles.includes(roleName)) {
            form.roles.push(roleName);
        }
        return;
    }

    form.roles = form.roles.filter((name) => name !== roleName);
};

const submit = () => {
    form.post(usersStore().url);
};
</script>

<template>
    <Head title="Create user" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader title="Create user" subtitle="Add a new user and assign roles." />

            <form class="max-w-3xl space-y-6" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="name">Full name</Label>
                    <Input id="name" v-model="form.name" required />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input id="email" v-model="form.email" type="email" required />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Temporary password (optional)</Label>
                    <Input id="password" v-model="form.password" type="password" />
                    <p class="text-xs text-muted-foreground">
                        Leave blank to auto-generate and force reset at login.
                    </p>
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label>Roles</Label>
                    <div class="grid gap-2 rounded-lg border border-border/60 p-4">
                        <label
                            v-for="role in props.roles"
                            :key="role.id"
                            class="flex items-center gap-3 text-sm"
                        >
                            <Checkbox
                                :model-value="form.roles.includes(role.name)"
                                @update:model-value="(value) => toggleRole(role.name, !!value)"
                            />
                            <span>{{ role.name }}</span>
                        </label>
                    </div>
                    <InputError :message="form.errors.roles" />
                </div>

                <div class="flex items-center gap-3">
                    <Checkbox
                        id="is_active"
                        :model-value="form.is_active"
                        @update:model-value="(value) => (form.is_active = !!value)"
                    />
                    <Label for="is_active">Active account</Label>
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="form.processing">Create user</Button>
                    <Button variant="ghost" as-child>
                        <Link :href="usersIndex().url">Cancel</Link>
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
