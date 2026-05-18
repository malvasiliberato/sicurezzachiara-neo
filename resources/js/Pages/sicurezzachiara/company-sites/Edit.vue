<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import SiteForm from "@/Pages/sicurezzachiara/companies/Partials/SiteForm.vue";

const props = defineProps({
  tenant: {
    type: Object,
    required: true,
  },
  company: {
    type: Object,
    required: true,
  },
  site: {
    type: Object,
    required: true,
  },
  comuniConfig: {
    type: Object,
    default: null,
  },
});

const form = useForm({
  name: props.site.name ?? "",
  site_code: props.site.site_code ?? "",
  is_headquarters: !!props.site.is_headquarters,
  address_line: props.site.address_line ?? "",
  street_number: props.site.street_number ?? "",
  postal_code: props.site.postal_code ?? "",
  city: props.site.city ?? "",
  province: props.site.province ?? "",
  notes: props.site.notes ?? "",
});

const submit = () => {
  form.put(route("companies.sites.update", [props.company.id, props.site.id]));
};
</script>

<template>
  <Layout>
    <Head :title="`Modifica sede ${site.name}`" />

    <PageHeader title="Modifica sede" pageTitle="SicurezzaChiara" />

    <BRow class="justify-content-center">
      <BCol xl="8">
        <BCard no-body class="mb-4">
          <BCardBody class="p-4">
            <span class="badge bg-info-subtle text-info text-uppercase mb-3">Tenant / azienda</span>
            <h4 class="mb-1">{{ company.name }}</h4>
            <p class="text-muted mb-0">{{ tenant.name }} - aggiorna la configurazione operativa della sede.</p>
          </BCardBody>
        </BCard>

        <form @submit.prevent="submit">
          <SiteForm
            :form="form"
            :comuni-config="comuniConfig"
            submit-label="Salva sede"
            :cancel-href="route('companies.show', company.id)"
          />
        </form>
      </BCol>
    </BRow>
  </Layout>
</template>
