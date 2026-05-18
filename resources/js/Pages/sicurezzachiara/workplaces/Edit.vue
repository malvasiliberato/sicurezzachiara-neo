<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import WorkplaceForm from "./Partials/WorkplaceForm.vue";

const props = defineProps({
  tenant: Object,
 workplace: Object,
  formOptions: Object,
});

const form = useForm({
  company_id: props.workplace.site?.company_id ?? "",
  company_site_id: props.workplace.company_site_id ?? "",
  workplace_type_id: props.workplace.workplace_type_id ?? "",
  custom_workplace_type_name: "",
  code: props.workplace.code ?? "",
  name: props.workplace.name ?? "",
  status: props.workplace.status ?? "active",
  notes: props.workplace.notes ?? props.workplace.description ?? "",
});

const submit = () => form.put(route("workplaces.update", props.workplace.id));
</script>

<template>
  <Layout>
    <Head :title="`Modifica ${workplace.name}`" />
    <PageHeader title="Modifica luogo" pageTitle="SicurezzaChiara" />

    <BRow class="justify-content-center">
      <BCol xl="10">
        <BCard no-body class="mb-4">
          <BCardBody class="p-4">
            <span class="badge bg-warning-subtle text-warning text-uppercase mb-3">Registro operativo</span>
            <h4 class="mb-1">{{ workplace.name }}</h4>
            <p class="text-muted mb-0">Aggiorna il luogo mantenendo coerente il legame con sede e tipologia.</p>
          </BCardBody>
        </BCard>
        <form @submit.prevent="submit">
          <WorkplaceForm :form="form" :form-options="formOptions" submit-label="Salva modifiche" />
        </form>
      </BCol>
    </BRow>
  </Layout>
</template>
