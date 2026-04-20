<script setup>
import { Link } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";

defineProps({
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
});
</script>

<template>
  <BCard no-body class="border shadow-none mb-0">
    <BCardHeader class="bg-light-subtle border-0">
      <div>
        <h5 class="card-title mb-1">Sede operativa</h5>
        <p class="text-muted mb-0">Contenitore operativo reale dell'azienda, non solo indirizzo anagrafico.</p>
      </div>
    </BCardHeader>
    <BCardBody>
      <BRow class="g-3">
        <BCol md="8">
          <InputLabel for="site_name" value="Nome sede *" />
          <TextInput id="site_name" v-model="form.name" type="text" :class="{ 'is-invalid': form.errors.name }" />
          <InputError :message="form.errors.name" />
        </BCol>
        <BCol md="4">
          <InputLabel for="site_code" value="Codice sede" />
          <TextInput id="site_code" v-model="form.site_code" type="text" :class="{ 'is-invalid': form.errors.site_code }" />
          <InputError :message="form.errors.site_code" />
        </BCol>
        <BCol md="8">
          <InputLabel for="address_line" value="Indirizzo" />
          <TextInput id="address_line" v-model="form.address_line" type="text" :class="{ 'is-invalid': form.errors.address_line }" />
          <InputError :message="form.errors.address_line" />
        </BCol>
        <BCol md="4">
          <InputLabel for="postal_code" value="CAP" />
          <TextInput id="postal_code" v-model="form.postal_code" type="text" :class="{ 'is-invalid': form.errors.postal_code }" />
          <InputError :message="form.errors.postal_code" />
        </BCol>
        <BCol md="6">
          <InputLabel for="site_city" value="Citta'" />
          <TextInput id="site_city" v-model="form.city" type="text" :class="{ 'is-invalid': form.errors.city }" />
          <InputError :message="form.errors.city" />
        </BCol>
        <BCol md="2">
          <InputLabel for="site_province" value="Provincia" />
          <TextInput id="site_province" v-model="form.province" type="text" :class="{ 'is-invalid': form.errors.province }" />
          <InputError :message="form.errors.province" />
        </BCol>
        <BCol md="4" class="d-flex align-items-end">
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
    <BCardFooter class="bg-white border-top">
      <div class="hstack justify-content-end gap-2">
        <Link v-if="cancelHref" :href="cancelHref" class="btn btn-soft-secondary">Annulla</Link>
        <BButton variant="primary" type="submit" :disabled="form.processing">{{ submitLabel }}</BButton>
      </div>
    </BCardFooter>
  </BCard>
</template>
