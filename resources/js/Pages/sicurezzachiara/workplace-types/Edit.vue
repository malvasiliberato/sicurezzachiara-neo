<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import WorkplaceTypeForm from "./Partials/WorkplaceTypeForm.vue";

const props = defineProps({
  tenant: Object,
  workplaceType: Object,
});

const form = useForm({
  code: props.workplaceType.code ?? "",
  name: props.workplaceType.name ?? "",
  description: props.workplaceType.description ?? "",
  is_active: props.workplaceType.is_active ?? true,
});

const submit = () => form.put(route("workplace-types.update", props.workplaceType.id));
</script>

<template>
  <Layout>
    <Head :title="`Modifica ${workplaceType.name}`" />
    <PageHeader title="Modifica tipologia luogo" pageTitle="SicurezzaChiara" />

    <BRow class="justify-content-center">
      <BCol xl="10">
        <BCard no-body class="mb-4">
          <BCardBody class="p-4">
            <span class="badge bg-warning-subtle text-warning text-uppercase mb-3">Catalogo tenant</span>
            <h4 class="mb-1">{{ workplaceType.name }}</h4>
            <p class="text-muted mb-0">Aggiorna la tipologia mantenendo pulita la base riusabile del tenant.</p>
          </BCardBody>
        </BCard>
        <form @submit.prevent="submit">
          <WorkplaceTypeForm :form="form" submit-label="Salva modifiche" />
        </form>
      </BCol>
    </BRow>
  </Layout>
</template>
