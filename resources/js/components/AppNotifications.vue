<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    CheckCircle2,
    Info,
    TriangleAlert,
} from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();

const flash = computed(() => page.props.flash || {});

const messages = computed(() => [
    {
        key: 'success',
        title: 'Success',
        message: flash.value.success,
        icon: CheckCircle2,
        variant: 'default',
    },
    {
        key: 'error',
        title: 'Error',
        message: flash.value.error,
        icon: AlertCircle,
        variant: 'destructive',
    },
    {
        key: 'warning',
        title: 'Warning',
        message: flash.value.warning,
        icon: TriangleAlert,
        variant: 'default',
    },
    {
        key: 'info',
        title: 'Info',
        message: flash.value.info,
        icon: Info,
        variant: 'default',
    },
]);

const visibleMessages = computed(() =>
    messages.value.filter((item) => item.message),
);
</script>

<template>
    <div class="flex w-full flex-col gap-3 px-6 pt-4 md:px-4">
        <Alert
            v-for="item in visibleMessages"
            :key="item.key"
            :variant="item.variant"
        >
            <component :is="item.icon" class="size-4" />
            <AlertTitle>{{ item.title }}</AlertTitle>
            <AlertDescription>{{ item.message }}</AlertDescription>
        </Alert>
    </div>
</template>
