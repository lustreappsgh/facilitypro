<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';

defineProps<{
    csvUrl: string;
    pdfUrl?: string | null;
}>();
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <Button variant="outline" as-child>
            <Link :href="csvUrl">Export CSV</Link>
        </Button>
        <Button v-if="pdfUrl" as-child>
            <Link :href="pdfUrl">Export PDF</Link>
        </Button>
    </div>
</template>
