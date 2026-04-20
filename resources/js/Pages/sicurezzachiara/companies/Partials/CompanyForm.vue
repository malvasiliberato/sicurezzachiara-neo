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
    default: "Salva azienda",
  },
});
</script>

<template>
  <BCard no-body>
    <BCardHeader class="border-0">
      <div>
        <h4 class="card-title mb-1">Anagrafica azienda</h4>
        <p class="text-muted mb-0">Base essenziale del cliente gestito dal tenant consulenziale.</p>
      </div>
    </BCardHeader>
    <BCardBody>
      <BRow class="g-3">
        <BCol md="6">
          <InputLabel for="name" value="Nome azienda *" />
          <TextInput id="name" v-model="form.name" type="text" :class="{ 'is-invalid': form.errors.name }" />
          <InputError :message="form.errors.name" />
        </BCol>
        <BCol md="6">
          <InputLabel for="legal_name" value="Ragione sociale" />
          <TextInput id="legal_name" v-model="form.legal_name" type="text" :class="{ 'is-invalid': form.errors.legal_name }" />
          <InputError :message="form.errors.legal_name" />
        </BCol>
        <BCol md="4">
          <InputLabel for="vat_number" value="Partita IVA" />
          <TextInput id="vat_number" v-model="form.vat_number" type="text" :class="{ 'is-invalid': form.errors.vat_number }" />
          <InputError :message="form.errors.vat_number" />
        </BCol>
        <BCol md="4">
          <InputLabel for="tax_code" value="Codice fiscale" />
          <TextInput id="tax_code" v-model="form.tax_code" type="text" :class="{ 'is-invalid': form.errors.tax_code }" />
          <InputError :message="form.errors.tax_code" />
        </BCol>
        <BCol md="4">
          <InputLabel for="industry" value="Settore / attivita'" />
          <TextInput id="industry" v-model="form.industry" type="text" :class="{ 'is-invalid': form.errors.industry }" />
          <InputError :message="form.errors.industry" />
        </BCol>
        <BCol md="6">
          <InputLabel for="contact_email" value="Email contatto" />
          <TextInput id="contact_email" v-model="form.contact_email" type="email" :class="{ 'is-invalid': form.errors.contact_email }" />
          <InputError :message="form.errors.contact_email" />
        </BCol>
        <BCol md="6">
          <InputLabel for="contact_phone" value="Telefono contatto" />
          <TextInput id="contact_phone" v-model="form.contact_phone" type="text" :class="{ 'is-invalid': form.errors.contact_phone }" />
          <InputError :message="form.errors.contact_phone" />
        </BCol>
        <BCol md="6">
          <InputLabel for="city" value="Citta'" />
          <TextInput id="city" v-model="form.city" type="text" :class="{ 'is-invalid': form.errors.city }" />
          <InputError :message="form.errors.city" />
        </BCol>
        <BCol md="2">
          <InputLabel for="province" value="Provincia" />
          <TextInput id="province" v-model="form.province" type="text" :class="{ 'is-invalid': form.errors.province }" />
          <InputError :message="form.errors.province" />
        </BCol>
        <BCol md="12">
          <InputLabel for="notes" value="Note operative" />
          <textarea id="notes" v-model="form.notes" rows="4" class="form-control" :class="{ 'is-invalid': form.errors.notes }"></textarea>
          <InputError :message="form.errors.notes" />
        </BCol>
      </BRow>
    </BCardBody>
    <BCardFooter class="bg-white border-top">
      <div class="hstack justify-content-end gap-2">
        <Link :href="route('companies.index')" class="btn btn-soft-secondary">Annulla</Link>
        <BButton variant="primary" type="submit" :disabled="form.processing">{{ submitLabel }}</BButton>
      </div>
    </BCardFooter>
  </BCard>
</template>
