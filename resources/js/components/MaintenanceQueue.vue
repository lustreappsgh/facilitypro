<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';

interface QueueMaintenanceRequest {
    id: number;
    facility?: string | null;
    request_type?: string | null;
    cost?: number | null;
    created_at?: string | null;
}

interface Props {
    requests: QueueMaintenanceRequest[];
    showRoute: (request: QueueMaintenanceRequest) => { url: string };
}

defineProps<Props>();

const currencyFormat = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0,
});
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
                <p class="text-xs text-muted-foreground">
                    {{ request.request_type ?? 'General' }} •
                    {{ request.created_at ?? '—' }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <p class="text-sm font-medium">
                    {{
                        request.cost !== null && request.cost !== undefined
                            ? currencyFormat.format(request.cost)
                            : '—'
                    }}
                </p>
                <Button variant="outline" size="sm" as-child>
                    <Link :href="showRoute(request).url">View</Link>
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
