<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{ links: PaginationLink[] }>();
</script>

<template>
    <nav
        v-if="links.length > 3"
        class="flex flex-wrap items-center justify-center gap-2"
        aria-label="Pagination"
    >
        <template v-for="link in links" :key="link.label">
            <span
                v-if="!link.url"
                class="rounded-md border border-transparent px-3 py-1 text-sm text-muted-foreground"
                v-html="link.label"
            />
            <Link
                v-else
                :href="link.url"
                class="rounded-md border px-3 py-1 text-sm transition hover:bg-accent"
                :class="
                    link.active
                        ? 'border-transparent bg-accent text-accent-foreground'
                        : 'border-input text-foreground'
                "
            >
                <span v-html="link.label" />
            </Link>
        </template>
    </nav>
</template>
