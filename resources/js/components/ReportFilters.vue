<script setup lang="ts">
import { DatePicker } from '@/components/ui/date-picker';
import { Button } from '@/components/ui/button';

interface Props {
    action: string;
    startDate?: string | null;
    endDate?: string | null;
}

const props = defineProps<Props>();
</script>

<template>
    <form
        :action="action"
        method="get"
        class="flex flex-wrap items-center gap-3 rounded-xl border border-border/60 bg-card p-4"
    >
        <DatePicker
            name="start_date"
            class="min-w-[150px]"
            :model-value="props.startDate ?? ''"
        />
        <DatePicker
            name="end_date"
            class="min-w-[150px]"
            :model-value="props.endDate ?? ''"
        />
        <div class="flex items-center gap-2">
            <Button type="submit">Apply filters</Button>
            <Button variant="ghost" as-child>
                <a :href="action">Reset</a>
            </Button>
        </div>
    </form>
</template>
