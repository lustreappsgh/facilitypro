export interface NotificationPayload {
    event: string | null;
    title: string | null;
    body: string | null;
    action_url?: string | null;
    meta?: Record<string, unknown>;
    category?: string | null;
    severity?: string | null;
}

export interface AppNotification {
    id: string;
    type: string;
    data: NotificationPayload;
    read_at: string | null;
    created_at?: string;
}
