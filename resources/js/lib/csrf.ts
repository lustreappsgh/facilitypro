export function getCsrfToken(): string | null {
    const element = document.querySelector<HTMLMetaElement>(
        'meta[name="csrf-token"]',
    );

    return element?.content ?? null;
}
