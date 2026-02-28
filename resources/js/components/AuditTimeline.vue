<script setup lang="ts">
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Button } from '@/components/ui/button';

interface Actor {
    id: number;
    name: string;
}

interface AuditLog {
    id: number;
    action: string;
    auditable_type?: string | null;
    auditable_id?: number | null;
    before?: Record<string, unknown> | null;
    after?: Record<string, unknown> | null;
    created_at: string;
    actor?: Actor | null;
}

interface Props {
    logs: AuditLog[];
}

defineProps<Props>();

const formatPayload = (payload?: Record<string, unknown> | null) => {
    if (!payload || Object.keys(payload).length === 0) {
        return '--';
    }

    return JSON.stringify(payload, null, 2);
};
</script>

<template>
    <div class="space-y-4">
        <div
            v-for="log in logs"
            :key="log.id"
            class="rounded-xl border border-border/70 bg-background p-4"
        >
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-medium">{{ log.action }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ log.created_at }} · {{ log.actor?.name ?? 'System' }}
                    </p>
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ log.auditable_type ?? '--' }}
                    <span v-if="log.auditable_id">#{{ log.auditable_id }}</span>
                </p>
            </div>
            <Collapsible>
                <CollapsibleTrigger as-child>
                    <Button variant="ghost" class="mt-3 px-0">
                        View before/after
                    </Button>
                </CollapsibleTrigger>
                <CollapsibleContent class="mt-3 grid gap-3 lg:grid-cols-2">
                    <div>
                        <p class="text-xs uppercase text-muted-foreground">
                            Before
                        </p>
                        <pre class="mt-2 whitespace-pre-wrap rounded-lg bg-muted/30 p-3 text-xs text-muted-foreground">
{{ formatPayload(log.before) }}
                        </pre>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-muted-foreground">
                            After
                        </p>
                        <pre class="mt-2 whitespace-pre-wrap rounded-lg bg-muted/30 p-3 text-xs text-muted-foreground">
{{ formatPayload(log.after) }}
                        </pre>
                    </div>
                </CollapsibleContent>
            </Collapsible>
        </div>
    </div>
</template>
