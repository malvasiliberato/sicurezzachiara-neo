<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

defineProps({
  tenant: Object,
  workplaces: Array,
  summary: Object,
  companyContext: {
    type: Object,
    default: null,
  },
});
</script>

<template>
  <Layout>
    <Head title="Luoghi" />
    <PageHeader title="Luoghi" pageTitle="SicurezzaChiara" />

    <BAlert v-if="$page.props.flash.success" show variant="success" class="mb-4">
      <i class="ri-checkbox-circle-line me-2 align-middle"></i>
      {{ $page.props.flash.success }}
    </BAlert>

    <BRow class="g-4 mb-4">
      <BCol lg="8">
        <BCard no-body class="overflow-hidden">
          <BCardBody class="p-4">
            <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">{{ companyContext ? "Sezione azienda" : "Istanza operativa" }}</span>
            <h2 class="mb-2">{{ companyContext ? `Luoghi - ${companyContext.name}` : "Luoghi delle sedi aziendali" }}</h2>
            <p class="text-muted mb-4">
              {{ companyContext
                ? "Qui i luoghi della singola azienda sono trattati come ambienti reali di lavoro da aprire e governare."
                : "Qui le tipologie luogo diventano ambienti reali di lavoro, pronti a collegarsi in seguito al profilo di rischio." }}
            </p>
            <div class="hstack gap-2 flex-wrap">
              <span class="badge bg-light text-body">{{ companyContext ? companyContext.name : tenant.name }}</span>
              <span class="badge bg-soft-primary text-primary">{{ summary.totalCount }} luoghi</span>
              <span class="badge bg-soft-success text-success">{{ summary.activeCount }} attivi</span>
              <span class="badge bg-soft-info text-info">{{ summary.sitesCount }} sedi coinvolte</span>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol lg="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0">
            <div>
              <h4 class="card-title mb-1">Azioni rapide</h4>
              <p class="text-muted mb-0">{{ companyContext ? "Apri la dashboard azienda o aggiungi un nuovo luogo nel contesto corrente." : "Censisci un luogo o governa il catalogo tipologie di supporto." }}</p>
            </div>
          </BCardHeader>
          <BCardBody class="d-flex flex-column gap-3">
            <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data" :href="companyContext?.createRoute || route('workplaces.create')" class="btn btn-primary">Nuovo luogo</Link>
            <Link :href="companyContext?.showRoute || route('workplace-types.index')" class="btn btn-soft-secondary">{{ companyContext ? "Torna alla dashboard azienda" : "Apri catalogo" }}</Link>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BCard no-body>
      <BCardHeader class="border-0">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <h4 class="card-title mb-1">{{ companyContext ? "Luoghi azienda" : "Registro luoghi" }}</h4>
            <p class="text-muted mb-0">{{ companyContext ? "Sezione dedicata ai luoghi della singola azienda." : "Vista minima ma reale degli ambienti operativi per le aziende del tenant." }}</p>
          </div>
          <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data" :href="companyContext?.createRoute || route('workplaces.create')" class="btn btn-primary btn-sm">Aggiungi luogo</Link>
        </div>
      </BCardHeader>
      <BCardBody class="pt-0">
        <div v-if="workplaces.length === 0" class="text-center py-4">
          <h5 class="mb-2">Nessun luogo ancora presente</h5>
          <p class="text-muted mb-0">Censisci il primo ambiente operativo per dare forma a questa sorgente del contesto.</p>
        </div>
        <div v-else class="table-responsive">
          <table class="table align-middle table-nowrap mb-0">
            <thead class="table-light">
              <tr>
                <th>Luogo</th>
                <th>Azienda</th>
                <th>Sede</th>
                <th>Tipologia</th>
                <th>Esposizioni</th>
                <th>Stato</th>
                <th class="text-end">Azioni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="workplace in workplaces" :key="workplace.id">
                <td>
                  <div class="fw-semibold">{{ workplace.name }}</div>
                  <div class="text-muted fs-13">{{ workplace.code || "Codice non indicato" }}</div>
                </td>
                <td>{{ workplace.site?.company?.name || "Non disponibile" }}</td>
                <td>{{ workplace.site?.name || "Non disponibile" }}</td>
                <td>{{ workplace.workplace_type?.name || "Tipologia non disponibile" }}</td>
                <td><span class="badge bg-soft-info text-info">{{ workplace.worker_exposures_count }} lavoratori</span></td>
                <td>
                  <span v-if="workplace.status === 'active'" class="badge bg-success-subtle text-success">Attivo</span>
                  <span v-else class="badge bg-light text-body">Non attivo</span>
                </td>
                <td class="text-end">
                  <div class="hstack gap-2 justify-content-end">
                    <Link :href="route('workplaces.show', workplace.id)" class="btn btn-soft-primary btn-sm">Apri</Link>
                    <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data" :href="route('workplaces.edit', workplace.id)" class="btn btn-soft-secondary btn-sm">Modifica</Link>
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
