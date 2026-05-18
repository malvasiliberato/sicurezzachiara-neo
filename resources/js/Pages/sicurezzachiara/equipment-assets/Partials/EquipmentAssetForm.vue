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

const customEquipmentTypeSentinel = "__custom__";

const selectedEquipmentType = ref(
  props.form.equipment_type_id
    ? String(props.form.equipment_type_id)
    : props.form.custom_equipment_type_name
      ? customEquipmentTypeSentinel
      : ""
);

const showCustomEquipmentTypeField = computed(() => selectedEquipmentType.value === customEquipmentTypeSentinel);

watch(
  () => props.form.company_id,
  () => {
    const siteStillAvailable = availableSites.value.some((site) => site.id === Number(props.form.company_site_id));

    if (!siteStillAvailable) {
      props.form.company_site_id = "";
    }
  }
);

watch(selectedEquipmentType, (value) => {
  if (value === customEquipmentTypeSentinel) {
    props.form.equipment_type_id = "";

    return;
  }

  if (!value) {
    props.form.equipment_type_id = "";
    props.form.custom_equipment_type_name = "";

    return;
  }

  props.form.equipment_type_id = value;
  props.form.custom_equipment_type_name = "";
});

watch(
  () => [props.form.equipment_type_id, props.form.custom_equipment_type_name],
  ([equipmentTypeId, customName]) => {
    if (equipmentTypeId) {
      selectedEquipmentType.value = String(equipmentTypeId);

      return;
    }

    selectedEquipmentType.value = customName ? customEquipmentTypeSentinel : "";
  }
);
</script>

<template>
  <BCard no-body :class="{ 'border-0 shadow-none': embedded }">
    <BCardHeader v-if="!embedded" class="border-0">
      <div>
        <h4 class="card-title mb-1">Macchinario operativo</h4>
        <p class="text-muted mb-0">Istanza reale collegata ad azienda, sede e tipologia riusabile del catalogo.</p>
      </div>
    </BCardHeader>
    <BCardBody :class="{ 'px-0': embedded }">
      <input v-if="embedded" v-model="form.company_id" type="hidden">
      <BRow class="g-3">
        <BCol md="8">
          <InputLabel for="name" value="Nome macchinario" required />
          <TextInput id="name" v-model="form.name" type="text" :class="{ 'is-invalid': form.errors.name }" />
          <InputError :message="form.errors.name" />
        </BCol>
        <BCol md="4">
          <InputLabel for="asset_code" value="Codice bene" />
          <TextInput id="asset_code" v-model="form.asset_code" type="text" :class="{ 'is-invalid': form.errors.asset_code }" />
          <InputError :message="form.errors.asset_code" />
        </BCol>
        <BCol v-if="!embedded" md="12">
          <InputLabel for="company_id" value="Azienda" required />
          <select id="company_id" v-model="form.company_id" class="form-select" :class="{ 'is-invalid': form.errors.company_id }">
            <option value="">Seleziona azienda</option>
            <option v-for="company in formOptions.companies" :key="company.id" :value="company.id">{{ company.name }}</option>
          </select>
          <InputError :message="form.errors.company_id" />
        </BCol>
        <BCol md="12">
          <InputLabel for="company_site_id" value="Sede operativa" />
          <select id="company_site_id" v-model="form.company_site_id" class="form-select" :class="{ 'is-invalid': form.errors.company_site_id }">
            <option value="">Nessuna sede specifica</option>
            <option v-for="site in availableSites" :key="site.id" :value="site.id">
              {{ site.name }}{{ site.is_headquarters ? " - principale" : "" }}
            </option>
          </select>
          <InputError :message="form.errors.company_site_id" />
        </BCol>
        <BCol md="12">
          <InputLabel for="equipment_type_id" value="Tipologia macchinario" required />
          <select id="equipment_type_id" v-model="selectedEquipmentType" class="form-select" :class="{ 'is-invalid': form.errors.equipment_type_id || form.errors.custom_equipment_type_name }">
            <option value="">Seleziona tipologia</option>
            <option v-for="equipmentType in formOptions.equipmentTypes" :key="equipmentType.id" :value="equipmentType.id">
              {{ equipmentType.name }}{{ equipmentType.source === "core" ? " - core" : "" }}
            </option>
            <option :value="customEquipmentTypeSentinel">Altro</option>
          </select>
          <InputError :message="form.errors.equipment_type_id" />
        </BCol>
        <BCol v-if="showCustomEquipmentTypeField" md="12">
          <InputLabel for="custom_equipment_type_name" value="Tipologia personalizzata" required />
          <TextInput
            id="custom_equipment_type_name"
            v-model="form.custom_equipment_type_name"
            type="text"
            placeholder="Inserisci la tipologia macchinario"
            :class="{ 'is-invalid': form.errors.custom_equipment_type_name }"
          />
          <InputError :message="form.errors.custom_equipment_type_name" />
        </BCol>
        <BCol md="6">
          <InputLabel for="manufacturer" value="Costruttore" />
          <TextInput id="manufacturer" v-model="form.manufacturer" type="text" :class="{ 'is-invalid': form.errors.manufacturer }" />
          <InputError :message="form.errors.manufacturer" />
        </BCol>
        <BCol md="6">
          <InputLabel for="model" value="Modello" />
          <TextInput id="model" v-model="form.model" type="text" :class="{ 'is-invalid': form.errors.model }" />
          <InputError :message="form.errors.model" />
        </BCol>
        <BCol md="12" class="d-flex align-items-end">
          <div class="form-check form-switch">
            <input
              id="equipment_status"
              v-model="form.status"
              class="form-check-input"
              type="checkbox"
              true-value="active"
              false-value="inactive"
            >
            <label class="form-check-label" for="equipment_status">
              Macchinario attivo
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
        <Link v-else :href="route('equipment-assets.index')" class="btn btn-soft-secondary">{{ cancelLabel }}</Link>
        <BButton variant="primary" type="submit" :disabled="form.processing">{{ submitLabel }}</BButton>
      </div>
    </BCardFooter>
  </BCard>
</template>
