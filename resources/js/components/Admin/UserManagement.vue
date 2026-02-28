<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <section class="space-y-1">
        <h1 class="text-2xl font-semibold">{{ title }}</h1>
        <p v-if="description" class="text-sm text-muted-foreground">
            {{ description }}
        </p>
    </section>
</template>
