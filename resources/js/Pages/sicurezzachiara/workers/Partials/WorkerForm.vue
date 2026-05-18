<script setup>
import { computed, watch } from "vue";
import { Link } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";

defineEmits(["cancel"]);

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
  formOptions: {
    type: Object,
    required: true,
  },
  submitLabel: {
    type: String,
    default: "Salva lavoratore",
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

const selectedSites = computed(() => {
  return props.formOptions.sitesByCompany?.[props.form.company_id] ?? [];
});

watch(
  () => props.form.company_id,
  () => {
    const siteStillAvailable = selectedSites.value.some((site) => site.id === Number(props.form.primary_site_id));

    if (!siteStillAvailable) {
      props.form.primary_site_id = "";
    }
  }
);
</script>

<template>
  <BCard no-body :class="{ 'border-0 shadow-none': embedded }">
    <BCardHeader v-if="!embedded" class="border-0">
      <div>
        <h4 class="card-title mb-1">Profilo lavoratore</h4>
        <p class="text-muted mb-0">Base anagrafica ed esposizione organizzativa minima, pronta per i moduli successivi.</p>
      </div>
    </BCardHeader>
    <BCardBody :class="{ 'px-0': embedded }">
      <input v-if="embedded" v-model="form.company_id" type="hidden">
      <BRow class="g-3">
        <BCol v-if="!embedded" md="12">
          <InputLabel for="company_id" value="Azienda" required />
          <select id="company_id" v-model="form.company_id" class="form-select" :class="{ 'is-invalid': form.errors.company_id }">
            <option value="">Seleziona azienda</option>
            <option v-for="company in formOptions.companies" :key="company.id" :value="company.id">
              {{ company.name }}
            </option>
          </select>
          <InputError :message="form.errors.company_id" />
        </BCol>
        <BCol md="12">
          <InputLabel for="primary_site_id" value="Sede prevalente" />
          <select
            id="primary_site_id"
            v-model="form.primary_site_id"
            class="form-select"
            :class="{ 'is-invalid': form.errors.primary_site_id }"
            :disabled="!form.company_id"
          >
            <option value="">Nessuna sede prevalente</option>
            <option v-for="site in selectedSites" :key="site.id" :value="site.id">
              {{ site.name }}{{ site.is_headquarters ? " - Sede principale" : "" }}
            </option>
          </select>
          <InputError :message="form.errors.primary_site_id" />
        </BCol>
        <BCol md="8">
          <InputLabel for="hire_date" value="Data assunzione" />
          <TextInput id="hire_date" v-model="form.hire_date" type="date" :class="{ 'is-invalid': form.errors.hire_date }" />
          <InputError :message="form.errors.hire_date" />
        </BCol>
        <BCol md="4">
          <InputLabel for="job_role_id" value="Mansione" />
          <select id="job_role_id" v-model="form.job_role_id" class="form-select" :class="{ 'is-invalid': form.errors.job_role_id }">
            <option value="">Nessuna mansione selezionata</option>
            <option v-for="jobRole in formOptions.jobRoles ?? []" :key="jobRole.id" :value="jobRole.id">
              {{ jobRole.name }}{{ jobRole.source === "core" ? " - core" : "" }}
            </option>
          </select>
          <InputError :message="form.errors.job_role_id" />
        </BCol>
        <BCol md="12">
          <InputLabel for="first_name" value="Nome" required />
          <TextInput id="first_name" v-model="form.first_name" type="text" :class="{ 'is-invalid': form.errors.first_name }" />
          <InputError :message="form.errors.first_name" />
        </BCol>
        <BCol md="12">
          <InputLabel for="last_name" value="Cognome" required />
          <TextInput id="last_name" v-model="form.last_name" type="text" :class="{ 'is-invalid': form.errors.last_name }" />
          <InputError :message="form.errors.last_name" />
        </BCol>
        <BCol md="6">
          <InputLabel for="birth_date" value="Data di nascita" />
          <TextInput id="birth_date" v-model="form.birth_date" type="date" :class="{ 'is-invalid': form.errors.birth_date }" />
          <InputError :message="form.errors.birth_date" />
        </BCol>
        <BCol md="6">
          <InputLabel for="tax_code" value="Codice fiscale" />
          <TextInput id="tax_code" v-model="form.tax_code" type="text" :class="{ 'is-invalid': form.errors.tax_code }" />
          <InputError :message="form.errors.tax_code" />
        </BCol>
        <BCol md="6">
          <InputLabel for="phone" value="Telefono" />
          <TextInput id="phone" v-model="form.phone" type="text" :class="{ 'is-invalid': form.errors.phone }" />
          <InputError :message="form.errors.phone" />
        </BCol>
        <BCol md="6">
          <InputLabel for="email" value="Email" />
          <TextInput id="email" v-model="form.email" type="email" :class="{ 'is-invalid': form.errors.email }" />
          <InputError :message="form.errors.email" />
        </BCol>
        <BCol md="12" class="d-flex align-items-end">
          <div class="form-check form-switch">
            <input
              id="worker_status"
              v-model="form.status"
              class="form-check-input"
              type="checkbox"
              true-value="active"
              false-value="inactive"
            >
            <label class="form-check-label" for="worker_status">
              Lavoratore attivo
            </label>
          </div>
          <InputError :message="form.errors.status" />
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
        <BButton v-if="embedded" variant="soft-secondary" type="button" @click="$emit('cancel')">{{ cancelLabel }}</BButton>
        <Link v-else-if="cancelHref" :href="cancelHref" class="btn btn-soft-secondary">{{ cancelLabel }}</Link>
        <Link v-else :href="route('workers.index')" class="btn btn-soft-secondary">{{ cancelLabel }}</Link>
        <BButton variant="primary" type="submit" :disabled="form.processing">{{ submitLabel }}</BButton>
      </div>
    </BCardFooter>
  </BCard>
</template>
