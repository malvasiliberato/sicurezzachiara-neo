<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

defineProps({
  tenant: Object,
  jobRoles: Array,
  summary: Object,
});
</script>

<template>
  <Layout>
    <Head title="Mansioni" />
    <PageHeader title="Mansioni" pageTitle="SicurezzaChiara" />

    <BAlert v-if="$page.props.flash.success" show variant="success" class="mb-4">
      <i class="ri-checkbox-circle-line me-2 align-middle"></i>
      {{ $page.props.flash.success }}
    </BAlert>

    <BRow class="g-4 mb-4">
      <BCol lg="8">
        <BCard no-body class="overflow-hidden">
          <BCardBody class="p-4">
            <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">SC-DOM-003A</span>
            <h2 class="mb-2">Catalogo mansioni del tenant</h2>
            <p class="text-muted mb-4">
              In questo step il catalogo distingue gia' una base `core` da una base `tenant`, ma la gestione operativa
              resta focalizzata sulle mansioni personalizzabili del consulente.
            </p>
            <div class="hstack gap-2 flex-wrap">
              <span class="badge bg-light text-body">{{ tenant.name }}</span>
              <span class="badge bg-soft-primary text-primary">{{ summary.totalCount }} mansioni visibili</span>
              <span class="badge bg-soft-success text-success">{{ summary.tenantCount }} tenant-level</span>
              <span class="badge bg-soft-info text-info">{{ summary.coreCount }} core</span>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol lg="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0">
            <div>
              <h4 class="card-title mb-1">Azioni rapide</h4>
              <p class="text-muted mb-0">Aggiungi le mansioni personalizzate riusabili tra aziende e lavoratori.</p>
            </div>
          </BCardHeader>
          <BCardBody class="d-flex flex-column gap-3">
            <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data" :href="route('job-roles.create')" class="btn btn-primary">Nuova mansione</Link>
            <Link :href="route('workers.index')" class="btn btn-soft-secondary">Apri lavoratori</Link>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BCard no-body>
      <BCardHeader class="border-0">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <h4 class="card-title mb-1">Repertorio mansioni</h4>
            <p class="text-muted mb-0">Le mansioni `core` sono visibili, quelle `tenant` sono gestibili e assegnabili.</p>
          </div>
          <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data" :href="route('job-roles.create')" class="btn btn-primary btn-sm">Aggiungi mansione</Link>
        </div>
      </BCardHeader>
      <BCardBody class="pt-0">
        <div class="table-responsive">
          <table class="table align-middle table-nowrap mb-0">
            <thead class="table-light">
              <tr>
                <th>Mansione</th>
                <th>Origine</th>
                <th>Stato</th>
                <th>Assegnazioni</th>
                <th class="text-end">Azioni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="jobRole in jobRoles" :key="jobRole.id">
                <td>
                  <div class="fw-semibold">{{ jobRole.name }}</div>
                  <div class="text-muted fs-13">{{ jobRole.code || "Codice non indicato" }}</div>
                </td>
                <td>
                  <span v-if="jobRole.source === 'tenant'" class="badge bg-primary-subtle text-primary">Tenant</span>
                  <span v-else class="badge bg-light text-body">Core</span>
                </td>
                <td>
                  <span v-if="jobRole.is_active" class="badge bg-success-subtle text-success">Attiva</span>
                  <span v-else class="badge bg-light text-body">Non attiva</span>
                </td>
                <td>
                  <span class="badge bg-soft-info text-info">{{ jobRole.worker_assignments_count }} assegnazioni</span>
                </td>
                <td class="text-end">
                  <div class="hstack gap-2 justify-content-end">
                    <Link :href="route('job-roles.show', jobRole.id)" class="btn btn-soft-primary btn-sm">Apri</Link>
                    <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data && jobRole.source === 'tenant'" :href="route('job-roles.edit', jobRole.id)" class="btn btn-soft-secondary btn-sm">
                      Modifica
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </BCardBody>
    </BCard>
  </Layout>
</template>
