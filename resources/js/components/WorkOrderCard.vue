<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { Eye } from 'lucide-vue-next';

interface VendorSummary {
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

interface Props {
    workOrder: WorkOrder;
    showRoute: (workOrderId: number) => { url: string };
}

defineProps<Props>();

const statusBadgeClass = (status: string) => {
    if (status === 'completed') {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (status === 'in_progress') {
        return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
    }

    if (status === 'cancelled') {
        return 'bg-rose-500/10 text-rose-700 dark:text-rose-300';
    }

    return 'bg-slate-500/10 text-slate-700 dark:text-slate-300';
};
</script>

<template>
    <div
        class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-border/60 bg-card p-4"
    >
        <div class="space-y-2">
            <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm font-semibold">Work order #{{ workOrder.id }}</p>
                <Badge
                    variant="secondary"
                    :class="statusBadgeClass(workOrder.status ?? 'in_progress')"
                >
                    {{ workOrder.status?.replace('_', ' ') ?? 'in progress' }}
                </Badge>
            </div>
            <div class="text-sm text-muted-foreground">
                <p>
                    Vendor:
                    <span class="text-foreground">
                        {{ workOrder.vendor?.name ?? '—' }}
                    </span>
                </p>
                <p>
                    Scheduled:
                    <span class="text-foreground">
                        {{ workOrder.scheduled_date ?? '—' }}
                    </span>
                </p>
            </div>
        </div>
        <Button variant="ghost" size="icon" class="h-8 w-8" as-child>
            <Link :href="showRoute(workOrder.id).url" aria-label="View work order">
                <Eye class="h-4 w-4" />
            </Link>
        </Button>
    </div>
</template>
