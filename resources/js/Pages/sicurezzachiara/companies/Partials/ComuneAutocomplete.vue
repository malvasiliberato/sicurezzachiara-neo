<script setup>
import axios from "axios";
import { computed, onBeforeUnmount, ref, watch } from "vue";

const props = defineProps({
  inputId: {
    type: String,
    default: "comune_search",
  },
  initialOption: {
    type: Object,
    default: null,
  },
  searchRoute: {
    type: String,
    required: true,
  },
  invalid: {
    type: Boolean,
    default: false,
  },
  placeholder: {
    type: String,
    default: "Cerca per città o CAP",
  },
});

const emit = defineEmits(["select", "clear"]);

const query = ref(props.initialOption?.label ?? "");
const selected = ref(props.initialOption);
const results = ref([]);
const loading = ref(false);
const open = ref(false);
let searchTimeout = null;
let blurTimeout = null;

const inputClasses = computed(() => ({
  "is-invalid": props.invalid,
}));

watch(
  () => props.initialOption,
  (option) => {
    selected.value = option;
    query.value = option?.label ?? "";
  },
  { immediate: true }
);

const runSearch = () => {
  const term = query.value.trim();

  if (term.length < 2) {
    results.value = [];
    open.value = false;
    loading.value = false;
    return;
  }

  loading.value = true;

  axios
    .get(props.searchRoute, {
      params: { q: term },
    })
    .then(({ data }) => {
      results.value = data.results ?? [];
      open.value = true;
    })
    .finally(() => {
      loading.value = false;
    });
};

const handleInput = (event) => {
  query.value = event.target.value;

  if (selected.value && query.value !== selected.value.label) {
    selected.value = null;
    emit("clear");
  }

  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(runSearch, 180);
};

const chooseOption = (option) => {
  selected.value = option;
  query.value = option.label;
  results.value = [];
  open.value = false;
  emit("select", option);
};

const clearSelection = () => {
  selected.value = null;
  query.value = "";
  results.value = [];
  open.value = false;
  emit("clear");
};

const handleFocus = () => {
  clearTimeout(blurTimeout);

  if (results.value.length > 0) {
    open.value = true;
  } else if (query.value.trim().length >= 2) {
    runSearch();
  }
};

const handleBlur = () => {
  blurTimeout = setTimeout(() => {
    open.value = false;

    if (!selected.value && props.initialOption) {
      selected.value = props.initialOption;
      query.value = props.initialOption.label;
    }
  }, 160);
};

onBeforeUnmount(() => {
  clearTimeout(searchTimeout);
  clearTimeout(blurTimeout);
});
</script>

<template>
  <div class="position-relative">
    <div class="input-group">
      <input
        :id="inputId"
        type="text"
        class="form-control"
        :class="inputClasses"
        :placeholder="placeholder"
        :value="query"
        autocomplete="off"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
      />
      <button
        v-if="selected"
        class="btn btn-soft-secondary"
        type="button"
        title="Pulisci selezione"
        aria-label="Pulisci selezione"
        @mousedown.prevent
        @click="clearSelection"
      >
        <i class="mdi mdi-broom fs-16"></i>
      </button>
    </div>

    <div v-if="open" class="dropdown-menu d-block w-100 mt-1 shadow-sm border">
      <div v-if="loading" class="dropdown-item-text text-muted">
        Ricerca in corso...
      </div>
      <div v-else-if="results.length === 0" class="dropdown-item-text text-muted">
        Nessun comune trovato. Prova con un altro nome o con il CAP.
      </div>
      <button
        v-for="option in results"
        :key="`${option.city}-${option.province}`"
        type="button"
        class="dropdown-item py-2"
        @mousedown.prevent
        @click="chooseOption(option)"
      >
        <div class="fw-semibold">{{ option.label }}</div>
        <div class="text-muted small text-wrap">{{ option.capLabel }}</div>
      </button>
    </div>
  </div>
</template>
