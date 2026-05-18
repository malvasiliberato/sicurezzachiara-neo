<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import EquipmentTypeForm from "./Partials/EquipmentTypeForm.vue";

const props = defineProps({
  tenant: Object,
  equipmentType: Object,
});

const form = useForm({
  code: props.equipmentType.code ?? "",
  name: props.equipmentType.name ?? "",
  description: props.equipmentType.description ?? "",
  is_active: props.equipmentType.is_active ?? true,
});

const submit = () => form.put(route("equipment-types.update", props.equipmentType.id));
</script>

<template>
  <Layout>
    <Head :title="`Modifica ${equipmentType.name}`" />
    <PageHeader title="Modifica tipologia macchinario" pageTitle="SicurezzaChiara" />

    <BRow class="justify-content-center">
      <BCol xl="10">
        <BCard no-body class="mb-4">
          <BCardBody class="p-4">
            <span class="badge bg-warning-subtle text-warning text-uppercase mb-3">Catalogo tenant</span>
            <h4 class="mb-1">{{ equipmentType.name }}</h4>
            <p class="text-muted mb-0">Aggiorna la tipologia mantenendo pulita la base riusabile del tenant.</p>
          </BCardBody>
        </BCard>
        <form @submit.prevent="submit">
          <EquipmentTypeForm :form="form" submit-label="Salva modifiche" />
        </form>
      </BCol>
    </BRow>
  </Layout>
</template>
