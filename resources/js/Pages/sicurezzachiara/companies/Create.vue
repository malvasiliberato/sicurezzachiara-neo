<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import CompanySetupWorkspace from "./Partials/CompanySetupWorkspace.vue";

const props = defineProps({
  tenant: {
    type: Object,
    required: true,
  },
  atecoConfig: {
    type: Object,
    required: true,
  },
  comuniConfig: {
    type: Object,
    required: true,
  },
});

const form = useForm({
  name: "",
  vat_number: "",
  tax_code: "",
  ateco_2025_id: null,
  industry: "",
  contact_email: "",
  contact_phone: "",
  address_line: "",
  street_number: "",
  city: "",
  province: "",
  postal_code: "",
  notes: "",
});

const submit = () => {
  form.post(route("companies.store"));
};
</script>

<template>
  <Layout>
    <Head title="Nuova azienda" />

    <PageHeader title="Configura azienda" pageTitle="SicurezzaChiara" />

    <CompanySetupWorkspace
      :tenant="props.tenant"
      :ateco-config="props.atecoConfig"
      :comuni-config="props.comuniConfig"
      :form="form"
      mode="create"
      submit-label="Crea azienda"
      @submit-company="submit"
    />
  </Layout>
</template>
