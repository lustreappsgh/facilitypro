<script setup lang="ts" generic="TData, TValue">
import { ref } from 'vue';
import {
    type ColumnDef,
    type SortingState,
    type ColumnFiltersState,
    type VisibilityState,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
    FlexRender,
} from '@tanstack/vue-table';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card } from '@/components/ui/card';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight, Settings2, Search } from 'lucide-vue-next';

interface Props {
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
    searchPlaceholder?: string;
    searchColumn?: string;
}

const props = defineProps<Props>();

const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});
const rowSelection = ref({});

const table = useVueTable({
    get data() { return props.data },
    get columns() { return props.columns },
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    onSortingChange: updaterOrValue => sorting.value = typeof updaterOrValue === 'function' ? updaterOrValue(sorting.value) : updaterOrValue,
    onColumnFiltersChange: updaterOrValue => columnFilters.value = typeof updaterOrValue === 'function' ? updaterOrValue(columnFilters.value) : updaterOrValue,
    onColumnVisibilityChange: updaterOrValue => columnVisibility.value = typeof updaterOrValue === 'function' ? updaterOrValue(columnVisibility.value) : updaterOrValue,
    onRowSelectionChange: updaterOrValue => rowSelection.value = typeof updaterOrValue === 'function' ? updaterOrValue(rowSelection.value) : updaterOrValue,
    state: {
        get sorting() { return sorting.value },
        get columnFilters() { return columnFilters.value },
        get columnVisibility() { return columnVisibility.value },
        get rowSelection() { return rowSelection.value },
    },
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <div class="flex flex-1 items-center gap-2 max-w-sm">
                <div v-if="searchColumn" class="relative w-full">
                    <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input :placeholder="searchPlaceholder || 'Search...'"
                        :model-value="(table.getColumn(searchColumn)?.getFilterValue() as string) ?? ''"
                        class="pl-9 h-9" @update:model-value="table.getColumn(searchColumn)?.setFilterValue($event)" />
                </div>
                <slot name="filters" :table="table" />
            </div>

            <div class="flex items-center gap-2">
                <slot name="actions" :table="table" />
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" size="sm" class="h-9 gap-1.5 px-3">
                            <Settings2 class="h-4 w-4" />
                            <span class="hidden sm:inline">View</span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-[150px]">
                        <DropdownMenuCheckboxItem
                            v-for="column in table.getAllColumns().filter(col => col.getCanHide())" :key="column.id"
                            class="capitalize" :model-value="column.getIsVisible()"
                            @update:model-value="(value) => column.toggleVisibility(!!value)">
                            {{ column.id }}
                        </DropdownMenuCheckboxItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>

        <Card class="overflow-hidden border-border/50">
            <div class="relative w-full overflow-auto">
                <Table>
                    <TableHeader class="bg-muted/50">
                        <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                            <TableHead v-for="header in headerGroup.headers" :key="header.id"
                                class="h-10 text-[10px] font-bold uppercase tracking-wider text-muted-foreground">
                                <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header"
                                    :props="header.getContext()" />
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="table.getRowModel().rows?.length">
                            <TableRow v-for="row in table.getRowModel().rows" :key="row.id"
                                class="border-b border-border/50 transition-colors hover:bg-muted/30"
                                :data-state="row.getIsSelected() && 'selected'">
                                <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id" class="py-3 px-4">
                                    <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                </TableCell>
                            </TableRow>
                        </template>
                        <TableRow v-else>
                            <TableCell :colspan="columns.length" class="h-24 text-center text-muted-foreground italic">
                                No results found.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </Card>

        <div class="flex items-center justify-between px-2">
            <div class="flex-1 text-xs text-muted-foreground">
                {{ table.getFilteredSelectedRowModel().rows.length }} of
                {{ table.getFilteredRowModel().rows.length }} row(s) selected.
            </div>
            <div class="flex items-center space-x-6 lg:space-x-8">
                <div class="flex items-center space-x-2">
                    <p class="text-xs font-medium">Rows per page</p>
                    <Select :model-value="`${table.getState().pagination.pageSize}`"
                        @update:model-value="(val) => table.setPageSize(Number(val))">
                        <SelectTrigger class="h-8 w-[70px]">
                            <SelectValue :placeholder="`${table.getState().pagination.pageSize}`" />
                        </SelectTrigger>
                        <SelectContent side="top">
                            <SelectItem v-for="pageSize in [10, 20, 30, 40, 50]" :key="pageSize" :value="`${pageSize}`">
                                {{ pageSize }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="flex w-[100px] items-center justify-center text-xs font-medium">
                    Page {{ table.getState().pagination.pageIndex + 1 }} of
                    {{ table.getPageCount() }}
                </div>
                <div class="flex items-center space-x-2">
                    <Button variant="outline" class="hidden h-8 w-8 p-0 lg:flex" :disabled="!table.getCanPreviousPage()"
                        @click="table.setPageIndex(0)">
                        <ChevronsLeft class="h-4 w-4" />
                    </Button>
                    <Button variant="outline" class="h-8 w-8 p-0" :disabled="!table.getCanPreviousPage()"
                        @click="table.previousPage()">
                        <ChevronLeft class="h-4 w-4" />
                    </Button>
                    <Button variant="outline" class="h-8 w-8 p-0" :disabled="!table.getCanNextPage()"
                        @click="table.nextPage()">
                        <ChevronRight class="h-4 w-4" />
                    </Button>
                    <Button variant="outline" class="hidden h-8 w-8 p-0 lg:flex" :disabled="!table.getCanNextPage()"
                        @click="table.setPageIndex(table.getPageCount() - 1)">
                        <ChevronsRight class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
