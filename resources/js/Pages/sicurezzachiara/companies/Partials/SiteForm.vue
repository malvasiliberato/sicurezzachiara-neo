<script setup>
import { computed, ref, watch } from "vue";
import { Link } from "@inertiajs/vue3";
import ComuneAutocomplete from "./ComuneAutocomplete.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";

defineEmits(["cancel"]);

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
  submitLabel: {
    type: String,
    default: "Salva sede",
  },
  cancelHref: {
    type: String,
    default: null,
  },
  embedded: {
    type: Boolean,
    default: false,
  },
  cancelLabel: {
    type: String,
    default: "Annulla",
  },
  comuniConfig: {
    type: Object,
    default: null,
  },
});

const selectedComune = ref(props.comuniConfig?.initialOption ?? null);

watch(
  () => props.comuniConfig?.initialOption,
  (option) => {
    selectedComune.value = option ?? null;
  },
  { immediate: true }
);

const capOptions = computed(() =>
  (selectedComune.value?.caps ?? []).map((cap) => ({
    value: cap,
    label: cap,
  }))
);

const hasMultipleCaps = computed(() => capOptions.value.length > 1);

const handleComuneSelect = (option) => {
  selectedComune.value = option;
  props.form.city = option?.city ?? "";
  props.form.province = option?.province ?? "";
  props.form.postal_code = option?.caps?.[0] ?? "";
};

const clearComuneSelection = () => {
  selectedComune.value = null;
  props.form.city = "";
  props.form.province = "";
  props.form.postal_code = "";
};
</script>

<template>
  <BCard no-body class="border shadow-none mb-0" :class="{ 'border-0 shadow-none': embedded }">
    <BCardHeader v-if="!embedded" class="bg-light-subtle border-0">
      <div>
        <h5 class="card-title mb-1">Sede operativa</h5>
        <p class="text-muted mb-0">Contenitore operativo reale dell'azienda, non solo indirizzo anagrafico.</p>
      </div>
    </BCardHeader>
    <BCardBody :class="{ 'px-0': embedded }">
      <BRow class="g-3">
        <BCol md="8">
          <InputLabel for="site_name" value="Nome sede" required />
          <TextInput id="site_name" v-model="form.name" type="text" :class="{ 'is-invalid': form.errors.name }" />
          <InputError :message="form.errors.name" />
        </BCol>
        <BCol md="4">
          <InputLabel for="site_code" value="Codice sede" />
          <TextInput id="site_code" v-model="form.site_code" type="text" :class="{ 'is-invalid': form.errors.site_code }" />
          <InputError :message="form.errors.site_code" />
        </BCol>
        <BCol md="10">
          <InputLabel for="address_line" value="Indirizzo" />
          <TextInput id="address_line" v-model="form.address_line" type="text" :class="{ 'is-invalid': form.errors.address_line }" />
          <InputError :message="form.errors.address_line" />
        </BCol>
        <BCol md="2">
          <InputLabel for="site_street_number" value="Civico" />
          <TextInput id="site_street_number" v-model="form.street_number" type="text" :class="{ 'is-invalid': form.errors.street_number }" />
          <InputError :message="form.errors.street_number" />
        </BCol>
        <BCol md="6">
          <InputLabel for="site_city" value="Citta'" />
          <ComuneAutocomplete
            input-id="site_city"
            :initial-option="comuniConfig?.initialOption ?? null"
            :search-route="comuniConfig?.searchRoute"
            :invalid="Boolean(form.errors.city)"
            @select="handleComuneSelect"
            @clear="clearComuneSelection"
          />
          <InputError :message="form.errors.city" />
        </BCol>
        <BCol md="2">
          <InputLabel for="site_province" value="Provincia" />
          <TextInput id="site_province" v-model="form.province" type="text" readonly :class="{ 'is-invalid': form.errors.province }" />
          <InputError :message="form.errors.province" />
        </BCol>
        <BCol md="4">
          <InputLabel for="site_postal_code" value="CAP" />
          <select
            v-if="hasMultipleCaps"
            id="site_postal_code"
            v-model="form.postal_code"
            class="form-select"
            :class="{ 'is-invalid': form.errors.postal_code }"
          >
            <option v-for="option in capOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
          <TextInput
            v-else
            id="site_postal_code"
            v-model="form.postal_code"
            type="text"
            readonly
            :class="{ 'is-invalid': form.errors.postal_code }"
          />
          <InputError :message="form.errors.postal_code" />
        </BCol>
        <BCol md="12" class="d-flex align-items-end">
          <div class="form-check form-switch">
            <input id="is_headquarters" v-model="form.is_headquarters" class="form-check-input" type="checkbox">
            <label class="form-check-label" for="is_headquarters">Sede principale</label>
          </div>
        </BCol>
        <BCol md="12">
          <InputLabel for="site_notes" value="Note operative" />
          <textarea id="site_notes" v-model="form.notes" rows="3" class="form-control" :class="{ 'is-invalid': form.errors.notes }"></textarea>
          <InputError :message="form.errors.notes" />
        </BCol>
      </BRow>
    </BCardBody>
    <BCardFooter class="bg-white border-top" :class="{ 'px-0 pb-0': embedded }">
      <div class="hstack justify-content-end gap-2">
        <BButton v-if="embedded" variant="soft-secondary" type="button" @click="$emit('cancel')">{{ cancelLabel }}</BButton>
        <Link v-else-if="cancelHref" :href="cancelHref" class="btn btn-soft-secondary">{{ cancelLabel }}</Link>
        <BButton variant="primary" type="submit" :disabled="form.processing">{{ submitLabel }}</BButton>
      </div>
    </BCardFooter>
  </BCard>
</template>
