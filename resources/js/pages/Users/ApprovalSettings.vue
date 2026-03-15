<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface UserSummary {
    id: number;
    name: string;
    email: string;
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
    user: UserSummary;
    requestTypes: RequestTypeOption[];
    requestTypePermissions: RequestTypePermission[];
    routes: {
        index: string;
        edit: string;
        update: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: props.routes.index,
    },
    {
        title: props.user.name,
        href: props.routes.edit,
    },
    {
        title: 'Approval Settings',
        href: '#',
    },
];

const permissionMap = new Map(
    props.requestTypePermissions.map((permission) => [
        permission.request_type_id,
        permission,
    ]),
);

const form = useForm({
    request_type_permissions: props.requestTypes.map((requestType) => {
        const existing = permissionMap.get(requestType.id);

        return {
            request_type_id: requestType.id,
            can_approve: existing?.can_approve ?? true,
            can_reject: existing?.can_reject ?? true,
        };
    }),
});

const selectedApproveCount = computed(
    () =>
        form.request_type_permissions.filter((permission) => permission.can_approve)
            .length,
);
const selectedRejectCount = computed(
    () =>
        form.request_type_permissions.filter((permission) => permission.can_reject)
            .length,
);

const toggleColumn = (
    column: 'can_approve' | 'can_reject',
    checked: boolean,
) => {
    form.request_type_permissions = form.request_type_permissions.map(
        (permission) => ({
            ...permission,
            [column]: checked,
        }),
    );
};

const updatePermission = (
    requestTypeId: number,
    column: 'can_approve' | 'can_reject',
    checked: boolean,
) => {
    form.request_type_permissions = form.request_type_permissions.map(
        (permission) =>
            permission.request_type_id === requestTypeId
                ? {
                      ...permission,
                      [column]: checked,
                  }
                : permission,
    );
};

const submit = () => {
    form.post(props.routes.update, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Manager approval settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader
                title="Manager approval settings"
                :subtitle="`Control which request types ${user.name} can approve or reject.`"
            />

            <div class="rounded-xl border border-border/60 bg-card/60 p-5">
                <div class="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                    <span>Approve enabled: {{ selectedApproveCount }}</span>
                    <span>Reject enabled: {{ selectedRejectCount }}</span>
                    <span>Total request types: {{ requestTypes.length }}</span>
                </div>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <div class="rounded-xl border border-border/60 bg-card/60">
                    <div
                        class="grid grid-cols-[minmax(0,1fr)_120px_120px] items-center gap-4 border-b border-border/60 px-4 py-3"
                    >
                        <div>
                            <h2 class="font-display text-lg font-semibold">
                                Request type permissions
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Visibility remains unchanged. These toggles only
                                control approve and reject actions.
                            </p>
                        </div>
                        <div class="flex items-center justify-center gap-2">
                            <Checkbox
                                id="approve-all"
                                :model-value="
                                    selectedApproveCount === requestTypes.length
                                "
                                @update:modelValue="
                                    (checked) =>
                                        toggleColumn(
                                            'can_approve',
                                            checked === true,
                                        )
                                "
                            />
                            <label
                                for="approve-all"
                                class="text-sm font-medium"
                            >
                                Approve all
                            </label>
                        </div>
                        <div class="flex items-center justify-center gap-2">
                            <Checkbox
                                id="reject-all"
                                :model-value="
                                    selectedRejectCount === requestTypes.length
                                "
                                @update:modelValue="
                                    (checked) =>
                                        toggleColumn(
                                            'can_reject',
                                            checked === true,
                                        )
                                "
                            />
                            <label
                                for="reject-all"
                                class="text-sm font-medium"
                            >
                                Reject all
                            </label>
                        </div>
                    </div>

                    <div class="divide-y divide-border/60">
                        <div
                            v-for="(requestType, index) in requestTypes"
                            :key="requestType.id"
                            class="grid grid-cols-[minmax(0,1fr)_120px_120px] items-center gap-4 px-4 py-3"
                        >
                            <div>
                                <p class="font-medium">{{ requestType.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    Request type #{{ requestType.id }}
                                </p>
                            </div>
                            <div class="flex items-center justify-center">
                                <Checkbox
                                    :id="`approve-${requestType.id}`"
                                    :model-value="
                                        form.request_type_permissions[index]
                                            ?.can_approve ?? false
                                    "
                                    @update:modelValue="
                                        (checked) =>
                                            updatePermission(
                                                requestType.id,
                                                'can_approve',
                                                checked === true,
                                            )
                                    "
                                />
                            </div>
                            <div class="flex items-center justify-center">
                                <Checkbox
                                    :id="`reject-${requestType.id}`"
                                    :model-value="
                                        form.request_type_permissions[index]
                                            ?.can_reject ?? false
                                    "
                                    @update:modelValue="
                                        (checked) =>
                                            updatePermission(
                                                requestType.id,
                                                'can_reject',
                                                checked === true,
                                            )
                                    "
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <InputError :message="form.errors.request_type_permissions" />

                <div class="flex items-center gap-3">
                    <Button :disabled="form.processing">Save settings</Button>
                    <Button variant="ghost" as-child>
                        <Link :href="routes.edit">Back</Link>
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
