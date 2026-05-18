<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

defineProps({
  tenant: Object,
  jobRole: Object,
});
</script>

<template>
  <Layout>
    <Head :title="jobRole.name" />
    <PageHeader title="Dettaglio mansione" pageTitle="SicurezzaChiara" />

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
                <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">Catalogo mansioni</span>
                <h2 class="mb-1">{{ jobRole.name }}</h2>
                <p class="text-muted mb-3">{{ jobRole.description || "Nessuna descrizione operativa presente." }}</p>
                <div class="hstack gap-2 flex-wrap">
                  <span class="badge bg-light text-body">{{ tenant.name }}</span>
                  <span v-if="jobRole.source === 'tenant'" class="badge bg-primary-subtle text-primary">Tenant</span>
                  <span v-else class="badge bg-light text-body">Core</span>
                  <span v-if="jobRole.is_active" class="badge bg-success-subtle text-success">Attiva</span>
                  <span v-else class="badge bg-light text-body">Non attiva</span>
                </div>
              </div>
              <div class="hstack gap-2 flex-wrap">
                <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data && jobRole.source === 'tenant'" :href="route('job-roles.edit', jobRole.id)" class="btn btn-primary">
                  Modifica mansione
                </Link>
                <Link :href="route('job-roles.index')" class="btn btn-soft-secondary">Torna elenco</Link>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0">
            <h4 class="card-title mb-0">Riepilogo</h4>
          </BCardHeader>
          <BCardBody>
            <div class="vstack gap-3">
              <div>
                <span class="text-muted d-block fs-13">Codice</span>
                <span class="fw-medium">{{ jobRole.code || "Non indicato" }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Origine</span>
                <span class="fw-medium">{{ jobRole.source === "tenant" ? "Catalogo tenant" : "Catalogo core" }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Tenant proprietario</span>
                <span class="fw-medium">{{ jobRole.tenant?.name || "Catalogo core condiviso" }}</span>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BCard no-body>
      <BCardHeader class="border-0">
        <div>
          <h4 class="card-title mb-1">Lavoratori assegnati</h4>
          <p class="text-muted mb-0">Vista operativa delle assegnazioni gia' attive su questa mansione.</p>
        </div>
      </BCardHeader>
      <BCardBody class="pt-0">
        <div v-if="jobRole.worker_assignments.length === 0" class="text-center py-4">
          <h5 class="mb-2">Nessuna assegnazione presente</h5>
          <p class="text-muted mb-0">La mansione e' pronta ma non e' ancora stata agganciata ai lavoratori.</p>
        </div>
        <div v-else class="table-responsive">
          <table class="table align-middle table-nowrap mb-0">
            <thead class="table-light">
              <tr>
                <th>Lavoratore</th>
                <th>Azienda</th>
                <th>Stato assegnazione</th>
                <th>Data</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="assignment in jobRole.worker_assignments" :key="assignment.id">
                <td>{{ assignment.worker?.full_name }}</td>
                <td>{{ assignment.worker?.company?.name || "Non disponibile" }}</td>
                <td>
                  <span v-if="assignment.is_primary" class="badge bg-primary-subtle text-primary">Prevalente</span>
                  <span v-else class="badge bg-light text-body">Secondaria</span>
                </td>
                <td>{{ assignment.assigned_on || "Non indicata" }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </BCardBody>
    </BCard>

    <BCard no-body class="mt-4">
      <BCardHeader class="border-0">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <h4 class="card-title mb-1">Rischi collegati</h4>
            <p class="text-muted mb-0">Primi mapping catalogo rischio -> mansione, senza attivare ancora il motore di profilazione.</p>
          </div>
          <Link :href="route('risk-catalog.index')" class="btn btn-soft-secondary btn-sm">Apri catalogo rischi</Link>
        </div>
      </BCardHeader>
      <BCardBody class="pt-0">
        <div v-if="jobRole.risk_source_links.length === 0" class="text-center py-4">
          <p class="text-muted mb-0">Nessun rischio ancora collegato a questa mansione.</p>
        </div>
        <div v-else class="table-responsive">
          <table class="table align-middle table-nowrap mb-0">
            <thead class="table-light">
              <tr>
                <th>Rischio</th>
                <th>Categoria</th>
                <th>Rilevanza</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="link in jobRole.risk_source_links" :key="link.id">
                <td>{{ link.risk_catalog_item?.name || "Non disponibile" }}</td>
                <td>{{ link.risk_catalog_item?.category?.name || "Non disponibile" }}</td>
                <td>{{ link.relevance }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </BCardBody>
    </BCard>
  </Layout>
</template>
