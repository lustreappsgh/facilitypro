<script setup lang="ts">
import WorkOrderController from '@/actions/App/Http/Controllers/WorkOrderController';
import CostTracker from '@/components/CostTracker.vue';
import StatusTimeline from '@/components/StatusTimeline.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { show as maintenanceShow } from '@/routes/maintenance';
import { edit, index as workOrdersIndex } from '@/routes/work-orders';
import { index as paymentsIndex } from '@/routes/payments';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { usePermissions } from '@/composables/usePermissions';

interface Vendor {
    id: number;
    name: string;
    email?: string | null;
    phone?: string | null;
}

interface Facility {
    id: number;
    name: string;
}

interface MaintenanceRequest {
    id: number;
    facility?: Facility | null;
}

interface Payment {
    id: number;
    cost?: number | null;
    comments?: string | null;
    status?: string | null;
}

interface WorkOrder {
    id: number;
    status?: string | null;
    assigned_date?: string | null;
    scheduled_date?: string | null;
    completed_date?: string | null;
    estimated_cost?: number | null;
    actual_cost?: number | null;
    vendor?: Vendor | null;
    maintenanceRequest?: MaintenanceRequest | null;
    payment?: Payment | null;
}

interface Props {
    workOrder: WorkOrder;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Work Orders',
        href: workOrdersIndex().url,
    },
    {
        title: `Work Order #${props.workOrder.id}`,
        href: '#',
    },
];

const { can } = usePermissions();

const statusMode = ref<string | null>(null);

const statusSteps = [
    { value: 'assigned', label: 'Assigned' },
    { value: 'in_progress', label: 'In progress' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
];

const statusBadgeClass = (status: string) => {
    if (status === 'completed') {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (status === 'in_progress') {
        return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
    }

    if (status === 'assigned') {
        return 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300';
    }

    if (status === 'cancelled') {
        return 'bg-rose-500/10 text-rose-700 dark:text-rose-300';
    }

    return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
};

const currentStatus = computed(() => props.workOrder.status ?? 'assigned');
const isInProgress = computed(() => currentStatus.value === 'in_progress');

const paymentForm = useForm({
    cost: props.workOrder.payment?.cost ?? 0,
    comments: props.workOrder.payment?.comments ?? '',
});

const paymentStatus = computed(() => props.workOrder.payment?.status ?? 'pending');
const paymentIsEditable = computed(() =>
    ['pending', 'rejected'].includes(paymentStatus.value),
);
const executionUnlocked = computed(() =>
    ['approved', 'paid'].includes(paymentStatus.value),
);

const paymentStatusBadgeClass = (status: string) => {
    if (status === 'paid') {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (status === 'approved') {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (status === 'rejected') {
        return 'bg-rose-500/10 text-rose-700 dark:text-rose-300';
    }

    return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
};
</script>

<template>
    <Head title="Work order" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader title="Work order" subtitle="Review work order status and details.">
                <template #actions>
                    <div class="flex flex-wrap gap-2">
                        <Button variant="outline" as-child>
                            <Link :href="workOrdersIndex().url">Back to list</Link>
                        </Button>
                        <Button
                            v-if="can('work_orders.update') && workOrder.status !== 'completed'"
                            :disabled="isInProgress"
                            :as-child="!isInProgress"
                        >
                            <template v-if="isInProgress">
                                Edit
                            </template>
                            <Link v-else :href="edit(workOrder.id).url">Edit</Link>
                        </Button>
                        <Button variant="secondary" as-child>
                            <Link :href="paymentsIndex().url">View payments</Link>
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Status & timeline</CardTitle>
                        <CardDescription>
                            Track lifecycle and timing.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <StatusTimeline
                            :steps="statusSteps"
                            :current="currentStatus"
                        />
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs uppercase text-muted-foreground">
                                    Assigned date
                                </p>
                                <p class="text-sm font-medium">
                                    {{ workOrder.assigned_date ?? '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-muted-foreground">
                                    Scheduled date
                                </p>
                                <p class="text-sm font-medium">
                                    {{ workOrder.scheduled_date ?? '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-muted-foreground">
                                    Completed date
                                </p>
                                <p class="text-sm font-medium">
                                    {{ workOrder.completed_date ?? '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-muted-foreground">
                                    Status
                                </p>
                                <Badge
                                    variant="secondary"
                                    :class="statusBadgeClass(currentStatus)"
                                >
                                    {{ currentStatus.replace('_', ' ') }}
                                </Badge>
                            </div>
                        </div>

                        <Form
                            v-if="can('work_orders.update')"
                            v-bind="WorkOrderController.update.form(workOrder.id)"
                            class="flex flex-wrap gap-2"
                            v-slot="{ processing, errors }"
                        >
                            <input
                                type="hidden"
                                name="status"
                                :value="statusMode ?? currentStatus"
                            />
                            <Button
                                v-if="currentStatus === 'in_progress'"
                                size="sm"
                                variant="outline"
                                :disabled="processing || !executionUnlocked"
                                @click="statusMode = 'completed'"
                            >
                                Mark completed
                            </Button>
                            <Button
                                v-if="currentStatus === 'in_progress'"
                                size="sm"
                                variant="secondary"
                                :disabled="processing || !executionUnlocked"
                                @click="statusMode = 'cancelled'"
                            >
                                Mark cancelled
                            </Button>
                            <p
                                v-if="errors.status"
                                class="text-sm text-rose-600"
                            >
                                {{ errors.status }}
                            </p>
                            <p
                                v-if="currentStatus === 'assigned' && !executionUnlocked"
                                class="text-sm text-muted-foreground"
                            >
                                Awaiting admin approval to move this work order to in progress.
                            </p>
                            <p
                                v-if="!executionUnlocked"
                                class="text-sm text-muted-foreground"
                            >
                                Approval is required before assigning vendors or updating work order status.
                            </p>
                        </Form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Vendor & request</CardTitle>
                        <CardDescription>
                            Context for coordination and approvals.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-xs uppercase text-muted-foreground">
                                Vendor
                            </p>
                            <p class="text-base font-medium">
                                {{ workOrder.vendor?.name ?? '—' }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ workOrder.vendor?.email ?? '' }}
                                {{ workOrder.vendor?.phone ?? '' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-muted-foreground">
                                Maintenance request
                            </p>
                            <Button
                                v-if="workOrder.maintenanceRequest?.id"
                                variant="link"
                                class="px-0"
                                as-child
                            >
                                <Link
                                    :href="
                                        maintenanceShow(
                                            workOrder.maintenanceRequest,
                                        ).url
                                    "
                                >
                                    Request #{{ workOrder.maintenanceRequest?.id }}
                                </Link>
                            </Button>
                            <p v-else class="text-sm text-muted-foreground">—</p>
                        </div>
                        <CostTracker
                            :estimated="workOrder.estimated_cost"
                            :actual="workOrder.actual_cost"
                        />
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Payment submission</CardTitle>
                    <CardDescription>
                        Set the cost and submit for approval.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <Badge
                            variant="secondary"
                            :class="paymentStatusBadgeClass(paymentStatus)"
                        >
                            {{ paymentStatus.replace('_', ' ') }}
                        </Badge>
                        <p class="text-sm text-muted-foreground">
                            Work order payments are created automatically.
                        </p>
                    </div>
                    <form
                        v-if="can('work_orders.update')"
                        class="grid gap-4 md:grid-cols-3"
                        @submit.prevent="
                            paymentForm.patch(
                                route('work-orders.payment.update', workOrder.id),
                                { preserveScroll: true },
                            )
                        "
                    >
                        <div class="space-y-2 md:col-span-1">
                            <label class="text-xs uppercase text-muted-foreground">
                                Cost
                            </label>
                            <Input
                                v-model.number="paymentForm.cost"
                                name="cost"
                                type="number"
                                min="0"
                                step="1"
                            />
                            <p v-if="paymentForm.errors.cost" class="text-sm text-rose-600">
                                {{ paymentForm.errors.cost }}
                            </p>
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-xs uppercase text-muted-foreground">
                                Comments
                            </label>
                            <textarea
                                v-model="paymentForm.comments"
                                name="comments"
                                rows="2"
                                class="w-full rounded-lg border border-border bg-transparent px-3 py-2 text-sm"
                                placeholder="Add cost context for approval."
                            ></textarea>
                        </div>
                        <div class="md:col-span-3">
                            <Button
                                type="submit"
                                :disabled="
                                    paymentForm.processing ||
                                    !paymentIsEditable ||
                                    Number(paymentForm.cost) <= 0
                                "
                            >
                                {{ paymentStatus === 'rejected' ? 'Resubmit for approval' : 'Submit for approval' }}
                            </Button>
                        </div>
                    </form>
                    <p v-else class="text-sm text-muted-foreground">
                        You do not have permission to submit payment details.
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Updates & notes</CardTitle>
                    <CardDescription>
                        Capture progress notes and vendor updates when available.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        class="flex items-center justify-center rounded-lg border border-dashed border-border p-6 text-sm text-muted-foreground"
                    >
                        Notes and updates will appear here.
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
