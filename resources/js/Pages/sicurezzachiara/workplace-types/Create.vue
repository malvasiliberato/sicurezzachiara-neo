<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import WorkplaceTypeForm from "./Partials/WorkplaceTypeForm.vue";

defineProps({
  tenant: Object,
});

const form = useForm({
  code: "",
  name: "",
  description: "",
  is_active: true,
});

const submit = () => form.post(route("workplace-types.store"));
</script>

<template>
  <Layout>
    <Head title="Nuova tipologia luogo" />
    <PageHeader title="Nuova tipologia luogo" pageTitle="SicurezzaChiara" />

    <BRow class="justify-content-center">
      <BCol xl="10">
        <BCard no-body class="mb-4">
          <BCardBody class="p-4">
            <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">Catalogo tenant</span>
            <h4 class="mb-1">{{ tenant.name }}</h4>
            <p class="text-muted mb-0">Crea una tipologia riusabile per descrivere i luoghi reali delle sedi aziendali.</p>
          </BCardBody>
        </BCard>
        <form @submit.prevent="submit">
          <WorkplaceTypeForm :form="form" submit-label="Crea tipologia" />
        </form>
      </BCol>
    </BRow>
  </Layout>
</template>
