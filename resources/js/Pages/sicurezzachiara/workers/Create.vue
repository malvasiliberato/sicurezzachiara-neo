<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import WorkerForm from "./Partials/WorkerForm.vue";

const props = defineProps({
  tenant: {
    type: Object,
    required: true,
  },
  formOptions: {
    type: Object,
    required: true,
  },
  defaults: {
    type: Object,
    required: true,
  },
  companyContext: {
    type: Object,
    default: null,
  },
});

const form = useForm({
  company_id: props.defaults.company_id ?? "",
  primary_site_id: "",
  job_role_id: "",
  first_name: "",
  last_name: "",
  tax_code: "",
  email: "",
  phone: "",
  birth_date: "",
  hire_date: "",
  status: "active",
  notes: "",
});

const submit = () => {
  form.post(route("workers.store"));
};
</script>

<template>
  <Layout>
    <Head title="Nuovo lavoratore" />

    <PageHeader title="Nuovo lavoratore" pageTitle="SicurezzaChiara" />

    <BRow class="justify-content-center">
      <BCol xl="10">
        <BCard no-body class="mb-4">
          <BCardBody class="p-4">
            <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">Tenant corrente</span>
            <h4 class="mb-1">{{ tenant.name }}</h4>
            <p class="text-muted mb-0">Crea un lavoratore dentro il tenant e aggancialo all'azienda corretta.</p>
          </BCardBody>
        </BCard>

        <form @submit.prevent="submit">
          <WorkerForm
            :form="form"
            :form-options="formOptions"
            submit-label="Crea lavoratore"
            :cancel-href="companyContext?.workersRoute || route('workers.index')"
            :cancel-label="companyContext ? 'Torna ai lavoratori azienda' : 'Annulla'"
          />
        </form>
      </BCol>
    </BRow>
  </Layout>
</template>
