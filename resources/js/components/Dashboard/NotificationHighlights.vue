<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Link } from '@inertiajs/vue3';

interface NotificationItem {
    id: string;
    title: string | null;
    body: string | null;
    category: string | null;
    severity: string | null;
    action_url?: string | null;
    created_at?: string | null;
}

interface Props {
    unreadCount: number;
    items: NotificationItem[];
}

defineProps<Props>();

const severityClass = (severity?: string | null) => {
    switch (severity) {
        case 'success':
            return 'border-success/20 bg-success-subtle text-success';
        case 'warning':
            return 'border-amber-200 bg-amber-100 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200';
        case 'danger':
            return 'border-destructive/20 bg-destructive/10 text-destructive';
        default:
            return 'border-border bg-muted/50 text-muted-foreground';
    }
};
</script>

<template>
    <Card class="border-border/60 bg-card/70">
        <CardHeader class="pb-3">
            <div class="flex items-center justify-between gap-3">
                <CardTitle class="font-display text-lg">Priority Notifications</CardTitle>
                <Button size="sm" variant="outline" as-child>
                    <Link href="/notifications/inbox">
                        Inbox<span v-if="unreadCount > 0"> ({{ unreadCount }})</span>
                    </Link>
                </Button>
            </div>
        </CardHeader>
        <CardContent>
            <div v-if="items.length" class="grid gap-3">
                <div
                    v-for="item in items"
                    :key="item.id"
                    class="rounded-lg border border-border/60 bg-card p-4"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge :class="severityClass(item.severity)">
                                    {{ item.severity ?? 'info' }}
                                </Badge>
                                <Badge variant="outline">
                                    {{ (item.category ?? 'system').replaceAll('_', ' ') }}
                                </Badge>
                            </div>
                            <p class="text-sm font-semibold">
                                {{ item.title ?? 'Notification' }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ item.body ?? '' }}
                            </p>
                        </div>
                        <Button
                            v-if="item.action_url"
                            size="sm"
                            variant="ghost"
                            as-child
                        >
                            <Link :href="item.action_url">View</Link>
                        </Button>
                    </div>
                </div>
            </div>
            <div v-else class="text-sm text-muted-foreground">
                No high-priority unread notifications.
            </div>
        </CardContent>
    </Card>
</template>
