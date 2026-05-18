<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

const props = defineProps({
  tenant: {
    type: Object,
    required: true,
  },
  activeFocus: {
    type: String,
    required: true,
  },
  focusOptions: {
    type: Array,
    required: true,
  },
  focusMeta: {
    type: Object,
    required: true,
  },
  engineSummary: {
    type: Object,
    required: true,
  },
  engineFlow: {
    type: Array,
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
  decisionBoard: {
    type: Array,
    required: true,
  },
  prioritySignals: {
    type: Array,
    required: true,
  },
  portfolioHotspots: {
    type: Array,
    required: true,
  },
  pressureCategories: {
    type: Array,
    required: true,
  },
  workspaceLanes: {
    type: Array,
    required: true,
  },
  summary: {
    type: Object,
    required: true,
  },
  alerts: {
    type: Array,
    required: true,
  },
  upcomingDeadlines: {
    type: Array,
    required: true,
  },
  attentionMeasures: {
    type: Array,
    required: true,
  },
  criticalQueue: {
    type: Array,
    required: true,
  },
  reviewQueue: {
    type: Array,
    required: true,
  },
  followUpQueue: {
    type: Array,
    required: true,
  },
  agendaQueue: {
    type: Array,
    required: true,
  },
  ownerAgenda: {
    type: Array,
    required: true,
  },
  companyAgenda: {
    type: Array,
    required: true,
  },
  recentOutcomes: {
    type: Array,
    required: true,
  },
  recentTimeline: {
    type: Array,
    required: true,
  },
  companySnapshots: {
    type: Array,
    required: true,
  },
});

const primaryStats = computed(() => [
  {
    title: "Rischi attivi",
    value: props.summary.activeRisks,
    helper: `${props.summary.coveredRisks} coperti | ${props.summary.uncoveredRisks} scoperti`,
    icon: "ri-alarm-warning-line",
    tone: "warning",
  },
  {
    title: "Scadenze imminenti",
    value: props.summary.imminentDeadlines,
    helper: "entro i prossimi 30 giorni",
    icon: "ri-calendar-event-line",
    tone: "danger",
  },
  {
    title: "Misure da verificare",
    value: props.summary.measuresToVerify,
    helper: "presidi ancora aperti",
    icon: "ri-shield-check-line",
    tone: "success",
  },
  {
    title: "Revisioni in agenda",
    value: props.summary.reviewsDue,
    helper: `${props.summary.overdueReviews} scadute | ${props.summary.reviewsDue - props.summary.overdueReviews} pianificate`,
    icon: "ri-calendar-check-line",
    tone: "info",
  },
  {
    title: "Follow-up aperti",
    value: props.summary.followUpsOpen,
    helper: "criticita' in presa in carico",
    icon: "ri-user-follow-line",
    tone: "primary",
  },
  {
    title: "Aziende monitorate",
    value: props.summary.companiesMonitored,
    helper: `${props.summary.workersMonitored} lavoratori nel perimetro`,
    icon: "ri-building-line",
    tone: "secondary",
  },
]);

const secondaryStats = computed(() => [
  { label: "Agenda operativa", value: props.summary.agendaItems },
  { label: "Referenti attivi", value: props.summary.ownersInAgenda },
  { label: "Aziende in agenda", value: props.summary.companiesInAgenda },
  { label: "Esiti registrati", value: props.summary.recordedOutcomes },
  { label: "Eventi timeline", value: props.summary.timelineEvents },
  { label: "Lavoratori monitorati", value: props.summary.workersMonitored },
]);

const nextActions = computed(() => props.agendaQueue.slice(0, 4));

const statusBadges = {
  Attuata: "bg-success-subtle text-success",
  "Da verificare": "bg-warning-subtle text-warning",
  "Non attuata": "bg-danger-subtle text-danger",
};

const alertClasses = {
  success: "alert alert-success alert-border-left mb-0",
  warning: "alert alert-warning alert-border-left mb-0",
  danger: "alert alert-danger alert-border-left mb-0",
  info: "alert alert-info alert-border-left mb-0",
  primary: "alert alert-primary alert-border-left mb-0",
};

const criticalBadges = {
  danger: "bg-danger-subtle text-danger",
  warning: "bg-warning-subtle text-warning",
  info: "bg-info-subtle text-info",
  primary: "bg-primary-subtle text-primary",
};

const reviewBadges = {
  Scaduta: "bg-info-subtle text-info",
  Pianificata: "bg-light text-body",
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

const outcomeLabels = {
  resolved: "Presidio completato",
  monitored: "Chiuso con monitoraggio",
  deferred: "Chiuso con rinvio",
};

const outcomeBadges = {
  resolved: "bg-success-subtle text-success",
  monitored: "bg-info-subtle text-info",
  deferred: "bg-warning-subtle text-warning",
};

const timelineBadges = {
  info: "bg-info-subtle text-info",
  primary: "bg-primary-subtle text-primary",
  success: "bg-success-subtle text-success",
  warning: "bg-warning-subtle text-warning",
  danger: "bg-danger-subtle text-danger",
  secondary: "bg-light text-body",
};

const flowBadges = {
  info: "bg-info-subtle text-info",
  primary: "bg-primary-subtle text-primary",
  warning: "bg-warning-subtle text-warning",
  danger: "bg-danger-subtle text-danger",
  success: "bg-success-subtle text-success",
  secondary: "bg-light text-body",
};

const starterPriorityBadge = (priority) => {
  if (priority === "high") {
    return "bg-danger-subtle text-danger";
  }

  if (priority === "medium") {
    return "bg-warning-subtle text-warning";
  }

  return "bg-light text-body";
};

const decisionBadges = {
  danger: "bg-danger-subtle text-danger",
  warning: "bg-warning-subtle text-warning",
  primary: "bg-primary-subtle text-primary",
  info: "bg-info-subtle text-info",
  success: "bg-success-subtle text-success",
};
</script>

<template>
  <Layout>
    <Head title="Dashboard" />

    <PageHeader title="Dashboard" pageTitle="SicurezzaChiara" />

    <BRow class="g-4 mb-4">
      <BCol lg="8">
        <BCard no-body class="overflow-hidden h-100">
          <BCardBody class="p-4 p-lg-5">
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
              <div>
                <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">Workspace operativo</span>
                <h2 class="mb-2">Presidio rischio e scadenze del tenant</h2>
                <p class="text-muted fs-15 mb-0">
                  Vista sintetica del lavoro consulenziale su {{ tenant.name }}: rischi attivi, coperture presenti,
                  misure in attenzione e prossime scadenze da presidiare.
                </p>
              </div>
              <div class="text-end">
                <div class="text-muted text-uppercase fs-12 fw-semibold mb-1">Copertura attuale</div>
                <h2 class="mb-0">{{ summary.coverageRate }}%</h2>
              </div>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap mb-4">
              <span class="badge bg-light text-body">Sorgenti attive: {{ engineSummary.sourceInputs }}</span>
              <span class="badge bg-light text-body">Sorgenti core: {{ engineSummary.coreSourceInputs }}</span>
              <span class="badge bg-light text-body">Rischi core suggeriti: {{ engineSummary.suggestedCoreRisks }}</span>
              <span class="badge bg-light text-body">Presidi attesi core: {{ engineSummary.expectedCoreMeasures }}</span>
              <span class="badge bg-light text-body">Rischi dedotti: {{ engineSummary.derivedRisks }}</span>
              <span class="badge bg-light text-body">Interventi consulente: {{ engineSummary.consultantAdjustedRisks }}</span>
              <span class="badge bg-light text-body">Misure pendenti: {{ engineSummary.pendingMeasures }}</span>
              <span class="badge bg-light text-body">Gap presidi attesi: {{ engineSummary.missingExpectedMeasures }}</span>
              <span class="badge bg-light text-body">Misure libere: {{ engineSummary.freeMeasures }}</span>
            </div>

            <div class="hstack gap-2 flex-wrap">
              <Link :href="route('measure-registries.index', { scope: 'attention', origin: 'dashboard', focus: activeFocus })" class="btn btn-primary">
                Apri registri
              </Link>
              <Link :href="route('companies.index')" class="btn btn-soft-primary">
                Apri aziende
              </Link>
              <Link :href="route('sicurezzachiara.ui-reference')" class="btn btn-soft-secondary">
                UI Reference
              </Link>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol lg="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0">
            <div>
              <h4 class="card-title mb-1">Da fare adesso</h4>
              <p class="text-muted mb-0">Le prossime azioni operative ordinate dal workspace.</p>
            </div>
          </BCardHeader>
          <BCardBody>
            <div v-if="nextActions.length === 0" class="text-center py-5 text-muted">
              Nessuna attivita' immediata nel tenant corrente.
            </div>
            <div v-else class="vstack gap-3">
              <Link
                v-for="item in nextActions"
                :key="`${item.kind}-${item.title}-${item.context}`"
                :href="item.route"
                class="border rounded p-3 text-reset text-decoration-none"
              >
                <div class="d-flex align-items-start justify-content-between gap-3">
                  <div>
                    <div class="fw-semibold">{{ item.title }}</div>
                    <div class="text-muted fs-13">{{ item.context }}</div>
                    <div class="text-muted fs-13">{{ item.detail }}</div>
                    <div v-if="item.owner_name" class="text-muted fs-13">Referente: {{ item.owner_name }}</div>
                  </div>
                  <div class="text-end">
                    <span class="badge bg-light text-body d-block mb-1">{{ item.status_label }}</span>
                    <span class="text-muted fs-13">{{ item.due_date || "Non definita" }}</span>
                  </div>
                </div>
              </Link>
            </div>
            <div class="border-top mt-3 pt-3">
              <div class="vstack gap-2">
                <div v-for="alert in alerts" :key="alert.title" :class="alertClasses[alert.tone] || alertClasses.warning">
                  <h6 class="mb-1">{{ alert.title }}</h6>
                  <p class="mb-0">{{ alert.text }}</p>
                </div>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BCard no-body class="mb-4">
      <BCardBody class="p-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <span class="badge bg-info-subtle text-info text-uppercase mb-2">Focus operativo</span>
            <h4 class="mb-1">{{ focusMeta.label }}</h4>
            <p class="text-muted mb-0">{{ focusMeta.description }}</p>
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <Link
              v-for="focus in focusOptions"
              :key="focus.value"
              :href="route('dashboard', focus.value === 'all' ? {} : { focus: focus.value })"
              class="btn btn-sm"
              :class="activeFocus === focus.value ? 'btn-primary' : 'btn-soft-primary'"
            >
              {{ focus.label }} <span class="ms-1 badge bg-light text-body">{{ focus.count }}</span>
            </Link>
          </div>
        </div>
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap border-top pt-3">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span v-for="item in focusMeta.highlights || []" :key="item.label" class="badge bg-light text-body fs-12">
              {{ item.label }}: {{ item.value }}
            </span>
          </div>
          <Link v-if="focusMeta.primaryAction" :href="focusMeta.primaryAction.href" class="btn btn-soft-primary btn-sm">
            {{ focusMeta.primaryAction.label }}
          </Link>
        </div>
      </BCardBody>
    </BCard>

    <BRow class="g-4 mb-4">
      <BCol xl="8">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0 pb-0">
            <div>
              <span class="badge bg-primary-subtle text-primary text-uppercase mb-2">Motore v1 nel workspace</span>
              <h4 class="card-title mb-1">Dalle sorgenti attive alla copertura letta in dashboard</h4>
              <p class="text-muted mb-0">
                La dashboard usa la stessa grammatica del motore: contesto reale, starter pack core, rischio governato, intervento consulente e presidi effettivi.
              </p>
            </div>
          </BCardHeader>
          <BCardBody class="pt-3">
            <BRow class="g-3">
              <BCol v-for="step in engineFlow" :key="step.key" xl="4" md="6">
                <div class="border rounded p-3 h-100">
                  <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                    <span class="badge" :class="flowBadges[step.tone] || 'bg-light text-body'">{{ step.label }}</span>
                    <span class="fw-semibold">{{ step.value }}</span>
                  </div>
                  <p class="text-muted mb-0 fs-13">{{ step.helper }}</p>
                </div>
              </BCol>
            </BRow>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0 pb-0">
            <div>
              <h4 class="card-title mb-1">Starter pack core nel workspace</h4>
              <p class="text-muted mb-0">Segnali standard oggi realmente letti dal tenant corrente.</p>
            </div>
          </BCardHeader>
          <BCardBody class="pt-3">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
              <span class="badge bg-soft-primary text-primary">{{ coreStarterPack.summary.sourceCount }} sorgenti</span>
              <span class="badge bg-soft-info text-info">{{ coreStarterPack.summary.suggestedRisksCount }} rischi core</span>
              <span class="badge bg-soft-warning text-warning">{{ coreStarterPack.summary.expectedMeasuresCount }} presidi attesi</span>
            </div>
            <div v-if="coreStarterPack.suggestedRisks.length === 0" class="text-muted fs-13">
              Nessun rischio core suggerito nel contesto operativo corrente.
            </div>
            <div v-else class="d-flex flex-wrap gap-2">
              <span
                v-for="risk in coreStarterPack.suggestedRisks.slice(0, 8)"
                :key="risk.id"
                class="badge"
                :class="starterPriorityBadge(risk.default_priority)"
              >
                {{ risk.name }} <span class="ms-1">{{ risk.trigger_count }} trigger</span>
              </span>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

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
      <BCol v-for="item in decisionBoard" :key="item.title" xl="4">
        <BCard no-body class="h-100">
          <BCardBody>
            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
              <div>
                <p class="text-uppercase fw-medium text-muted mb-2">{{ item.title }}</p>
                <h4 class="mb-1">{{ item.count }}</h4>
                <p class="text-muted mb-0 fs-13">{{ item.helper }}</p>
              </div>
              <span class="badge" :class="decisionBadges[item.tone] || 'bg-light text-body'">
                {{ item.top_label || "Nessun focus" }}
              </span>
            </div>
            <div v-if="item.top_helper" class="text-muted fs-13 mb-3">{{ item.top_helper }}</div>
            <Link :href="item.cta_route" class="btn btn-soft-primary btn-sm">
              {{ item.cta_label }}
            </Link>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4 mb-4">
      <BCol xl="5">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0 pb-0">
            <div>
              <h4 class="card-title mb-1">Segnali strutturali del portafoglio</h4>
              <p class="text-muted mb-0">Lettura sintetica di urgenza, fragilita' di copertura e pressione sul portafoglio consulenziale.</p>
            </div>
          </BCardHeader>
          <BCardBody class="pt-3">
            <BRow class="g-3">
              <BCol v-for="signal in prioritySignals" :key="signal.label" md="6">
                <div class="border rounded p-3 h-100">
                  <div class="text-uppercase text-muted fs-12 fw-semibold mb-2">{{ signal.label }}</div>
                  <div class="fw-semibold fs-3 mb-1">{{ signal.value }}</div>
                  <div class="text-muted fs-13">{{ signal.helper }}</div>
                </div>
              </BCol>
            </BRow>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0 pb-0">
            <div>
              <h4 class="card-title mb-1">Aziende da aprire subito</h4>
              <p class="text-muted mb-0">Contesti dove il workspace ha gia' un'azione consigliata tra scaduti, review, follow-up e gap di copertura.</p>
            </div>
          </BCardHeader>
          <BCardBody class="pt-3">
            <div v-if="portfolioHotspots.length === 0" class="text-center py-5 text-muted">
              Nessuna azienda esposta oltre soglia nel tenant corrente.
            </div>
            <div v-else class="vstack gap-3">
              <div v-for="item in portfolioHotspots" :key="item.company_id" class="border rounded p-3">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                  <div>
                    <div class="fw-semibold">{{ item.company_name }}</div>
                    <div class="text-muted fs-13">{{ item.industry || "Settore non definito" }}</div>
                  </div>
                  <div class="text-end">
                    <span class="badge d-block mb-1" :class="decisionBadges[item.decision.tone] || 'bg-light text-body'">
                      {{ item.decision.label }}
                    </span>
                    <span class="badge bg-light text-body">Copertura {{ item.coverage_rate }}%</span>
                  </div>
                </div>
                <div class="text-muted fs-13 mb-3">
                  {{ item.uncovered_risks }} rischi scoperti | {{ item.missing_expected_measures }} gap | {{ item.urgent_items }} urgenze | {{ item.suggested_core_risks }} rischi core
                </div>
                <div v-if="item.bridge_summary" class="text-muted fs-13 mb-3">{{ item.bridge_summary }}</div>
                <div class="text-muted fs-13 mb-3">{{ item.decision.helper }}</div>
                <div class="hstack gap-2 flex-wrap">
                  <Link :href="item.decision.route" class="btn btn-soft-primary btn-sm">{{ item.decision.label }}</Link>
                  <Link :href="item.review_route" class="btn btn-soft-secondary btn-sm">Riallinea review</Link>
                  <Link :href="item.follow_up_route" class="btn btn-soft-info btn-sm">Apri follow-up</Link>
                </div>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xl="3">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0 pb-0">
            <div>
              <h4 class="card-title mb-1">Categorie sotto pressione</h4>
              <p class="text-muted mb-0">Famiglie di rischio che oggi concentrano scoperture, priorita' alte o pressione diffusa nel tenant.</p>
            </div>
          </BCardHeader>
          <BCardBody class="pt-3">
            <div v-if="pressureCategories.length === 0" class="text-center py-5 text-muted">
              Nessuna categoria critica disponibile.
            </div>
            <div v-else class="vstack gap-3">
              <div v-for="item in pressureCategories" :key="item.category" class="border rounded p-3">
                <div class="fw-semibold">{{ item.category }}</div>
                <div class="text-muted fs-13">{{ item.uncovered_risks }} scoperti | {{ item.high_priority_risks }} alta priorita'</div>
                <div class="text-muted fs-13">{{ item.active_risks }} attivi su {{ item.companies_count }} aziende</div>
                <div class="text-muted fs-13 mt-2">{{ item.dominant_signal }}</div>
                <div class="text-muted fs-13">{{ item.recommended_focus }}</div>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4 mb-4">
      <BCol v-for="lane in props.workspaceLanes" :key="lane.title" xl="4" md="6">
        <BCard no-body class="h-100">
          <BCardBody>
            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
              <div>
                <p class="text-uppercase fw-medium text-muted mb-2">{{ lane.title }}</p>
                <h4 class="mb-1">{{ lane.count }}</h4>
                <p class="text-muted mb-0 fs-13">{{ lane.helper }}</p>
              </div>
              <div class="avatar-sm">
                <span :class="`avatar-title bg-${lane.tone}-subtle text-${lane.tone} rounded fs-3`">
                  <i :class="lane.icon"></i>
                </span>
              </div>
            </div>
            <Link :href="lane.href" class="btn btn-soft-primary btn-sm">
              Apri corsia operativa
            </Link>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4 mb-4">
      <BCol v-for="item in primaryStats" :key="item.title" xl="4" md="6">
        <BCard no-body class="card-animate card-height-100">
          <BCardBody>
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <p class="text-uppercase fw-medium text-muted text-truncate mb-2">{{ item.title }}</p>
                <h4 class="fs-22 fw-semibold ff-secondary mb-1">{{ item.value }}</h4>
                <p class="text-muted mb-0 fs-13">{{ item.helper }}</p>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span :class="`avatar-title rounded-2 bg-${item.tone}-subtle text-${item.tone} fs-3`">
                  <i :class="item.icon"></i>
                </span>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xl="4" md="6">
        <BCard no-body class="card-height-100">
          <BCardHeader class="border-0 pb-0">
            <h4 class="card-title mb-1">Tenuta del workspace</h4>
            <p class="text-muted mb-0">Metriche di contesto utili a leggere il carico operativo del tenant.</p>
          </BCardHeader>
          <BCardBody class="pt-3">
            <div class="vstack gap-3">
              <div v-for="item in secondaryStats" :key="item.label" class="d-flex align-items-center justify-content-between">
                <span class="text-muted">{{ item.label }}</span>
                <span class="fw-semibold">{{ item.value }}</span>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4 mb-4">
      <BCol xl="7">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0 pb-0">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
              <div>
                <h4 class="card-title mb-1">Scadenze prioritarie</h4>
                <p class="text-muted mb-0">Misure in scadenza entro 30 giorni e gia' agganciate al rischio operativo.</p>
              </div>
              <Link :href="route('measure-registries.index', { scope: 'attention', origin: 'dashboard', focus: activeFocus })" class="btn btn-soft-primary btn-sm">
                Vedi tutti i registri
              </Link>
            </div>
          </BCardHeader>
          <BCardBody class="pt-3">
            <div v-if="upcomingDeadlines.length === 0" class="text-center py-5 text-muted">
              Nessuna scadenza imminente nel tenant corrente.
            </div>
            <div v-else class="table-responsive">
              <table class="table align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Misura</th>
                    <th>Contesto</th>
                    <th>Registro</th>
                    <th>Stato</th>
                    <th>Scadenza</th>
                    <th class="text-end">Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in upcomingDeadlines" :key="item.id">
                    <td>
                      <div class="fw-semibold">{{ item.title }}</div>
                      <div class="text-muted fs-13">{{ item.risk_name || "Rischio non disponibile" }}</div>
                      <div v-if="item.expected_binding?.label" class="text-muted fs-13">
                        {{ item.expected_binding.label }}
                      </div>
                    </td>
                    <td>{{ item.context }}</td>
                    <td>{{ item.family }}</td>
                    <td>
                      <span class="badge" :class="statusBadges[item.status] || 'bg-light text-body'">
                        {{ item.status }}
                      </span>
                    </td>
                    <td>
                      <Link :href="item.route" class="text-reset text-decoration-underline">
                        {{ item.due_date }}
                      </Link>
                      <div v-if="item.next_step?.helper" class="text-muted fs-13 mt-1">{{ item.next_step.helper }}</div>
                    </td>
                    <td class="text-end">
                      <div class="hstack gap-2 justify-content-end flex-wrap">
                        <Link v-if="item.next_step?.route" :href="item.next_step.route" class="btn btn-soft-primary btn-sm">{{ item.next_step.label }}</Link>
                        <Link v-if="item.measures_route" :href="item.measures_route" class="btn btn-soft-secondary btn-sm">Misure</Link>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0 pb-0">
            <h4 class="card-title mb-1">Coda criticita'</h4>
            <p class="text-muted mb-0">Rischi scoperti e misure scadute che richiedono priorita' operativa.</p>
          </BCardHeader>
          <BCardBody class="pt-3">
            <div v-if="criticalQueue.length === 0" class="text-center py-5 text-muted">
              Nessuna criticita' aperta nel tenant corrente.
            </div>
            <div v-else class="vstack gap-3">
              <Link
                v-for="item in criticalQueue"
                :key="`${item.kind}-${item.title}-${item.context}`"
                :href="item.route"
                class="border rounded p-3 text-reset text-decoration-none"
              >
                <div class="d-flex align-items-start justify-content-between gap-3">
                  <div>
                    <h6 class="mb-1">{{ item.title }}</h6>
                    <div class="text-muted fs-13 mb-1">{{ item.context }}</div>
                    <div class="text-muted fs-13">{{ item.detail }}</div>
                  </div>
                  <div class="text-end">
                    <span class="badge" :class="criticalBadges[item.tone] || 'bg-light text-body'">
                      {{ item.meta }}
                    </span>
                  </div>
                </div>
              </Link>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0 pb-0">
            <h4 class="card-title mb-1">Timeline operativa recente</h4>
            <p class="text-muted mb-0">Sequenza cronologica minima di review, follow-up, esiti e misure nel tenant corrente.</p>
          </BCardHeader>
          <BCardBody class="pt-3">
            <div v-if="recentTimeline.length === 0" class="text-center py-5 text-muted">
              Nessun evento operativo recente nel tenant corrente.
            </div>
            <div v-else class="vstack gap-3">
              <Link
                v-for="event in recentTimeline"
                :key="`${event.type}-${event.title}-${event.occurred_at}-${event.context}`"
                :href="event.route"
                class="border rounded p-3 text-reset text-decoration-none"
              >
                <div class="d-flex align-items-start justify-content-between gap-3">
                  <div>
                    <div class="fw-semibold">{{ event.title }}</div>
                    <div class="text-muted fs-13">{{ event.context }}<template v-if="event.company_name"> | {{ event.company_name }}</template></div>
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
              </Link>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4 mb-4">
      <BCol xl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0 pb-0">
            <h4 class="card-title mb-1">Agenda consulenziale</h4>
            <p class="text-muted mb-0">Coda minima del lavoro da presidiare, derivata da review, follow-up e misure aperte.</p>
          </BCardHeader>
          <BCardBody class="pt-3">
            <div v-if="agendaQueue.length === 0" class="text-center py-5 text-muted">
              Nessuna attivita' in agenda nel tenant corrente.
            </div>
            <div v-else class="list-group list-group-flush">
              <Link
                v-for="item in agendaQueue"
                :key="`${item.kind}-${item.title}-${item.context}-${item.due_date || 'nd'}`"
                :href="item.route"
                class="list-group-item list-group-item-action px-0"
              >
                <div class="d-flex align-items-start justify-content-between gap-3">
                  <div>
                    <div class="fw-semibold">{{ item.title }}</div>
                    <div class="text-muted fs-13">{{ item.context }}</div>
                    <div class="text-muted fs-13">{{ item.detail }}</div>
                    <div v-if="item.owner_name" class="text-muted fs-13">Referente: {{ item.owner_name }}</div>
                  </div>
                  <div class="text-end">
                    <span class="badge bg-light text-body d-block mb-1">{{ item.status_label }}</span>
                    <span class="text-muted fs-13">{{ item.due_date || "Non definita" }}</span>
                  </div>
                </div>
              </Link>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0 pb-0">
            <h4 class="card-title mb-1">Revisioni rischio in agenda</h4>
            <p class="text-muted mb-0">Rischi con revisione consulente pianificata o gia' scaduta.</p>
          </BCardHeader>
          <BCardBody class="pt-3">
            <div v-if="reviewQueue.length === 0" class="text-center py-5 text-muted">
              Nessuna revisione pianificata.
            </div>
            <div v-else class="list-group list-group-flush">
              <Link
                v-for="item in reviewQueue"
                :key="item.id"
                :href="item.route"
                class="list-group-item list-group-item-action px-0"
              >
                <div class="d-flex align-items-center justify-content-between gap-3">
                  <div>
                    <div class="fw-semibold">{{ item.risk_name }}</div>
                    <div class="text-muted fs-13">{{ item.context }}</div>
                    <div v-if="item.next_step?.helper" class="text-muted fs-13">{{ item.next_step.helper }}</div>
                  </div>
                  <div class="text-end">
                    <span class="badge d-block mb-1" :class="reviewBadges[item.status] || 'bg-light text-body'">
                      {{ item.status }}
                    </span>
                    <span class="text-muted fs-13">{{ item.due_date }}</span>
                  </div>
                </div>
                <div class="mt-2">
                  <span class="btn btn-soft-primary btn-sm">{{ item.next_step?.label || "Apri review" }}</span>
                </div>
              </Link>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0 pb-0">
            <h4 class="card-title mb-1">Follow-up operativi</h4>
            <p class="text-muted mb-0">Criticita' in presa in carico con referente, stato e prossima verifica operativa.</p>
          </BCardHeader>
          <BCardBody class="pt-3">
            <div v-if="followUpQueue.length === 0" class="text-center py-5 text-muted">
              Nessun follow-up operativo aperto.
            </div>
            <div v-else class="table-responsive">
              <table class="table align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Rischio</th>
                    <th>Contesto</th>
                    <th>Referente</th>
                    <th>Stato</th>
                    <th>Scadenza</th>
                    <th class="text-end">Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in followUpQueue" :key="item.id">
                    <td>
                      <Link :href="item.route" class="fw-semibold text-reset text-decoration-underline">
                        {{ item.risk_name }}
                      </Link>
                      <div v-if="item.notes" class="text-muted fs-13">{{ item.notes }}</div>
                      <div v-if="item.next_measure_title" class="text-muted fs-13">
                        Misura aperta: {{ item.next_measure_title }}
                        <template v-if="item.next_measure_due_date"> | {{ item.next_measure_due_date }}</template>
                      </div>
                    </td>
                    <td>{{ item.context }}</td>
                    <td>
                      <div>{{ item.owner_name || "Da assegnare" }}</div>
                      <div class="text-muted fs-13">{{ item.open_measure_count }} misure aperte</div>
                    </td>
                    <td>
                      <span class="badge" :class="followUpBadges[item.status] || 'bg-light text-body'">
                        {{ followUpLabels[item.status] || item.status }}
                      </span>
                    </td>
                    <td>{{ item.due_date || "Non definita" }}</td>
                    <td class="text-end">
                      <div class="hstack gap-2 justify-content-end flex-wrap">
                        <Link :href="item.profile_route" class="btn btn-soft-secondary btn-sm">Profilo</Link>
                        <Link :href="item.next_step?.route || item.registry_route" class="btn btn-soft-primary btn-sm">
                          {{ item.next_step?.label || "Apri registri" }}
                        </Link>
                      </div>
                      <div v-if="item.next_step?.helper" class="text-muted fs-12 mt-2 text-end">
                        {{ item.next_step.helper }}
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

    <BRow class="g-4 mb-4">
      <BCol xl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0 pb-0">
            <h4 class="card-title mb-1">Agenda per referente</h4>
            <p class="text-muted mb-0">Distribuzione essenziale del lavoro operativo per owner o referenti da assegnare.</p>
          </BCardHeader>
          <BCardBody class="pt-3">
            <div v-if="ownerAgenda.length === 0" class="text-center py-5 text-muted">
              Nessun referente presente in agenda.
            </div>
            <div v-else class="vstack gap-3">
              <div v-for="item in ownerAgenda" :key="item.owner_name" class="border rounded p-3">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                  <div>
                    <div class="fw-semibold">{{ item.owner_name }}</div>
                    <div class="text-muted fs-13">{{ item.top_item || "Nessun item prioritario" }}</div>
                  </div>
                  <div class="text-end">
                    <div class="fw-semibold">{{ item.items_count }} attivita'</div>
                    <div class="text-muted fs-13">{{ item.overdue_count }} urgenti</div>
                  </div>
                </div>
                <Link v-if="item.workspace_route" :href="item.workspace_route" class="btn btn-soft-primary btn-sm">
                  Apri registri referente
                </Link>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0 pb-0">
            <h4 class="card-title mb-1">Agenda per azienda</h4>
            <p class="text-muted mb-0">Contesti aziendali dove il carico operativo richiede piu' presidio nel breve.</p>
          </BCardHeader>
          <BCardBody class="pt-3">
            <div v-if="companyAgenda.length === 0" class="text-center py-5 text-muted">
              Nessuna azienda presente in agenda.
            </div>
            <div v-else class="vstack gap-3">
              <Link
                v-for="item in companyAgenda"
                :key="item.company_id"
                :href="item.next_step?.route || item.workspace_route || item.route"
                class="border rounded p-3 text-reset text-decoration-none"
              >
                <div class="d-flex align-items-start justify-content-between gap-3">
                  <div>
                    <div class="fw-semibold">{{ item.company_name }}</div>
                    <div class="text-muted fs-13">{{ item.top_item || "Nessun item prioritario" }}</div>
                    <div v-if="item.next_step?.helper" class="text-muted fs-13">{{ item.next_step.helper }}</div>
                  </div>
                  <div class="text-end">
                    <div class="fw-semibold">{{ item.items_count }} attivita'</div>
                    <div class="text-muted fs-13">{{ item.overdue_count }} urgenti</div>
                  </div>
                </div>
                <div class="mt-3">
                  <span class="btn btn-soft-primary btn-sm">{{ item.next_step?.label || "Apri registri azienda" }}</span>
                </div>
              </Link>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0 pb-0">
            <h4 class="card-title mb-1">Misure da verificare</h4>
            <p class="text-muted mb-0">Presidi aperti che richiedono verifica operativa o chiusura nel breve periodo.</p>
          </BCardHeader>
          <BCardBody class="pt-3">
            <div v-if="attentionMeasures.length === 0" class="text-center py-5 text-muted">
              Nessuna misura aperta da verificare.
            </div>
            <div v-else class="list-group list-group-flush">
              <Link
                v-for="item in attentionMeasures"
                :key="item.id"
                :href="item.next_step?.route || item.route"
                class="list-group-item list-group-item-action px-0"
              >
                <div class="d-flex align-items-center justify-content-between gap-3">
                  <div>
                    <div class="fw-semibold">{{ item.title }}</div>
                    <div class="text-muted fs-13">{{ item.context }}</div>
                    <div v-if="item.expected_binding?.label" class="text-muted fs-13">{{ item.expected_binding.label }}</div>
                    <div v-if="item.next_step?.helper" class="text-muted fs-13">{{ item.next_step.helper }}</div>
                  </div>
                  <div class="text-end">
                    <span class="badge d-block mb-1" :class="statusBadges[item.status] || 'bg-light text-body'">
                      {{ item.status }}
                    </span>
                    <span class="text-muted fs-13">{{ item.due_date }}</span>
                  </div>
                </div>
                <div class="mt-2">
                  <span class="btn btn-soft-primary btn-sm">{{ item.next_step?.label || "Apri contesto" }}</span>
                </div>
              </Link>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0 pb-0">
            <h4 class="card-title mb-1">Aziende da presidiare</h4>
            <p class="text-muted mb-0">Vista sintetica per capire dove concentrare il lavoro operativo del consulente.</p>
          </BCardHeader>
          <BCardBody class="pt-3">
            <div v-if="companySnapshots.length === 0" class="text-center py-5 text-muted">
              Nessuna azienda disponibile nel tenant corrente.
            </div>
            <div v-else class="table-responsive">
              <table class="table align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Azienda</th>
                    <th>Contesto</th>
                    <th>Rischi</th>
                    <th>Presidi</th>
                    <th>Prossima scadenza</th>
                    <th class="text-end">Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="company in companySnapshots" :key="company.id">
                    <td>
                      <Link :href="company.route" class="fw-semibold text-reset text-decoration-underline">
                        {{ company.name }}
                      </Link>
                      <div class="text-muted fs-13">{{ company.industry || "Settore non definito" }}</div>
                    </td>
                    <td>
                      <div>{{ company.workers_count }} lavoratori</div>
                      <div class="text-muted fs-13">{{ company.sites_count }} sedi | {{ company.city || "Sede non definita" }}</div>
                    </td>
                    <td>
                      <div>{{ company.active_risks }} attivi</div>
                      <div class="text-muted fs-13">
                        {{ company.uncovered_risks }} scoperti | {{ company.follow_ups_open }} follow-up
                      </div>
                      <div class="text-muted fs-13">
                        {{ company.suggested_core_risks }} rischi core | copertura {{ company.coverage_rate }}%
                      </div>
                    </td>
                    <td>
                      <div>{{ company.measures_attention }} in attenzione</div>
                      <div class="text-muted fs-13">{{ company.overdue_measures }} scadute</div>
                      <div class="text-muted fs-13">
                        {{ company.expected_core_measures }} presidi attesi | {{ company.missing_expected_measures }} gap
                      </div>
                      <div v-if="company.bridge_summary" class="text-muted fs-13">{{ company.bridge_summary }}</div>
                    </td>
                    <td>
                      <Link :href="company.workspace_route || company.risk_profile_route" class="text-reset text-decoration-underline">
                        {{ company.next_deadline || "Nessuna" }}
                      </Link>
                      <div class="text-muted fs-13">{{ company.next_deadline_label || "Nessun presidio in scadenza" }}</div>
                      <div class="text-muted fs-13">{{ company.decision.helper }}</div>
                    </td>
                    <td class="text-end">
                      <div class="hstack gap-2 justify-content-end flex-wrap">
                        <Link :href="company.decision.route" class="btn btn-soft-primary btn-sm">{{ company.decision.label }}</Link>
                        <Link :href="company.review_route" class="btn btn-soft-secondary btn-sm">Riallinea review</Link>
                        <Link :href="company.follow_up_route" class="btn btn-soft-info btn-sm">Follow-up</Link>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0 pb-0">
            <h4 class="card-title mb-1">Esiti operativi recenti</h4>
            <p class="text-muted mb-0">Chiusure di follow-up gia' registrate, utili per leggere cosa e' stato effettivamente presidiato.</p>
          </BCardHeader>
          <BCardBody class="pt-3">
            <div v-if="recentOutcomes.length === 0" class="text-center py-5 text-muted">
              Nessun esito operativo registrato nel tenant corrente.
            </div>
            <div v-else class="vstack gap-3">
              <Link
                v-for="item in recentOutcomes"
                :key="item.id"
                :href="item.route"
                class="border rounded p-3 text-reset text-decoration-none"
              >
                <div class="d-flex align-items-start justify-content-between gap-3">
                  <div>
                    <div class="fw-semibold">{{ item.risk_name }}</div>
                    <div class="text-muted fs-13">{{ item.context }}</div>
                    <div v-if="item.outcome_notes" class="text-muted fs-13">{{ item.outcome_notes }}</div>
                    <div v-if="item.owner_name" class="text-muted fs-13">Referente: {{ item.owner_name }}</div>
                  </div>
                  <div class="text-end">
                    <span class="badge d-block mb-1" :class="outcomeBadges[item.outcome_status] || 'bg-light text-body'">
                      {{ outcomeLabels[item.outcome_status] || item.outcome_status }}
                    </span>
                    <span class="text-muted fs-13">{{ item.recorded_at || "Non disponibile" }}</span>
                  </div>
                </div>
              </Link>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>
  </Layout>
</template>
