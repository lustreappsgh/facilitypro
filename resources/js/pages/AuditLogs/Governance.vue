<script setup lang="ts">
import { DatePicker } from '@/components/ui/date-picker';
import AuditTimeline from '@/components/AuditTimeline.vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { governance as auditLogsGovernance } from '@/routes/audit-logs';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const filtersVisible = ref(false);

interface Actor {
    id: number;
    name: string;
}

interface AuditLog {
    id: number;
    action: string;
    auditable_type?: string | null;
    auditable_id?: number | null;
    before?: Record<string, unknown> | null;
    after?: Record<string, unknown> | null;
    created_at: string;
    actor?: Actor | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedLogs {
    data: AuditLog[];
    links: PaginationLink[];
}

interface Filters {
    action?: string | null;
    start_date?: string | null;
    end_date?: string | null;
}

interface Props {
    data: {
        logs: PaginatedLogs;
    };
    filters: Filters;
    actions: string[];
    permissions: string[];
    routes: Record<string, string>;
    meta: Record<string, string>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Audit Governance',
        href: auditLogsGovernance().url,
    },
];

const actionFilter = ref(props.filters.action ?? '');
const startDateFilter = ref(props.filters.start_date ?? '');
const endDateFilter = ref(props.filters.end_date ?? '');

const selectedActionLabel = computed(() => {
    if (!actionFilter.value) {
        return 'All actions';
    }

    return actionFilter.value;
});
</script>

<template>
    <Head title="Audit governance" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <PageHeader
                title="Audit log"
                subtitle="Governance timeline and approval records."
                :show-filters-toggle="true"
                :filters-visible="filtersVisible"
                @toggle-filters="filtersVisible = !filtersVisible"
            />

            <form
                v-if="filtersVisible"
                :action="auditLogsGovernance().url"
                method="get"
                class="flex flex-wrap items-center gap-3 rounded-xl border border-border/60 bg-card p-4"
            >
                <input type="hidden" name="action" :value="actionFilter" />
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" class="min-w-[220px] justify-between">
                            {{ selectedActionLabel }}
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-72">
                        <DropdownMenuLabel>Action</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem @click="actionFilter = ''">
                            All actions
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            v-for="action in actions"
                            :key="action"
                            @click="actionFilter = action"
                        >
                            {{ action }}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <DatePicker
                    v-model="startDateFilter"
                    name="start_date"
                    class="min-w-[150px]"
                 />
                <DatePicker
                    v-model="endDateFilter"
                    name="end_date"
                    class="min-w-[150px]"
                 />

                <div class="flex items-center gap-2">
                    <Button type="submit">Apply filters</Button>
                    <Button variant="ghost" as-child>
                        <Link :href="auditLogsGovernance().url">Reset</Link>
                    </Button>
                </div>
            </form>

            <AuditTimeline :logs="props.data.logs.data" />

            <PaginationLinks :links="props.data.logs.links" />
        </div>
    </AppLayout>
</template>
