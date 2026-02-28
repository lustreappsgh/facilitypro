<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Props {
    cost?: number | null;
    amountPaid?: number | null;
}

const props = defineProps<Props>();

const currencyFormat = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
});

const outstanding = () => {
    if (props.cost === null || props.cost === undefined) {
        return null;
    }

    const paid = props.amountPaid ?? 0;
    return Math.max(props.cost - paid, 0);
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Cost breakdown</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3">
            <div class="flex items-center justify-between text-sm">
                <span class="text-muted-foreground">Total cost</span>
                <span class="font-medium">
                    {{
                        cost !== null && cost !== undefined
                            ? currencyFormat.format(cost)
                            : '--'
                    }}
                </span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-muted-foreground">Amount paid</span>
                <span class="font-medium">
                    {{
                        amountPaid !== null && amountPaid !== undefined
                            ? currencyFormat.format(amountPaid)
                            : '--'
                    }}
                </span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-muted-foreground">Outstanding</span>
                <span class="font-medium">
                    {{
                        outstanding() !== null
                            ? currencyFormat.format(outstanding() ?? 0)
                            : '--'
                    }}
                </span>
            </div>
        </CardContent>
    </Card>
</template>
