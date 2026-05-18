<script setup>
import axios from "axios";
import { computed, onBeforeUnmount, ref, watch } from "vue";

const props = defineProps({
  inputId: {
    type: String,
    default: "ateco_search",
  },
  modelValue: {
    type: [Number, String, null],
    default: null,
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
    default: "Cerca per codice o descrizione ATECO",
  },
});

const emit = defineEmits(["update:modelValue", "select"]);

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
    if (!props.modelValue && !option) {
      selected.value = null;
      query.value = "";
      return;
    }

    if (option && option.id === props.modelValue) {
      selected.value = option;
      query.value = option.label;
    }
  },
  { immediate: true }
);

watch(
  () => props.modelValue,
  (value) => {
    if (!value) {
      selected.value = null;
    }
  }
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
    emit("update:modelValue", null);
    emit("select", null);
  }

  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(runSearch, 180);
};

const chooseOption = (option) => {
  selected.value = option;
  query.value = option.label;
  results.value = [];
  open.value = false;
  emit("update:modelValue", option.id);
  emit("select", option);
};

const clearSelection = () => {
  selected.value = null;
  query.value = "";
  results.value = [];
  open.value = false;
  emit("update:modelValue", null);
  emit("select", null);
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

    if (!selected.value && props.initialOption && props.modelValue === props.initialOption.id) {
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
        v-if="modelValue"
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
        Nessun codice ATECO trovato. Prova con codice o parole chiave diverse.
      </div>
      <button
        v-for="option in results"
        :key="option.id"
        type="button"
        class="dropdown-item py-2"
        @mousedown.prevent
        @click="chooseOption(option)"
      >
        <div class="fw-semibold">{{ option.code }}</div>
        <div class="text-muted small text-wrap">{{ option.title }}</div>
      </button>
    </div>
  </div>
</template>
