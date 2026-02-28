import { format, formatDistanceToNow, parseISO, isValid } from 'date-fns';

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
            const date = typeof dateString === 'string' ? parseISO(dateString) : dateString;
            
            if (!isValid(date)) return 'Invalid date';

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
        formatDate,
        formatTableDate,
        formatRelative,
        formatDateTime,
    };
}
