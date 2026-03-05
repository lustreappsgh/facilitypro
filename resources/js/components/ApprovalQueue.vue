<script setup lang="ts">
import { DatePicker } from '@/components/ui/date-picker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    approve as paymentApprove,
    bulkApprove as paymentBulkApprove,
    bulkReject as paymentBulkReject,
    reject as paymentReject,
    show as paymentShow,
} from '@/routes/payments';
import { show as maintenanceShow } from '@/routes/maintenance';
import { Link, useForm } from '@inertiajs/vue3';
import { Check, Eye, Search, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

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
    request_type?: RequestType | null;
}

interface Payment {
    id: number;
    cost?: number | null;
    status?: string | null;
    created_at?: string | null;
    maintenanceRequest?: MaintenanceRequest | null;
    maintenance_request?: MaintenanceRequest | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedPayments {
    data: Payment[];
    links: PaginationLink[];
}

interface Filters {
    search?: string | null;
    status?: string | null;
    facility?: string | null;
    start_date?: string | null;
    end_date?: string | null;
    min_amount?: string | null;
    max_amount?: string | null;
}

interface Props {
    payments: PaginatedPayments;
    facilities: Facility[];
    filters: Filters;
}

const props = defineProps<Props>();
const paymentApprovalsUrl = '/payment-approvals';

const statusFilter = ref(props.filters.status ?? 'pending');
const facilityFilter = ref(props.filters.facility ?? '');
const startDateFilter = ref(props.filters.start_date ?? '');
const endDateFilter = ref(props.filters.end_date ?? '');
const minAmountFilter = ref(props.filters.min_amount ?? '');
const maxAmountFilter = ref(props.filters.max_amount ?? '');
const searchFilter = ref(props.filters.search ?? '');

const selectedFacilityLabel = computed(() => {
    const match = props.facilities.find(
        (facility) => String(facility.id) === facilityFilter.value,
    );
    return match?.name ?? 'All facilities';
});

const selectedStatusLabel = computed(() => {
    if (!statusFilter.value || statusFilter.value === 'all') {
        return 'All statuses';
    }

    return statusFilter.value;
});

const approveForm = useForm({
    comments: '',
});

const rejectForm = useForm({
    comments: '',
});

const bulkApproveForm = useForm({
    payment_ids: [] as number[],
    comments: '',
});

const bulkRejectForm = useForm({
    payment_ids: [] as number[],
    comments: '',
});

const approvalPayment = ref<Payment | null>(null);
const rejectionPayment = ref<Payment | null>(null);
const approveOpen = ref(false);
const rejectOpen = ref(false);
const bulkApproveOpen = ref(false);
const bulkRejectOpen = ref(false);
const selectedPaymentIds = ref<number[]>([]);

const currencyFormat = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
});

const statusBadgeClass = (status: string) => {
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

const openApprove = (payment: Payment) => {
    approvalPayment.value = payment;
    approveForm.reset('comments');
    approveOpen.value = true;
};

const openReject = (payment: Payment) => {
    rejectionPayment.value = payment;
    rejectForm.reset('comments');
    rejectOpen.value = true;
};

const paymentRequest = (payment: Payment) =>
    payment.maintenanceRequest ?? payment.maintenance_request ?? null;

const pendingPayments = computed(() =>
    props.payments.data.filter((payment) => payment.status === 'pending'),
);

const pendingPaymentIds = computed(() => pendingPayments.value.map((payment) => payment.id));

const selectedPendingCount = computed(() => selectedPaymentIds.value.length);

const allPendingSelected = computed(() => {
    if (!pendingPaymentIds.value.length) {
        return false;
    }

    return pendingPaymentIds.value.every((id) => selectedPaymentIds.value.includes(id));
});

const somePendingSelected = computed(() =>
    selectedPaymentIds.value.length > 0 && !allPendingSelected.value,
);

const isSelected = (paymentId: number) => selectedPaymentIds.value.includes(paymentId);

const togglePaymentSelection = (paymentId: number, checked: boolean) => {
    if (checked) {
        if (!selectedPaymentIds.value.includes(paymentId)) {
            selectedPaymentIds.value = [...selectedPaymentIds.value, paymentId];
        }
        return;
    }

    selectedPaymentIds.value = selectedPaymentIds.value.filter((id) => id !== paymentId);
};

const toggleSelectAllPending = (checked: boolean) => {
    if (checked) {
        selectedPaymentIds.value = [...pendingPaymentIds.value];
        return;
    }

    selectedPaymentIds.value = [];
};

const openBulkApprove = () => {
    bulkApproveForm.reset('comments');
    bulkApproveForm.clearErrors();
    bulkApproveOpen.value = true;
};

const openBulkReject = () => {
    bulkRejectForm.reset('comments');
    bulkRejectForm.clearErrors();
    bulkRejectOpen.value = true;
};

const submitApprove = () => {
    if (!approvalPayment.value) {
        return;
    }

    approveForm.post(paymentApprove(approvalPayment.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            approveOpen.value = false;
        },
    });
};

const submitReject = () => {
    if (!rejectionPayment.value) {
        return;
    }

    rejectForm.post(paymentReject(rejectionPayment.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            rejectOpen.value = false;
        },
    });
};

const submitBulkApprove = () => {
    if (!selectedPendingCount.value) {
        return;
    }

    bulkApproveForm.payment_ids = [...selectedPaymentIds.value];
    bulkApproveForm.post(paymentBulkApprove().url, {
        preserveScroll: true,
        onSuccess: () => {
            bulkApproveOpen.value = false;
            selectedPaymentIds.value = [];
        },
    });
};

const submitBulkReject = () => {
    if (!selectedPendingCount.value) {
        return;
    }

    bulkRejectForm.payment_ids = [...selectedPaymentIds.value];
    bulkRejectForm.post(paymentBulkReject().url, {
        preserveScroll: true,
        onSuccess: () => {
            bulkRejectOpen.value = false;
            selectedPaymentIds.value = [];
        },
    });
};

watch(
    () => props.payments.data,
    () => {
        selectedPaymentIds.value = selectedPaymentIds.value.filter((id) =>
            pendingPaymentIds.value.includes(id),
        );
    },
);
</script>

<template>
    <div class="space-y-4">
        <form
            :action="paymentApprovalsUrl"
            method="get"
            class="flex flex-wrap items-center gap-3 rounded-xl border border-border/60 bg-card p-4"
        >
            <div class="relative min-w-[220px] flex-1">
                <Search
                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="searchFilter"
                    name="search"
                    class="pl-9"
                    placeholder="Search request or facility"
                />
            </div>

            <input type="hidden" name="status" :value="statusFilter" />
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" class="min-w-[150px] justify-between">
                        {{ selectedStatusLabel }}
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-56">
                    <DropdownMenuLabel>Status</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem @click="statusFilter = 'pending'">
                        Pending
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="statusFilter = 'approved'">
                        Approved
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="statusFilter = 'paid'">
                        Paid
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="statusFilter = 'rejected'">
                        Rejected
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="statusFilter = 'all'">
                        All statuses
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <input type="hidden" name="facility" :value="facilityFilter" />
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" class="min-w-[170px] justify-between">
                        {{ selectedFacilityLabel }}
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-64">
                    <DropdownMenuLabel>Facility</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem @click="facilityFilter = ''">
                        All facilities
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        v-for="facility in facilities"
                        :key="facility.id"
                        @click="facilityFilter = String(facility.id)"
                    >
                        {{ facility.name }}
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <DatePicker
                v-model="startDateFilter"
                name="start_date"
                class="min-w-[150px]"
             />
            <DatePicker
                v-model="endDateFilter"
                name="end_date"
                class="min-w-[150px]"
             />

            <Input
                v-model="minAmountFilter"
                name="min_amount"
                type="number"
                class="min-w-[140px]"
                placeholder="Min amount"
            />
            <Input
                v-model="maxAmountFilter"
                name="max_amount"
                type="number"
                class="min-w-[140px]"
                placeholder="Max amount"
            />

            <div class="flex items-center gap-2">
                <Button type="submit">Apply filters</Button>
                    <Button variant="ghost" as-child>
                        <Link :href="paymentApprovalsUrl">Reset</Link>
                    </Button>
            </div>
        </form>

        <div
            v-if="pendingPaymentIds.length > 0"
            class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-border/50 bg-muted/20 px-3 py-2"
        >
            <div class="flex items-center gap-3 text-xs text-muted-foreground">
                <div class="flex items-center gap-2">
                    <Checkbox
                        :model-value="allPendingSelected || (somePendingSelected && 'indeterminate')"
                        :aria-label="'Select all pending payments'"
                        @update:model-value="(value) => toggleSelectAllPending(value === true)"
                    />
                    <span>Select all pending</span>
                </div>
                <span>{{ selectedPendingCount }} selected</span>
            </div>
            <div class="flex items-center gap-2">
                <Button
                    size="sm"
                    class="h-8 px-3 text-xs"
                    :disabled="selectedPendingCount === 0"
                    @click="openBulkApprove"
                >
                    Approve selected
                </Button>
                <Button
                    size="sm"
                    variant="secondary"
                    class="h-8 px-3 text-xs"
                    :disabled="selectedPendingCount === 0"
                    @click="openBulkReject"
                >
                    Reject selected
                </Button>
            </div>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-background">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-10" />
                        <TableHead>Request</TableHead>
                        <TableHead>Facility</TableHead>
                        <TableHead>Cost</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Created</TableHead>
                        <TableHead>Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="payment in payments.data" :key="payment.id">
                        <TableCell>
                            <Checkbox
                                :model-value="isSelected(payment.id)"
                                :disabled="payment.status !== 'pending'"
                                :aria-label="`Select payment ${payment.id}`"
                                @update:model-value="(value) => togglePaymentSelection(payment.id, value === true)"
                            />
                        </TableCell>
                        <TableCell>
                            <Button
                                v-if="paymentRequest(payment)?.id"
                                variant="link"
                                class="px-0"
                                as-child
                            >
                                <Link
                                    :href="
                                        maintenanceShow(
                                            paymentRequest(payment)!,
                                        ).url
                                    "
                                >
                                    Request #{{ paymentRequest(payment)?.id }}
                                </Link>
                            </Button>
                            <span v-else>--</span>
                        </TableCell>
                        <TableCell>
                            {{ paymentRequest(payment)?.facility?.name ?? '--' }}
                        </TableCell>
                        <TableCell>
                            {{
                                payment.cost !== null && payment.cost !== undefined
                                    ? currencyFormat.format(payment.cost)
                                    : '--'
                            }}
                        </TableCell>
                        <TableCell>
                            <Badge
                                variant="secondary"
                                :class="statusBadgeClass(payment.status ?? '')"
                            >
                                {{ payment.status ?? 'unknown' }}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            {{ payment.created_at ?? '--' }}
                        </TableCell>
                        <TableCell>
                            <div class="flex items-center gap-1">
                                <Button variant="ghost" size="icon" class="h-8 w-8" as-child>
                                    <Link :href="paymentShow(payment.id).url" aria-label="View payment">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button
                                    v-if="payment.status === 'pending'"
                                    size="icon"
                                    class="h-8 w-8"
                                    aria-label="Approve payment"
                                    @click="openApprove(payment)"
                                >
                                    <Check class="h-4 w-4" />
                                </Button>
                                <Button
                                    v-if="payment.status === 'pending'"
                                    size="icon"
                                    variant="secondary"
                                    class="h-8 w-8 text-rose-600 hover:text-rose-700"
                                    aria-label="Reject payment"
                                    @click="openReject(payment)"
                                >
                                    <X class="h-4 w-4" />
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="!payments.data.length">
                        <TableCell
                            colspan="7"
                            class="py-8 text-center text-sm text-muted-foreground"
                        >
                            No payments match these filters.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

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

        <Dialog v-model:open="bulkApproveOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Approve selected payments</DialogTitle>
                    <DialogDescription>
                        Confirm approval for {{ selectedPendingCount }} pending payment(s).
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-2">
                    <label class="text-sm font-medium">Comments (optional)</label>
                    <Input
                        v-model="bulkApproveForm.comments"
                        placeholder="Add context for the bulk approval"
                    />
                    <p v-if="bulkApproveForm.errors.payment_ids" class="text-sm text-destructive">
                        {{ bulkApproveForm.errors.payment_ids }}
                    </p>
                    <p v-if="bulkApproveForm.errors.comments" class="text-sm text-destructive">
                        {{ bulkApproveForm.errors.comments }}
                    </p>
                </div>
                <DialogFooter>
                    <Button variant="ghost" @click="bulkApproveOpen = false">
                        Cancel
                    </Button>
                    <Button :disabled="bulkApproveForm.processing || selectedPendingCount === 0" @click="submitBulkApprove">
                        Confirm bulk approval
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="bulkRejectOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Reject selected payments</DialogTitle>
                    <DialogDescription>
                        Provide a reason for rejecting {{ selectedPendingCount }} payment(s).
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-2">
                    <label class="text-sm font-medium">Rejection reason</label>
                    <Input
                        v-model="bulkRejectForm.comments"
                        placeholder="Explain why these payments are rejected"
                    />
                    <p v-if="bulkRejectForm.errors.payment_ids" class="text-sm text-destructive">
                        {{ bulkRejectForm.errors.payment_ids }}
                    </p>
                    <p v-if="bulkRejectForm.errors.comments" class="text-sm text-destructive">
                        {{ bulkRejectForm.errors.comments }}
                    </p>
                </div>
                <DialogFooter>
                    <Button variant="ghost" @click="bulkRejectOpen = false">
                        Cancel
                    </Button>
                    <Button
                        :disabled="bulkRejectForm.processing || selectedPendingCount === 0"
                        variant="secondary"
                        @click="submitBulkReject"
                    >
                        Confirm bulk rejection
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
    </div>
</template>
