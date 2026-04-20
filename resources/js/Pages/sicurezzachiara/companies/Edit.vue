<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import CompanyForm from "./Partials/CompanyForm.vue";

const props = defineProps({
  tenant: {
    type: Object,
    required: true,
  },
  company: {
    type: Object,
    required: true,
  },
});

const form = useForm({
  name: props.company.name ?? "",
  legal_name: props.company.legal_name ?? "",
  vat_number: props.company.vat_number ?? "",
  tax_code: props.company.tax_code ?? "",
  industry: props.company.industry ?? "",
  contact_email: props.company.contact_email ?? "",
  contact_phone: props.company.contact_phone ?? "",
  city: props.company.city ?? "",
  province: props.company.province ?? "",
  notes: props.company.notes ?? "",
});

const submit = () => {
  form.put(route("companies.update", props.company.id));
};
</script>

<template>
  <Layout>
    <Head :title="`Modifica ${company.name}`" />

    <PageHeader title="Modifica azienda" pageTitle="SicurezzaChiara" />

    <BRow class="justify-content-center">
      <BCol xl="10">
        <BCard no-body class="mb-4">
          <BCardBody class="p-4">
            <span class="badge bg-info-subtle text-info text-uppercase mb-3">Tenant corrente</span>
            <h4 class="mb-1">{{ tenant.name }}</h4>
            <p class="text-muted mb-0">Aggiorna l'anagrafica essenziale dell'azienda senza uscire dal perimetro del tenant.</p>
          </BCardBody>
        </BCard>

        <form @submit.prevent="submit">
          <CompanyForm :form="form" submit-label="Salva modifiche" />
        </form>
      </BCol>
    </BRow>
  </Layout>
</template>
