<script setup lang="ts">
import { DatePicker } from '@/components/ui/date-picker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { index as paymentApprovalsIndex } from '@/routes/payment-approvals/index';
import { approve as paymentApprove, reject as paymentReject, show as paymentShow } from '@/routes/payments';
import { show as maintenanceShow } from '@/routes/maintenance';
import { Link, useForm } from '@inertiajs/vue3';
import { Check, Eye, Search, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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

interface Payment {
    id: number;
    cost?: number | null;
    status?: string | null;
    created_at?: string | null;
    maintenanceRequest?: MaintenanceRequest | null;
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

const approvalPayment = ref<Payment | null>(null);
const rejectionPayment = ref<Payment | null>(null);
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
</script>

<template>
    <div class="space-y-4">
        <form
            :action="paymentApprovalsIndex().url"
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
                    <Link :href="paymentApprovalsIndex().url">Reset</Link>
                </Button>
            </div>
        </form>

        <div class="rounded-xl border border-sidebar-border/70 bg-background">
            <Table>
                <TableHeader>
                    <TableRow>
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
                            <span v-else>--</span>
                        </TableCell>
                        <TableCell>
                            {{ payment.maintenanceRequest?.facility?.name ?? '--' }}
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
                            colspan="6"
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
