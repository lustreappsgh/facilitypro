<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as usersIndex, update as usersUpdate } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Role {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    profile_photo_url?: string;
    manager_id?: number | null;
    roles?: (Role | string)[];
}

interface ManagerOption {
    id: number;
    name: string;
    email: string;
    disabled?: boolean;
    note?: string;
}

interface ManagerAssignment {
    manager_id: number | null;
    manager: ManagerOption | null;
    has_maintenance_access: boolean;
    other_direct_reports: number;
    is_facility_manager: boolean;
}

interface Props {
    user: User;
    roles: Role[];
    assignedRoles: string[];
    managerOptions: ManagerOption[];
    managerAssignment: ManagerAssignment;
    reportOptions: ManagerOption[];
    directReportIds: number[];
    routes: Record<string, string>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: usersIndex().url,
    },
    {
        title: props.user.name,
        href: '#',
    },
];

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    roles: props.assignedRoles ?? [],
    is_active: props.user.is_active,
    manager_id: props.managerAssignment.manager_id,
    profile_photo: null as File | null,
});

const accessForm = useForm({});
const reportsForm = useForm({
    report_ids: props.directReportIds.map((id) => Number(id)),
});
const roleDescriptions: Record<string, string> = {
    Admin: 'Full platform administration access.',
    Manager: 'Approvals, oversight, and governance visibility.',
    'Facility Manager': 'Facility-level inspections, requests, and todos.',
    'Maintenance Manager': 'Work-order execution and vendor coordination.',
};
const roleDisplayOrder = [
    'Admin',
    'Manager',
    'Facility Manager',
    'Maintenance Manager',
];
const orderedRoles = computed(() => {
    const orderMap = new Map(
        roleDisplayOrder.map((name, index) => [name, index]),
    );

    return [...props.roles].sort((a, b) => {
        const indexA = orderMap.get(a.name) ?? Number.MAX_SAFE_INTEGER;
        const indexB = orderMap.get(b.name) ?? Number.MAX_SAFE_INTEGER;

        return indexA - indexB;
    });
});

const profilePreviewUrl = ref<string | null>(null);

const initials = computed(() => {
    const trimmed = form.name.trim();
    if (!trimmed) {
        return 'U';
    }

    const parts = trimmed.split(' ').filter(Boolean);
    const letters = parts
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '');

    return letters.join('') || 'U';
});

const activeProfileUrl = computed(
    () => profilePreviewUrl.value || props.user.profile_photo_url || null,
);

const updateProfilePreview = (file: File | null) => {
    if (profilePreviewUrl.value) {
        URL.revokeObjectURL(profilePreviewUrl.value);
    }

    profilePreviewUrl.value = file ? URL.createObjectURL(file) : null;
};

const handleProfilePhotoChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const [file] = input.files ?? [];

    form.profile_photo = file ?? null;
    updateProfilePreview(file ?? null);
};

const assignedManagerSelection = computed(() =>
    props.managerAssignment.manager_id
        ? String(props.managerAssignment.manager_id)
        : 'none',
);

const managerSelection = ref(assignedManagerSelection.value);

watch(managerSelection, (value) => {
    form.manager_id = value === 'none' ? null : Number(value);
});

watch(assignedManagerSelection, (value) => {
    managerSelection.value = value;
    form.manager_id = value === 'none' ? null : Number(value);
});

watch(
    () => props.directReportIds,
    (value) => {
        reportsForm.report_ids = value.map((id) => Number(id));
    },
);

watch(
    () => props.assignedRoles,
    (value) => {
        form.roles = [...value];
    },
);

const hasPendingManagerChange = computed(
    () => managerSelection.value !== assignedManagerSelection.value,
);

const hasAssignedManager = computed(
    () => assignedManagerSelection.value !== 'none',
);

const hasAssignedReports = computed(() => props.directReportIds.length > 0);
const isManagerRoleSelected = computed(() => form.roles.includes('Manager'));
const isFacilityManagerRoleSelected = computed(() =>
    form.roles.includes('Facility Manager'),
);
const eligibleManagerCount = computed(
    () => props.managerOptions.filter((manager) => !manager.disabled).length,
);
const reportOptionCount = computed(() => props.reportOptions.length);
const selectedReportCount = computed(() => reportsForm.report_ids.length);

const canGrantAccess = computed(
    () => hasAssignedManager.value && !hasPendingManagerChange.value,
);

const canRevokeAccess = computed(
    () =>
        hasAssignedManager.value &&
        !hasPendingManagerChange.value &&
        props.managerAssignment.other_direct_reports === 0,
);
const canUpdateMaintenanceAccess = computed(() =>
    props.managerAssignment.has_maintenance_access
        ? canRevokeAccess.value
        : canGrantAccess.value,
);
const maintenanceAccessActionLabel = computed(() =>
    props.managerAssignment.has_maintenance_access
        ? 'Disable maintenance access'
        : 'Enable maintenance access',
);

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
    form.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(usersUpdate(props.user).url, { forceFormData: true });
};

const grantAccess = () => {
    if (!canGrantAccess.value) {
        return;
    }
    accessForm.post(props.routes.grantManagerAccess, {
        preserveScroll: true,
    });
};

const revokeAccess = () => {
    if (!canRevokeAccess.value) {
        return;
    }
    accessForm.post(props.routes.revokeManagerAccess, {
        preserveScroll: true,
    });
};

const toggleMaintenanceAccess = () => {
    if (props.managerAssignment.has_maintenance_access) {
        revokeAccess();
        return;
    }

    grantAccess();
};

const selectAllReports = () => {
    reportsForm.report_ids = props.reportOptions.map((report) => report.id);
};

const clearReports = () => {
    reportsForm.report_ids = [];
};

const toggleReport = (userId: number, checked: boolean) => {
    if (checked) {
        if (!reportsForm.report_ids.includes(userId)) {
            reportsForm.report_ids.push(userId);
        }
        return;
    }

    reportsForm.report_ids = reportsForm.report_ids.filter(
        (id) => id !== userId,
    );
};

const submitReports = () => {
    reportsForm.post(props.routes.updateDirectReports, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Edit user" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader
                title="Edit user"
                subtitle="Update profile details and access controls."
            />

            <form class="max-w-3xl space-y-6" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="name">Full name</Label>
                    <Input id="name" v-model="form.name" required />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Reset password (optional)</Label>
                    <Input
                        id="password"
                        v-model="form.password"
                        type="password"
                    />
                    <p class="text-xs text-muted-foreground">
                        Leave blank to keep the existing password.
                    </p>
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-3">
                    <Label for="profile_photo">Profile photo</Label>
                    <div
                        class="flex flex-wrap items-center gap-4 rounded-lg border border-border/60 p-4"
                    >
                        <Avatar class="h-16 w-16">
                            <AvatarImage
                                v-if="activeProfileUrl"
                                :src="activeProfileUrl"
                                :alt="form.name || 'User photo'"
                            />
                            <AvatarFallback class="text-lg font-semibold">
                                {{ initials }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="flex-1 space-y-2">
                            <Input
                                id="profile_photo"
                                type="file"
                                accept="image/*"
                                @change="handleProfilePhotoChange"
                            />
                            <p class="text-xs text-muted-foreground">
                                PNG, JPG, or WEBP up to 2MB.
                            </p>
                        </div>
                    </div>
                    <InputError :message="form.errors.profile_photo" />
                </div>

                <div class="grid gap-2">
                    <Label>Roles</Label>
                    <div
                        class="grid gap-2 rounded-lg border border-border/60 p-4"
                    >
                        <label
                            v-for="role in orderedRoles"
                            :key="role.id"
                            class="flex items-start gap-3 text-sm"
                        >
                            <Checkbox
                                :model-value="form.roles.includes(role.name)"
                                @update:modelValue="
                                    (checked) =>
                                        toggleRole(role.name, checked === true)
                                "
                            />
                            <span class="space-y-0.5">
                                <span class="block font-medium">{{
                                    role.name
                                }}</span>
                                <span
                                    class="block text-xs text-muted-foreground"
                                >
                                    {{
                                        roleDescriptions[role.name] ??
                                        'Access role.'
                                    }}
                                </span>
                            </span>
                        </label>
                    </div>
                    <InputError :message="form.errors.roles" />
                </div>

                <div
                    v-if="isManagerRoleSelected || hasAssignedReports"
                    class="grid gap-4 rounded-xl border border-border/60 bg-card/60 p-5"
                >
                    <div class="space-y-1">
                        <h2
                            class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Direct Reports
                        </h2>
                        <p class="text-xs text-muted-foreground">
                            Assign facility managers to report directly to this
                            user.
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Selected: {{ selectedReportCount }} of
                            {{ reportOptionCount }}
                        </p>
                        <p
                            v-if="hasAssignedManager"
                            class="text-xs text-amber-600"
                        >
                            Remove the supervising manager before adding new
                            direct reports.
                        </p>
                        <p
                            v-if="!isManagerRoleSelected"
                            class="text-xs text-amber-600"
                        >
                            Add the Manager role to manage direct reports.
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <Label>Facility Managers</Label>
                            <div class="flex items-center gap-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-[11px]"
                                    :disabled="
                                        !isManagerRoleSelected ||
                                        reportOptionCount === 0
                                    "
                                    @click="selectAllReports"
                                >
                                    Select all
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-[11px]"
                                    :disabled="
                                        !isManagerRoleSelected ||
                                        selectedReportCount === 0
                                    "
                                    @click="clearReports"
                                >
                                    Clear
                                </Button>
                            </div>
                        </div>
                        <div
                            class="max-h-60 space-y-2 overflow-y-auto rounded-lg border border-border/60 p-4"
                        >
                            <div
                                v-if="props.reportOptions.length === 0"
                                class="text-xs text-muted-foreground"
                            >
                                No facility managers available for assignment.
                            </div>
                            <label
                                v-for="report in props.reportOptions"
                                :key="report.id"
                                class="flex items-center gap-3 text-sm"
                            >
                                <Checkbox
                                    :model-value="
                                        reportsForm.report_ids.includes(
                                            report.id,
                                        )
                                    "
                                    :disabled="
                                        !isManagerRoleSelected ||
                                        (hasAssignedManager &&
                                            !reportsForm.report_ids.includes(
                                                report.id,
                                            ))
                                    "
                                    @update:modelValue="
                                        (checked) =>
                                            toggleReport(
                                                report.id,
                                                checked === true,
                                            )
                                    "
                                />
                                <span
                                    >{{ report.name }} ({{
                                        report.email
                                    }})</span
                                >
                            </label>
                        </div>
                        <InputError :message="reportsForm.errors.report_ids" />
                    </div>

                    <div class="flex items-center gap-3">
                        <Button
                            type="button"
                            variant="secondary"
                            :disabled="
                                reportsForm.processing || !isManagerRoleSelected
                            "
                            @click="submitReports"
                        >
                            Save direct reports
                        </Button>
                    </div>
                </div>

                <div
                    v-if="isFacilityManagerRoleSelected || hasAssignedManager"
                    class="grid gap-4 rounded-xl border border-border/60 bg-card/60 p-5"
                >
                    <div class="space-y-1">
                        <h2
                            class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Facility Manager Supervisor
                        </h2>
                        <p class="text-xs text-muted-foreground">
                            Assign a supervising manager and explicitly grant or
                            revoke maintenance access.
                        </p>
                        <p
                            v-if="hasAssignedReports"
                            class="text-xs text-amber-600"
                        >
                            Clear direct reports before assigning a supervising
                            manager.
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label>Manager</Label>
                        <Select v-model="managerSelection">
                            <SelectTrigger class="h-10">
                                <SelectValue placeholder="Unassigned" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">Unassigned</SelectItem>
                                <SelectItem
                                    v-for="manager in props.managerOptions"
                                    :key="manager.id"
                                    :value="String(manager.id)"
                                    :disabled="
                                        manager.disabled || hasAssignedReports
                                    "
                                >
                                    {{ manager.name }} ({{ manager.email }})
                                    <span
                                        v-if="manager.note"
                                        class="text-xs text-muted-foreground"
                                    >
                                        - {{ manager.note }}
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="
                                eligibleManagerCount === 0 &&
                                !hasAssignedReports
                            "
                            class="text-xs text-muted-foreground"
                        >
                            No eligible supervisors available.
                        </p>
                        <p
                            v-if="hasAssignedReports"
                            class="text-xs text-amber-600"
                        >
                            Supervisors are disabled while direct reports are
                            assigned.
                        </p>
                        <InputError
                            :message="
                                form.errors.manager_id ||
                                accessForm.errors.manager_id
                            "
                        />
                    </div>

                    <div
                        class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground"
                    >
                        <span>
                            Maintenance access:
                            <strong class="text-foreground">
                                {{
                                    props.managerAssignment
                                        .has_maintenance_access
                                        ? 'Enabled'
                                        : 'Not granted'
                                }}
                            </strong>
                        </span>
                        <span
                            v-if="props.managerAssignment.other_direct_reports"
                        >
                            Other direct reports:
                            {{ props.managerAssignment.other_direct_reports }}
                        </span>
                    </div>

                    <p
                        v-if="hasPendingManagerChange"
                        class="text-xs text-amber-600"
                    >
                        Save the manager assignment before updating access.
                    </p>

                    <div class="flex flex-wrap items-center gap-3">
                        <Button
                            type="button"
                            variant="secondary"
                            :disabled="
                                accessForm.processing ||
                                !canUpdateMaintenanceAccess
                            "
                            @click="toggleMaintenanceAccess"
                        >
                            {{ maintenanceAccessActionLabel }}
                        </Button>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Checkbox
                        id="is_active"
                        :model-value="form.is_active"
                        @update:modelValue="
                            (checked) => (form.is_active = checked === true)
                        "
                    />
                    <Label for="is_active">Active account</Label>
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="form.processing">Save changes</Button>
                    <Button variant="ghost" as-child>
                        <Link :href="usersIndex().url">Cancel</Link>
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
