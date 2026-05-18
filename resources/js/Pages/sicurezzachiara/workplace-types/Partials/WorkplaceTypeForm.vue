<script setup>
import { Link } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";

defineProps({
  form: Object,
  submitLabel: String,
});
</script>

<template>
  <BCard no-body>
    <BCardHeader class="border-0">
      <div>
        <h4 class="card-title mb-1">Catalogo tipologie luogo</h4>
        <p class="text-muted mb-0">Base tenant-level riusabile per descrivere gli ambienti operativi reali delle sedi.</p>
      </div>
    </BCardHeader>
    <BCardBody>
      <BRow class="g-3">
        <BCol md="4">
          <InputLabel for="code" value="Codice" />
          <TextInput id="code" v-model="form.code" type="text" :class="{ 'is-invalid': form.errors.code }" />
          <InputError :message="form.errors.code" />
        </BCol>
        <BCol md="8">
          <InputLabel for="name" value="Nome tipologia *" />
          <TextInput id="name" v-model="form.name" type="text" :class="{ 'is-invalid': form.errors.name }" />
          <InputError :message="form.errors.name" />
        </BCol>
        <BCol md="12">
          <InputLabel for="description" value="Descrizione operativa" />
          <textarea id="description" v-model="form.description" rows="4" class="form-control" :class="{ 'is-invalid': form.errors.description }"></textarea>
          <InputError :message="form.errors.description" />
        </BCol>
        <BCol md="4">
          <div class="form-check form-switch form-switch-md mt-2">
            <input id="is_active" v-model="form.is_active" class="form-check-input" type="checkbox" />
            <label class="form-check-label" for="is_active">Tipologia attiva</label>
          </div>
          <InputError :message="form.errors.is_active" />
        </BCol>
      </BRow>
    </BCardBody>
    <BCardFooter class="bg-white border-top">
      <div class="hstack justify-content-end gap-2">
        <Link :href="route('workplace-types.index')" class="btn btn-soft-secondary">Annulla</Link>
        <BButton variant="primary" type="submit" :disabled="form.processing">{{ submitLabel }}</BButton>
      </div>
    </BCardFooter>
  </BCard>
</template>
