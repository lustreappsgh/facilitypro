export const APP_LOCALE = 'en-GH';

export function createNumberFormatter(
    options: Intl.NumberFormatOptions = {},
): Intl.NumberFormat {
    return new Intl.NumberFormat(APP_LOCALE, options);
}

const shortDateFormatter = new Intl.DateTimeFormat(APP_LOCALE, {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
});

const shortDateTimeFormatter = new Intl.DateTimeFormat(APP_LOCALE, {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
});

const monthDayFormatter = new Intl.DateTimeFormat(APP_LOCALE, {
    day: '2-digit',
    month: 'short',
});

export function formatLocaleDate(value: Date): string {
    return shortDateFormatter.format(value);
}

export function formatLocaleDateTime(value: Date): string {
    return shortDateTimeFormatter.format(value);
}

export function formatLocaleDateRange(start: Date, end: Date): string {
    if (start.getFullYear() === end.getFullYear()) {
        return `${monthDayFormatter.format(start)} - ${shortDateFormatter.format(end)}`;
    }

    return `${shortDateFormatter.format(start)} - ${shortDateFormatter.format(end)}`;
}
