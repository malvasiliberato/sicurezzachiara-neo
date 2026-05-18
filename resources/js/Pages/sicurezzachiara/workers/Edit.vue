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
  worker: {
    type: Object,
    required: true,
  },
  formOptions: {
    type: Object,
    required: true,
  },
  companyContext: {
    type: Object,
    default: null,
  },
});

const form = useForm({
  company_id: props.worker.company_id ?? "",
  primary_site_id: props.worker.primary_site_id ?? "",
  job_role_id: props.worker.job_role_assignments?.[0]?.job_role_id ?? "",
  first_name: props.worker.first_name ?? "",
  last_name: props.worker.last_name ?? "",
  tax_code: props.worker.tax_code ?? "",
  email: props.worker.email ?? "",
  phone: props.worker.phone ?? "",
  birth_date: props.worker.birth_date ?? "",
  hire_date: props.worker.hire_date ?? "",
  status: props.worker.status ?? "active",
  notes: props.worker.notes ?? "",
});

const submit = () => {
  form.put(route("workers.update", props.worker.id));
};
</script>

<template>
  <Layout>
    <Head :title="`Modifica ${worker.full_name}`" />

    <PageHeader title="Modifica lavoratore" pageTitle="SicurezzaChiara" />

    <BRow class="justify-content-center">
      <BCol xl="10">
        <BCard no-body class="mb-4">
          <BCardBody class="p-4">
            <span class="badge bg-warning-subtle text-warning text-uppercase mb-3">Aggiornamento profilo</span>
            <h4 class="mb-1">{{ worker.full_name }}</h4>
            <p class="text-muted mb-0">Mantieni coerente il presidio anagrafico senza aprire ancora il layer rischio.</p>
          </BCardBody>
        </BCard>

        <form @submit.prevent="submit">
          <WorkerForm
            :form="form"
            :form-options="formOptions"
            submit-label="Salva modifiche"
            :cancel-href="companyContext?.workersRoute || route('workers.index')"
            :cancel-label="companyContext ? 'Torna ai lavoratori azienda' : 'Annulla'"
          />
        </form>
      </BCol>
    </BRow>
  </Layout>
</template>
