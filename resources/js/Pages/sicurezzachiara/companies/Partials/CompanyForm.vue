<script setup>
import { computed, ref } from "vue";
import { Link } from "@inertiajs/vue3";
import AtecoAutocomplete from "./AtecoAutocomplete.vue";
import ComuneAutocomplete from "./ComuneAutocomplete.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
  atecoConfig: {
    type: Object,
    default: null,
  },
  comuniConfig: {
    type: Object,
    default: null,
  },
  submitLabel: {
    type: String,
    default: "Salva azienda",
  },
  lightCreate: {
    type: Boolean,
    default: false,
  },
  embedded: {
    type: Boolean,
    default: false,
  },
  cancelHref: {
    type: String,
    default: null,
  },
  cancelLabel: {
    type: String,
    default: "Annulla",
  },
});

const handleAtecoSelect = (option) => {
  props.form.industry = option?.title ?? "";
};

const selectedComune = ref(props.comuniConfig?.initialOption ?? null);

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

  if (!option) {
    props.form.postal_code = "";
    return;
  }

  props.form.postal_code = option.caps?.length === 1
    ? option.caps[0]
    : option.caps?.[0] ?? "";
};

const clearComuneSelection = () => {
  selectedComune.value = null;
  props.form.city = "";
  props.form.province = "";
  props.form.postal_code = "";
};
</script>

<template>
  <BCard no-body :class="{ 'border-0 shadow-none': embedded }">
    <BCardHeader v-if="!embedded" class="border-0">
      <div>
        <h4 class="card-title mb-1">Anagrafica azienda</h4>
        <p class="text-muted mb-0">Base essenziale del cliente gestito dal tenant consulenziale.</p>
      </div>
    </BCardHeader>
    <BCardBody :class="{ 'px-0': embedded }">
      <BRow class="g-3">
        <BCol md="6">
          <InputLabel for="name" value="Nome azienda" required />
          <TextInput id="name" v-model="form.name" type="text" :class="{ 'is-invalid': form.errors.name }" />
          <InputError :message="form.errors.name" />
        </BCol>
        <BCol md="6">
          <InputLabel for="industry" value="Settore / attivita'" />
          <AtecoAutocomplete
            input-id="industry"
            v-model="form.ateco_2025_id"
            :initial-option="atecoConfig?.initialOption ?? null"
            :search-route="atecoConfig?.searchRoute"
            :invalid="Boolean(form.errors.ateco_2025_id || form.errors.industry)"
            @select="handleAtecoSelect"
          />
          <div class="form-text">Usalo per descrivere l'attivita' aziendale in modo orientativo.</div>
          <InputError :message="form.errors.ateco_2025_id" />
          <InputError :message="form.errors.industry" />
        </BCol>
        <BCol md="3">
          <InputLabel for="vat_number" value="Partita IVA" />
          <TextInput id="vat_number" v-model="form.vat_number" type="text" :class="{ 'is-invalid': form.errors.vat_number }" />
          <InputError :message="form.errors.vat_number" />
        </BCol>
        <BCol md="3">
          <InputLabel for="tax_code" value="Codice fiscale" />
          <TextInput id="tax_code" v-model="form.tax_code" type="text" :class="{ 'is-invalid': form.errors.tax_code }" />
          <InputError :message="form.errors.tax_code" />
        </BCol>
        <BCol md="3">
          <InputLabel for="contact_email" value="Email referente" />
          <TextInput id="contact_email" v-model="form.contact_email" type="email" :class="{ 'is-invalid': form.errors.contact_email }" />
          <InputError :message="form.errors.contact_email" />
        </BCol>
        <BCol md="3">
          <InputLabel for="contact_phone" value="Telefono referente" />
          <TextInput id="contact_phone" v-model="form.contact_phone" type="text" :class="{ 'is-invalid': form.errors.contact_phone }" />
          <InputError :message="form.errors.contact_phone" />
        </BCol>
        <BCol md="5">
          <InputLabel for="address_line" value="Indirizzo" />
          <TextInput id="address_line" v-model="form.address_line" type="text" :class="{ 'is-invalid': form.errors.address_line }" />
          <InputError :message="form.errors.address_line" />
        </BCol>
        <BCol md="1">
          <InputLabel for="street_number" value="Civico" />
          <TextInput id="street_number" v-model="form.street_number" type="text" :class="{ 'is-invalid': form.errors.street_number }" />
          <InputError :message="form.errors.street_number" />
        </BCol>
        <BCol md="3">
          <InputLabel for="city" value="Citta'" />
          <ComuneAutocomplete
            input-id="city"
            :initial-option="comuniConfig?.initialOption ?? null"
            :search-route="comuniConfig?.searchRoute"
            :invalid="Boolean(form.errors.city)"
            @select="handleComuneSelect"
            @clear="clearComuneSelection"
          />
          <InputError :message="form.errors.city" />
        </BCol>
        <BCol md="1">
          <InputLabel for="province" value="Provincia" />
          <TextInput
            id="province"
            v-model="form.province"
            type="text"
            readonly
            :class="{ 'is-invalid': form.errors.province }"
          />
          <InputError :message="form.errors.province" />
        </BCol>
        <BCol md="2">
          <InputLabel for="postal_code" value="CAP" />
          <select
            v-if="hasMultipleCaps"
            id="postal_code"
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
            id="postal_code"
            v-model="form.postal_code"
            type="text"
            readonly
            :class="{ 'is-invalid': form.errors.postal_code }"
          />
          <InputError :message="form.errors.postal_code" />
        </BCol>
        <BCol md="12">
          <InputLabel for="notes" value="Note operative" />
          <textarea id="notes" v-model="form.notes" rows="4" class="form-control" :class="{ 'is-invalid': form.errors.notes }"></textarea>
          <InputError :message="form.errors.notes" />
        </BCol>
      </BRow>
    </BCardBody>
    <BCardFooter class="bg-white border-top" :class="{ 'px-0 pb-0': embedded }">
      <div class="hstack justify-content-end gap-2">
        <Link v-if="cancelHref" :href="cancelHref" class="btn btn-soft-secondary">{{ cancelLabel }}</Link>
        <Link v-else-if="!embedded" :href="route('companies.index')" class="btn btn-soft-secondary">{{ cancelLabel }}</Link>
        <BButton variant="primary" type="submit" :disabled="form.processing">{{ submitLabel }}</BButton>
      </div>
    </BCardFooter>
  </BCard>
</template>
