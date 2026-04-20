<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import CompanyForm from "./Partials/CompanyForm.vue";

defineProps({
  tenant: {
    type: Object,
    required: true,
  },
});

const form = useForm({
  name: "",
  legal_name: "",
  vat_number: "",
  tax_code: "",
  industry: "",
  contact_email: "",
  contact_phone: "",
  city: "",
  province: "",
  notes: "",
});

const submit = () => {
  form.post(route("companies.store"));
};
</script>

<template>
  <Layout>
    <Head title="Nuova azienda" />

    <PageHeader title="Nuova azienda" pageTitle="SicurezzaChiara" />

    <BRow class="justify-content-center">
      <BCol xl="10">
        <BCard no-body class="mb-4">
          <BCardBody class="p-4">
            <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">Tenant corrente</span>
            <h4 class="mb-1">{{ tenant.name }}</h4>
            <p class="text-muted mb-0">Crea un nuovo cliente all'interno del perimetro consulenziale attivo.</p>
          </BCardBody>
        </BCard>

        <form @submit.prevent="submit">
          <CompanyForm :form="form" submit-label="Crea azienda" />
        </form>
      </BCol>
    </BRow>
  </Layout>
</template>
