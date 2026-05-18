<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

defineProps({
  tenant: Object,
  equipmentType: Object,
});
</script>

<template>
  <Layout>
    <Head :title="equipmentType.name" />
    <PageHeader title="Dettaglio tipologia macchinario" pageTitle="SicurezzaChiara" />

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
                <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">Catalogo macchinari</span>
                <h2 class="mb-1">{{ equipmentType.name }}</h2>
                <p class="text-muted mb-3">{{ equipmentType.description || "Nessuna descrizione operativa presente." }}</p>
                <div class="hstack gap-2 flex-wrap">
                  <span class="badge bg-light text-body">{{ tenant.name }}</span>
                  <span v-if="equipmentType.source === 'tenant'" class="badge bg-primary-subtle text-primary">Tenant</span>
                  <span v-else class="badge bg-light text-body">Core</span>
                  <span v-if="equipmentType.is_active" class="badge bg-success-subtle text-success">Attiva</span>
                  <span v-else class="badge bg-light text-body">Non attiva</span>
                </div>
              </div>
              <div class="hstack gap-2 flex-wrap">
                <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data && equipmentType.source === 'tenant'" :href="route('equipment-types.edit', equipmentType.id)" class="btn btn-primary">
                  Modifica tipologia
                </Link>
                <Link :href="route('equipment-types.index')" class="btn btn-soft-secondary">Torna elenco</Link>
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
                <span class="fw-medium">{{ equipmentType.code || "Non indicato" }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Origine</span>
                <span class="fw-medium">{{ equipmentType.source === "tenant" ? "Catalogo tenant" : "Catalogo core" }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Tenant proprietario</span>
                <span class="fw-medium">{{ equipmentType.tenant?.name || "Catalogo core condiviso" }}</span>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BCard no-body>
      <BCardHeader class="border-0">
        <div>
          <h4 class="card-title mb-1">Macchinari censiti</h4>
          <p class="text-muted mb-0">Vista operativa delle istanze reali gia' agganciate a questa tipologia.</p>
        </div>
      </BCardHeader>
      <BCardBody class="pt-0">
        <div v-if="equipmentType.assets.length === 0" class="text-center py-4">
          <h5 class="mb-2">Nessun macchinario ancora presente</h5>
          <p class="text-muted mb-0">La tipologia e' pronta ma non e' ancora stata usata per censire istanze operative.</p>
        </div>
        <div v-else class="table-responsive">
          <table class="table align-middle table-nowrap mb-0">
            <thead class="table-light">
              <tr>
                <th>Macchinario</th>
                <th>Azienda</th>
                <th>Sede</th>
                <th>Stato</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="asset in equipmentType.assets" :key="asset.id">
                <td>
                  <div class="fw-semibold">{{ asset.name }}</div>
                  <div class="text-muted fs-13">{{ asset.asset_code || "Codice non indicato" }}</div>
                </td>
                <td>{{ asset.company?.name || "Non disponibile" }}</td>
                <td>{{ asset.site?.name || "Non assegnata" }}</td>
                <td>
                  <span v-if="asset.status === 'active'" class="badge bg-success-subtle text-success">Attivo</span>
                  <span v-else class="badge bg-light text-body">Non attivo</span>
                </td>
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
            <p class="text-muted mb-0">Primi mapping catalogo rischio -> tipologia macchinario, pronti per il motore futuro.</p>
          </div>
          <Link :href="route('risk-catalog.index')" class="btn btn-soft-secondary btn-sm">Apri catalogo rischi</Link>
        </div>
      </BCardHeader>
      <BCardBody class="pt-0">
        <div v-if="equipmentType.risk_source_links.length === 0" class="text-center py-4">
          <p class="text-muted mb-0">Nessun rischio ancora collegato a questa tipologia.</p>
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
              <tr v-for="link in equipmentType.risk_source_links" :key="link.id">
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
