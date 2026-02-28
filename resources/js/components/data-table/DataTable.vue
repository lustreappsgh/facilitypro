<!--
  Custom component: shadcn-vue does not provide a generic TanStack data-table shell.
  Needed to unify sorting, filtering, selection, and pagination with the reference layout.
-->
<script setup lang="ts" generic="TData">
import { computed, ref, useSlots } from "vue"
import type {
  ColumnDef,
  ColumnFiltersState,
  SortingState,
  VisibilityState,
} from "@tanstack/vue-table"
import { router } from "@inertiajs/vue3"
import {
  FlexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useVueTable,
} from "@tanstack/vue-table"
import { ArrowUpDown, ChevronDown, Search } from "lucide-vue-next"
import { Button } from "@/components/ui/button"
import { Checkbox } from "@/components/ui/checkbox"
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"

interface PageSizeOption {
  value: number
  label?: string
}

interface ServerPaginationConfig {
  currentPage: number
  lastPage: number
  perPage?: number
  prevUrl?: string | null
  nextUrl?: string | null
  only?: string[]
}

const props = withDefaults(defineProps<{
  data: TData[]
  columns: ColumnDef<TData, any>[]
  pageSize?: number
  pageSizeOptions?: PageSizeOption[]
  showSearch?: boolean
  searchPlaceholder?: string
  enableColumnToggle?: boolean
  showSelectionSummary?: boolean
  showPagination?: boolean
  enableRowSelection?: boolean
  serverPagination?: ServerPaginationConfig | null
}>(), {
  pageSize: 10,
  pageSizeOptions: () => [
    { value: 10 },
    { value: 20 },
    { value: 30 },
    { value: 40 },
    { value: 50 },
  ],
  showSearch: false,
  searchPlaceholder: "Search",
  enableColumnToggle: true,
  showSelectionSummary: true,
  showPagination: true,
  enableRowSelection: true,
  serverPagination: null,
})

const slots = useSlots()
const hasTabs = computed(() => Boolean(slots.tabs))
const hasFilters = computed(() => Boolean(slots.filters) || props.showSearch)
const isServerPagination = computed(() => Boolean(props.serverPagination))

const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const globalFilter = ref("")
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})

const table = useVueTable({
  get data() {
    return props.data
  },
  columns: props.columns,
  enableRowSelection: props.enableRowSelection,
  enableMultiRowSelection: props.enableRowSelection,
  state: {
    get sorting() { return sorting.value },
    get columnFilters() { return columnFilters.value },
    get globalFilter() { return globalFilter.value },
    get columnVisibility() { return columnVisibility.value },
    get rowSelection() { return rowSelection.value },
  },
  onSortingChange: (updaterOrValue) => {
    sorting.value = typeof updaterOrValue === "function"
      ? updaterOrValue(sorting.value)
      : updaterOrValue
  },
  onColumnFiltersChange: (updaterOrValue) => {
    columnFilters.value = typeof updaterOrValue === "function"
      ? updaterOrValue(columnFilters.value)
      : updaterOrValue
  },
  onGlobalFilterChange: (updaterOrValue) => {
    globalFilter.value = typeof updaterOrValue === "function"
      ? updaterOrValue(globalFilter.value)
      : updaterOrValue
  },
  onColumnVisibilityChange: (updaterOrValue) => {
    columnVisibility.value = typeof updaterOrValue === "function"
      ? updaterOrValue(columnVisibility.value)
      : updaterOrValue
  },
  onRowSelectionChange: (updaterOrValue) => {
    rowSelection.value = typeof updaterOrValue === "function"
      ? updaterOrValue(rowSelection.value)
      : updaterOrValue
  },
  getCoreRowModel: getCoreRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
  initialState: {
    pagination: {
      pageSize: props.pageSize,
    },
  },
})

const displayedPage = computed(() =>
  isServerPagination.value
    ? props.serverPagination?.currentPage ?? 1
    : table.getState().pagination.pageIndex + 1,
)

const displayedPageCount = computed(() =>
  isServerPagination.value
    ? props.serverPagination?.lastPage ?? 1
    : table.getPageCount(),
)

const displayedPageSize = computed(() =>
  isServerPagination.value
    ? props.serverPagination?.perPage ?? props.pageSize
    : table.getState().pagination.pageSize,
)

const canGoPrevious = computed(() =>
  isServerPagination.value
    ? Boolean(props.serverPagination?.prevUrl)
    : table.getCanPreviousPage(),
)

const canGoNext = computed(() =>
  isServerPagination.value
    ? Boolean(props.serverPagination?.nextUrl)
    : table.getCanNextPage(),
)

const goToPrevious = () => {
  if (!isServerPagination.value) {
    table.previousPage()
    return
  }

  if (!props.serverPagination?.prevUrl) return

  router.get(props.serverPagination.prevUrl, {}, {
    preserveState: true,
    preserveScroll: true,
    only: props.serverPagination.only,
  })
}

const goToNext = () => {
  if (!isServerPagination.value) {
    table.nextPage()
    return
  }

  if (!props.serverPagination?.nextUrl) return

  router.get(props.serverPagination.nextUrl, {}, {
    preserveState: true,
    preserveScroll: true,
    only: props.serverPagination.only,
  })
}
</script>

<template>
  <div class="flex flex-col gap-4">
    <div
      v-if="hasTabs || $slots.actions"
      class="flex flex-wrap items-center justify-between gap-3"
    >
      <div v-if="hasTabs" class="flex items-center gap-2">
        <slot name="tabs" :table="table" />
      </div>
      <div class="flex items-center gap-2">
        <slot name="actions" :table="table" />
        <DropdownMenu v-if="enableColumnToggle">
          <DropdownMenuTrigger as-child>
            <Button variant="outline" size="sm" class="gap-2">
              <span class="hidden sm:inline">Customize Columns</span>
              <span class="sm:hidden">Columns</span>
              <ChevronDown class="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-56">
            <template
              v-for="column in table.getAllColumns().filter((column) => typeof column.accessorFn !== 'undefined' && column.getCanHide())"
              :key="column.id"
            >
              <DropdownMenuCheckboxItem
                class="capitalize"
                :model-value="column.getIsVisible()"
                @update:model-value="(value) => column.toggleVisibility(!!value)"
              >
                {{ column.id }}
              </DropdownMenuCheckboxItem>
            </template>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>

    <div v-if="hasFilters" class="flex flex-wrap items-center gap-3">
      <div v-if="showSearch" class="relative min-w-[200px] flex-1">
        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <Input
          v-model="globalFilter"
          class="pl-9"
          :placeholder="searchPlaceholder"
        />
      </div>
      <slot name="filters" :table="table" />
    </div>

    <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
      <Table>
        <TableHeader class="border-b border-border/60 bg-muted/30 text-muted-foreground">
          <TableRow>
            <TableHead
              v-if="enableRowSelection"
              class="w-12 font-bold tracking-wider text-muted-foreground"
            >
              <Checkbox
                :model-value="table.getIsAllPageRowsSelected() || (table.getIsSomePageRowsSelected() && 'indeterminate')"
                aria-label="Select all rows"
                @update:model-value="(value) => table.toggleAllPageRowsSelected(!!value)"
              />
            </TableHead>
            <TableHead
              v-for="header in table.getHeaderGroups()[0]?.headers ?? []"
              :key="header.id"
              class="font-bold tracking-wider text-muted-foreground"
            >
              <div
                v-if="header.column.getCanSort()"
                class="flex items-center gap-1"
              >
                <Button
                  variant="ghost"
                  size="icon"
                  class="-ml-2 h-6 w-6 text-muted-foreground"
                  type="button"
                  @click="header.column.toggleSorting(header.column.getIsSorted() === 'asc')"
                >
                  <ArrowUpDown class="h-3.5 w-3.5" />
                </Button>
                <FlexRender
                  v-if="!header.isPlaceholder"
                  :render="header.column.columnDef.header"
                  :props="header.getContext()"
                />
              </div>
              <FlexRender
                v-else-if="!header.isPlaceholder"
                :render="header.column.columnDef.header"
                :props="header.getContext()"
              />
            </TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <template v-if="table.getRowModel().rows.length">
            <TableRow
              v-for="row in table.getRowModel().rows"
              :key="row.id"
              :data-state="row.getIsSelected() && 'selected'"
              class="border-b border-border/50 hover:bg-muted/40"
            >
              <TableCell v-if="enableRowSelection" class="w-12">
                <Checkbox
                  :model-value="row.getIsSelected()"
                  aria-label="Select row"
                  @update:model-value="(value) => row.toggleSelected(!!value)"
                />
              </TableCell>
              <TableCell
                v-for="cell in row.getVisibleCells()"
                :key="cell.id"
              >
                <FlexRender
                  :render="cell.column.columnDef.cell"
                  :props="cell.getContext()"
                />
              </TableCell>
            </TableRow>
          </template>
          <TableRow v-else>
            <TableCell
              :col-span="table.getAllColumns().length + (enableRowSelection ? 1 : 0)"
              class="h-24 text-center text-sm text-muted-foreground"
            >
              No results.
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <div
      v-if="(showSelectionSummary && enableRowSelection) || showPagination"
      class="flex flex-wrap items-center justify-between gap-3"
    >
      <div v-if="showSelectionSummary && enableRowSelection" class="text-sm text-muted-foreground">
        {{ table.getFilteredSelectedRowModel().rows.length }} of
        {{ table.getFilteredRowModel().rows.length }} row(s) selected.
      </div>
      <div v-if="showPagination" class="flex items-center gap-6">
        <div class="hidden items-center gap-2 sm:flex">
          <Label for="rows-per-page" class="text-sm font-medium">
            Rows per page
          </Label>
          <template v-if="isServerPagination">
            <div class="text-sm font-medium">{{ displayedPageSize }}</div>
          </template>
          <Select
            v-else
            :model-value="table.getState().pagination.pageSize"
            @update:model-value="(value) => table.setPageSize(Number(value))"
          >
            <SelectTrigger id="rows-per-page" size="sm" class="w-20">
              <SelectValue :placeholder="`${table.getState().pagination.pageSize}`" />
            </SelectTrigger>
            <SelectContent side="top">
              <SelectItem
                v-for="option in pageSizeOptions"
                :key="option.value"
                :value="`${option.value}`"
              >
                {{ option.label ?? option.value }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div class="text-sm font-medium">
          Page {{ displayedPage }} of
          {{ displayedPageCount }}
        </div>
        <div class="flex items-center gap-2">
          <Button
            variant="outline"
            size="icon"
            class="h-8 w-8"
            :disabled="!canGoPrevious"
            @click="goToPrevious"
          >
            <span class="sr-only">Go to previous page</span>
            <ChevronDown class="h-4 w-4 rotate-90" />
          </Button>
          <Button
            variant="outline"
            size="icon"
            class="h-8 w-8"
            :disabled="!canGoNext"
            @click="goToNext"
          >
            <span class="sr-only">Go to next page</span>
            <ChevronDown class="h-4 w-4 -rotate-90" />
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
