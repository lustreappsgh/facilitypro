export interface NotificationPayload {
    event: string;
    title: string;
    body: string;
    action_url?: string | null;
    meta?: Record<string, unknown>;
}

export interface AppNotification {
    id: string;
    type: string;
    data: NotificationPayload;
    read_at: string | null;
    created_at?: string;
}
