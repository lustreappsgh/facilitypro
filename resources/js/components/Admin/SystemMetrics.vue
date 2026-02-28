<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface MetricItem {
    label: string;
    value: string | number;
    helper?: string;
}

defineProps<{
    title?: string;
    items: MetricItem[];
}>();
</script>

<template>
    <section class="space-y-3">
        <h2 v-if="title" class="text-lg font-semibold">
            {{ title }}
        </h2>
        <div class="grid gap-4 md:grid-cols-3">
            <Card v-for="item in items" :key="item.label">
                <CardHeader>
                    <CardTitle>{{ item.label }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-semibold">{{ item.value }}</p>
                    <p v-if="item.helper" class="text-xs text-muted-foreground">
                        {{ item.helper }}
                    </p>
                </CardContent>
            </Card>
        </div>
    </section>
</template>
