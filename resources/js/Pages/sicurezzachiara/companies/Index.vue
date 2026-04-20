<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

defineProps({
  tenant: {
    type: Object,
    required: true,
  },
  companies: {
    type: Array,
    required: true,
  },
  summary: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <Layout>
    <Head title="Aziende" />

    <PageHeader title="Aziende" pageTitle="SicurezzaChiara" />

    <BAlert v-if="$page.props.flash.success" show variant="success" class="mb-4">
      <i class="ri-checkbox-circle-line me-2 align-middle"></i>
      {{ $page.props.flash.success }}
    </BAlert>

    <BRow class="g-4 mb-4">
      <BCol lg="8">
        <BCard no-body class="overflow-hidden">
          <BCardBody class="p-4">
            <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">SC-DOM-001</span>
            <h2 class="mb-2">Contesto aziende del tenant</h2>
            <p class="text-muted mb-4">
              Le aziende sono clienti del tenant consulenziale. Da qui partiremo per agganciare sedi, lavoratori e,
              piu' avanti, il presidio del rischio.
            </p>
            <div class="hstack gap-2 flex-wrap">
              <span class="badge bg-light text-body">{{ tenant.name }}</span>
              <span class="badge bg-soft-primary text-primary">{{ summary.companiesCount }} aziende</span>
              <span class="badge bg-soft-info text-info">{{ summary.sitesCount }} sedi censite</span>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol lg="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0">
            <div>
              <h4 class="card-title mb-1">Azioni rapide</h4>
              <p class="text-muted mb-0">Base minima per iniziare il perimetro cliente del tenant.</p>
            </div>
          </BCardHeader>
          <BCardBody class="d-flex flex-column gap-3">
            <Link :href="route('companies.create')" class="btn btn-primary">
              Nuova azienda
            </Link>
            <Link :href="route('sicurezzachiara.ui-reference')" class="btn btn-soft-secondary">
              Apri UI Reference
            </Link>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4">
      <BCol lg="12">
        <BCard no-body>
          <BCardHeader class="border-0">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <h4 class="card-title mb-1">Portafoglio aziende</h4>
                <p class="text-muted mb-0">Elenco minimo operativo coerente con il tenant corrente.</p>
              </div>
              <Link :href="route('companies.create')" class="btn btn-primary btn-sm">
                Aggiungi azienda
              </Link>
            </div>
          </BCardHeader>
          <BCardBody class="pt-0">
            <div v-if="companies.length === 0" class="text-center py-5">
              <div class="avatar-md mx-auto mb-3">
                <div class="avatar-title bg-light text-primary rounded-circle fs-2">
                  <i class="ri-building-line"></i>
                </div>
              </div>
              <h5 class="mb-2">Nessuna azienda ancora presente</h5>
              <p class="text-muted mb-4">
                Crea la prima azienda del tenant per iniziare a costruire il contesto operativo del progetto.
              </p>
              <Link :href="route('companies.create')" class="btn btn-primary">Crea prima azienda</Link>
            </div>

            <div v-else class="table-responsive">
              <table class="table align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Azienda</th>
                    <th>Settore</th>
                    <th>Contatti</th>
                    <th>Citta'</th>
                    <th>Sedi</th>
                    <th class="text-end">Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="company in companies" :key="company.id">
                    <td>
                      <div class="fw-semibold">{{ company.name }}</div>
                      <div class="text-muted fs-13">{{ company.legal_name || "Ragione sociale non specificata" }}</div>
                    </td>
                    <td>{{ company.industry || "Non indicato" }}</td>
                    <td>
                      <div>{{ company.contact_email || "Email non indicata" }}</div>
                      <div class="text-muted fs-13">{{ company.contact_phone || "Telefono non indicato" }}</div>
                    </td>
                    <td>{{ company.city || "Non indicata" }}</td>
                    <td>
                      <span class="badge bg-info-subtle text-info">{{ company.sites_count }} sedi</span>
                    </td>
                    <td class="text-end">
                      <div class="hstack gap-2 justify-content-end">
                        <Link :href="route('companies.show', company.id)" class="btn btn-soft-primary btn-sm">Apri</Link>
                        <Link :href="route('companies.edit', company.id)" class="btn btn-soft-secondary btn-sm">Modifica</Link>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>
  </Layout>
</template>
