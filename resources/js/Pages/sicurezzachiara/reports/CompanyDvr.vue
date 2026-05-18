<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

defineProps({
  tenant: {
    type: Object,
    required: true,
  },
  company: {
    type: Object,
    required: true,
  },
  summary: {
    type: Object,
    required: true,
  },
  engine: {
    type: Object,
    required: true,
  },
  sourceSummary: {
    type: Object,
    required: true,
  },
  coreStarterPack: {
    type: Object,
    required: true,
  },
  coverageSignals: {
    type: Array,
    required: true,
  },
  contextBridge: {
    type: Object,
    required: true,
  },
  documentScope: {
    type: Object,
    required: true,
  },
  lightScope: {
    type: Object,
    required: true,
  },
  riskEntries: {
    type: Array,
    required: true,
  },
  measureEntries: {
    type: Array,
    required: true,
  },
  timelineEntries: {
    type: Array,
    required: true,
  },
});

const statusBadges = {
  Coperto: "bg-success-subtle text-success",
  "Da presidiare": "bg-warning-subtle text-warning",
  Escluso: "bg-secondary-subtle text-secondary",
  Attuata: "bg-success-subtle text-success",
  "Da verificare": "bg-warning-subtle text-warning",
  "Non attuata": "bg-danger-subtle text-danger",
};

const followUpBadges = {
  open: "bg-warning-subtle text-warning",
  in_progress: "bg-primary-subtle text-primary",
  blocked: "bg-danger-subtle text-danger",
  closed: "bg-success-subtle text-success",
};

const followUpLabels = {
  open: "Aperto",
  in_progress: "In lavorazione",
  blocked: "Bloccato",
  closed: "Chiuso",
};

const followUpOutcomeLabels = {
  resolved: "Presidio completato",
  monitored: "Chiuso con monitoraggio",
  deferred: "Chiuso con rinvio",
};

const timelineBadges = {
  info: "bg-info-subtle text-info",
  primary: "bg-primary-subtle text-primary",
  success: "bg-success-subtle text-success",
  warning: "bg-warning-subtle text-warning",
  danger: "bg-danger-subtle text-danger",
  secondary: "bg-light text-body",
};

const scopeToneBadges = {
  success: "bg-success-subtle text-success",
  warning: "bg-warning-subtle text-warning",
};

const guidanceToneBadges = {
  danger: "bg-danger-subtle text-danger",
  warning: "bg-warning-subtle text-warning",
  info: "bg-info-subtle text-info",
  primary: "bg-primary-subtle text-primary",
};

const printReport = () => {
  window.print();
};
</script>

<template>
  <Layout>
    <Head :title="`DVR light - ${company.name}`" />

    <PageHeader title="DVR light" pageTitle="SicurezzaChiara" />

    <BRow class="g-4 mb-4">
      <BCol xl="8">
        <BCard no-body class="overflow-hidden h-100">
          <BCardBody class="p-4 p-lg-5">
            <span class="badge bg-danger-subtle text-danger text-uppercase mb-3">Output derivato dal dominio</span>
            <h2 class="mb-2">{{ company.name }}</h2>
            <p class="text-muted fs-15 mb-3">
              Questo DVR light non nasce da compilazione statica: aggrega contesto aziendale, sorgenti del rischio,
              profilo rischio e stato dei presidi attualmente registrati nel sistema, mantenendo esplicito il suo perimetro consultabile.
            </p>
            <div class="hstack gap-2 flex-wrap">
              <span class="badge bg-light text-body">{{ tenant.name }}</span>
              <span class="badge bg-soft-primary text-primary">{{ summary.sitesCount }} sedi</span>
              <span class="badge bg-soft-info text-info">{{ summary.workersCount }} lavoratori</span>
              <span class="badge bg-soft-warning text-warning">{{ summary.activeRisks }} rischi attivi</span>
              <span class="badge bg-soft-success text-success">{{ summary.measuresCount }} misure</span>
              <span class="badge bg-soft-danger text-danger">{{ summary.missingExpectedMeasures }} gap attesi</span>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0">
            <h4 class="card-title mb-0">Azioni rapide</h4>
          </BCardHeader>
          <BCardBody>
            <div class="vstack gap-2">
              <button type="button" class="btn btn-primary" @click="printReport">
                Stampa browser
              </button>
              <Link :href="contextBridge.actions.riskProfileRoute" class="btn btn-soft-danger">
                {{ contextBridge.suggestedAction.label }}
              </Link>
              <Link :href="contextBridge.actions.registryRoute" class="btn btn-soft-info">
                Apri registri azienda
              </Link>
              <Link :href="contextBridge.actions.dashboardRoute" class="btn btn-soft-warning">
                Focus dashboard
              </Link>
              <Link :href="contextBridge.actions.companyRoute" class="btn btn-soft-secondary">
                Torna azienda
              </Link>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BCard no-body class="mb-4">
      <BCardBody class="p-3">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <div class="fw-semibold mb-1">{{ documentScope.title }}</div>
            <div class="text-muted fs-13">{{ documentScope.helper }}</div>
          </div>
          <span class="badge" :class="scopeToneBadges[documentScope.statusTone] || 'bg-light text-body'">
            {{ documentScope.status }}
          </span>
        </div>

        <BRow class="g-3">
          <BCol v-for="item in documentScope.items" :key="item.label" md="6" xl="3">
            <div class="border rounded p-3 h-100">
              <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                <span class="text-uppercase text-muted fs-12 fw-semibold">{{ item.label }}</span>
                <span class="badge" :class="scopeToneBadges[item.tone] || 'bg-light text-body'">
                  {{ item.value }}
                </span>
              </div>
              <div class="text-muted fs-13">{{ item.helper }}</div>
            </div>
          </BCol>
        </BRow>
      </BCardBody>
    </BCard>

    <BRow class="g-4 mb-4">
      <BCol xl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0 pb-0">
            <h4 class="card-title mb-1">{{ lightScope.title }}</h4>
            <p class="text-muted mb-0">{{ lightScope.helper }}</p>
          </BCardHeader>
          <BCardBody class="pt-3">
            <div class="vstack gap-3">
              <div v-for="item in lightScope.reads" :key="item.label" class="border rounded p-3">
                <div class="fw-semibold mb-1">{{ item.label }}</div>
                <div class="text-muted fs-13">{{ item.helper }}</div>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>

      <BCol xl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0 pb-0">
            <h4 class="card-title mb-1">Cosa non sostituisce ancora</h4>
            <p class="text-muted mb-0">Serve a evitare overclaim documentale e a tenere chiaro il perimetro attuale del prodotto.</p>
          </BCardHeader>
          <BCardBody class="pt-3">
            <div class="vstack gap-3">
              <div v-for="item in lightScope.notIncluded" :key="item.label" class="border rounded p-3">
                <div class="fw-semibold mb-1">{{ item.label }}</div>
                <div class="text-muted fs-13">{{ item.helper }}</div>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>

      <BCol xl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0 pb-0">
            <h4 class="card-title mb-1">Per renderlo piu' utile adesso</h4>
            <p class="text-muted mb-0">Le prossime mosse restano nel workspace operativo: profilo rischio, registri, review e follow-up.</p>
          </BCardHeader>
          <BCardBody class="pt-3">
            <div v-if="lightScope.nextSteps.length" class="vstack gap-3">
              <div v-for="item in lightScope.nextSteps" :key="item.label" class="border rounded p-3">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                  <div class="fw-semibold">{{ item.label }}</div>
                  <span class="badge" :class="guidanceToneBadges[item.tone] || 'bg-light text-body'">
                    Azione
                  </span>
                </div>
                <div class="text-muted fs-13">{{ item.helper }}</div>
              </div>
            </div>
            <div v-else class="border rounded p-3">
              <div class="fw-semibold mb-1">Rileggi profilo e registri per conferma finale</div>
              <div class="text-muted fs-13">
                Il DVR light e' coerente col dominio corrente: puoi usarlo come lettura consultabile e rientrare nel workspace per la verifica finale del consulente.
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BCard no-body class="mb-4">
      <BCardBody class="p-3">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
          <div>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
              <span class="badge bg-soft-primary text-primary">Focus: {{ contextBridge.focusLabel }}</span>
              <span class="badge bg-light text-body">{{ contextBridge.stats.activeRisks }} rischi attivi</span>
            </div>
            <div class="fw-semibold mb-1">{{ contextBridge.suggestedAction.label }}</div>
            <div class="text-muted fs-13">{{ contextBridge.suggestedAction.helper }}</div>
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-info-subtle text-info">{{ contextBridge.stats.reviewsDue }} review dovute</span>
            <span class="badge bg-primary-subtle text-primary">{{ contextBridge.stats.followUpsOpen }} follow-up aperti</span>
            <span class="badge bg-danger-subtle text-danger">{{ contextBridge.stats.overdueMeasures }} scadute</span>
          </div>
        </div>
      </BCardBody>
    </BCard>

    <BCard no-body class="mb-4">
      <BCardHeader class="border-0 pb-0">
        <h4 class="card-title mb-1">Motore v1 letto dal DVR</h4>
        <p class="text-muted mb-0">Il documento legge lo stesso motore del prodotto: sorgenti attive, rischi consolidati, intervento consulente e copertura dei presidi.</p>
      </BCardHeader>
      <BCardBody class="pt-3">
        <div class="d-flex align-items-stretch gap-3 flex-wrap">
          <div v-for="step in engine.flow" :key="step.key" class="border rounded p-3 flex-grow-1" style="min-width: 220px;">
            <div class="text-uppercase text-muted fs-12 fw-semibold mb-2">{{ step.label }}</div>
            <div class="fw-semibold fs-5 mb-1">{{ step.value }}</div>
            <div class="text-muted fs-13">{{ step.helper }}</div>
          </div>
        </div>
      </BCardBody>
    </BCard>

    <BRow class="g-4 mb-4">
      <BCol v-for="signal in coverageSignals" :key="signal.label" xl="3" md="6">
        <BCard no-body class="h-100">
          <BCardBody>
            <p class="text-uppercase fw-medium text-muted mb-2">{{ signal.label }}</p>
            <h4 class="mb-1">{{ signal.value }}</h4>
            <p class="text-muted mb-0 fs-13">{{ signal.helper }}</p>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4 mb-4">
      <BCol md="6" xl="3">
        <BCard no-body class="card-animate h-100">
          <BCardBody>
            <p class="text-uppercase fw-medium text-muted mb-3">Rischi attivi</p>
            <h3 class="mb-0">{{ summary.activeRisks }}</h3>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol md="6" xl="3">
        <BCard no-body class="card-animate h-100">
          <BCardBody>
            <p class="text-uppercase fw-medium text-muted mb-3">Da presidiare</p>
            <h3 class="mb-0">{{ summary.uncoveredRisks }}</h3>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol md="6" xl="3">
        <BCard no-body class="card-animate h-100">
          <BCardBody>
            <p class="text-uppercase fw-medium text-muted mb-3">Revisioni in agenda</p>
            <h3 class="mb-0">{{ summary.reviewsDue }}</h3>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol md="6" xl="3">
        <BCard no-body class="card-animate h-100">
          <BCardBody>
            <p class="text-uppercase fw-medium text-muted mb-3">Follow-up aperti</p>
            <h3 class="mb-0">{{ summary.followUpsOpen }}</h3>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol md="6" xl="3">
        <BCard no-body class="card-animate h-100">
          <BCardBody>
            <p class="text-uppercase fw-medium text-muted mb-3">Follow-up chiusi</p>
            <h3 class="mb-0">{{ summary.followUpsClosed }}</h3>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol md="6" xl="3">
        <BCard no-body class="card-animate h-100">
          <BCardBody>
            <p class="text-uppercase fw-medium text-muted mb-3">Misure scadute</p>
            <h3 class="mb-0">{{ summary.overdueMeasures }}</h3>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4 mb-4">
      <BCol xl="5">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0 pb-0">
            <h4 class="card-title mb-1">Contesto aziendale</h4>
            <p class="text-muted mb-0">Anagrafica sintetica e perimetro minimo che il DVR light sta gia' leggendo.</p>
          </BCardHeader>
          <BCardBody class="pt-3">
            <div class="vstack gap-3">
              <div>
                <span class="text-muted d-block fs-13">Ragione sociale</span>
                <span class="fw-medium">{{ company.legal_name || company.name }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Settore</span>
                <span class="fw-medium">{{ company.industry || "Non indicato" }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Contatti</span>
                <span class="fw-medium">{{ company.contact_email || "Email non indicata" }}</span>
                <div class="text-muted fs-13">{{ company.contact_phone || "Telefono non indicato" }}</div>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Localita'</span>
                <span class="fw-medium">{{ company.city || "Non indicata" }}<template v-if="company.province"> ({{ company.province }})</template></span>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>

      <BCol xl="7">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0 pb-0">
            <h4 class="card-title mb-1">Sorgenti del rischio censite</h4>
            <p class="text-muted mb-0">Sorgenti operative gia' censite e quindi leggibili nel DVR light.</p>
          </BCardHeader>
          <BCardBody class="pt-3">
            <BRow class="g-3">
              <BCol md="4">
                <div class="border rounded p-3 h-100">
                  <h6 class="mb-2">Mansioni</h6>
                  <div v-if="sourceSummary.job_roles.length" class="d-flex flex-wrap gap-2">
                    <span v-for="item in sourceSummary.job_roles" :key="item" class="badge bg-light text-body">{{ item }}</span>
                  </div>
                  <p v-else class="text-muted mb-0">Nessuna mansione rilevata.</p>
                </div>
              </BCol>
              <BCol md="4">
                <div class="border rounded p-3 h-100">
                  <h6 class="mb-2">Macchinari</h6>
                  <div v-if="sourceSummary.equipment_assets.length" class="d-flex flex-wrap gap-2">
                    <span v-for="item in sourceSummary.equipment_assets" :key="item" class="badge bg-light text-body">{{ item }}</span>
                  </div>
                  <p v-else class="text-muted mb-0">Nessun macchinario rilevato.</p>
                </div>
              </BCol>
              <BCol md="4">
                <div class="border rounded p-3 h-100">
                  <h6 class="mb-2">Luoghi</h6>
                  <div v-if="sourceSummary.workplaces.length" class="d-flex flex-wrap gap-2">
                    <span v-for="item in sourceSummary.workplaces" :key="item" class="badge bg-light text-body">{{ item }}</span>
                  </div>
                  <p v-else class="text-muted mb-0">Nessun luogo rilevato.</p>
                </div>
              </BCol>
            </BRow>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BCard no-body class="mb-4">
      <BCardHeader class="border-0 pb-0">
        <h4 class="card-title mb-1">Starter pack core letto nel DVR</h4>
        <p class="text-muted mb-0">Segnali letti dal core standard e presidi attesi che il contesto attuale porta gia' con se'.</p>
      </BCardHeader>
      <BCardBody class="pt-3">
        <BRow class="g-3 mb-3">
          <BCol md="6" xl="3">
            <div class="border rounded p-3 h-100">
              <div class="text-uppercase text-muted fs-12 fw-semibold mb-2">Sorgenti rilevate</div>
              <div class="fw-semibold fs-4">{{ coreStarterPack.summary.sourceCount }}</div>
            </div>
          </BCol>
          <BCol md="6" xl="3">
            <div class="border rounded p-3 h-100">
              <div class="text-uppercase text-muted fs-12 fw-semibold mb-2">Sorgenti core</div>
              <div class="fw-semibold fs-4">{{ coreStarterPack.summary.coreSourceCount }}</div>
            </div>
          </BCol>
          <BCol md="6" xl="3">
            <div class="border rounded p-3 h-100">
              <div class="text-uppercase text-muted fs-12 fw-semibold mb-2">Rischi core suggeriti</div>
              <div class="fw-semibold fs-4">{{ coreStarterPack.summary.suggestedRisksCount }}</div>
            </div>
          </BCol>
          <BCol md="6" xl="3">
            <div class="border rounded p-3 h-100">
              <div class="text-uppercase text-muted fs-12 fw-semibold mb-2">Presidi attesi core</div>
              <div class="fw-semibold fs-4">{{ coreStarterPack.summary.expectedMeasuresCount }}</div>
            </div>
          </BCol>
        </BRow>

        <div v-if="coreStarterPack.suggestedRisks.length" class="d-flex flex-wrap gap-2">
          <span v-for="risk in coreStarterPack.suggestedRisks" :key="risk.id" class="badge bg-light text-body">
            {{ risk.name }} | {{ risk.expected_measures_count }} presidi attesi
          </span>
        </div>
        <div v-else class="text-muted">Nessun rischio core leggibile dal contesto attuale del DVR.</div>
      </BCardBody>
    </BCard>

    <BCard no-body class="mb-4">
      <BCardHeader class="border-0 pb-0">
        <h4 class="card-title mb-1">Profilo rischio attuale</h4>
        <p class="text-muted mb-0">Lettura aggregata di azienda e lavoratori, derivata dallo stato reale del dominio.</p>
      </BCardHeader>
      <BCardBody class="pt-3">
        <div v-if="riskEntries.length === 0" class="text-center py-5 text-muted">
          Nessun rischio disponibile per questa azienda.
        </div>
        <div v-else class="table-responsive">
          <table class="table align-middle table-nowrap mb-0">
            <thead class="table-light">
              <tr>
                <th>Ambito</th>
                <th>Rischio</th>
                <th>Categoria</th>
                <th>Priorita'</th>
                <th>Stato</th>
                <th>Review</th>
                <th>Follow-up</th>
                <th>Sorgenti</th>
                <th>Presidi attesi</th>
                <th>Misure</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="entry in riskEntries" :key="entry.id">
                <td>
                  <div class="fw-semibold">{{ entry.scope_label }}</div>
                  <div class="text-muted fs-13">{{ entry.scope }}</div>
                </td>
                <td>{{ entry.risk_name }}</td>
                <td>{{ entry.risk_category || "Categoria non disponibile" }}</td>
                <td>{{ entry.priority }}</td>
                <td>
                  <span class="badge" :class="statusBadges[entry.status] || 'bg-light text-body'">
                    {{ entry.status }}
                  </span>
                </td>
                <td>
                  <div>{{ entry.review_due_at || "Non pianificata" }}</div>
                  <div v-if="entry.reviewed_at" class="text-muted fs-13">Ultima: {{ entry.reviewed_at }}</div>
                </td>
                <td>
                  <div v-if="entry.follow_up_status">
                    <span class="badge" :class="followUpBadges[entry.follow_up_status] || 'bg-light text-body'">
                      {{ followUpLabels[entry.follow_up_status] || entry.follow_up_status }}
                    </span>
                    <div class="text-muted fs-13 mt-1">{{ entry.operational_owner_name || "Da assegnare" }}</div>
                    <div v-if="entry.follow_up_due_at" class="text-muted fs-13">Entro {{ entry.follow_up_due_at }}</div>
                    <div v-if="entry.follow_up_outcome_status" class="text-muted fs-13">
                      Esito: {{ followUpOutcomeLabels[entry.follow_up_outcome_status] || entry.follow_up_outcome_status }}
                    </div>
                    <div v-if="entry.follow_up_outcome_notes" class="text-muted fs-13">{{ entry.follow_up_outcome_notes }}</div>
                  </div>
                  <span v-else class="text-muted fs-13">Nessun follow-up</span>
                </td>
                <td>
                  <div class="d-flex flex-wrap gap-2">
                    <span v-for="source in entry.sources" :key="source" class="badge bg-light text-body">
                      {{ source }}
                    </span>
                  </div>
                </td>
                <td>
                  <div v-if="entry.expected_measures.summary.expected_count" class="text-muted fs-13">
                    {{ entry.expected_measures.summary.covered_count }} / {{ entry.expected_measures.summary.expected_count }} coperti
                  </div>
                  <div v-if="entry.expected_measures.summary.substituted_count" class="text-muted fs-13">
                    {{ entry.expected_measures.summary.substituted_count }} per equivalenza
                  </div>
                  <span v-else class="text-muted fs-13">Nessuna attesa esplicita</span>
                </td>
                <td>
                  <div v-if="entry.measures.length" class="d-flex flex-column gap-2">
                    <div v-for="measure in entry.measures" :key="`${entry.id}-${measure.title}`" class="fs-13">
                      <span class="fw-medium">{{ measure.title }}</span>
                      <span class="text-muted"> | {{ measure.status }}<template v-if="measure.due_date"> | {{ measure.due_date }}</template></span>
                    </div>
                  </div>
                  <span v-else class="text-muted fs-13">Nessuna misura collegata</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </BCardBody>
    </BCard>

    <BCard no-body class="mb-4">
      <BCardHeader class="border-0 pb-0">
        <h4 class="card-title mb-1">Presidi e scadenze operative</h4>
        <p class="text-muted mb-0">Misure collegate ai rischi e gia' visibili nei registri famiglia dell'azienda.</p>
      </BCardHeader>
      <BCardBody class="pt-3">
        <div v-if="measureEntries.length === 0" class="text-center py-5 text-muted">
          Nessuna misura collegata ai rischi di questa azienda.
        </div>
        <div v-else class="table-responsive">
          <table class="table align-middle table-nowrap mb-0">
            <thead class="table-light">
              <tr>
                <th>Misura</th>
                <th>Famiglia misura</th>
                <th>Contesto</th>
                <th>Rischio</th>
                <th>Stato</th>
                <th>Scadenza</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="entry in measureEntries" :key="entry.id">
                <td>{{ entry.title }}</td>
                <td>{{ entry.family }}</td>
                <td>{{ entry.context }}</td>
                <td>{{ entry.risk_name || "Rischio non disponibile" }}</td>
                <td>
                  <span class="badge" :class="statusBadges[entry.status] || 'bg-light text-body'">
                    {{ entry.status }}
                  </span>
                </td>
                <td>{{ entry.due_date }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </BCardBody>
    </BCard>

    <BCard no-body>
      <BCardHeader class="border-0 pb-0">
        <h4 class="card-title mb-1">Timeline operativa aziendale</h4>
        <p class="text-muted mb-0">Sequenza cronologica minima di review, follow-up, esiti e misure che il DVR light sta gia' leggendo.</p>
      </BCardHeader>
      <BCardBody class="pt-3">
        <div v-if="timelineEntries.length === 0" class="text-center py-5 text-muted">
          Nessun evento operativo disponibile per questa azienda.
        </div>
        <div v-else class="vstack gap-3">
          <div v-for="event in timelineEntries" :key="`${event.type}-${event.title}-${event.occurred_at}-${event.scope_label}`" class="border rounded p-3">
            <div class="d-flex align-items-start justify-content-between gap-3">
              <div>
                <div class="fw-semibold">{{ event.title }}</div>
                <div class="text-muted fs-13">{{ event.scope_label }}</div>
                <div class="text-muted fs-13">{{ event.label }}</div>
                <div class="text-muted fs-13">{{ event.detail }}</div>
                <div v-if="event.meta" class="text-muted fs-13">{{ event.meta }}</div>
              </div>
              <div class="text-end">
                <span class="badge d-block mb-1" :class="timelineBadges[event.tone] || 'bg-light text-body'">
                  {{ event.type }}
                </span>
                <span class="text-muted fs-13">{{ event.occurred_at || "Non disponibile" }}</span>
              </div>
            </div>
          </div>
        </div>
      </BCardBody>
    </BCard>
  </Layout>
</template>
