<script setup lang="ts">
import CostTracker from '@/components/CostTracker.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import InlineVendorCreateDialog from '@/components/InlineVendorCreateDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { createCurrencyFormatter } from '@/lib/currency';
import { destroy, edit, index as maintenanceIndex } from '@/routes/maintenance';
import { show as paymentShow } from '@/routes/payments';
import { index as vendorsIndex } from '@/routes/vendors';
import {
    bulkCreate as workOrdersBulkCreate,
    show as workOrderShow,
} from '@/routes/work-orders';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import {
    ArrowLeft,
    ClipboardCheck,
    ClipboardList,
    Eye,
    Pencil,
    Trash2,
    UsersRound,
    X,
} from 'lucide-vue-next';
import { computed, h, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

interface FacilityType {
    id: number;
    name: string;
}

interface FacilityManager {
    id: number;
    name: string;
}

interface Facility {
    id: number;
    name: string;
    condition?: string | null;
    facilityType?: FacilityType | null;
    manager?: FacilityManager | null;
}

interface RequestedBy {
    id: number;
    name: string;
}

interface RequestType {
    id: number;
    name: string;
}

interface MaintenanceRequest {
    id: number;
    status: string;
    priority?: 'low' | 'medium' | 'high' | null;
    cost?: number | null;
    description?: string | null;
    rejection_reason?: string | null;
    facility?: Facility | null;
    requestedBy?: RequestedBy | null;
    requested_by?: RequestedBy | null;
    requestType?: RequestType | null;
    request_type?: RequestType | null;
}

interface VendorSummary {
    id: number;
    name: string;
}

interface Vendor {
    id: number;
    name: string;
}

interface WorkOrder {
    id: number;
    status?: string | null;
    scheduled_date?: string | null;
    estimated_cost?: number | null;
    actual_cost?: number | null;
    vendor?: VendorSummary | null;
}

interface Approver {
    id: number;
    name: string;
}

interface PaymentApproval {
    id: number;
    status: string;
    comments?: string | null;
    approver?: Approver | null;
}

interface Payment {
    id: number;
    status?: string | null;
    cost?: number | null;
    amount_payed?: number | null;
    approvals?: PaymentApproval[];
}

interface Props {
    request: MaintenanceRequest;
    workOrders: WorkOrder[];
    payments: Payment[];
    vendors: Vendor[];
    actions?: {
        can_approve?: boolean;
        can_reject?: boolean;
    };
}

const props = defineProps<Props>();
const page = usePage();
const UNASSIGNED_VENDOR = 'unassigned';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Maintenance Requests',
        href: maintenanceIndex().url,
    },
    {
        title: 'Details',
        href: '#',
    },
];

const { can } = usePermissions();
const currentUserId = computed<number | null>(() => {
    const id = page.props.auth?.user?.id;

    return typeof id === 'number' ? id : null;
});
const isOwnRequest = computed(() => {
    const requesterId =
        props.request.requestedBy?.id ?? props.request.requested_by?.id ?? null;

    return currentUserId.value !== null && requesterId === currentUserId.value;
});
const canManageAll = computed(() => can('maintenance.manage_all'));
const canAdminFastTrack = computed(
    () =>
        canManageAll.value &&
        props.actions?.can_approve !== false &&
        !isOwnRequest.value &&
        ['submitted', 'pending'].includes(props.request.status),
);
const canFirstApproval = computed(
    () =>
        can('maintenance.review') &&
        props.actions?.can_approve !== false &&
        !canManageAll.value &&
        !isOwnRequest.value &&
        ['submitted', 'pending'].includes(props.request.status),
);
const canFinalApproval = computed(
    () =>
        canManageAll.value &&
        props.actions?.can_approve !== false &&
        !isOwnRequest.value &&
        ['approved', 'assigned', 'work_order_created'].includes(
            props.request.status,
        ),
);
const canRejectRequest = computed(
    () =>
        props.actions?.can_reject !== false &&
        (canFirstApproval.value || canAdminFastTrack.value || canFinalApproval.value),
);

const approveForm = useForm({
    vendor_id: UNASSIGNED_VENDOR,
    estimated_cost: '',
    scheduled_date: '',
});
const vendorOptions = computed(() => props.vendors);
const rejectOpen = ref(false);
const rejectForm = useForm({
    rejection_reason: '',
});

const defaultEstimatedCost = computed(() => {
    if (props.request.cost === null || props.request.cost === undefined) {
        return '';
    }
    return String(props.request.cost);
});

if (!approveForm.estimated_cost) {
    approveForm.estimated_cost = defaultEstimatedCost.value;
}

const handleVendorCreated = (vendor: Vendor) => {
    router.reload({
        preserveScroll: true,
        preserveState: true,
        only: ['vendors', 'flash'],
        onSuccess: () => {
            approveForm.vendor_id = String(vendor.id);
        },
    });
};

const openRejectDialog = () => {
    rejectForm.reset('rejection_reason');
    rejectForm.clearErrors();
    rejectOpen.value = true;
};

const submitReject = () => {
    rejectForm.post(`/maintenance/${props.request.id}/reject`, {
        preserveScroll: true,
        onSuccess: () => {
            rejectOpen.value = false;
        },
    });
};

const currencyFormat = createCurrencyFormatter();
const canDeleteRequest = computed(
    () =>
        (can('maintenance.update') || can('maintenance_requests.update')) &&
        ['submitted', 'pending'].includes(props.request.status) &&
        props.workOrders.length === 0,
);

const confirmDeleteRequest = () => {
    if (!window.confirm('Delete this request? This action cannot be undone.')) {
        return;
    }

    router.delete(destroy(props.request.id).url, {
        data: {
            redirect_to: maintenanceIndex().url,
        },
        preserveScroll: true,
    });
};

const statusLabel = computed(() => props.request.status.replace(/_/g, ' '));
const hasApprovedRequest = computed(() =>
    [
        'approved',
        'assigned',
        'work_order_created',
        'in_progress',
        'completed_pending_payment',
        'paid',
        'closed',
        'completed',
    ].includes(props.request.status),
);

const statusBadgeClass = (status: string) => {
    if (status === 'paid') {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (status === 'closed' || status === 'completed') {
        return 'bg-slate-500/10 text-slate-700 dark:text-slate-300';
    }

    if (status === 'in_progress') {
        return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
    }

    if (status === 'completed_pending_payment') {
        return 'bg-orange-500/10 text-orange-700 dark:text-orange-300';
    }

    if (status === 'cancelled' || status === 'rejected') {
        return 'bg-rose-500/10 text-rose-700 dark:text-rose-300';
    }

    if (
        status === 'approved' ||
        status === 'assigned' ||
        status === 'work_order_created'
    ) {
        return 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300';
    }

    return 'bg-slate-500/10 text-slate-700 dark:text-slate-300';
};

const priorityBadgeClass = (priority?: string | null) => {
    if (priority === 'high') {
        return 'bg-rose-500/10 text-rose-700 dark:text-rose-300';
    }

    if (priority === 'medium') {
        return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
    }

    return 'bg-slate-500/10 text-slate-700 dark:text-slate-300';
};

const paymentColumns: ColumnDef<Payment>[] = [
    {
        id: 'cost',
        accessorFn: (row) => row.cost ?? 0,
        header: 'Cost',
        cell: ({ row }) =>
            row.original.cost !== null && row.original.cost !== undefined
                ? currencyFormat.format(row.original.cost)
                : '-',
    },
    {
        id: 'amountPaid',
        accessorFn: (row) => row.amount_payed ?? 0,
        header: 'Amount paid',
        cell: ({ row }) =>
            row.original.amount_payed !== null &&
            row.original.amount_payed !== undefined
                ? currencyFormat.format(row.original.amount_payed)
                : '-',
    },
    {
        id: 'status',
        accessorFn: (row) => row.status ?? '',
        header: 'Status',
        cell: ({ row }) =>
            h(
                Badge,
                {
                    variant: 'secondary',
                    class: statusBadgeClass(row.original.status ?? ''),
                },
                () => row.original.status ?? 'unknown',
            ),
    },
    {
        id: 'approvals',
        header: 'Approvals',
        cell: ({ row }) => {
            const approvals = row.original.approvals ?? [];
            if (!approvals.length) {
                return h(
                    'span',
                    { class: 'text-xs text-muted-foreground' },
                    '--',
                );
            }
            return h(
                'div',
                { class: 'space-y-1' },
                approvals.map((approval) =>
                    h(
                        'div',
                        { class: 'text-xs text-muted-foreground' },
                        `${approval.approver?.name ?? 'Manager'}: ${approval.status}`,
                    ),
                ),
            );
        },
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) =>
            h(Tooltip, {}, () => [
                h(TooltipTrigger, { asChild: true }, () =>
                    h(
                        Button,
                        {
                            variant: 'ghost',
                            size: 'icon',
                            class: 'h-8 w-8',
                            asChild: true,
                        },
                        () =>
                            h(
                                Link,
                                {
                                    href: paymentShow(row.original).url,
                                    'aria-label': 'View payment',
                                },
                                () => h(Eye, { class: 'h-4 w-4' }),
                            ),
                    ),
                ),
                h(TooltipContent, { side: 'top' }, () => 'View payment'),
            ]),
        enableSorting: false,
        enableHiding: false,
    },
];

const workOrderColumns: ColumnDef<WorkOrder>[] = [
    {
        id: 'id',
        header: 'Work order',
        cell: ({ row }) => `#${row.original.id}`,
    },
    {
        id: 'vendor',
        header: 'Vendor',
        cell: ({ row }) => row.original.vendor?.name ?? '-',
    },
    {
        id: 'scheduled',
        header: 'Scheduled',
        cell: ({ row }) => row.original.scheduled_date ?? '-',
    },
    {
        id: 'estimated',
        header: 'Est. cost',
        cell: ({ row }) =>
            row.original.estimated_cost !== null &&
            row.original.estimated_cost !== undefined
                ? currencyFormat.format(row.original.estimated_cost)
                : '-',
    },
    {
        id: 'actual',
        header: 'Actual cost',
        cell: ({ row }) =>
            row.original.actual_cost !== null &&
            row.original.actual_cost !== undefined
                ? currencyFormat.format(row.original.actual_cost)
                : '-',
    },
    {
        id: 'status',
        header: 'Status',
        cell: ({ row }) =>
            h(
                Badge,
                {
                    variant: 'secondary',
                    class: statusBadgeClass(row.original.status ?? ''),
                },
                () => row.original.status ?? 'unknown',
            ),
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) =>
            h(Tooltip, {}, () => [
                h(TooltipTrigger, { asChild: true }, () =>
                    h(
                        Button,
                        {
                            variant: 'ghost',
                            size: 'icon',
                            class: 'h-8 w-8',
                            asChild: true,
                        },
                        () =>
                            h(
                                Link,
                                {
                                    href: workOrderShow(row.original.id).url,
                                    'aria-label': 'View work order',
                                },
                                () => h(Eye, { class: 'h-4 w-4' }),
                            ),
                    ),
                ),
                h(TooltipContent, { side: 'top' }, () => 'View work order'),
            ]),
        enableSorting: false,
        enableHiding: false,
    },
];
</script>

<template>
    <Head title="Maintenance request" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <PageHeader
                title="Maintenance request"
                subtitle="Review request context and advance maintenance work."
            >
                <template #actions>
                    <div class="flex flex-wrap items-center gap-2">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button
                                    variant="outline"
                                    size="icon"
                                    class="h-9 w-9"
                                    as-child
                                >
                                    <Link
                                        :href="maintenanceIndex().url"
                                        aria-label="Back to list"
                                    >
                                        <ArrowLeft class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent side="top"
                                >Back to list</TooltipContent
                            >
                        </Tooltip>
                        <form
                            v-if="canFirstApproval || canAdminFastTrack"
                            class="flex flex-wrap items-end gap-2"
                            @submit.prevent="
                                approveForm
                                    .transform((data) => ({
                                        ...data,
                                        vendor_id:
                                            data.vendor_id === UNASSIGNED_VENDOR
                                                ? ''
                                                : data.vendor_id,
                                    }))
                                    .post(`/maintenance/${request.id}/approve`, {
                                        preserveScroll: true,
                                    })
                            "
                        >
                            <div class="min-w-[180px]">
                                <div class="mb-2 flex items-center justify-between gap-2">
                                    <span class="text-xs font-medium text-muted-foreground">Vendor</span>
                                    <InlineVendorCreateDialog @created="handleVendorCreated" />
                                </div>
                                <Select v-model="approveForm.vendor_id">
                                    <SelectTrigger class="h-9">
                                        <SelectValue
                                            placeholder="Assign vendor later"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem :value="UNASSIGNED_VENDOR">
                                            Assign later
                                        </SelectItem>
                                        <SelectItem
                                            v-for="vendor in vendorOptions"
                                            :key="vendor.id"
                                            :value="String(vendor.id)"
                                        >
                                            {{ vendor.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p
                                    v-if="approveForm.errors.vendor_id"
                                    class="mt-1 text-xs text-destructive"
                                >
                                    {{ approveForm.errors.vendor_id }}
                                </p>
                            </div>
                            <div class="min-w-[140px]">
                                <Input
                                    v-model="approveForm.estimated_cost"
                                    type="number"
                                    step="1"
                                    class="h-9"
                                    placeholder="Estimated cost"
                                />
                                <p
                                    v-if="approveForm.errors.estimated_cost"
                                    class="mt-1 text-xs text-destructive"
                                >
                                    {{ approveForm.errors.estimated_cost }}
                                </p>
                            </div>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        :disabled="approveForm.processing"
                                        size="icon"
                                        class="h-9 w-9"
                                        aria-label="Approve request"
                                    >
                                        <ClipboardCheck class="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent side="top"
                                    >Approve request</TooltipContent
                                >
                            </Tooltip>
                            <p
                                v-if="approveForm.errors.status"
                                class="basis-full text-xs text-destructive"
                            >
                                {{ approveForm.errors.status }}
                            </p>
                        </form>
                        <Tooltip v-if="canFinalApproval">
                            <TooltipTrigger as-child>
                                <Button
                                    :disabled="approveForm.processing"
                                    size="icon"
                                    class="h-9 w-9"
                                    as-child
                                >
                                    <Link
                                        :href="`/maintenance/${request.id}/approve`"
                                        method="post"
                                        as="button"
                                        aria-label="Final approve request"
                                    >
                                        <ClipboardCheck class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent side="top"
                                >Final approve request</TooltipContent
                            >
                        </Tooltip>
                        <Tooltip
                            v-if="
                                canRejectRequest
                            "
                        >
                            <TooltipTrigger as-child>
                                <Button
                                    variant="secondary"
                                    size="icon"
                                    class="h-9 w-9 bg-rose-500/10 text-rose-700 hover:bg-rose-500/20"
                                    @click="openRejectDialog"
                                >
                                    <X class="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent side="top"
                                >Reject request</TooltipContent
                            >
                        </Tooltip>
                        <Tooltip
                            v-if="
                                can('work_orders.create') &&
                                workOrders.length === 0 &&
                                [
                                    'submitted',
                                    'pending',
                                    'rejected',
                                    'approved',
                                ].includes(request.status)
                            "
                        >
                            <TooltipTrigger as-child>
                                <Button size="icon" class="h-9 w-9" as-child>
                                    <Link
                                        :href="
                                            workOrdersBulkCreate({
                                                query: {
                                                    request_ids: [request.id],
                                                    intent: 'review',
                                                },
                                            }).url
                                        "
                                        aria-label="Review request"
                                    >
                                        <ClipboardList class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent side="top"
                                >Review request</TooltipContent
                            >
                        </Tooltip>
                        <Tooltip
                            v-else-if="
                                can('work_orders.view') && workOrders.length > 0
                            "
                        >
                            <TooltipTrigger as-child>
                                <Button
                                    variant="outline"
                                    size="icon"
                                    class="h-9 w-9"
                                    as-child
                                >
                                    <Link
                                        :href="
                                            workOrderShow(workOrders[0].id).url
                                        "
                                        aria-label="View work order"
                                    >
                                        <ClipboardList class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent side="top"
                                >View work order</TooltipContent
                            >
                        </Tooltip>
                        <Tooltip
                            v-if="
                                can('maintenance.update') &&
                                ['submitted', 'pending'].includes(
                                    request.status,
                                )
                            "
                        >
                            <TooltipTrigger as-child>
                                <Button
                                    variant="secondary"
                                    size="icon"
                                    class="h-9 w-9"
                                    as-child
                                >
                                    <Link
                                        :href="edit(request).url"
                                        aria-label="Edit request"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent side="top"
                                >Edit request</TooltipContent
                            >
                        </Tooltip>
                        <Tooltip v-if="canDeleteRequest">
                            <TooltipTrigger as-child>
                                <Button
                                    variant="secondary"
                                    size="icon"
                                    class="h-9 w-9 bg-rose-500/10 text-rose-700 hover:bg-rose-500/20"
                                    aria-label="Delete request"
                                    @click="confirmDeleteRequest"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent side="top"
                                >Delete request</TooltipContent
                            >
                        </Tooltip>
                        <Tooltip
                            v-if="
                                can('maintenance.close') &&
                                request.status === 'paid'
                            "
                        >
                            <TooltipTrigger as-child>
                                <Button
                                    variant="secondary"
                                    size="icon"
                                    class="h-9 w-9"
                                    as-child
                                >
                                    <Link
                                        :href="`/maintenance/${request.id}/close`"
                                        method="post"
                                        as="button"
                                        aria-label="Close request"
                                    >
                                        <ClipboardCheck class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent side="top"
                                >Close request</TooltipContent
                            >
                        </Tooltip>
                    </div>
                </template>
            </PageHeader>

            <Card class="overflow-hidden">
                <CardHeader class="border-b border-border/60 bg-muted/30">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <CardTitle>Request details</CardTitle>
                            <CardDescription>
                                Scope, facility, and status summary.
                            </CardDescription>
                        </div>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="vendorsIndex().url">
                                Browse vendors
                            </Link>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="divide-y divide-border/60 text-sm">
                        <div class="grid grid-cols-3 gap-4 px-6 py-4">
                            <div
                                class="text-xs text-muted-foreground uppercase"
                            >
                                Facility
                            </div>
                            <div class="col-span-2 font-semibold">
                                {{ request.facility?.name ?? '-' }}
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-4">
                            <div
                                class="text-xs text-muted-foreground uppercase"
                            >
                                Facility type
                            </div>
                            <div class="col-span-2 font-semibold">
                                {{
                                    request.facility?.facilityType?.name ?? '-'
                                }}
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-4">
                            <div
                                class="text-xs text-muted-foreground uppercase"
                            >
                                Facility manager
                            </div>
                            <div
                                class="col-span-2 flex items-center gap-2 font-semibold"
                            >
                                <UsersRound
                                    class="h-4 w-4 text-muted-foreground"
                                />
                                <span>{{
                                    request.facility?.manager?.name ?? '-'
                                }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-4">
                            <div
                                class="text-xs text-muted-foreground uppercase"
                            >
                                Condition
                            </div>
                            <div class="col-span-2 font-semibold">
                                {{ request.facility?.condition ?? '-' }}
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-4">
                            <div
                                class="text-xs text-muted-foreground uppercase"
                            >
                                Request type
                            </div>
                            <div class="col-span-2 font-semibold">
                                {{
                                    request.requestType?.name ??
                                    request.request_type?.name ??
                                    '-'
                                }}
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-4">
                            <div
                                class="text-xs text-muted-foreground uppercase"
                            >
                                Requested by
                            </div>
                            <div class="col-span-2 font-semibold">
                                {{
                                    request.requestedBy?.name ??
                                    request.requested_by?.name ??
                                    '-'
                                }}
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-4">
                            <div
                                class="text-xs text-muted-foreground uppercase"
                            >
                                Priority
                            </div>
                            <div class="col-span-2">
                                <Badge
                                    variant="secondary"
                                    :class="priorityBadgeClass(request.priority)"
                                >
                                    {{ request.priority ?? 'medium' }}
                                </Badge>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-4">
                            <div
                                class="text-xs text-muted-foreground uppercase"
                            >
                                Status
                            </div>
                            <div class="col-span-2">
                                <Badge
                                    variant="secondary"
                                    :class="statusBadgeClass(request.status)"
                                >
                                    {{ statusLabel }}
                                </Badge>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-4">
                            <div
                                class="text-xs text-muted-foreground uppercase"
                            >
                                Description
                            </div>
                            <div class="col-span-2 whitespace-pre-line text-muted-foreground">
                                {{
                                    request.description ??
                                    'No description provided.'
                                }}
                            </div>
                        </div>
                        <div
                            v-if="request.rejection_reason"
                            class="grid grid-cols-3 gap-4 px-6 py-4"
                        >
                            <div
                                class="text-xs text-muted-foreground uppercase"
                            >
                                Rejection reason
                            </div>
                            <div class="col-span-2 whitespace-pre-line text-muted-foreground">
                                {{ request.rejection_reason }}
                            </div>
                        </div>
                        <div class="px-6 py-4">
                            <CostTracker
                                :estimated="request.cost"
                                :actual="null"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div v-if="hasApprovedRequest" class="grid gap-6 xl:grid-cols-2">
                <Card class="overflow-hidden">
                    <CardHeader class="border-b border-border/60 bg-muted/30">
                        <CardTitle>Linked work orders</CardTitle>
                        <CardDescription>
                            Assignments created from this request.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="p-6">
                        <DataTable
                            :data="workOrders"
                            :columns="workOrderColumns"
                            :show-search="false"
                            :enable-column-toggle="false"
                            :show-selection-summary="false"
                            class="portfolio-table"
                        />
                        <div
                            v-if="!workOrders.length"
                            class="mt-4 rounded-lg border border-dashed border-border/60 px-4 py-6 text-center text-sm text-muted-foreground"
                        >
                            No work orders linked yet.
                        </div>
                    </CardContent>
                </Card>

                <Card class="overflow-hidden">
                    <CardHeader class="border-b border-border/60 bg-muted/30">
                        <CardTitle>Linked payments</CardTitle>
                        <CardDescription>
                            Payment readiness and approvals.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="p-6">
                        <DataTable
                            :data="payments"
                            :columns="paymentColumns"
                            :show-search="false"
                            :enable-column-toggle="false"
                            :show-selection-summary="false"
                            class="portfolio-table"
                        />
                    </CardContent>
                </Card>
            </div>
        </div>

        <Dialog v-model:open="rejectOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Reject request</DialogTitle>
                    <DialogDescription>
                        Provide a reason so the requester can correct and resubmit it.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-2">
                    <label class="text-sm font-medium">Rejection reason</label>
                    <textarea
                        v-model="rejectForm.rejection_reason"
                        rows="4"
                        class="border-input bg-transparent text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] min-h-[112px] w-full rounded-md border px-3 py-2 outline-none"
                        placeholder="Explain why this request is being rejected"
                    />
                    <p
                        v-if="rejectForm.errors.rejection_reason"
                        class="text-sm text-destructive"
                    >
                        {{ rejectForm.errors.rejection_reason }}
                    </p>
                    <p
                        v-if="rejectForm.errors.status"
                        class="text-sm text-destructive"
                    >
                        {{ rejectForm.errors.status }}
                    </p>
                </div>
                <DialogFooter>
                    <Button variant="ghost" @click="rejectOpen = false">
                        Cancel
                    </Button>
                    <Button
                        variant="secondary"
                        :disabled="rejectForm.processing"
                        @click="submitReject"
                    >
                        Confirm rejection
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
