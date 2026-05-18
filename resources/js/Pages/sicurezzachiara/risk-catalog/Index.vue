<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

defineProps({
  tenant: Object,
  risks: Array,
  summary: Object,
});
</script>

<template>
  <Layout>
    <Head title="Catalogo rischi" />
    <PageHeader title="Catalogo rischi" pageTitle="SicurezzaChiara" />

    <BAlert v-if="$page.props.flash.success" show variant="success" class="mb-4">
      <i class="ri-checkbox-circle-line me-2 align-middle"></i>
      {{ $page.props.flash.success }}
    </BAlert>

    <BRow class="g-4 mb-4">
      <BCol lg="8">
        <BCard no-body class="overflow-hidden">
          <BCardBody class="p-4">
            <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">SC-DOM-004</span>
            <h2 class="mb-2">Catalogo rischi minimo e tassonomia</h2>
            <p class="text-muted mb-4">
              Questo layer non coincide con le sorgenti: normalizza i rischi e li rende agganciabili a mansioni,
              tipologie macchinario e tipologie luogo, preparando il futuro motore di profilo rischio.
            </p>
            <div class="hstack gap-2 flex-wrap">
              <span class="badge bg-light text-body">{{ tenant.name }}</span>
              <span class="badge bg-soft-primary text-primary">{{ summary.totalCount }} rischi visibili</span>
              <span class="badge bg-soft-success text-success">{{ summary.tenantCount }} tenant-level</span>
              <span class="badge bg-soft-info text-info">{{ summary.coreCount }} core</span>
              <span class="badge bg-soft-secondary text-secondary">{{ summary.categoriesCount }} categorie</span>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol lg="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0">
            <div>
              <h4 class="card-title mb-1">Azioni rapide</h4>
              <p class="text-muted mb-0">Aggiungi rischi tenant-level o apri i cataloghi sorgente per affinare i mapping.</p>
            </div>
          </BCardHeader>
          <BCardBody class="d-flex flex-column gap-3">
            <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data" :href="route('risk-catalog.create')" class="btn btn-primary">Nuovo rischio</Link>
            <Link :href="route('job-roles.index')" class="btn btn-soft-secondary">Apri mansioni</Link>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BCard no-body>
      <BCardHeader class="border-0">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <h4 class="card-title mb-1">Repertorio rischi</h4>
            <p class="text-muted mb-0">I rischi `core` restano condivisi; quelli `tenant` supportano personalizzazioni locali del consulente.</p>
          </div>
          <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data" :href="route('risk-catalog.create')" class="btn btn-primary btn-sm">Aggiungi rischio</Link>
        </div>
      </BCardHeader>
      <BCardBody class="pt-0">
        <div class="table-responsive">
          <table class="table align-middle table-nowrap mb-0">
            <thead class="table-light">
              <tr>
                <th>Rischio</th>
                <th>Categoria</th>
                <th>Origine</th>
                <th>Priorita'</th>
                <th>Collegamenti</th>
                <th class="text-end">Azioni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="risk in risks" :key="risk.id">
                <td>
                  <div class="fw-semibold">{{ risk.name }}</div>
                  <div class="text-muted fs-13">{{ risk.code || "Codice non indicato" }}</div>
                </td>
                <td>{{ risk.category?.name || "Non disponibile" }}</td>
                <td>
                  <span v-if="risk.source === 'tenant'" class="badge bg-primary-subtle text-primary">Tenant</span>
                  <span v-else class="badge bg-light text-body">Core</span>
                </td>
                <td>
                  <span
                    class="badge"
                    :class="{
                      'bg-danger-subtle text-danger': risk.default_priority === 'high',
                      'bg-warning-subtle text-warning': risk.default_priority === 'medium',
                      'bg-success-subtle text-success': risk.default_priority === 'low',
                    }"
                  >
                    {{ risk.default_priority }}
                  </span>
                </td>
                <td><span class="badge bg-soft-info text-info">{{ risk.source_links_count }} mapping</span></td>
                <td class="text-end">
                  <div class="hstack gap-2 justify-content-end">
                    <Link :href="route('risk-catalog.show', risk.id)" class="btn btn-soft-primary btn-sm">Apri</Link>
                    <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data && risk.source === 'tenant'" :href="route('risk-catalog.edit', risk.id)" class="btn btn-soft-secondary btn-sm">Modifica</Link>
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
