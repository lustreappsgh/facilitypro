<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { createCurrencyFormatter } from '@/lib/currency';
import { Link } from '@inertiajs/vue3';
import { Eye } from 'lucide-vue-next';

interface QueueMaintenanceRequest {
    id: number;
    facility?: string | null;
    request_type?: string | null;
    description?: string | null;
    priority?: 'low' | 'medium' | 'high' | null;
    cost?: number | null;
    created_at?: string | null;
}

interface Props {
    requests: QueueMaintenanceRequest[];
    showRoute: (request: QueueMaintenanceRequest) => { url: string };
}

defineProps<Props>();

const currencyFormat = createCurrencyFormatter();
const priorityBadgeClass = (priority?: string | null) => {
    if (priority === 'high') {
        return 'bg-rose-500/10 text-rose-700';
    }

    if (priority === 'medium') {
        return 'bg-amber-500/10 text-amber-700';
    }

    return 'bg-slate-500/10 text-slate-700';
};
</script>

<template>
    <div class="space-y-3">
        <div
            v-for="request in requests"
            :key="request.id"
            class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border/60 bg-card p-4"
        >
            <div>
                <p class="text-sm font-semibold">
                    {{ request.facility ?? 'Unknown facility' }}
                </p>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <p class="text-xs text-muted-foreground">
                        {{ request.request_type ?? 'General' }} -
                        {{ request.created_at ?? '-' }}
                    </p>
                    <span
                        class="rounded-full px-2 py-0.5 text-[10px] font-black uppercase tracking-wider"
                        :class="priorityBadgeClass(request.priority)"
                    >
                        {{ request.priority ?? 'medium' }}
                    </span>
                </div>
                <p
                    v-if="request.description"
                    class="mt-1 line-clamp-3 max-w-xl text-xs text-muted-foreground"
                >
                    {{ request.description }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <p class="text-sm font-medium">
                    {{
                        request.cost !== null && request.cost !== undefined
                            ? currencyFormat.format(request.cost)
                            : '-'
                    }}
                </p>
                <Button variant="ghost" size="icon" class="h-8 w-8" as-child>
                    <Link
                        :href="showRoute(request).url"
                        aria-label="View maintenance request"
                    >
                        <Eye class="h-4 w-4" />
                    </Link>
                </Button>
            </div>
        </div>
        <div
            v-if="!requests.length"
            class="rounded-lg border border-dashed border-border/60 px-4 py-6 text-center text-sm text-muted-foreground"
        >
            No pending maintenance requests right now.
        </div>
    </div>
</template>
