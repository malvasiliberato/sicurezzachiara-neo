<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import RiskCatalogForm from "./Partials/RiskCatalogForm.vue";

const props = defineProps({
  tenant: Object,
  formOptions: Object,
});

const form = useForm({
  risk_category_id: "",
  code: "",
  name: "",
  description: "",
  expected_measures: [],
  default_priority: "medium",
  is_active: true,
});

const submit = () => form.post(route("risk-catalog.store"));
</script>

<template>
  <Layout>
    <Head title="Nuovo rischio" />
    <PageHeader title="Nuovo rischio" pageTitle="SicurezzaChiara" />

    <BRow class="justify-content-center">
      <BCol xl="10">
        <BCard no-body class="mb-4">
          <BCardBody class="p-4">
            <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">Catalogo tenant</span>
            <h4 class="mb-1">{{ tenant.name }}</h4>
            <p class="text-muted mb-0">Crea un rischio riusabile e pronto a essere collegato alle sorgenti del dominio.</p>
          </BCardBody>
        </BCard>
        <form @submit.prevent="submit">
          <RiskCatalogForm :form="form" :form-options="formOptions" submit-label="Crea rischio" />
        </form>
      </BCol>
    </BRow>
  </Layout>
</template>
