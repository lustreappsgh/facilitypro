<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { complete, edit } from '@/routes/todos';
import { Link } from '@inertiajs/vue3';
import { Check, Pencil } from 'lucide-vue-next';

interface Facility {
    name: string;
}

interface Todo {
    id: number;
    description: string;
    status: string;
    week_start: string | null;
    facility?: Facility | null;
}

interface TodoGroup {
    week: string;
    todos: Todo[];
}

interface Props {
    groups: TodoGroup[];
    canUpdate: boolean;
    canComplete: boolean;
}

defineProps<Props>();

const statusLabels: Record<string, string> = {
    pending: 'Pending',
    completed: 'Completed',
    overdue: 'Overdue',
};

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        pending: 'bg-muted text-muted-foreground border-border',
        completed: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
        overdue: 'bg-rose-500/10 text-rose-600 border-rose-500/20',
    };
    return colors[status] || 'bg-muted text-muted-foreground';
};
</script>

<template>
    <div class="space-y-6">
        <section
            v-for="group in groups"
            :key="group.week"
            class="overflow-hidden rounded-xl border border-sidebar-border/70"
        >
            <div class="flex flex-wrap items-center justify-between gap-2 bg-muted/40 px-4 py-3">
                <div class="text-sm font-semibold">
                    {{ group.week === 'unscheduled' ? 'Unscheduled' : group.week }}
                </div>
                <div class="text-xs text-muted-foreground">
                    {{ group.todos.length }} items
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-muted-foreground">
                        <tr class="text-left">
                            <th class="px-4 py-3 font-medium">Facility</th>
                            <th class="px-4 py-3 font-medium">Description</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="todo in group.todos" :key="todo.id">
                            <td class="px-4 py-3">
                                {{ todo.facility?.name ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ todo.description }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge 
                                    variant="outline"
                                    :class="`rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider ${getStatusColor(todo.status)}`"
                                >
                                    {{ statusLabels[todo.status] ?? todo.status }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    <Button
                                        v-if="canUpdate && todo.status === 'pending'"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8"
                                        as-child
                                    >
                                        <Link :href="edit(todo).url" aria-label="Edit todo">
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="canComplete && ['pending', 'overdue'].includes(todo.status)"
                                        variant="secondary"
                                        size="icon"
                                        class="h-8 w-8 bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/20"
                                        as-child
                                    >
                                        <Link :href="complete(todo).url" method="post" as="button" aria-label="Complete todo">
                                            <Check class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!group.todos.length">
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-muted-foreground">
                                No todos for this week.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        <div v-if="!groups.length" class="rounded-xl border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
            No todos for the selected filters.
        </div>
    </div>
</template>
