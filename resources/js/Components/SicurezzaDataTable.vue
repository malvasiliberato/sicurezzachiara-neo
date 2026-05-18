<script setup>
import { computed, ref } from "vue";
import {
  FlexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useVueTable,
} from "@tanstack/vue-table";

const props = defineProps({
  columns: {
    type: Array,
    required: true,
  },
  data: {
    type: Array,
    required: true,
  },
  striped: {
    type: Boolean,
    default: true,
  },
  tableClass: {
    type: String,
    default: "",
  },
  searchPlaceholder: {
    type: String,
    default: "Cerca...",
  },
  pageSizeOptions: {
    type: Array,
    default: () => [10, 25, 50],
  },
  initialPageSize: {
    type: Number,
    default: 10,
  },
  emptyTitle: {
    type: String,
    default: "Nessun risultato",
  },
  emptyText: {
    type: String,
    default: "Nessun record disponibile con i filtri correnti.",
  },
});

const sorting = ref([]);
const globalFilter = ref("");
const pagination = ref({
  pageIndex: 0,
  pageSize: props.initialPageSize,
});

const table = useVueTable({
  get data() {
    return props.data;
  },
  get columns() {
    return props.columns;
  },
  state: {
    get sorting() {
      return sorting.value;
    },
    get globalFilter() {
      return globalFilter.value;
    },
    get pagination() {
      return pagination.value;
    },
  },
  onSortingChange: (updater) => {
    sorting.value = typeof updater === "function" ? updater(sorting.value) : updater;
  },
  onGlobalFilterChange: (updater) => {
    globalFilter.value = typeof updater === "function" ? updater(globalFilter.value) : updater;
    pagination.value = {
      ...pagination.value,
      pageIndex: 0,
    };
  },
  onPaginationChange: (updater) => {
    pagination.value = typeof updater === "function" ? updater(pagination.value) : updater;
  },
  getCoreRowModel: getCoreRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
});

const visibleColumns = computed(() => table.getVisibleLeafColumns());
const totalRows = computed(() => props.data.length);
const filteredRows = computed(() => table.getFilteredRowModel().rows.length);
const pageRows = computed(() => table.getRowModel().rows);
const pageCount = computed(() => table.getPageCount());
const pageIndex = computed(() => table.getState().pagination.pageIndex);
const pageSize = computed(() => table.getState().pagination.pageSize);

const pageStart = computed(() => {
  if (filteredRows.value === 0) return 0;

  return pageIndex.value * pageSize.value + 1;
});

const pageEnd = computed(() => {
  if (filteredRows.value === 0) return 0;

  return Math.min((pageIndex.value + 1) * pageSize.value, filteredRows.value);
});

const sortIcon = (column) => {
  const sorted = column.getIsSorted();

  if (sorted === "asc") return "ri-arrow-up-s-line";
  if (sorted === "desc") return "ri-arrow-down-s-line";

  return "ri-expand-up-down-line";
};

const paginationLabel = computed(() => {
  if (filteredRows.value === 0) {
    return "0 risultati";
  }

  return `${pageStart.value}-${pageEnd.value} di ${filteredRows.value}`;
});
</script>

<template>
  <div class="d-flex flex-column gap-3">
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
      <div class="d-inline-flex align-items-center gap-2 flex-wrap">
        <label class="text-muted small mb-0">Mostra</label>
        <select
          class="form-select form-select-sm"
          style="width: 88px;"
          :value="pageSize"
          @change="table.setPageSize(Number($event.target.value))"
        >
          <option v-for="option in pageSizeOptions" :key="option" :value="option">
            {{ option }}
          </option>
        </select>
        <span class="text-muted small">righe</span>
      </div>

      <div class="d-flex align-items-center gap-2 ms-auto">
        <label class="text-muted small mb-0" for="sicurezza-table-search">Cerca</label>
        <input
          id="sicurezza-table-search"
          type="search"
          class="form-control form-control-sm"
          style="width: min(280px, 100%);"
          :placeholder="searchPlaceholder"
          :value="globalFilter"
          @input="table.setGlobalFilter($event.target.value)"
        />
      </div>
    </div>

    <div class="table-responsive">
    <table
      class="table align-middle mb-0 w-100"
      :class="[
        tableClass,
        {
          'table-striped': striped,
        },
      ]"
      style="table-layout: fixed;"
    >
      <colgroup>
        <col
          v-for="column in visibleColumns"
          :key="column.id"
          :style="column.columnDef.meta?.width ? { width: column.columnDef.meta.width } : undefined"
        >
      </colgroup>

      <thead class="table-light">
        <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
          <th
            v-for="header in headerGroup.headers"
            :key="header.id"
            :class="header.column.columnDef.meta?.thClass"
            :style="header.column.columnDef.meta?.thStyle"
          >
            <template v-if="!header.isPlaceholder">
              <button
                v-if="header.column.getCanSort()"
                type="button"
                class="btn btn-ghost-dark btn-sm d-inline-flex align-items-center gap-1 p-0 border-0 bg-transparent fw-semibold"
                @click="header.column.toggleSorting(header.column.getIsSorted() === 'asc')"
              >
                <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
                <i :class="sortIcon(header.column)" class="text-muted"></i>
              </button>
              <FlexRender
                v-else
                :render="header.column.columnDef.header"
                :props="header.getContext()"
              />
            </template>
          </th>
        </tr>
      </thead>

      <tbody>
        <tr v-if="pageRows.length === 0">
          <td :colspan="visibleColumns.length" class="text-center py-5">
            <div class="fw-semibold mb-1">{{ emptyTitle }}</div>
            <div class="text-muted fs-13">{{ emptyText }}</div>
          </td>
        </tr>
        <tr v-for="row in pageRows" v-else :key="row.id">
          <td
            v-for="cell in row.getVisibleCells()"
            :key="cell.id"
            :class="cell.column.columnDef.meta?.tdClass"
            :style="cell.column.columnDef.meta?.tdStyle"
          >
            <slot
              v-if="cell.column.columnDef.meta?.slot"
              :name="`cell-${cell.column.id}`"
              :row="row.original"
              :value="cell.getValue()"
              :cell="cell"
            />
            <FlexRender
              v-else
              :render="cell.column.columnDef.cell"
              :props="cell.getContext()"
            />
          </td>
        </tr>
      </tbody>
    </table>
    </div>

    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
      <div class="text-muted small">
        {{ paginationLabel }}
        <span v-if="filteredRows !== totalRows">su {{ totalRows }}</span>
      </div>

      <div class="d-inline-flex align-items-center gap-1">
        <button
          type="button"
          class="btn btn-soft-secondary btn-sm btn-icon"
          :disabled="!table.getCanPreviousPage()"
          @click="table.setPageIndex(0)"
        >
          <i class="ri-skip-left-line"></i>
        </button>
        <button
          type="button"
          class="btn btn-soft-secondary btn-sm btn-icon"
          :disabled="!table.getCanPreviousPage()"
          @click="table.previousPage()"
        >
          <i class="ri-arrow-left-s-line"></i>
        </button>
        <span class="text-muted small px-2">
          Pagina {{ pageCount === 0 ? 0 : pageIndex + 1 }} / {{ pageCount }}
        </span>
        <button
          type="button"
          class="btn btn-soft-secondary btn-sm btn-icon"
          :disabled="!table.getCanNextPage()"
          @click="table.nextPage()"
        >
          <i class="ri-arrow-right-s-line"></i>
        </button>
        <button
          type="button"
          class="btn btn-soft-secondary btn-sm btn-icon"
          :disabled="!table.getCanNextPage()"
          @click="table.setPageIndex(Math.max(pageCount - 1, 0))"
        >
          <i class="ri-skip-right-line"></i>
        </button>
      </div>
    </div>
  </div>
</template>
