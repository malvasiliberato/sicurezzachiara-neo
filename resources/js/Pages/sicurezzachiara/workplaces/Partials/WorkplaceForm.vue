<script setup>
import { computed, ref, watch } from "vue";
import { Link } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";

defineEmits(["cancel"]);

const props = defineProps({
  form: Object,
  formOptions: Object,
  submitLabel: String,
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

const availableSites = computed(() => {
  if (!props.form.company_id) {
    return [];
  }

  return props.formOptions.sitesByCompany?.[props.form.company_id] ?? props.formOptions.sitesByCompany?.[String(props.form.company_id)] ?? [];
});

const customWorkplaceTypeSentinel = "__custom__";
const selectedWorkplaceType = ref(
  props.form.workplace_type_id
    ? String(props.form.workplace_type_id)
    : props.form.custom_workplace_type_name
      ? customWorkplaceTypeSentinel
      : ""
);

const showCustomWorkplaceTypeField = computed(() => selectedWorkplaceType.value === customWorkplaceTypeSentinel);

watch(selectedWorkplaceType, (value) => {
  if (value === customWorkplaceTypeSentinel) {
    props.form.workplace_type_id = "";

    return;
  }

  if (!value) {
    props.form.workplace_type_id = "";
    props.form.custom_workplace_type_name = "";

    return;
  }

  props.form.workplace_type_id = value;
  props.form.custom_workplace_type_name = "";
});

watch(
  () => [props.form.workplace_type_id, props.form.custom_workplace_type_name],
  ([workplaceTypeId, customName]) => {
    if (workplaceTypeId) {
      selectedWorkplaceType.value = String(workplaceTypeId);

      return;
    }

    selectedWorkplaceType.value = customName ? customWorkplaceTypeSentinel : "";
  }
);

watch(
  () => props.form.company_id,
  () => {
    const siteStillAvailable = availableSites.value.some((site) => site.id === Number(props.form.company_site_id));

    if (!siteStillAvailable) {
      props.form.company_site_id = "";
    }
  }
);
</script>

<template>
  <BCard no-body :class="{ 'border-0 shadow-none': embedded }">
    <BCardHeader v-if="!embedded" class="border-0">
      <div>
        <h4 class="card-title mb-1">Luogo operativo</h4>
        <p class="text-muted mb-0">Istanza reale collegata a sede aziendale e tipologia luogo riusabile del catalogo.</p>
      </div>
    </BCardHeader>
    <BCardBody :class="{ 'px-0': embedded }">
      <input v-if="embedded" v-model="form.company_id" type="hidden">
      <BRow class="g-3">
        <BCol md="8">
          <InputLabel for="workplace_name" value="Nome luogo" required />
          <TextInput id="workplace_name" v-model="form.name" type="text" :class="{ 'is-invalid': form.errors.name }" />
          <InputError :message="form.errors.name" />
        </BCol>
        <BCol md="4">
          <InputLabel for="workplace_code" value="Codice luogo" />
          <TextInput id="workplace_code" v-model="form.code" type="text" :class="{ 'is-invalid': form.errors.code }" />
          <InputError :message="form.errors.code" />
        </BCol>
        <BCol v-if="!embedded" md="6">
          <InputLabel for="company_id" value="Azienda di riferimento" required />
          <select id="company_id" v-model="form.company_id" class="form-select" :class="{ 'is-invalid': form.errors.company_id }">
            <option value="">Seleziona azienda</option>
            <option v-for="company in formOptions.companies" :key="company.id" :value="company.id">{{ company.name }}</option>
          </select>
          <InputError :message="form.errors.company_id" />
        </BCol>
        <BCol :md="12">
          <InputLabel for="company_site_id" value="Sede operativa" required />
          <select id="company_site_id" v-model="form.company_site_id" class="form-select" :class="{ 'is-invalid': form.errors.company_site_id }">
            <option value="">Seleziona sede</option>
            <option v-for="site in availableSites" :key="site.id" :value="site.id">
              {{ site.name }}{{ site.is_headquarters ? " - principale" : "" }}
            </option>
          </select>
          <InputError :message="form.errors.company_site_id" />
        </BCol>
        <BCol :md="12">
          <InputLabel for="workplace_type_id" value="Tipologia luogo" required />
          <select id="workplace_type_id" v-model="selectedWorkplaceType" class="form-select" :class="{ 'is-invalid': form.errors.workplace_type_id || form.errors.custom_workplace_type_name }">
            <option value="">Seleziona tipologia</option>
            <option v-for="workplaceType in formOptions.workplaceTypes" :key="workplaceType.id" :value="workplaceType.id">
              {{ workplaceType.name }}{{ workplaceType.source === "core" ? " - core" : "" }}
            </option>
            <option :value="customWorkplaceTypeSentinel">Altro</option>
          </select>
          <InputError :message="form.errors.workplace_type_id" />
        </BCol>
        <BCol v-if="showCustomWorkplaceTypeField" :md="12">
          <InputLabel for="custom_workplace_type_name" value="Tipologia personalizzata" required />
          <TextInput
            id="custom_workplace_type_name"
            v-model="form.custom_workplace_type_name"
            type="text"
            placeholder="Inserisci la tipologia luogo"
            :class="{ 'is-invalid': form.errors.custom_workplace_type_name }"
          />
          <InputError :message="form.errors.custom_workplace_type_name" />
        </BCol>
        <BCol :md="embedded ? 12 : 12" class="d-flex align-items-end">
          <div class="form-check form-switch">
            <input
              id="workplace_status"
              v-model="form.status"
              class="form-check-input"
              type="checkbox"
              true-value="active"
              false-value="inactive"
            >
            <label class="form-check-label" for="workplace_status">
              Luogo attivo
            </label>
          </div>
          <InputError :message="form.errors.status" />
        </BCol>
        <BCol md="12">
          <InputLabel for="notes" value="Note sul luogo" />
          <textarea id="notes" v-model="form.notes" rows="4" class="form-control" :class="{ 'is-invalid': form.errors.notes }"></textarea>
          <InputError :message="form.errors.notes" />
        </BCol>
      </BRow>
    </BCardBody>
    <BCardFooter class="bg-white border-top" :class="{ 'px-0 pb-0': embedded }">
      <div class="hstack justify-content-end gap-2">
        <BButton v-if="embedded" variant="soft-secondary" type="button" @click="$emit('cancel')">{{ cancelLabel }}</BButton>
        <Link v-else-if="cancelHref" :href="cancelHref" class="btn btn-soft-secondary">{{ cancelLabel }}</Link>
        <Link v-else :href="route('workplaces.index')" class="btn btn-soft-secondary">{{ cancelLabel }}</Link>
        <BButton variant="primary" type="submit" :disabled="form.processing">{{ submitLabel }}</BButton>
      </div>
    </BCardFooter>
  </BCard>
</template>
