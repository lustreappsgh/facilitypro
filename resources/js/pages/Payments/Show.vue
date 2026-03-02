<script setup lang="ts">
import CostBreakdown from '@/components/CostBreakdown.vue';
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
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { show as maintenanceShow } from '@/routes/maintenance';
import { approve as paymentApprove, index as paymentsIndex, reject as paymentReject } from '@/routes/payments';
import { show as workOrderShow } from '@/routes/work-orders';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';
import { ArrowLeft, Check, Plus, Send, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface Facility {
    id: number;
    name: string;
}

interface RequestType {
    id: number;
    name: string;
}

interface MaintenanceRequest {
    id: number;
    facility?: Facility | null;
    requestType?: RequestType | null;
}

interface Vendor {
    id: number;
    name: string;
}

interface WorkOrder {
    id: number;
    status?: string | null;
    vendor?: Vendor | null;
    scheduled_date?: string | null;
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
    cost?: number | null;
    amount_payed?: number | null;
    status?: string | null;
    comments?: string | null;
    approvals?: PaymentApproval[];
    maintenanceRequest?: MaintenanceRequest | null;
}

interface Props {
    payment: Payment;
    workOrder?: WorkOrder | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Payments',
        href: paymentsIndex().url,
    },
    {
        title: `Payment #${props.payment.id}`,
        href: '#',
    },
];

const { can } = usePermissions();

const approveForm = useForm({
    comments: '',
});

const rejectForm = useForm({
    comments: '',
});

const approveOpen = ref(false);
const rejectOpen = ref(false);

const currencyFormat = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
});

const statusBadgeClass = (status: string) => {
    if (status === 'paid') {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (status === 'approved') {
        return 'bg-sky-500/10 text-sky-700 dark:text-sky-300';
    }

    if (status === 'rejected') {
        return 'bg-rose-500/10 text-rose-700 dark:text-rose-300';
    }

    return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
};

const submitApprove = () => {
    approveForm.post(paymentApprove(props.payment.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            approveOpen.value = false;
        },
    });
};

const submitReject = () => {
    rejectForm.post(paymentReject(props.payment.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            rejectOpen.value = false;
        },
    });
};
</script>

<template>
    <Head title="Payment details" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader :title="`Payment #${payment.id}`" subtitle="Review payment status and approvals.">
                <template #actions>
                    <div class="flex flex-wrap gap-2">
                        <Button variant="outline" size="icon" class="h-9 w-9" as-child>
                            <Link :href="paymentsIndex().url" aria-label="Back to list">
                                <ArrowLeft class="h-4 w-4" />
                            </Link>
                        </Button>
                        <Button
                            v-if="payment.status === 'pending' && can('payments.submit') && !can('payments.approve')"
                            variant="secondary"
                            size="icon"
                            class="h-9 w-9"
                            aria-label="Submit for approval"
                        >
                            <Send class="h-4 w-4" />
                        </Button>
                        <Button
                            v-if="payment.status === 'pending' && can('payments.approve')"
                            size="icon"
                            class="h-9 w-9"
                            @click="approveOpen = true"
                            aria-label="Approve payment"
                        >
                            <Check class="h-4 w-4" />
                        </Button>
                        <Button
                            v-if="payment.status === 'pending' && can('payments.reject')"
                            variant="secondary"
                            size="icon"
                            class="h-9 w-9"
                            @click="rejectOpen = true"
                            aria-label="Reject payment"
                        >
                            <X class="h-4 w-4" />
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Request context</CardTitle>
                        <CardDescription>
                            Linked request and work order information.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-xs uppercase text-muted-foreground">
                                Maintenance request
                            </p>
                            <Button
                                v-if="payment.maintenanceRequest?.id"
                                variant="link"
                                class="px-0"
                                as-child
                            >
                                <Link
                                    :href="
                                        maintenanceShow(
                                            payment.maintenanceRequest,
                                        ).url
                                    "
                                >
                                    Request #{{ payment.maintenanceRequest?.id }}
                                </Link>
                            </Button>
                            <p v-else class="text-sm text-muted-foreground">—</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-muted-foreground">
                                Facility
                            </p>
                            <p class="text-sm font-medium">
                                {{
                                    payment.maintenanceRequest?.facility?.name ??
                                    '—'
                                }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-muted-foreground">
                                Request type
                            </p>
                            <p class="text-sm font-medium">
                                {{
                                    payment.maintenanceRequest?.requestType?.name ??
                                    '—'
                                }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-muted-foreground">
                                Work order
                            </p>
                            <Button
                                v-if="workOrder?.id"
                                variant="link"
                                class="px-0"
                                as-child
                            >
                                <Link :href="workOrderShow(workOrder.id).url">
                                    Work order #{{ workOrder.id }}
                                </Link>
                            </Button>
                            <p v-else class="text-sm text-muted-foreground">
                                No work order linked yet.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Payment status</CardTitle>
                        <CardDescription>
                            Cost fields and approval steps.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-xs uppercase text-muted-foreground">
                                Cost
                            </p>
                            <p class="text-sm font-medium">
                                {{
                                    payment.cost !== null &&
                                    payment.cost !== undefined
                                        ? currencyFormat.format(payment.cost)
                                        : '—'
                                }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-muted-foreground">
                                Amount paid
                            </p>
                            <p class="text-sm font-medium">
                                {{
                                    payment.amount_payed !== null &&
                                    payment.amount_payed !== undefined
                                        ? currencyFormat.format(
                                              payment.amount_payed,
                                          )
                                        : '—'
                                }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-muted-foreground">
                                Status
                            </p>
                            <Badge
                                variant="secondary"
                                :class="statusBadgeClass(payment.status ?? '')"
                            >
                                {{ payment.status ?? 'unknown' }}
                            </Badge>
                        </div>
                        <div v-if="payment.comments">
                            <p class="text-xs uppercase text-muted-foreground">
                                Comments
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ payment.comments }}
                            </p>
                        </div>
                        <Button v-if="can('payments.create')" variant="outline" size="icon" class="h-9 w-9" aria-label="Add payment record">
                            <Plus class="h-4 w-4" />
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <CostBreakdown
                :cost="payment.cost"
                :amount-paid="payment.amount_payed"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Attachments</CardTitle>
                    <CardDescription>
                        Receipts and proof of completion.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        class="flex items-center justify-center rounded-lg border border-dashed border-border p-6 text-sm text-muted-foreground"
                    >
                        Attachments are not yet available for this payment.
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Approval history</CardTitle>
                    <CardDescription>
                        Manager decisions recorded for this payment.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="!payment.approvals?.length"
                        class="text-sm text-muted-foreground"
                    >
                        No approvals recorded yet.
                    </div>
                    <div
                        v-else
                        class="space-y-3"
                    >
                        <div
                            v-for="approval in payment.approvals"
                            :key="approval.id"
                            class="rounded-lg border border-border/70 p-3"
                        >
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium">
                                    {{ approval.approver?.name ?? 'Manager' }}
                                </span>
                                <Badge
                                    variant="secondary"
                                    :class="statusBadgeClass(approval.status)"
                                >
                                    {{ approval.status }}
                                </Badge>
                            </div>
                            <p class="mt-2 text-xs text-muted-foreground">
                                {{ approval.comments ?? 'No comments provided.' }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>

    <Dialog v-model:open="approveOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Approve payment</DialogTitle>
                <DialogDescription>
                    Confirm this approval to move the payment forward.
                </DialogDescription>
            </DialogHeader>
            <div class="space-y-2">
                <label class="text-sm font-medium">Comments (optional)</label>
                <Input
                    v-model="approveForm.comments"
                    placeholder="Add context for the approval"
                />
                <p v-if="approveForm.errors.comments" class="text-sm text-destructive">
                    {{ approveForm.errors.comments }}
                </p>
            </div>
            <DialogFooter>
                <Button variant="ghost" @click="approveOpen = false">
                    Cancel
                </Button>
                <Button :disabled="approveForm.processing" @click="submitApprove">
                    Confirm approval
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="rejectOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Reject payment</DialogTitle>
                <DialogDescription>
                    Provide a reason so the team can address the issue.
                </DialogDescription>
            </DialogHeader>
            <div class="space-y-2">
                <label class="text-sm font-medium">Rejection reason</label>
                <Input
                    v-model="rejectForm.comments"
                    placeholder="Explain why the payment is rejected"
                />
                <p v-if="rejectForm.errors.comments" class="text-sm text-destructive">
                    {{ rejectForm.errors.comments }}
                </p>
            </div>
            <DialogFooter>
                <Button variant="ghost" @click="rejectOpen = false">
                    Cancel
                </Button>
                <Button
                    :disabled="rejectForm.processing"
                    variant="secondary"
                    @click="submitReject"
                >
                    Confirm rejection
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
