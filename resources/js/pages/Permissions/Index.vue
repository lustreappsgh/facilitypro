<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as permissionsIndex } from '@/routes/permissions';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { ref } from 'vue';

interface Permission {
    id: number;
    name: string;
}

interface Props {
    permissions: Permission[];
    groups: Record<string, Permission[]>;
    filters: {
        search?: string | null;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Permissions',
        href: permissionsIndex().url,
    },
];

const searchFilter = ref(props.filters.search ?? '');
</script>

<template>

    <Head title="Permissions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-8 p-6 lg:p-10">
            <PageHeader title="Permissions" subtitle="View system-wide permission capabilities."
                :show-filters-toggle="false" />

            <form :action="permissionsIndex().url" method="get"
                class="flex flex-wrap items-center gap-3 rounded-xl border border-border/60 bg-card p-4">
                <div class="relative min-w-[220px] flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="searchFilter" name="search" class="pl-9" placeholder="Search permission keys" />
                </div>
                <div class="flex items-center gap-2">
                    <Button type="submit" class="whitespace-nowrap">
                        Apply filters
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link :href="permissionsIndex().url">Reset</Link>
                    </Button>
                </div>
            </form>

            <div class="grid gap-4 md:grid-cols-2">
                <div v-for="(items, group) in groups" :key="group"
                    class="rounded-xl border border-border/60 bg-card p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-lg font-semibold capitalize">
                            {{ group.replace(/_/g, ' ') }}
                        </h2>
                        <Badge variant="secondary">{{ items.length }}</Badge>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Badge v-for="permission in items" :key="permission.id" variant="outline">
                            {{ permission.name }}
                        </Badge>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
