import { format, formatDistanceToNow, parseISO, isValid, parse } from 'date-fns';

export interface DateFormatOptions {
    relative?: boolean;
    includeTime?: boolean;
    format?: string;
}

/**
 * Composable for formatting dates in a human-readable way
 * Converts ISO timestamps like "2025-12-14T00:00:00.000000Z" to readable formats
 */
export function useDateFormat() {
    const parseDate = (dateString: string | Date | null | undefined): Date | null => {
        if (!dateString) return null;
        if (dateString instanceof Date) return dateString;

        const iso = parseISO(dateString);
        if (isValid(iso)) return iso;

        const humanDate = parse(dateString, 'MMM d, yyyy', new Date());
        if (isValid(humanDate)) return humanDate;

        const humanDateTime = parse(dateString, 'MMM d, yyyy h:mm a', new Date());
        if (isValid(humanDateTime)) return humanDateTime;

        return null;
    };

    /**
     * Format a date string to human-readable format
     * @param dateString - ISO date string or Date object
     * @param options - Formatting options
     * @returns Formatted date string
     */
    const formatDate = (
        dateString: string | Date | null | undefined,
        options: DateFormatOptions = {}
    ): string => {
        if (!dateString) return 'Not set';

        try {
            const date = parseDate(dateString);
            
            if (!date || !isValid(date)) return 'Invalid date';

            const { relative = false, includeTime = false, format: customFormat } = options;

            // Custom format takes precedence
            if (customFormat) {
                return format(date, customFormat);
            }

            // Relative format (e.g., "2 days ago")
            if (relative) {
                return formatDistanceToNow(date, { addSuffix: true });
            }

            // Absolute format
            if (includeTime) {
                return format(date, 'MMM d, yyyy h:mm a');
            }

            return format(date, 'MMM d, yyyy');
        } catch (error) {
            console.error('Date formatting error:', error);
            return 'Invalid date';
        }
    };

    /**
     * Format a date for display in tables (short format)
     */
    const formatTableDate = (dateString: string | Date | null | undefined): string => {
        return formatDate(dateString, { includeTime: false });
    };

    /**
     * Format a date with relative time (e.g., "2 days ago")
     */
    const formatRelative = (dateString: string | Date | null | undefined): string => {
        return formatDate(dateString, { relative: true });
    };

    /**
     * Format a date with time
     */
    const formatDateTime = (dateString: string | Date | null | undefined): string => {
        return formatDate(dateString, { includeTime: true });
    };

    return {
        parseDate,
        formatDate,
        formatTableDate,
        formatRelative,
        formatDateTime,
    };
}
