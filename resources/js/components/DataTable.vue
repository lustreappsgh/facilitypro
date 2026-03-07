<script lang="ts">
import { z } from 'zod';

export const schema = z.object({
    id: z.number(),
    header: z.string(),
    type: z.string(),
    status: z.string(),
    target: z.string(),
    limit: z.string(),
    reviewer: z.string(),
});
</script>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

interface TableData {
    id: number;
    header: string;
    type: string;
    status: string;
    target: string;
    limit: string;
    reviewer: string;
}

defineProps<{
    data: TableData[];
}>();
</script>

<template>
    <div class="overflow-hidden rounded-lg border">
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Header</TableHead>
                    <TableHead>Type</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Target</TableHead>
                    <TableHead>Limit</TableHead>
                    <TableHead>Reviewer</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="row in data" :key="row.id">
                    <TableCell>{{ row.header }}</TableCell>
                    <TableCell>
                        <Badge variant="outline">{{ row.type }}</Badge>
                    </TableCell>
                    <TableCell>{{ row.status }}</TableCell>
                    <TableCell class="font-mono text-xs">{{
                        row.target
                    }}</TableCell>
                    <TableCell class="font-mono text-xs">{{
                        row.limit
                    }}</TableCell>
                    <TableCell>{{ row.reviewer }}</TableCell>
                </TableRow>
                <TableRow v-if="data.length === 0">
                    <TableCell
                        colspan="6"
                        class="h-24 text-center text-muted-foreground"
                    >
                        No results.
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
