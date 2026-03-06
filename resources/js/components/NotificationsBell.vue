<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useNotifications } from '@/composables/useNotifications';
import type { AppNotification } from '@/types/notifications';
import { Link } from '@inertiajs/vue3';
import { Bell, Check } from 'lucide-vue-next';
import { formatDistanceToNow } from 'date-fns';
import { computed, onMounted } from 'vue';

const {
    notifications,
    unread,
    load,
    markRead,
    markAllRead,
    subscribe,
} = useNotifications();

const recentNotifications = computed(() => notifications.value.slice(0, 6));
const hasNotifications = computed(() => notifications.value.length > 0);

const formatTime = (notification: AppNotification) => {
    const createdAt = notification.created_at ?? new Date().toISOString();
    return formatDistanceToNow(new Date(createdAt), {
        addSuffix: true,
    });
};

const handleClick = (notification: AppNotification) => {
    if (!notification.read_at) {
        markRead(notification.id);
    }
};

onMounted(async () => {
    await load();
    subscribe();
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger :as-child="true">
            <Button
                variant="ghost"
                size="icon"
                class="relative h-9 w-9"
                aria-label="Notifications"
            >
                <Bell class="h-5 w-5" />
                <Badge
                    v-if="unread > 0"
                    class="absolute -right-1 -top-1 h-5 min-w-5 rounded-full px-1 text-[10px]"
                >
                    {{ unread > 9 ? '9+' : unread }}
                </Badge>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-96 p-0">
            <div class="flex items-center justify-between px-4 py-3">
                <div class="text-sm font-semibold">Notifications</div>
                <Button
                    v-if="unread > 0"
                    variant="ghost"
                    size="sm"
                    class="h-7 text-xs"
                    @click="markAllRead"
                >
                    Mark all read
                </Button>
            </div>
            <DropdownMenuSeparator />
            <div v-if="!hasNotifications" class="px-4 py-6 text-sm text-muted-foreground">
                No notifications yet.
            </div>
            <div v-else class="max-h-[320px] overflow-auto">
                <DropdownMenuItem
                    v-for="notification in recentNotifications"
                    :key="notification.id"
                    class="items-start gap-3 px-4 py-3"
                >
                    <div
                        class="mt-1 h-2 w-2 rounded-full"
                        :class="notification.read_at ? 'bg-muted' : 'bg-primary'"
                    ></div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <div class="truncate text-sm font-medium">
                                {{ notification.data.title }}
                            </div>
                            <div class="text-[11px] text-muted-foreground">
                                {{ formatTime(notification) }}
                            </div>
                        </div>
                        <div class="mt-1 text-xs text-muted-foreground">
                            {{ notification.data.body }}
                        </div>
                        <div class="mt-2">
                            <Link
                                v-if="notification.data.action_url"
                                class="inline-flex items-center gap-1 text-xs font-medium text-foreground underline-offset-4 hover:underline"
                                :href="notification.data.action_url"
                                @click="handleClick(notification)"
                            >
                                View details
                                <Check class="h-3 w-3" />
                            </Link>
                            <button
                                v-else
                                type="button"
                                class="text-xs text-muted-foreground"
                                @click="handleClick(notification)"
                            >
                                Mark read
                            </button>
                        </div>
                    </div>
                </DropdownMenuItem>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
