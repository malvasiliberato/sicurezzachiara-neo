<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

const props = defineProps({
  tenant: Object,
  workplace: Object,
  contextBridge: Object,
  governanceContext: Object,
});

const measureStatusLabels = {
  implemented: "Attuata",
  not_implemented: "Non attuata",
  to_verify: "Da verificare",
};

const measureStatusBadges = {
  implemented: "bg-success-subtle text-success",
  not_implemented: "bg-danger-subtle text-danger",
  to_verify: "bg-warning-subtle text-warning",
};

const suggestedRisks = computed(() =>
  (props.workplace.workplace_type?.risk_source_links ?? []).map((link) => ({
    id: link.id,
    title: link.risk_catalog_item?.title ?? "Rischio non disponibile",
    category: link.risk_catalog_item?.category?.name ?? "Categoria non disponibile",
    relevance: link.relevance === "primary" ? "Primario" : "Secondario",
    notes: link.notes,
  }))
);
</script>

<template>
  <Layout>
    <Head :title="workplace.name" />
    <PageHeader title="Dettaglio luogo" pageTitle="SicurezzaChiara" />

    <BAlert v-if="$page.props.flash.success" show variant="success" class="mb-4">
      <i class="ri-checkbox-circle-line me-2 align-middle"></i>
      {{ $page.props.flash.success }}
    </BAlert>

    <BCard no-body class="mb-4 overflow-hidden">
      <BCardBody class="p-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
          <div>
            <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">Produttore di rischio</span>
            <h2 class="mb-1">{{ workplace.name }}</h2>
            <p class="text-muted mb-3">{{ workplace.notes || workplace.description || "Nessuna nota sul luogo presente." }}</p>
            <div class="hstack gap-2 flex-wrap">
              <span class="badge bg-light text-body">{{ tenant.name }}</span>
              <span class="badge bg-soft-info text-info">{{ workplace.site?.company?.name || "Azienda non disponibile" }}</span>
              <span class="badge bg-soft-secondary text-secondary">{{ workplace.site?.name || "Sede non disponibile" }}</span>
              <span v-if="workplace.status === 'active'" class="badge bg-success-subtle text-success">Attivo</span>
              <span v-else class="badge bg-light text-body">Non attivo</span>
            </div>
          </div>
          <div class="hstack gap-2 flex-wrap">
            <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data" :href="route('workplaces.edit', workplace.id)" class="btn btn-primary">Modifica luogo</Link>
            <Link :href="route('workplaces.index', { company_id: workplace.site?.company?.id })" class="btn btn-soft-secondary">Torna ai luoghi azienda</Link>
          </div>
        </div>
      </BCardBody>
    </BCard>

    <BCard no-body>
      <BCardBody class="p-4">
        <div class="border rounded-3 p-3 mb-4 bg-light-subtle">
          <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
            <div>
              <h5 class="mb-1">Governo del rischio</h5>
              <p class="text-muted mb-0">{{ contextBridge.narrative }}</p>
            </div>
            <span class="badge bg-soft-primary text-primary">{{ contextBridge.sourceLabel }}</span>
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
            <span class="badge bg-light text-body">{{ contextBridge.stats.suggestedRisks }} rischi suggeriti</span>
            <span class="badge bg-light text-body">{{ contextBridge.stats.linkedWorkers }} lavoratori collegati</span>
            <span class="badge bg-soft-info text-info">{{ contextBridge.companyName || "Azienda non disponibile" }}</span>
          </div>
          <div class="hstack gap-2 flex-wrap">
            <Link v-if="contextBridge.actions.riskProfileRoute" :href="contextBridge.actions.riskProfileRoute" class="btn btn-soft-primary btn-sm">
              Apri profilo rischio azienda
            </Link>
            <Link v-if="contextBridge.actions.registryRoute" :href="contextBridge.actions.registryRoute" class="btn btn-soft-info btn-sm">
              Apri registri misure
            </Link>
            <Link v-if="contextBridge.actions.companyRoute" :href="contextBridge.actions.companyRoute" class="btn btn-soft-secondary btn-sm">
              Dashboard azienda
            </Link>
          </div>
        </div>

        <BTabs nav-class="nav-success mb-4" pills>
          <BTab title="Contesto" active>
            <BRow class="g-3">
              <BCol md="6" xl="3">
                <div class="border rounded-3 p-3 h-100">
                  <span class="text-muted d-block fs-13">Tipologia luogo</span>
                  <div class="fw-semibold mt-1">{{ workplace.workplace_type?.name || "Non disponibile" }}</div>
                </div>
              </BCol>
              <BCol md="6" xl="3">
                <div class="border rounded-3 p-3 h-100">
                  <span class="text-muted d-block fs-13">Codice</span>
                  <div class="fw-semibold mt-1">{{ workplace.code || "Non indicato" }}</div>
                </div>
              </BCol>
              <BCol md="6" xl="3">
                <div class="border rounded-3 p-3 h-100">
                  <span class="text-muted d-block fs-13">Sede operativa</span>
                  <div class="fw-semibold mt-1">{{ workplace.site?.name || "Non disponibile" }}</div>
                </div>
              </BCol>
              <BCol md="6" xl="3">
                <div class="border rounded-3 p-3 h-100">
                  <span class="text-muted d-block fs-13">Localita'</span>
                  <div class="fw-semibold mt-1">
                    {{ workplace.site?.city || "Non indicata" }}<template v-if="workplace.site?.province"> ({{ workplace.site.province }})</template>
                  </div>
                </div>
              </BCol>
            </BRow>
          </BTab>

          <BTab title="Rischi suggeriti">
            <div v-if="suggestedRisks.length === 0" class="text-center py-4">
              <h5 class="mb-2">Nessun rischio suggerito disponibile</h5>
              <p class="text-muted mb-0">La tipologia del luogo non ha ancora collegamenti espliciti nel catalogo rischi.</p>
            </div>
            <div v-else class="table-responsive">
              <table class="table align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Rischio</th>
                    <th>Categoria</th>
                    <th>Rilevanza</th>
                    <th>Note</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="risk in suggestedRisks" :key="risk.id">
                    <td class="fw-semibold">{{ risk.title }}</td>
                    <td>{{ risk.category }}</td>
                    <td><span class="badge bg-soft-primary text-primary">{{ risk.relevance }}</span></td>
                    <td>{{ risk.notes || "Nessuna nota" }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </BTab>

          <BTab title="Presidi e scadenze">
            <BRow class="g-3 mb-4">
              <BCol md="6" xl="3">
                <div class="border rounded-3 p-3 h-100">
                  <span class="text-muted d-block fs-13">Rischi attivi nel contesto</span>
                  <div class="fw-semibold mt-1">{{ governanceContext.summary.activeRisks }}</div>
                </div>
              </BCol>
              <BCol md="6" xl="3">
                <div class="border rounded-3 p-3 h-100">
                  <span class="text-muted d-block fs-13">Misure collegate</span>
                  <div class="fw-semibold mt-1">{{ governanceContext.summary.totalMeasures }}</div>
                </div>
              </BCol>
              <BCol md="6" xl="3">
                <div class="border rounded-3 p-3 h-100">
                  <span class="text-muted d-block fs-13">Scadute</span>
                  <div class="fw-semibold mt-1">{{ governanceContext.summary.overdueMeasures }}</div>
                </div>
              </BCol>
              <BCol md="6" xl="3">
                <div class="border rounded-3 p-3 h-100">
                  <span class="text-muted d-block fs-13">Follow-up aperti</span>
                  <div class="fw-semibold mt-1">{{ governanceContext.summary.followUpsOpen }}</div>
                </div>
              </BCol>
            </BRow>

            <div v-if="governanceContext.previewMeasures.length === 0" class="text-center py-4">
              <h5 class="mb-2">Nessun presidio contestuale disponibile</h5>
              <p class="text-muted mb-0">Per i rischi suggeriti da questo luogo non emergono ancora misure o scadenze nel contesto aziendale collegato.</p>
            </div>
            <div v-else class="table-responsive">
              <table class="table align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Presidio</th>
                    <th>Rischio</th>
                    <th>Contesto</th>
                    <th>Stato</th>
                    <th>Scadenza</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="measure in governanceContext.previewMeasures" :key="measure.id">
                    <td class="fw-semibold">{{ measure.title }}</td>
                    <td>{{ measure.riskName || "Rischio non disponibile" }}</td>
                    <td>
                      <div>{{ measure.contextLabel }}</div>
                      <div class="text-muted fs-12">{{ measure.scopeLabel }}</div>
                    </td>
                    <td>
                      <span class="badge" :class="measureStatusBadges[measure.status] || 'bg-light text-body'">
                        {{ measureStatusLabels[measure.status] || measure.status }}
                      </span>
                    </td>
                    <td>{{ measure.dueDate || "Non pianificata" }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </BTab>

          <BTab :title="`Lavoratori collegati (${workplace.worker_exposures.length})`">
            <div v-if="workplace.worker_exposures.length === 0" class="text-center py-4">
              <h5 class="mb-2">Nessuna associazione presente</h5>
              <p class="text-muted mb-0">Il luogo e' censito ma non e' ancora stato collegato ai lavoratori.</p>
            </div>
            <div v-else class="table-responsive">
              <table class="table align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Lavoratore</th>
                    <th>Azienda</th>
                    <th>Stato</th>
                    <th>Note</th>
                    <th class="text-end">Apri</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="exposure in workplace.worker_exposures" :key="exposure.id">
                    <td>{{ exposure.worker?.full_name }}</td>
                    <td>{{ exposure.worker?.company?.name || "Non disponibile" }}</td>
                    <td>
                      <span v-if="exposure.is_primary" class="badge bg-primary-subtle text-primary">Prevalente</span>
                      <span v-else class="badge bg-light text-body">Secondaria</span>
                    </td>
                    <td>{{ exposure.notes || "Nessuna nota" }}</td>
                    <td class="text-end">
                      <Link v-if="exposure.worker?.id" :href="route('workers.show', exposure.worker.id)" class="btn btn-soft-primary btn-sm">Apri</Link>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </BTab>
        </BTabs>
      </BCardBody>
    </BCard>
  </Layout>
</template>
