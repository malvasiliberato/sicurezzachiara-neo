<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import WorkplaceForm from "./Partials/WorkplaceForm.vue";

const props = defineProps({
  tenant: Object,
  formOptions: Object,
  defaults: Object,
});

const form = useForm({
  company_id: props.defaults?.company_id ?? "",
  company_site_id: props.defaults?.company_site_id ?? "",
  workplace_type_id: "",
  custom_workplace_type_name: "",
  code: "",
  name: "",
  status: "active",
  notes: "",
});

const submit = () => form.post(route("workplaces.store"));
</script>

<template>
  <Layout>
    <Head title="Nuovo luogo" />
    <PageHeader title="Nuovo luogo" pageTitle="SicurezzaChiara" />

    <BRow class="justify-content-center">
      <BCol xl="10">
        <BCard no-body class="mb-4">
          <BCardBody class="p-4">
            <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">Area 1 - Gestione azienda</span>
            <h4 class="mb-1">{{ tenant.name }}</h4>
            <p class="text-muted mb-0">Configura un luogo dentro il percorso aziendale, collegandolo a sede reale e tipologia riusabile.</p>
          </BCardBody>
        </BCard>
        <form @submit.prevent="submit">
          <WorkplaceForm :form="form" :form-options="formOptions" submit-label="Crea luogo" :cancel-href="route('workplaces.index')" />
        </form>
      </BCol>
    </BRow>
  </Layout>
</template>
