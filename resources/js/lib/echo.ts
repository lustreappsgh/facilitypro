import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { getCsrfToken } from '@/lib/csrf';

let initialized = false;

export function initializeEcho(userId?: number | null) {
    if (initialized || !userId) {
        return;
    }

    const key = import.meta.env.VITE_REVERB_APP_KEY;
    if (!key) {
        return;
    }

    initialized = true;
    window.Pusher = Pusher;

    const csrfToken = getCsrfToken();

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
        auth: csrfToken
            ? {
                  headers: {
                      'X-CSRF-TOKEN': csrfToken,
                  },
              }
            : undefined,
    });
}
