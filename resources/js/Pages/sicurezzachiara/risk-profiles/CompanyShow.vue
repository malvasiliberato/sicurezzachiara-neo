<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

const props = defineProps({
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
  coreStarterPack: {
    type: Object,
    required: true,
  },
  workspaceBridge: {
    type: Object,
    required: true,
  },
  manualRiskOptions: {
    type: Array,
    required: true,
  },
  formOptions: {
    type: Object,
    required: true,
  },
});

const priorityBadge = {
  high: "bg-danger-subtle text-danger",
  medium: "bg-warning-subtle text-warning",
  low: "bg-success-subtle text-success",
};

const relevanceBadge = {
  primary: "bg-primary-subtle text-primary",
  secondary: "bg-info-subtle text-info",
};

const manualForm = useForm({
  risk_catalog_item_id: "",
  final_priority: "",
  consultant_notes: "",
  review_due_at: "",
});

const submitManualRisk = () => {
  manualForm.post(route("companies.risk-profile.manual.store", props.company.id), {
    preserveScroll: true,
    onSuccess: () => manualForm.reset(),
  });
};

const decisionLabels = {
  confirmed: "Confermato",
  customized: "Personalizzato",
  excluded: "Escluso",
  manual_addition: "Aggiunta manuale",
};

const starterPriorityBadge = {
  high: "bg-danger-subtle text-danger",
  medium: "bg-warning-subtle text-warning",
  low: "bg-success-subtle text-success",
};

const focusWorkQueue = props.workspaceBridge.workQueue ?? [];
const operationalQueue = props.workspaceBridge.operationalQueue ?? [];

const queueToneBadgeClasses = {
  danger: "bg-danger-subtle text-danger",
  warning: "bg-warning-subtle text-warning",
  primary: "bg-primary-subtle text-primary",
  info: "bg-info-subtle text-info",
  secondary: "bg-light text-body",
};

const queueToneButtonClasses = {
  danger: "btn-soft-danger",
  warning: "btn-soft-warning",
  primary: "btn-soft-primary",
  info: "btn-soft-info",
  secondary: "btn-soft-secondary",
};
</script>

<template>
  <Layout>
    <Head :title="`Profilo rischio - ${company.name}`" />

    <PageHeader title="Profilo rischio azienda" pageTitle="SicurezzaChiara" />

    <BRow class="g-4 mb-4">
      <BCol xxl="8">
        <BCard no-body class="overflow-hidden">
          <BCardBody class="p-4">
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
              <div>
                <span class="badge bg-danger-subtle text-danger text-uppercase mb-3">Profilo rischio operativo</span>
                <h2 class="mb-1">{{ company.name }}</h2>
                <p class="text-muted mb-3">
                  Lettura operativa del rischio: il sistema deduce, il consulente conferma o corregge, il workspace mostra cosa presidiare.
                </p>
                <div class="hstack gap-2 flex-wrap">
                  <span class="badge bg-light text-body">{{ tenant.name }}</span>
                  <span class="badge bg-soft-info text-info">{{ company.sites.length }} sedi</span>
                  <span class="badge bg-soft-primary text-primary">{{ company.workers.length }} lavoratori</span>
                  <span class="badge bg-soft-success text-success">{{ summary.totalMeasures }} misure</span>
                  <span v-if="summary.reviewsDue > 0" class="badge bg-soft-info text-info">{{ summary.reviewsDue }} revisioni in agenda</span>
                  <span v-if="summary.excludedRisks > 0" class="badge bg-soft-secondary text-secondary">{{ summary.excludedRisks }} esclusi</span>
                </div>
              </div>
              <div class="hstack gap-2 flex-wrap">
                <Link :href="route('companies.show', company.id)" class="btn btn-soft-secondary">
                  Torna azienda
                </Link>
                <Link :href="route('measure-registries.index', { company_id: company.id, scope: 'attention', origin: 'company_risk_profile' })" class="btn btn-soft-info">
                  Registri azienda
                </Link>
                <Link :href="route('companies.dvr.show', company.id)" class="btn btn-soft-warning">
                  DVR iniziale
                </Link>
                <Link :href="route('risk-catalog.index')" class="btn btn-primary">
                  Apri catalogo rischi
                </Link>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xxl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0">
            <h4 class="card-title mb-0">Lettura sintetica</h4>
          </BCardHeader>
          <BCardBody>
            <div class="vstack gap-3">
              <div>
                <span class="text-muted d-block fs-13">Rischi dedotti</span>
                <span class="fw-semibold fs-5">{{ summary.totalRisks }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Rischi esclusi</span>
                <span class="fw-semibold fs-5">{{ summary.excludedRisks }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Rischi da presidiare</span>
                <span class="fw-semibold fs-5">{{ summary.uncoveredRisks }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Priorita' alta</span>
                <span class="fw-semibold fs-5">{{ summary.highPriorityRisks }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Famiglie sorgente presenti</span>
                <span class="fw-semibold fs-5">{{ summary.sourceFamilies }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Revisioni pianificate</span>
                <span class="fw-semibold fs-5">{{ summary.reviewsDue }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Follow-up aperti</span>
                <span class="fw-semibold fs-5">{{ summary.followUpsOpen }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Gap presidi attesi</span>
                <span class="fw-semibold fs-5">{{ summary.missingExpectedMeasures }}</span>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BAlert show variant="info" class="mb-4">
      <div class="fw-semibold mb-1">Come leggere questa pagina</div>
      <div class="text-muted">
        Parti dai rischi in presidio, apri la valutazione consulente quando serve una decisione professionale, poi scendi su misure e registri per vedere cosa e' davvero coperto o ancora in attenzione.
      </div>
    </BAlert>

    <BCard no-body class="mb-4">
      <BCardHeader class="border-0 pb-0">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
          <div>
            <h4 class="card-title mb-1">Passaggio operativo del contesto</h4>
            <p class="text-muted mb-0">Il profilo azienda resta il ponte tra lettura consulenziale e corsia operativa nei registri.</p>
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span v-if="workspaceBridge.originLabel" class="badge bg-soft-info text-info">{{ workspaceBridge.originLabel }}</span>
            <span v-if="workspaceBridge.focusLabel" class="badge bg-light text-body">Focus: {{ workspaceBridge.focusLabel }}</span>
            <span class="badge bg-soft-primary text-primary">Focus consigliato: {{ workspaceBridge.suggestedFocusLabel }}</span>
          </div>
        </div>
      </BCardHeader>
      <BCardBody class="pt-3">
        <BRow class="g-3 mb-3">
          <BCol md="6" xl="3">
            <div class="border rounded p-3 h-100">
              <div class="text-uppercase text-muted fs-12 fw-semibold mb-2">Scaduti</div>
              <div class="fw-semibold fs-4">{{ workspaceBridge.stats.overdueMeasures }}</div>
            </div>
          </BCol>
          <BCol md="6" xl="3">
            <div class="border rounded p-3 h-100">
              <div class="text-uppercase text-muted fs-12 fw-semibold mb-2">Review</div>
              <div class="fw-semibold fs-4">{{ workspaceBridge.stats.reviewsDue }}</div>
            </div>
          </BCol>
          <BCol md="6" xl="3">
            <div class="border rounded p-3 h-100">
              <div class="text-uppercase text-muted fs-12 fw-semibold mb-2">Follow-up</div>
              <div class="fw-semibold fs-4">{{ workspaceBridge.stats.followUpsOpen }}</div>
            </div>
          </BCol>
          <BCol md="6" xl="3">
            <div class="border rounded p-3 h-100">
              <div class="text-uppercase text-muted fs-12 fw-semibold mb-2">Gap attesi</div>
              <div class="fw-semibold fs-4">{{ workspaceBridge.stats.missingExpectedMeasures }}</div>
            </div>
          </BCol>
        </BRow>
        <div class="border rounded p-3 mb-3">
          <div class="fw-semibold mb-1">{{ workspaceBridge.suggestedAction.label }}</div>
          <div class="text-muted fs-13">{{ workspaceBridge.suggestedAction.helper }}</div>
        </div>
        <div v-if="workspaceBridge.originRisk" class="border rounded p-3 mb-3">
          <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
              <div class="text-uppercase text-muted fs-12 fw-semibold mb-2">Rischio di rientro</div>
              <div class="fw-semibold mb-1">{{ workspaceBridge.originRisk.riskName }}</div>
              <div class="text-muted fs-13">{{ workspaceBridge.originRisk.helper }}</div>
            </div>
            <div class="hstack gap-2 flex-wrap">
              <Link :href="workspaceBridge.originRisk.reviewRoute" class="btn btn-soft-primary btn-sm">
                Torna alla review
              </Link>
              <Link :href="workspaceBridge.originRisk.measuresRoute" class="btn btn-soft-danger btn-sm">
                Torna alle misure
              </Link>
              <Link :href="workspaceBridge.originRisk.registryRoute" class="btn btn-soft-info btn-sm">
                Riapri registro rischio
              </Link>
            </div>
          </div>
        </div>
        <div v-if="focusWorkQueue.length" class="rounded-3 bg-light-subtle border mb-3 overflow-hidden">
          <div class="px-3 py-2 border-bottom text-uppercase text-muted fw-semibold fs-12">
            Coda di lavoro minima
          </div>
          <div
            v-for="(item, index) in focusWorkQueue"
            :key="item.key"
            class="d-flex align-items-start justify-content-between gap-3 px-3 py-3"
            :class="{ 'border-top': index > 0 }"
          >
            <div class="flex-grow-1">
              <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <span class="fw-semibold">{{ item.label }}</span>
                <span class="badge bg-light text-body">{{ item.count }}</span>
                <span
                  v-if="item.laneLabel"
                  class="badge"
                  :class="queueToneBadgeClasses[item.tone] || 'bg-light text-body'"
                >
                  {{ item.laneLabel }}
                </span>
              </div>
              <div class="text-muted fs-13">{{ item.helper }}</div>
            </div>
            <Link
              :href="item.actionRoute"
              class="btn btn-sm"
              :class="queueToneButtonClasses[item.tone] || 'btn-soft-primary'"
            >
              {{ item.actionLabel }}
            </Link>
          </div>
        </div>
        <div class="hstack gap-2 flex-wrap">
          <Link :href="workspaceBridge.actions.registryRoute" class="btn btn-primary">
            {{ workspaceBridge.suggestedAction.label }}
          </Link>
          <Link :href="workspaceBridge.actions.allMeasuresRoute" class="btn btn-soft-info">
            Apri tutti i registri azienda
          </Link>
          <Link :href="workspaceBridge.actions.companyRoute" class="btn btn-soft-secondary">
            Dettaglio azienda
          </Link>
          <Link v-if="workspaceBridge.actions.dashboardRoute" :href="workspaceBridge.actions.dashboardRoute" class="btn btn-soft-warning">
            Torna alla dashboard
          </Link>
        </div>
        <div v-if="operationalQueue.length" class="border-top pt-3 mt-3">
          <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-2">
            <div>
              <h6 class="mb-1">Corsie operative del profilo</h6>
              <p class="text-muted mb-0 fs-13">
                Il profilo rischio usa la stessa grammatica del cockpit e del registro: prima scegli la corsia, poi chiudi il lavoro.
              </p>
            </div>
          </div>
          <div class="d-flex align-items-stretch gap-2 flex-wrap">
            <Link
              v-for="item in operationalQueue"
              :key="item.key"
              :href="item.actionRoute"
              class="btn btn-sm text-start"
              :class="queueToneButtonClasses[item.tone] || 'btn-soft-secondary'"
            >
              <span class="fw-semibold d-block">{{ item.label }} <span class="ms-1">({{ item.count }})</span></span>
              <span class="d-block fs-12" v-if="item.laneLabel">{{ item.laneLabel }}</span>
              <span class="fs-12">{{ item.helper }}</span>
            </Link>
          </div>
        </div>
      </BCardBody>
    </BCard>

    <BCard no-body class="mb-4">
      <BCardHeader class="border-0 pb-0">
        <h4 class="card-title mb-1">Motore v1 del profilo</h4>
        <p class="text-muted mb-0">Sorgenti attive, rischi dedotti, intervento del consulente e copertura vengono letti come un unico flusso.</p>
      </BCardHeader>
      <BCardBody class="pt-3">
        <div class="d-flex align-items-stretch gap-3 flex-wrap mb-3">
          <div v-for="step in engine.flow" :key="step.key" class="border rounded p-3 flex-grow-1" style="min-width: 220px;">
            <div class="text-uppercase text-muted fs-12 fw-semibold mb-2">{{ step.label }}</div>
            <div class="fw-semibold fs-5 mb-1">{{ step.value }}</div>
            <div class="text-muted fs-13">{{ step.helper }}</div>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span v-for="family in engine.sourceFamilies" :key="family.family" class="badge bg-light text-body">
            {{ family.label }}: {{ family.count }}
          </span>
        </div>
      </BCardBody>
    </BCard>

    <BCard no-body class="mb-4">
      <BCardHeader class="border-0 pb-0">
        <h4 class="card-title mb-1">Starter pack core letto nel profilo</h4>
        <p class="text-muted mb-0">Il profilo espone anche quali segnali il core standard sta suggerendo dal contesto aziendale attuale, prima della decisione professionale e dei presidi effettivi.</p>
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
          <span v-for="risk in coreStarterPack.suggestedRisks.slice(0, 10)" :key="risk.id" class="badge bg-light text-body">
            {{ risk.name }}
            <span class="ms-1 badge" :class="starterPriorityBadge[risk.default_priority] || 'bg-light text-body'">
              {{ risk.default_priority === "high" ? "Alta" : risk.default_priority === "medium" ? "Media" : "Bassa" }}
            </span>
          </span>
        </div>
        <div v-else class="text-muted">Nessun segnale core leggibile dal contesto aziendale corrente.</div>
      </BCardBody>
    </BCard>

    <BRow class="g-4 mb-4">
      <BCol md="6" xl="3">
        <BCard no-body class="card-animate h-100">
          <BCardBody>
            <p class="text-uppercase fw-medium text-muted mb-3">Rischi dedotti</p>
            <div class="d-flex align-items-center justify-content-between">
              <h3 class="mb-0">{{ summary.totalRisks }}</h3>
              <div class="avatar-sm">
                <span class="avatar-title bg-primary-subtle text-primary rounded fs-3">
                  <i class="ri-alert-line"></i>
                </span>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol md="6" xl="3">
        <BCard no-body class="card-animate h-100">
          <BCardBody>
            <p class="text-uppercase fw-medium text-muted mb-3">Da presidiare</p>
            <div class="d-flex align-items-center justify-content-between">
              <h3 class="mb-0">{{ summary.uncoveredRisks }}</h3>
              <div class="avatar-sm">
                <span class="avatar-title bg-warning-subtle text-warning rounded fs-3">
                  <i class="ri-shield-flash-line"></i>
                </span>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol md="6" xl="3">
        <BCard no-body class="card-animate h-100">
          <BCardBody>
            <p class="text-uppercase fw-medium text-muted mb-3">Priorita' alta</p>
            <div class="d-flex align-items-center justify-content-between">
              <h3 class="mb-0">{{ summary.highPriorityRisks }}</h3>
              <div class="avatar-sm">
                <span class="avatar-title bg-danger-subtle text-danger rounded fs-3">
                  <i class="ri-error-warning-line"></i>
                </span>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol md="6" xl="3">
        <BCard no-body class="card-animate h-100">
          <BCardBody>
            <p class="text-uppercase fw-medium text-muted mb-3">Revisioni in agenda</p>
            <div class="d-flex align-items-center justify-content-between">
              <h3 class="mb-0">{{ summary.reviewsDue }}</h3>
              <div class="avatar-sm">
                <span class="avatar-title bg-info-subtle text-info rounded fs-3">
                  <i class="ri-calendar-check-line"></i>
                </span>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BCard no-body>
      <BCardHeader class="border-0">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
          <div>
            <h4 class="card-title mb-1">Rischi attualmente dedotti</h4>
                <p class="text-muted mb-0">
              Il profilo mostra il livello dedotto dal contesto operativo attuale, la decisione professionale applicata e il primo collegamento verso le misure di presidio.
            </p>
          </div>
          <span class="badge bg-soft-danger text-danger">{{ summary.totalRisks }} elementi</span>
        </div>
      </BCardHeader>
      <BCardBody class="pt-0">
        <div v-if="company.risk_profile_items.length === 0" class="text-center py-5">
          <div class="avatar-md mx-auto mb-3">
            <div class="avatar-title bg-light text-info rounded-circle fs-2">
              <i class="ri-radar-line"></i>
            </div>
          </div>
          <h5 class="mb-2">Nessun rischio ancora dedotto</h5>
          <p class="text-muted mb-0">
            Per questa azienda non emergono ancora collegamenti sufficienti tra sorgenti operative e catalogo rischi.
          </p>
        </div>

        <div v-else class="table-responsive">
          <table class="table align-middle table-nowrap mb-0">
            <thead class="table-light">
              <tr>
                <th>Rischio</th>
                <th>Categoria</th>
                <th>Priorita'</th>
                <th>Stato finale</th>
                <th>Sorgenti</th>
                <th>Misure</th>
                <th class="text-end">Valutazione</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="profileItem in company.risk_profile_items" :key="profileItem.id">
                <td>
                  <div class="fw-semibold">{{ profileItem.risk_catalog_item?.name }}</div>
                  <div class="text-muted fs-13">
                    {{ profileItem.risk_catalog_item?.description || "Descrizione non disponibile." }}
                  </div>
                </td>
                <td>{{ profileItem.risk_catalog_item?.category?.name || "Categoria non disponibile" }}</td>
                <td>
                  <span class="badge" :class="priorityBadge[profileItem.effective_priority] || 'bg-light text-body'">
                    {{ profileItem.effective_priority === 'high' ? 'Alta' : profileItem.effective_priority === 'medium' ? 'Media' : 'Bassa' }}
                  </span>
                </td>
                <td>
                  <div class="d-flex flex-column gap-2 align-items-start">
                    <span
                      class="badge"
                      :class="profileItem.operational_status === 'excluded' ? 'bg-secondary-subtle text-secondary' : 'bg-warning-subtle text-warning'"
                    >
                      {{
                        profileItem.operational_status === 'excluded'
                          ? 'Escluso dal profilo finale'
                          : profileItem.status === 'uncovered'
                            ? 'Da presidiare'
                            : 'Coperto'
                      }}
                    </span>
                    <span v-if="profileItem.consultant_decision" class="badge bg-light text-body">
                      {{ decisionLabels[profileItem.consultant_decision] || profileItem.consultant_decision }}
                    </span>
                    <span v-if="profileItem.is_manual" class="badge bg-primary-subtle text-primary">Manuale</span>
                    <span v-if="profileItem.review_due_at" class="badge" :class="profileItem.is_review_due ? 'bg-info-subtle text-info' : 'bg-light text-body'">
                      Revisione {{ profileItem.review_due_at }}
                    </span>
                    <span v-if="profileItem.follow_up_status" class="badge" :class="profileItem.is_follow_up_due ? 'bg-danger-subtle text-danger' : 'bg-soft-primary text-primary'">
                      Follow-up {{ profileItem.follow_up_due_at || profileItem.follow_up_status }}
                    </span>
                  </div>
                </td>
                <td>
                  <div v-if="profileItem.sources.length > 0" class="d-flex flex-wrap gap-2 mb-1">
                    <span
                      v-for="source in profileItem.sources"
                      :key="source.id"
                      class="badge"
                      :class="relevanceBadge[source.relevance] || 'bg-light text-body'"
                    >
                      {{ source.source_label }}
                    </span>
                  </div>
                  <div v-else class="text-muted fs-13 mb-1">Nessuna sorgente attiva: mantenuto dal consulente.</div>
                  <div class="text-muted fs-13">
                    {{ profileItem.is_currently_derived ? `${profileItem.source_count} sorgenti collegate` : "Derivazione automatica non piu' attiva" }}
                  </div>
                </td>
                <td>
                  <div class="d-flex flex-column gap-2 align-items-start">
                    <div v-if="profileItem.measures_preview?.length" class="d-flex flex-wrap gap-2">
                      <span
                        v-for="measure in profileItem.measures_preview"
                        :key="measure.id"
                        class="badge bg-light text-body"
                      >
                        {{ measure.title }}
                      </span>
                    </div>
                    <span v-else class="text-muted fs-13">Nessuna misura collegata</span>
                    <span v-if="profileItem.engine_coverage?.summary?.expected?.expected_count" class="text-muted fs-13">
                      {{ profileItem.engine_coverage.summary.expected.covered_count }} / {{ profileItem.engine_coverage.summary.expected.expected_count }} presidi attesi coperti
                    </span>
                    <Link :href="profileItem.measures_route" class="btn btn-soft-secondary btn-sm">
                      Gestisci misure
                    </Link>
                    <Link :href="route('measure-registries.index', { company_id: company.id, scope: 'attention', origin: 'company_risk_profile' })" class="btn btn-soft-info btn-sm">
                      Apri registri azienda
                    </Link>
                  </div>
                </td>
                <td class="text-end">
                  <div class="d-flex flex-column align-items-end gap-2">
                    <Link :href="profileItem.review_route" class="btn btn-soft-primary btn-sm">
                      Valutazione consulente
                    </Link>
                    <span v-if="profileItem.operational_owner_name" class="text-muted fs-13 text-end">
                      In carico a {{ profileItem.operational_owner_name }}
                    </span>
                    <span v-if="profileItem.follow_up_notes" class="text-muted fs-13 text-end">
                      {{ profileItem.follow_up_notes }}
                    </span>
                    <span v-if="profileItem.last_reviewed_at" class="text-muted fs-13 text-end">
                      Ultima review {{ profileItem.last_reviewed_at }}
                    </span>
                    <span v-if="profileItem.consultant_notes" class="text-muted fs-13 text-end">
                      {{ profileItem.consultant_notes }}
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </BCardBody>
    </BCard>

    <BCard v-if="$page.props.tenantContext?.permissions?.can_manage_data" no-body class="mt-4">
      <BCardHeader class="border-0">
        <div>
          <h4 class="card-title mb-1">Aggiunta manuale rischio</h4>
          <p class="text-muted mb-0">
            Il consulente puo' introdurre nel profilo finale un rischio anche se non emerge dalle sorgenti automatiche attuali.
          </p>
        </div>
      </BCardHeader>
      <BCardBody>
        <form @submit.prevent="submitManualRisk" class="row g-3">
          <div class="col-lg-5">
            <label class="form-label">Rischio</label>
            <select v-model="manualForm.risk_catalog_item_id" class="form-select" :class="{ 'is-invalid': manualForm.errors.risk_catalog_item_id }">
              <option value="">Seleziona rischio</option>
              <option v-for="risk in manualRiskOptions" :key="risk.id" :value="risk.id">
                {{ risk.name }}{{ risk.category_name ? ` - ${risk.category_name}` : "" }}{{ risk.already_present ? " (gia' presente)" : "" }}
              </option>
            </select>
            <div v-if="manualForm.errors.risk_catalog_item_id" class="invalid-feedback d-block">{{ manualForm.errors.risk_catalog_item_id }}</div>
          </div>
          <div class="col-lg-3">
            <label class="form-label">Priorita' finale</label>
            <select v-model="manualForm.final_priority" class="form-select" :class="{ 'is-invalid': manualForm.errors.final_priority }">
              <option value="">Usa priorita' catalogo</option>
              <option v-for="priority in formOptions.priorities" :key="priority.value" :value="priority.value">
                {{ priority.label }}
              </option>
            </select>
            <div v-if="manualForm.errors.final_priority" class="invalid-feedback d-block">{{ manualForm.errors.final_priority }}</div>
          </div>
          <div class="col-lg-4">
            <label class="form-label">Nota consulente</label>
            <input v-model="manualForm.consultant_notes" type="text" class="form-control" :class="{ 'is-invalid': manualForm.errors.consultant_notes }" placeholder="Motivazione sintetica" />
            <div v-if="manualForm.errors.consultant_notes" class="invalid-feedback d-block">{{ manualForm.errors.consultant_notes }}</div>
          </div>
          <div class="col-lg-4">
            <label class="form-label">Prossima revisione</label>
            <input v-model="manualForm.review_due_at" type="date" class="form-control" :class="{ 'is-invalid': manualForm.errors.review_due_at }" />
            <div v-if="manualForm.errors.review_due_at" class="invalid-feedback d-block">{{ manualForm.errors.review_due_at }}</div>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary" :disabled="manualForm.processing">Aggiungi al profilo finale</button>
          </div>
        </form>
      </BCardBody>
    </BCard>
  </Layout>
</template>
