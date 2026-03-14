import { createNumberFormatter } from '@/lib/locale';

const numberFormat = createNumberFormatter({
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

export function createCurrencyFormatter() {
    return {
        format(value: number): string {
            return `GHC ${numberFormat.format(value)}`;
        },
    };
}

export function formatCurrency(value: number): string {
    return `GHC ${numberFormat.format(value)}`;
}
