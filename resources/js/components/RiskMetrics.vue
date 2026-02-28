<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Props {
    oldestPendingDate?: string | null;
    oldestPendingDays?: number | null;
    highCostThreshold?: number | null;
    highCostPendingCount?: number | null;
    highCostPendingTotal?: number | null;
}

defineProps<Props>();

const currencyFormat = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0,
});
</script>

<template>
    <div class="grid gap-4 md:grid-cols-3">
        <Card>
            <CardHeader>
                <CardTitle>Oldest pending</CardTitle>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-semibold">
                    {{ oldestPendingDays !== null && oldestPendingDays !== undefined
                        ? `${oldestPendingDays} days`
                        : '--' }}
                </p>
                <p class="text-sm text-muted-foreground">
                    {{ oldestPendingDate ?? 'No pending approvals' }}
                </p>
            </CardContent>
        </Card>
        <Card>
            <CardHeader>
                <CardTitle>High cost approvals</CardTitle>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-semibold">
                    {{ highCostPendingCount ?? 0 }}
                </p>
                <p class="text-sm text-muted-foreground">
                    Threshold: {{
                        highCostThreshold !== null && highCostThreshold !== undefined
                            ? currencyFormat.format(highCostThreshold)
                            : '--'
                    }}
                </p>
            </CardContent>
        </Card>
        <Card>
            <CardHeader>
                <CardTitle>High cost exposure</CardTitle>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-semibold">
                    {{
                        highCostPendingTotal !== null && highCostPendingTotal !== undefined
                            ? currencyFormat.format(highCostPendingTotal)
                            : '--'
                    }}
                </p>
                <p class="text-sm text-muted-foreground">
                    Pending approvals above threshold
                </p>
            </CardContent>
        </Card>
    </div>
</template>
