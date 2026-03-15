<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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

interface RequestTypeOption {
    id: number;
    name: string;
}

interface RequestTypePermission {
    request_type_id: number;
    can_approve: boolean;
    can_reject: boolean;
}

interface Props {
    role: Role;
    permissions: Permission[];
    requestTypes: RequestTypeOption[];
    requestTypePermissions: RequestTypePermission[];
    routes: Record<string, string>;
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

const permissionMap = new Map(
    props.requestTypePermissions.map((permission) => [
        permission.request_type_id,
        permission,
    ]),
);

const requestTypesForm = useForm({
    request_type_permissions: props.requestTypes.map((requestType) => {
        const existing = permissionMap.get(requestType.id);

        return {
            request_type_id: requestType.id,
            can_approve: existing?.can_approve ?? false,
            can_reject: existing?.can_reject ?? false,
        };
    }),
});

const setPermissionColumn = (
    column: 'can_approve' | 'can_reject',
    checked: boolean,
) => {
    requestTypesForm.request_type_permissions =
        requestTypesForm.request_type_permissions.map((permission) => ({
            ...permission,
            [column]: checked,
        }));
};

const updatePermission = (
    requestTypeId: number,
    column: 'can_approve' | 'can_reject',
    checked: boolean,
) => {
    requestTypesForm.request_type_permissions =
        requestTypesForm.request_type_permissions.map((permission) =>
            permission.request_type_id === requestTypeId
                ? {
                      ...permission,
                      [column]: checked,
                  }
                : permission,
        );
};

const submitRequestTypes = () => {
    requestTypesForm.post(props.routes.updateRequestTypes, {
        preserveScroll: true,
    });
};

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

                <div class="grid gap-4 rounded-xl border border-border/60 bg-card/60 p-5">
                    <div class="space-y-1">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                            Request Type Approval Settings
                        </h2>
                        <p class="text-xs text-muted-foreground">
                            Control which maintenance request types this role can approve or reject.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                        <span>
                            Approve enabled:
                            {{
                                requestTypesForm.request_type_permissions.filter(
                                    (permission) => permission.can_approve,
                                ).length
                            }}
                        </span>
                        <span>
                            Reject enabled:
                            {{
                                requestTypesForm.request_type_permissions.filter(
                                    (permission) => permission.can_reject,
                                ).length
                            }}
                        </span>
                        <span>Total: {{ props.requestTypes.length }}</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Button
                            type="button"
                            variant="secondary"
                            size="sm"
                            :disabled="requestTypesForm.processing"
                            @click="setPermissionColumn('can_approve', true)"
                        >
                            Approve all
                        </Button>
                        <Button
                            type="button"
                            variant="secondary"
                            size="sm"
                            :disabled="requestTypesForm.processing"
                            @click="setPermissionColumn('can_reject', true)"
                        >
                            Reject all
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            :disabled="requestTypesForm.processing"
                            @click="setPermissionColumn('can_approve', false)"
                        >
                            Clear approve
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            :disabled="requestTypesForm.processing"
                            @click="setPermissionColumn('can_reject', false)"
                        >
                            Clear reject
                        </Button>
                    </div>

                    <div class="grid gap-2">
                        <div
                            v-for="requestType in props.requestTypes"
                            :key="requestType.id"
                            class="grid grid-cols-[minmax(0,1fr)_96px_96px] items-center gap-3 rounded-lg border border-border/60 px-3 py-2 text-sm"
                        >
                            <label
                                class="flex-1 cursor-pointer"
                            >
                                {{ requestType.name }}
                            </label>
                            <div class="flex items-center justify-center gap-2">
                                <Checkbox
                                    :id="`request-type-approve-${requestType.id}`"
                                    :model-value="
                                        requestTypesForm.request_type_permissions.find(
                                            (permission) =>
                                                permission.request_type_id ===
                                                requestType.id,
                                        )?.can_approve ?? false
                                    "
                                    :disabled="requestTypesForm.processing"
                                    @update:modelValue="
                                        (checked) =>
                                            updatePermission(
                                                requestType.id,
                                                'can_approve',
                                                checked === true,
                                            )
                                    "
                                />
                                <label
                                    :for="`request-type-approve-${requestType.id}`"
                                >
                                    Approve
                                </label>
                            </div>
                            <div class="flex items-center justify-center gap-2">
                                <Checkbox
                                    :id="`request-type-reject-${requestType.id}`"
                                    :model-value="
                                        requestTypesForm.request_type_permissions.find(
                                            (permission) =>
                                                permission.request_type_id ===
                                                requestType.id,
                                        )?.can_reject ?? false
                                    "
                                    :disabled="requestTypesForm.processing"
                                    @update:modelValue="
                                        (checked) =>
                                            updatePermission(
                                                requestType.id,
                                                'can_reject',
                                                checked === true,
                                            )
                                    "
                                />
                                <label
                                    :for="`request-type-reject-${requestType.id}`"
                                >
                                    Reject
                                </label>
                            </div>
                        </div>
                        <InputError
                            :message="requestTypesForm.errors.request_type_permissions"
                        />
                    </div>

                    <div class="flex items-center gap-3">
                        <Button
                            type="button"
                            variant="secondary"
                            :disabled="requestTypesForm.processing"
                            @click="submitRequestTypes"
                        >
                            Save approval settings
                        </Button>
                    </div>
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
