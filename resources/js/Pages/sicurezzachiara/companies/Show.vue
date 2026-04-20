<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import SiteForm from "./Partials/SiteForm.vue";

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

const siteForm = useForm({
  name: "",
  site_code: "",
  is_headquarters: props.company.sites.length === 0,
  address_line: "",
  postal_code: "",
  city: props.company.city ?? "",
  province: props.company.province ?? "",
  notes: "",
});

const submitSite = () => {
  siteForm.post(route("companies.sites.store", props.company.id), {
    preserveScroll: true,
    onSuccess: () => {
      siteForm.reset("name", "site_code", "address_line", "postal_code", "notes");
      siteForm.is_headquarters = false;
    },
  });
};
</script>

<template>
  <Layout>
    <Head :title="company.name" />

    <PageHeader title="Dettaglio azienda" pageTitle="SicurezzaChiara" />

    <BAlert v-if="$page.props.flash.success" show variant="success" class="mb-4">
      <i class="ri-checkbox-circle-line me-2 align-middle"></i>
      {{ $page.props.flash.success }}
    </BAlert>

    <BRow class="g-4 mb-4">
      <BCol xl="8">
        <BCard no-body class="overflow-hidden">
          <BCardBody class="p-4">
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
              <div>
                <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">Azienda del tenant</span>
                <h2 class="mb-1">{{ company.name }}</h2>
                <p class="text-muted mb-3">{{ company.legal_name || "Ragione sociale non specificata" }}</p>
                <div class="hstack gap-2 flex-wrap">
                  <span class="badge bg-light text-body">{{ tenant.name }}</span>
                  <span class="badge bg-soft-info text-info">{{ company.sites.length }} sedi</span>
                  <span class="badge bg-soft-secondary text-secondary">{{ company.industry || "Settore da completare" }}</span>
                </div>
              </div>
              <div class="hstack gap-2 flex-wrap">
                <Link :href="route('companies.edit', company.id)" class="btn btn-primary">
                  Modifica azienda
                </Link>
                <Link :href="route('companies.index')" class="btn btn-soft-secondary">
                  Torna elenco
                </Link>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0">
            <h4 class="card-title mb-0">Riepilogo anagrafico</h4>
          </BCardHeader>
          <BCardBody>
            <div class="vstack gap-3">
              <div>
                <span class="text-muted d-block fs-13">Email contatto</span>
                <span class="fw-medium">{{ company.contact_email || "Non indicata" }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Telefono contatto</span>
                <span class="fw-medium">{{ company.contact_phone || "Non indicato" }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Localita'</span>
                <span class="fw-medium">{{ company.city || "Non indicata" }}<template v-if="company.province"> ({{ company.province }})</template></span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Note</span>
                <span class="fw-medium">{{ company.notes || "Nessuna nota operativa presente." }}</span>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4">
      <BCol xl="7">
        <BCard no-body>
          <BCardHeader class="border-0">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <h4 class="card-title mb-1">Sedi aziendali</h4>
                <p class="text-muted mb-0">Perimetro operativo reale della singola azienda.</p>
              </div>
              <span class="badge bg-info-subtle text-info">{{ company.sites.length }} sedi</span>
            </div>
          </BCardHeader>
          <BCardBody class="pt-0">
            <div v-if="company.sites.length === 0" class="text-center py-4">
              <div class="avatar-md mx-auto mb-3">
                <div class="avatar-title bg-light text-info rounded-circle fs-2">
                  <i class="ri-map-pin-line"></i>
                </div>
              </div>
              <h5 class="mb-2">Nessuna sede ancora presente</h5>
              <p class="text-muted mb-0">Aggiungi la prima sede per trasformare l'azienda in un contenitore operativo reale.</p>
            </div>

            <div v-else class="table-responsive">
              <table class="table align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Sede</th>
                    <th>Localita'</th>
                    <th>Stato</th>
                    <th class="text-end">Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="site in company.sites" :key="site.id">
                    <td>
                      <div class="fw-semibold">{{ site.name }}</div>
                      <div class="text-muted fs-13">{{ site.site_code || "Codice non indicato" }}</div>
                    </td>
                    <td>
                      {{ site.city || "Non indicata" }}
                      <template v-if="site.province"> ({{ site.province }})</template>
                    </td>
                    <td>
                      <span v-if="site.is_headquarters" class="badge bg-primary-subtle text-primary">Sede principale</span>
                      <span v-else class="badge bg-light text-body">Operativa</span>
                    </td>
                    <td class="text-end">
                      <Link :href="route('companies.sites.edit', [company.id, site.id])" class="btn btn-soft-secondary btn-sm">
                        Modifica
                      </Link>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </BCardBody>
        </BCard>
      </BCol>

      <BCol xl="5">
        <form @submit.prevent="submitSite">
          <SiteForm :form="siteForm" submit-label="Aggiungi sede" />
        </form>
      </BCol>
    </BRow>
  </Layout>
</template>
