<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import EquipmentAssetForm from "./Partials/EquipmentAssetForm.vue";

const props = defineProps({
  tenant: Object,
  asset: Object,
  formOptions: Object,
});

const form = useForm({
  company_id: props.asset.company_id ?? "",
  company_site_id: props.asset.company_site_id ?? "",
  equipment_type_id: props.asset.equipment_type_id ?? "",
  custom_equipment_type_name: "",
  asset_code: props.asset.asset_code ?? "",
  name: props.asset.name ?? "",
  manufacturer: props.asset.manufacturer ?? "",
  model: props.asset.model ?? "",
  status: props.asset.status ?? "active",
  notes: props.asset.notes ?? "",
});

const submit = () => form.put(route("equipment-assets.update", props.asset.id));
</script>

<template>
  <Layout>
    <Head :title="`Modifica ${asset.name}`" />
    <PageHeader title="Modifica macchinario" pageTitle="SicurezzaChiara" />

    <BRow class="justify-content-center">
      <BCol xl="10">
        <BCard no-body class="mb-4">
          <BCardBody class="p-4">
            <span class="badge bg-warning-subtle text-warning text-uppercase mb-3">Registro operativo</span>
            <h4 class="mb-1">{{ asset.name }}</h4>
            <p class="text-muted mb-0">Aggiorna il macchinario mantenendo coerente il legame con azienda, sede e tipologia.</p>
          </BCardBody>
        </BCard>
        <form @submit.prevent="submit">
          <EquipmentAssetForm :form="form" :form-options="formOptions" submit-label="Salva modifiche" />
        </form>
      </BCol>
    </BRow>
  </Layout>
</template>
