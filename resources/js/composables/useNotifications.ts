import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { AppNotification } from '@/types/notifications';
import { getCsrfToken } from '@/lib/csrf';

const notifications = ref<AppNotification[]>([]);
const unreadCount = ref(0);
let subscribed = false;
let loaded = false;

async function postJson(url: string) {
    const csrfToken = getCsrfToken();
    return fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
        body: JSON.stringify({}),
    });
}

export function useNotifications() {
    const page = usePage();
    const userId = computed(() => page.props.auth?.user?.id ?? null);

    const unread = computed(() => unreadCount.value);

    const load = async () => {
        if (loaded || !userId.value) {
            return;
        }

        loaded = true;
        const response = await fetch('/notifications', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            return;
        }

        const data = await response.json();
        notifications.value = data.notifications ?? [];
        unreadCount.value = data.unread_count ?? 0;
    };

    const markRead = async (notificationId: string) => {
        const response = await postJson(
            `/notifications/${notificationId}/read`,
        );
        if (!response.ok) {
            return;
        }

        const data = await response.json();
        unreadCount.value = data.unread_count ?? unreadCount.value;

        const updated = data.notification as AppNotification | undefined;
        if (!updated) {
            return;
        }

        const index = notifications.value.findIndex(
            (item) => item.id === updated.id,
        );
        if (index >= 0) {
            notifications.value[index] = updated;
        } else {
            notifications.value.unshift(updated);
        }
    };

    const markAllRead = async () => {
        const response = await postJson('/notifications/read-all');
        if (!response.ok) {
            return;
        }

        unreadCount.value = 0;
        notifications.value = notifications.value.map((item) => ({
            ...item,
            read_at: item.read_at ?? new Date().toISOString(),
        }));
    };

    const subscribe = () => {
        if (subscribed || !userId.value || !window.Echo) {
            return;
        }

        subscribed = true;

        window.Echo.private(`App.Models.User.${userId.value}`).notification(
            (notification: AppNotification) => {
                const hydrated: AppNotification = {
                    ...notification,
                    created_at:
                        notification.created_at ?? new Date().toISOString(),
                    read_at: notification.read_at ?? null,
                };

                const existingIndex = notifications.value.findIndex(
                    (item) => item.id === hydrated.id,
                );

                if (existingIndex >= 0) {
                    const wasRead = Boolean(notifications.value[existingIndex].read_at);
                    notifications.value[existingIndex] = hydrated;
                    if (wasRead && !hydrated.read_at) {
                        unreadCount.value += 1;
                    }
                } else {
                    notifications.value.unshift(hydrated);
                    if (!hydrated.read_at) {
                        unreadCount.value += 1;
                    }
                }
            },
        );
    };

    return {
        notifications,
        unread,
        load,
        markRead,
        markAllRead,
        subscribe,
    };
}
