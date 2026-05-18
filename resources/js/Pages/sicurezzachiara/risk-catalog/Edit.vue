<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import RiskCatalogForm from "./Partials/RiskCatalogForm.vue";

const props = defineProps({
  tenant: Object,
  risk: Object,
  formOptions: Object,
});

const form = useForm({
  risk_category_id: props.risk.risk_category_id ?? "",
  code: props.risk.code ?? "",
  name: props.risk.name ?? "",
  description: props.risk.description ?? "",
  expected_measures: (props.risk.expected_measures ?? []).map((item, index) => ({
    code: item.code ?? `measure_${index + 1}`,
    family: item.family ?? "organizational",
    title: item.title ?? "",
    description: item.description ?? "",
    is_required: item.is_required ?? true,
  })),
  default_priority: props.risk.default_priority ?? "medium",
  is_active: props.risk.is_active ?? true,
});

const submit = () => form.put(route("risk-catalog.update", props.risk.id));
</script>

<template>
  <Layout>
    <Head :title="`Modifica ${risk.name}`" />
    <PageHeader title="Modifica rischio" pageTitle="SicurezzaChiara" />

    <BRow class="justify-content-center">
      <BCol xl="10">
        <BCard no-body class="mb-4">
          <BCardBody class="p-4">
            <span class="badge bg-warning-subtle text-warning text-uppercase mb-3">Catalogo tenant</span>
            <h4 class="mb-1">{{ risk.name }}</h4>
            <p class="text-muted mb-0">Aggiorna il rischio mantenendo coerente la sua tassonomia di base.</p>
          </BCardBody>
        </BCard>
        <form @submit.prevent="submit">
          <RiskCatalogForm :form="form" :form-options="formOptions" submit-label="Salva modifiche" />
        </form>
      </BCol>
    </BRow>
  </Layout>
</template>
