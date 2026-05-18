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
  company: {
    type: Object,
    required: true,
  },
  configureForms: {
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
  name: props.company.name ?? "",
  vat_number: props.company.vat_number ?? "",
  tax_code: props.company.tax_code ?? "",
  ateco_2025_id: props.company.ateco_2025_id ?? null,
  industry: props.company.industry ?? "",
  contact_email: props.company.contact_email ?? "",
  contact_phone: props.company.contact_phone ?? "",
  address_line: props.company.address_line ?? "",
  street_number: props.company.street_number ?? "",
  city: props.company.city ?? "",
  province: props.company.province ?? "",
  postal_code: props.company.postal_code ?? "",
  notes: props.company.notes ?? "",
});

const submit = () => {
  form.put(route("companies.update", props.company.id));
};
</script>

<template>
  <Layout>
    <Head :title="`Modifica ${company.name}`" />

    <PageHeader title="Configura azienda" pageTitle="SicurezzaChiara" />

    <CompanySetupWorkspace
      :tenant="tenant"
      :company="company"
      :configure-forms="configureForms"
      :ateco-config="atecoConfig"
      :comuni-config="comuniConfig"
      :form="form"
      mode="edit"
      submit-label="Salva azienda"
      @submit-company="submit"
    />
  </Layout>
</template>
