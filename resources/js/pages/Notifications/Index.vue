<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { getCsrfToken } from '@/lib/csrf';
import type { AppNotification } from '@/types/notifications';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { formatDistanceToNow } from 'date-fns';
import { ref, watch } from 'vue';

interface Props {
    notifications: {
        data: AppNotification[];
        links: any[];
        total: number;
    };
    unreadCount: number;
    filters: {
        category?: string | null;
        unread?: boolean;
    };
    categories: string[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Notifications', href: '/notifications/inbox' },
];

const notificationRows = ref<AppNotification[]>(props.notifications.data ?? []);
const localUnreadCount = ref(props.unreadCount ?? 0);
const selectedCategory = ref(props.filters.category ?? 'all');
const unreadOnly = ref(Boolean(props.filters.unread));

watch(
    () => props.notifications.data,
    (value) => {
        notificationRows.value = value ?? [];
    },
);

watch(
    () => props.unreadCount,
    (value) => {
        localUnreadCount.value = value ?? 0;
    },
);

const applyFilters = () => {
    router.get(
        '/notifications/inbox',
        {
            category: selectedCategory.value === 'all' ? undefined : selectedCategory.value,
            unread: unreadOnly.value ? 1 : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const resetFilters = () => {
    selectedCategory.value = 'all';
    unreadOnly.value = false;
    applyFilters();
};

const updateUnreadOnly = (value: boolean | 'indeterminate') => {
    unreadOnly.value = value === true;
};

const formatTime = (notification: AppNotification) =>
    formatDistanceToNow(new Date(notification.created_at ?? new Date().toISOString()), {
        addSuffix: true,
    });

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

const markRead = async (notificationId: string) => {
    const csrfToken = getCsrfToken();
    const response = await fetch(`/notifications/${notificationId}/read`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
        body: JSON.stringify({}),
    });

    if (!response.ok) {
        return;
    }

    const payload = await response.json();
    const updated = payload.notification as AppNotification | undefined;
    if (!updated) {
        return;
    }

    localUnreadCount.value = payload.unread_count ?? localUnreadCount.value;

    notificationRows.value = notificationRows.value.map((item) =>
        item.id === updated.id ? updated : item,
    );
};

const markAllRead = async () => {
    const csrfToken = getCsrfToken();
    const response = await fetch('/notifications/read-all', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
        body: JSON.stringify({}),
    });

    if (!response.ok) {
        return;
    }

    localUnreadCount.value = 0;
    notificationRows.value = notificationRows.value.map((item) => ({
        ...item,
        read_at: item.read_at ?? new Date().toISOString(),
    }));
};
</script>

<template>
    <Head title="Notifications" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader
                title="Notifications"
                :subtitle="`Unread: ${localUnreadCount}`"
            >
                <template #actions>
                    <Button variant="outline" @click="markAllRead">
                        Mark all read
                    </Button>
                </template>
            </PageHeader>

            <Card class="border-border/60 bg-card/60">
                <CardContent class="flex flex-wrap items-end gap-3 p-4">
                    <div class="min-w-[220px]">
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            Category
                        </label>
                        <Select v-model="selectedCategory">
                            <SelectTrigger>
                                <SelectValue placeholder="All categories" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All categories</SelectItem>
                                <SelectItem
                                    v-for="category in categories"
                                    :key="category"
                                    :value="category"
                                >
                                    {{ category.replaceAll('_', ' ') }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <label class="flex items-center gap-2 pb-2 text-sm">
                        <Checkbox
                            :model-value="unreadOnly"
                            @update:modelValue="updateUnreadOnly"
                        />
                        Unread only
                    </label>

                    <div class="flex items-center gap-2">
                        <Button @click="applyFilters">Apply</Button>
                        <Button variant="ghost" @click="resetFilters">Reset</Button>
                    </div>
                </CardContent>
            </Card>

            <div class="space-y-4">
                <Card
                    v-for="notification in notificationRows"
                    :key="notification.id"
                    class="border-border/60 bg-card/60"
                >
                    <CardContent class="flex flex-col gap-3 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Badge :class="severityClass(notification.data.severity)">
                                        {{ notification.data.severity ?? 'info' }}
                                    </Badge>
                                    <Badge variant="outline">
                                        {{ (notification.data.category ?? 'system').replaceAll('_', ' ') }}
                                    </Badge>
                                    <Badge v-if="!notification.read_at" variant="secondary">
                                        Unread
                                    </Badge>
                                </div>
                                <h2 class="font-display text-lg font-semibold">
                                    {{ notification.data.title ?? 'Notification' }}
                                </h2>
                                <p class="text-sm text-muted-foreground">
                                    {{ notification.data.body ?? '' }}
                                </p>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                {{ formatTime(notification) }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <Button
                                v-if="notification.data.action_url"
                                variant="outline"
                                as-child
                            >
                                <Link
                                    :href="notification.data.action_url"
                                    @click="!notification.read_at && markRead(notification.id)"
                                >
                                    View details
                                </Link>
                            </Button>
                            <Button
                                v-if="!notification.read_at"
                                variant="ghost"
                                @click="markRead(notification.id)"
                            >
                                Mark read
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="notificationRows.length === 0" class="border-border/60 bg-card/60">
                    <CardContent class="p-6 text-sm text-muted-foreground">
                        No notifications match the current filters.
                    </CardContent>
                </Card>
            </div>

            <PaginationLinks
                :links="props.notifications.links"
            />
        </div>
    </AppLayout>
</template>
