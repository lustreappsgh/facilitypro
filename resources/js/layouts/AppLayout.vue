<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { login } from '@/routes';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const isAuthenticated = computed(() => Boolean(page.props.auth?.user));
</script>

<template>
    <AppLayout v-if="isAuthenticated" :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>
    <div v-else class="mx-auto flex min-h-screen max-w-2xl items-center px-6">
        <Alert>
            <AlertTitle>Authentication Required</AlertTitle>
            <AlertDescription>
                Please sign in to access this area.
                <TextLink :href="login()" class="ml-2">Sign in</TextLink>
            </AlertDescription>
        </Alert>
    </div>
</template>
