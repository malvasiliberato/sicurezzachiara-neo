<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

defineProps({
  tenant: Object,
  assets: Array,
  summary: Object,
  companyContext: {
    type: Object,
    default: null,
  },
});
</script>

<template>
  <Layout>
    <Head title="Macchinari" />
    <PageHeader title="Macchinari" pageTitle="SicurezzaChiara" />

    <BAlert v-if="$page.props.flash.success" show variant="success" class="mb-4">
      <i class="ri-checkbox-circle-line me-2 align-middle"></i>
      {{ $page.props.flash.success }}
    </BAlert>

    <BRow class="g-4 mb-4">
      <BCol lg="8">
        <BCard no-body class="overflow-hidden">
          <BCardBody class="p-4">
            <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">{{ companyContext ? "Sezione azienda" : "Istanza operativa" }}</span>
            <h2 class="mb-2">{{ companyContext ? `Macchinari - ${companyContext.name}` : "Macchinari delle aziende del tenant" }}</h2>
            <p class="text-muted mb-4">
              {{ companyContext
                ? "Qui i macchinari della singola azienda sono trattati come oggetti operativi da aprire e governare."
                : "Qui il catalogo diventa realta' operativa: ogni macchinario e' collegato a una tipologia riusabile e a un contesto aziendale concreto." }}
            </p>
            <div class="hstack gap-2 flex-wrap">
              <span class="badge bg-light text-body">{{ companyContext ? companyContext.name : tenant.name }}</span>
              <span class="badge bg-soft-primary text-primary">{{ summary.totalCount }} macchinari</span>
              <span class="badge bg-soft-success text-success">{{ summary.activeCount }} attivi</span>
              <span class="badge bg-soft-info text-info">{{ companyContext ? "Azienda corrente" : `${summary.companiesCount} aziende coinvolte` }}</span>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol lg="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0">
            <div>
              <h4 class="card-title mb-1">Azioni rapide</h4>
              <p class="text-muted mb-0">{{ companyContext ? "Apri la dashboard azienda o aggiungi un nuovo macchinario nel contesto corrente." : "Censisci un macchinario o governa il catalogo tipologie di supporto." }}</p>
            </div>
          </BCardHeader>
          <BCardBody class="d-flex flex-column gap-3">
            <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data" :href="companyContext?.createRoute || route('equipment-assets.create')" class="btn btn-primary">Nuovo macchinario</Link>
            <Link :href="companyContext?.showRoute || route('equipment-types.index')" class="btn btn-soft-secondary">{{ companyContext ? "Torna alla dashboard azienda" : "Apri catalogo" }}</Link>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BCard no-body>
      <BCardHeader class="border-0">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <h4 class="card-title mb-1">{{ companyContext ? "Macchinari azienda" : "Registro macchinari" }}</h4>
            <p class="text-muted mb-0">{{ companyContext ? "Sezione dedicata ai macchinari della singola azienda." : "Vista minima ma reale del perimetro macchinari per le aziende del tenant." }}</p>
          </div>
          <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data" :href="companyContext?.createRoute || route('equipment-assets.create')" class="btn btn-primary btn-sm">Aggiungi macchinario</Link>
        </div>
      </BCardHeader>
      <BCardBody class="pt-0">
        <div v-if="assets.length === 0" class="text-center py-4">
          <h5 class="mb-2">Nessun macchinario ancora presente</h5>
          <p class="text-muted mb-0">Censisci il primo bene operativo per iniziare a popolare questa sorgente del contesto.</p>
        </div>
        <div v-else class="table-responsive">
          <table class="table align-middle table-nowrap mb-0">
            <thead class="table-light">
              <tr>
                <th>Macchinario</th>
                <th>Azienda</th>
                <th>Sede</th>
                <th>Tipologia</th>
                <th>Esposizioni</th>
                <th>Stato</th>
                <th class="text-end">Azioni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="asset in assets" :key="asset.id">
                <td>
                  <div class="fw-semibold">{{ asset.name }}</div>
                  <div class="text-muted fs-13">{{ asset.asset_code || "Codice non indicato" }}</div>
                </td>
                <td>{{ asset.company?.name || "Non disponibile" }}</td>
                <td>{{ asset.site?.name || "Non assegnata" }}</td>
                <td>
                  <div>{{ asset.equipment_type?.name || "Tipologia non disponibile" }}</div>
                  <div class="text-muted fs-13">{{ asset.manufacturer || "Costruttore non indicato" }}</div>
                </td>
                <td><span class="badge bg-soft-info text-info">{{ asset.worker_exposures_count }} lavoratori</span></td>
                <td>
                  <span v-if="asset.status === 'active'" class="badge bg-success-subtle text-success">Attivo</span>
                  <span v-else class="badge bg-light text-body">Non attivo</span>
                </td>
                <td class="text-end">
                  <div class="hstack gap-2 justify-content-end">
                    <Link :href="route('equipment-assets.show', asset.id)" class="btn btn-soft-primary btn-sm">Apri</Link>
                    <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data" :href="route('equipment-assets.edit', asset.id)" class="btn btn-soft-secondary btn-sm">Modifica</Link>
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
