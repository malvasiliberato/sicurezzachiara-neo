<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import EquipmentAssetForm from "./Partials/EquipmentAssetForm.vue";

const props = defineProps({
  tenant: Object,
  formOptions: Object,
  defaults: Object,
});

const form = useForm({
  company_id: props.defaults?.company_id ?? "",
  company_site_id: props.defaults?.company_site_id ?? "",
  equipment_type_id: "",
  custom_equipment_type_name: "",
  asset_code: "",
  name: "",
  manufacturer: "",
  model: "",
  status: "active",
  notes: "",
});

const submit = () => form.post(route("equipment-assets.store"));
</script>

<template>
  <Layout>
    <Head title="Nuovo macchinario" />
    <PageHeader title="Nuovo macchinario" pageTitle="SicurezzaChiara" />

    <BRow class="justify-content-center">
      <BCol xl="10">
        <BCard no-body class="mb-4">
          <BCardBody class="p-4">
            <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">Area 1 - Gestione azienda</span>
            <h4 class="mb-1">{{ tenant.name }}</h4>
            <p class="text-muted mb-0">Configura un macchinario dentro il percorso aziendale, collegandolo a sede e tipologia operativa.</p>
          </BCardBody>
        </BCard>
        <form @submit.prevent="submit">
          <EquipmentAssetForm :form="form" :form-options="formOptions" submit-label="Crea macchinario" :cancel-href="route('equipment-assets.index')" />
        </form>
      </BCol>
    </BRow>
  </Layout>
</template>
