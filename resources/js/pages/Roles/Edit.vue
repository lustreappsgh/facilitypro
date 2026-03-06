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

interface Props {
    role: Role;
    permissions: Permission[];
    requestTypes: RequestTypeOption[];
    allowedRequestTypeIds: number[];
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

const requestTypesForm = useForm({
    request_type_ids: props.allowedRequestTypeIds.map((id) => Number(id)),
});

const selectAllRequestTypes = () => {
    requestTypesForm.request_type_ids = props.requestTypes.map((type) => type.id);
};

const clearRequestTypes = () => {
    requestTypesForm.request_type_ids = [];
};

const toggleRequestType = (typeId: number, checked: boolean) => {
    if (checked) {
        if (!requestTypesForm.request_type_ids.includes(typeId)) {
            requestTypesForm.request_type_ids.push(typeId);
        }
        return;
    }

    requestTypesForm.request_type_ids = requestTypesForm.request_type_ids.filter(
        (id) => id !== typeId,
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
                            Request Type Access
                        </h2>
                        <p class="text-xs text-muted-foreground">
                            Control which maintenance request types this role can approve or create work orders for.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                        <span>Selected: {{ requestTypesForm.request_type_ids.length }}</span>
                        <span>Total: {{ props.requestTypes.length }}</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Button
                            type="button"
                            variant="secondary"
                            size="sm"
                            :disabled="requestTypesForm.processing"
                            @click="selectAllRequestTypes"
                        >
                            Select all
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            :disabled="requestTypesForm.processing || requestTypesForm.request_type_ids.length === 0"
                            @click="clearRequestTypes"
                        >
                            Clear
                        </Button>
                    </div>

                    <div class="grid gap-2">
                        <div
                            v-for="requestType in props.requestTypes"
                            :key="requestType.id"
                            class="flex items-center gap-3 rounded-lg border border-border/60 px-3 py-2 text-sm"
                        >
                            <Checkbox
                                :id="`request-type-${requestType.id}`"
                                :model-value="requestTypesForm.request_type_ids.includes(requestType.id)"
                                :disabled="requestTypesForm.processing"
                                @update:modelValue="
                                    (checked) =>
                                        toggleRequestType(
                                            requestType.id,
                                            checked === true,
                                        )
                                "
                            />
                            <label
                                class="flex-1 cursor-pointer"
                                :for="`request-type-${requestType.id}`"
                            >
                                {{ requestType.name }}
                            </label>
                        </div>
                        <InputError :message="requestTypesForm.errors.request_type_ids" />
                    </div>

                    <div class="flex items-center gap-3">
                        <Button
                            type="button"
                            variant="secondary"
                            :disabled="requestTypesForm.processing"
                            @click="submitRequestTypes"
                        >
                            Save request types
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
