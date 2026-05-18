<script setup>
import { computed, watch } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

const props = defineProps({
  tenant: { type: Object, required: true },
  parentType: { type: String, required: true },
  parent: { type: Object, required: true },
  profileItem: { type: Object, required: true },
  reviews: { type: Array, required: true },
  measures: { type: Array, required: true },
  timeline: { type: Array, required: true },
  engineContext: { type: Object, required: true },
  coreStarterPack: { type: Object, required: true },
  reviewBridge: { type: Object, required: true },
  formOptions: { type: Object, required: true },
  backRoute: { type: String, required: true },
  saveRoute: { type: String, required: true },
  contextRoutes: { type: Object, required: true },
});

const form = useForm({
  operational_status: props.profileItem.operational_status,
  consultant_decision: props.profileItem.consultant_decision || (props.profileItem.is_manual ? "manual_addition" : "confirmed"),
  final_priority: props.profileItem.final_priority || "",
  consultant_notes: props.profileItem.consultant_notes || "",
  review_due_at: props.profileItem.review_due_at || "",
  operational_owner_user_id: props.profileItem.operational_owner_user_id || "",
  follow_up_status: props.profileItem.follow_up_status || "",
  follow_up_notes: props.profileItem.follow_up_notes || "",
  follow_up_due_at: props.profileItem.follow_up_due_at || "",
  follow_up_outcome_status: props.profileItem.follow_up_outcome_status || "",
  follow_up_outcome_notes: props.profileItem.follow_up_outcome_notes || "",
});

const submit = () => {
  form.put(props.saveRoute);
};

watch(
  () => form.follow_up_status,
  (status) => {
    if (status !== "closed") {
      form.follow_up_outcome_status = "";
      form.follow_up_outcome_notes = "";
    }
  }
);

const priorityBadge = {
  high: "bg-danger-subtle text-danger",
  medium: "bg-warning-subtle text-warning",
  low: "bg-success-subtle text-success",
};

const decisionDescriptions = {
  confirmed: "Il rischio resta valido nel profilo finale cosi' come emerge dal sistema.",
  customized: "Il rischio resta valido ma con lettura professionale rafforzata o adattata.",
  excluded: "Il rischio viene escluso dal profilo finale pur restando tracciata la derivazione.",
  manual_addition: "Il rischio e' stato introdotto o mantenuto manualmente dal consulente.",
};

const decisionLabels = {
  confirmed: "Confermato",
  customized: "Personalizzato",
  excluded: "Escluso",
  manual_addition: "Aggiunta manuale",
};

const historyEventLabels = props.formOptions.historyEventLabels || {};

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

const measureStatusLabels = {
  implemented: "Attuata",
  not_implemented: "Non attuata",
  to_verify: "Da verificare",
};

const starterPriorityBadge = {
  high: "bg-danger-subtle text-danger",
  medium: "bg-warning-subtle text-warning",
  low: "bg-success-subtle text-success",
};

const openMeasures = computed(() =>
  props.measures.filter((measure) => ["not_implemented", "to_verify"].includes(measure.status))
);

const reviewHighlights = computed(() => [
  {
    title: "Suggerimento sistema",
    value: props.profileItem.is_currently_derived ? "Derivato dal contesto" : "Mantenuto dal consulente",
    helper: `${props.profileItem.sources.length} sorgenti attive`,
    tone: "info",
    icon: "ri-radar-line",
  },
  {
    title: "Decisione consulente",
    value: decisionLabels[props.profileItem.consultant_decision] || "Da esplicitare",
    helper: props.profileItem.operational_status === "excluded" ? "Rischio escluso dal profilo finale" : "Rischio attivo nel profilo finale",
    tone: "primary",
    icon: "ri-user-settings-line",
  },
  {
    title: "Presidio attuale",
    value: props.profileItem.status === "covered" ? "Coperto" : "Da presidiare",
    helper: `${openMeasures.value.length} misure aperte o da verificare`,
    tone: props.profileItem.status === "covered" ? "success" : "warning",
    icon: "ri-shield-check-line",
  },
  {
    title: "Prossima azione",
    value: props.profileItem.follow_up_due_at || props.profileItem.review_due_at || "Da pianificare",
    helper: props.profileItem.follow_up_status
      ? `Follow-up ${followUpLabels[props.profileItem.follow_up_status] || props.profileItem.follow_up_status}`
      : "Nessun follow-up aperto",
    tone: "secondary",
    icon: "ri-calendar-check-line",
  },
]);

</script>

<template>
  <Layout>
    <Head :title="`Valutazione rischio - ${profileItem.risk_catalog_item?.name}`" />
    <PageHeader title="Valutazione consulente" pageTitle="SicurezzaChiara" />

    <BAlert v-if="$page.props.flash.success" show variant="success" class="mb-4">
      <i class="ri-checkbox-circle-line me-2 align-middle"></i>
      {{ $page.props.flash.success }}
    </BAlert>

    <BRow class="g-4 mb-4">
      <BCol xxl="8">
        <BCard no-body class="overflow-hidden">
          <BCardBody class="p-4">
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
              <div>
                <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">Valutazione professionale</span>
                <h2 class="mb-1">{{ profileItem.risk_catalog_item?.name }}</h2>
                <p class="text-muted mb-3">{{ profileItem.risk_catalog_item?.description || "Descrizione non disponibile." }}</p>
                <div class="hstack gap-2 flex-wrap">
                  <span class="badge bg-light text-body">{{ tenant.name }}</span>
                  <span class="badge bg-soft-secondary text-secondary">{{ parent.name }}</span>
                  <span class="badge" :class="priorityBadge[profileItem.effective_priority] || 'bg-light text-body'">
                    Priorita' {{ profileItem.effective_priority === "high" ? "alta" : profileItem.effective_priority === "medium" ? "media" : "bassa" }}
                  </span>
                  <span v-if="profileItem.is_manual" class="badge bg-primary-subtle text-primary">Rischio manuale</span>
                  <span v-if="!profileItem.is_currently_derived" class="badge bg-secondary-subtle text-secondary">Non piu' dedotto automaticamente</span>
                </div>
              </div>
              <div class="hstack gap-2 flex-wrap">
                <Link :href="backRoute" class="btn btn-soft-secondary">Torna al profilo</Link>
                <Link :href="contextRoutes.measures" class="btn btn-primary">
                  Gestisci misure del rischio
                </Link>
                <Link v-if="contextRoutes.worker" :href="contextRoutes.worker" class="btn btn-soft-primary">
                  Apri lavoratore
                </Link>
                <Link v-if="contextRoutes.company" :href="contextRoutes.company" class="btn btn-soft-info">
                  Apri azienda
                </Link>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xxl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0">
            <h4 class="card-title mb-0">Lettura corrente</h4>
          </BCardHeader>
          <BCardBody>
            <div class="vstack gap-3">
              <div>
                <span class="text-muted d-block fs-13">Categoria</span>
                <span class="fw-medium">{{ profileItem.risk_catalog_item?.category?.name || "Non disponibile" }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Copertura misure</span>
                <span class="fw-medium">{{ profileItem.status === "covered" ? "Coperto" : "Da presidiare" }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Stato operativo finale</span>
                <span class="fw-medium">{{ profileItem.operational_status === "excluded" ? "Escluso" : "Attivo" }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Decisione registrata</span>
                <span class="fw-medium">{{ decisionLabels[profileItem.consultant_decision] || "Non ancora esplicitata" }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Ultima revisione</span>
                <span class="fw-medium">{{ profileItem.reviewed_at || "Non registrata" }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Prossima revisione</span>
                <span class="fw-medium">{{ profileItem.review_due_at || "Non pianificata" }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Referente operativo</span>
                <span class="fw-medium">{{ profileItem.operational_owner_name || "Non assegnato" }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Follow-up</span>
                <span class="fw-medium">{{ followUpLabels[profileItem.follow_up_status] || "Non aperto" }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Esito operativo</span>
                <span class="fw-medium">{{ followUpOutcomeLabels[profileItem.follow_up_outcome_status] || "Non registrato" }}</span>
              </div>
              <div v-if="engineContext.expectedMeasures?.summary?.expected_count">
                <span class="text-muted d-block fs-13">Presidi attesi</span>
                <span class="fw-medium">{{ engineContext.expectedMeasures.summary.covered_count }} / {{ engineContext.expectedMeasures.summary.expected_count }} coperti</span>
                <div v-if="engineContext.expectedMeasures.summary.substituted_count" class="text-muted fs-13">
                  {{ engineContext.expectedMeasures.summary.substituted_count }} coperture tramite equivalenza di famiglia
                </div>
              </div>
              <div class="border-top pt-3 mt-1">
                <span class="text-muted d-block fs-13 mb-2">Azioni contestuali</span>
                <div class="d-flex flex-wrap gap-2">
                  <Link :href="contextRoutes.measures" class="btn btn-soft-primary btn-sm">Misure del rischio</Link>
                  <Link :href="contextRoutes.workspace" class="btn btn-soft-info btn-sm">Registri contestuali</Link>
                  <Link :href="contextRoutes.dashboard" class="btn btn-soft-secondary btn-sm">Focus dashboard</Link>
                </div>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BAlert show variant="info" class="mb-4">
      <div class="fw-semibold mb-1">Grammatica della review</div>
      <div class="text-muted">
        Qui il consulente decide se il rischio resta attivo o escluso, definisce la priorita' finale, collega il lavoro operativo a misure e follow-up e pianifica la prossima revisione.
      </div>
    </BAlert>

    <BCard no-body class="mb-4">
      <BCardBody class="p-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <span class="badge bg-info-subtle text-info text-uppercase mb-2">Bridge operativo del rischio</span>
            <h4 class="mb-1">{{ reviewBridge.decision.label }}</h4>
            <p class="text-muted mb-0">{{ reviewBridge.decision.helper }}</p>
          </div>
              <div class="hstack gap-2 flex-wrap">
                <Link :href="reviewBridge.actions.measuresRoute" class="btn btn-soft-primary btn-sm">Apri misure</Link>
                <Link :href="reviewBridge.actions.workspaceRoute" class="btn btn-soft-info btn-sm">Apri registri</Link>
                <Link v-if="reviewBridge.actions.workerRoute" :href="reviewBridge.actions.workerRoute" class="btn btn-soft-primary btn-sm">Apri lavoratore</Link>
                <Link v-if="reviewBridge.actions.companyRoute" :href="reviewBridge.actions.companyRoute" class="btn btn-soft-secondary btn-sm">Apri azienda</Link>
              </div>
            </div>
        <BRow class="g-3">
          <BCol md="4">
            <div class="border rounded p-3 h-100">
              <div class="text-uppercase text-muted fs-12 fw-semibold mb-2">Misure aperte</div>
              <div class="fw-semibold fs-4">{{ reviewBridge.stats.openMeasures }}</div>
              <div class="text-muted fs-13">Presidi ancora da attuare o verificare.</div>
            </div>
          </BCol>
          <BCol md="4">
            <div class="border rounded p-3 h-100">
              <div class="text-uppercase text-muted fs-12 fw-semibold mb-2">Gap attesi</div>
              <div class="fw-semibold fs-4">{{ reviewBridge.stats.coverageGapCount }}</div>
              <div class="text-muted fs-13">
                {{ reviewBridge.stats.missingExpectedMeasures }} mancanti | {{ reviewBridge.stats.partialExpectedMeasures }} parziali
              </div>
            </div>
          </BCol>
          <BCol md="4">
            <div class="border rounded p-3 h-100">
              <div class="text-uppercase text-muted fs-12 fw-semibold mb-2">Stato follow-up</div>
              <div class="fw-semibold fs-4">{{ reviewBridge.stats.followUpOpen ? "Aperto" : "Non aperto" }}</div>
              <div class="text-muted fs-13">Il rischio resta leggibile tra review, registri e workspace.</div>
            </div>
          </BCol>
        </BRow>
      </BCardBody>
    </BCard>

    <BCard no-body class="mb-4">
      <BCardHeader class="border-0 pb-0">
        <h4 class="card-title mb-1">Catena del motore su questo rischio</h4>
        <p class="text-muted mb-0">La review si inserisce tra suggerimento automatico, rischio finale, presidi collegati e stato di copertura.</p>
      </BCardHeader>
      <BCardBody class="pt-3">
        <div class="d-flex align-items-stretch gap-3 flex-wrap">
          <div v-for="step in engineContext.flow" :key="step.label" class="border rounded p-3 flex-grow-1" style="min-width: 220px;">
            <div class="text-uppercase text-muted fs-12 fw-semibold mb-2">{{ step.label }}</div>
            <div class="fw-semibold fs-5 mb-1">{{ step.value }}</div>
            <div class="text-muted fs-13">{{ step.helper }}</div>
          </div>
        </div>
      </BCardBody>
    </BCard>

    <BCard no-body class="mb-4">
      <BCardHeader class="border-0 pb-0">
        <h4 class="card-title mb-1">Starter pack core su questo rischio</h4>
        <p class="text-muted mb-0">Questa sezione rende esplicito quali segnali core stanno contribuendo alla review corrente e quali presidi attesi si portano dietro.</p>
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
            {{ risk.name }}
            <span class="ms-1 badge" :class="starterPriorityBadge[risk.default_priority] || 'bg-light text-body'">
              {{ risk.default_priority === "high" ? "Alta" : risk.default_priority === "medium" ? "Media" : "Bassa" }}
            </span>
          </span>
        </div>
        <div v-else class="text-muted">La review non ha al momento sorgenti core leggibili nel contesto attivo.</div>
      </BCardBody>
    </BCard>

    <BRow class="g-4 mb-4">
      <BCol v-for="item in reviewHighlights" :key="item.title" lg="3" md="6">
        <BCard no-body class="card-animate h-100">
          <BCardBody>
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <p class="text-uppercase fw-medium text-muted mb-2">{{ item.title }}</p>
                <h5 class="mb-1">{{ item.value }}</h5>
                <p class="text-muted mb-0 fs-13">{{ item.helper }}</p>
              </div>
              <div class="avatar-sm">
                <span :class="`avatar-title bg-${item.tone}-subtle text-${item.tone} rounded fs-3`">
                  <i :class="item.icon"></i>
                </span>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4">
      <BCol xl="7">
        <BCard no-body>
          <BCardHeader class="border-0">
            <h4 class="card-title mb-1">Origine del rischio e presidi attuali</h4>
            <p class="text-muted mb-0">Qui si vede cosa ha suggerito il sistema e con quali presidi il rischio e' oggi collegato.</p>
          </BCardHeader>
          <BCardBody class="pt-0">
            <div class="mb-4">
              <h6 class="text-uppercase text-muted mb-3">Sorgenti attive</h6>
              <div v-if="profileItem.sources.length === 0" class="text-muted">Nessuna sorgente automatica attiva al momento.</div>
              <div v-else class="d-flex flex-wrap gap-2">
                <span v-for="source in profileItem.sources" :key="source.id" class="badge bg-light text-body">
                  {{ source.source_label }} - {{ source.relevance === "primary" ? "primaria" : "secondaria" }}
                </span>
              </div>
            </div>

            <div>
              <div v-if="engineContext.expectedMeasures?.summary?.expected_count" class="mb-4">
                <h6 class="text-uppercase text-muted mb-3">Presidi attesi</h6>
                <div class="table-responsive">
                  <table class="table align-middle table-nowrap mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Presidio</th>
                        <th>Famiglia</th>
                        <th>Stato</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="expectedMeasure in engineContext.expectedMeasures.templates" :key="expectedMeasure.code">
                        <td>
                          <div class="fw-semibold">{{ expectedMeasure.title }}</div>
                          <div class="text-muted fs-13">{{ expectedMeasure.description || "Nessuna descrizione aggiuntiva." }}</div>
                          <div v-if="expectedMeasure.coverage_mode === 'family_substitution'" class="text-info fs-13">
                            Coperto tramite misura equivalente della stessa famiglia
                          </div>
                          <div v-else-if="expectedMeasure.allows_family_substitution" class="text-muted fs-13">
                            Ammette equivalenze della stessa famiglia
                          </div>
                        </td>
                        <td>{{ expectedMeasure.family }}</td>
                        <td>
                          {{
                            expectedMeasure.status === "covered"
                              ? "Coperto"
                              : expectedMeasure.status === "partial"
                                ? "Parziale"
                                : "Mancante"
                          }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <h6 class="text-uppercase text-muted mb-3">Misure collegate</h6>
              <div
                v-if="(form.follow_up_status || profileItem.follow_up_status) && openMeasures.length > 0"
                class="alert alert-primary alert-border-left"
              >
                <h6 class="mb-1">Raccordo operativo attivo</h6>
                <p class="mb-2">
                  Il follow-up di questo rischio e' collegato a {{ openMeasures.length }} misure ancora aperte o da verificare.
                </p>
                <Link
                  :href="route('measure-registries.index', {
                    family: 'follow_up',
                    company_id: parentType === 'company' ? parent.id : parent.company_id,
                    origin: 'risk_review',
                    ...(profileItem.operational_owner_user_id ? { owner_user_id: profileItem.operational_owner_user_id } : {}),
                  })"
                  class="btn btn-sm btn-soft-primary"
                >
                  Apri registri in carico
                </Link>
              </div>
              <div
                v-else-if="(form.follow_up_status || profileItem.follow_up_status) && openMeasures.length === 0"
                class="alert alert-warning alert-border-left"
              >
                <h6 class="mb-1">Follow-up aperto senza misure aperte</h6>
                <p class="mb-0">
                  La criticita' e' in carico operativo, ma non risultano misure ancora aperte collegate a questo rischio.
                </p>
              </div>
              <div v-if="measures.length === 0" class="text-muted">Nessuna misura ancora collegata a questo rischio.</div>
              <div v-else class="table-responsive">
                <table class="table align-middle table-nowrap mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Misura</th>
                      <th>Famiglia</th>
                      <th>Stato</th>
                      <th>Presidio atteso</th>
                      <th>Scadenza</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="measure in measures" :key="measure.id">
                      <td>{{ measure.title }}</td>
                      <td>{{ measure.family }}</td>
                      <td>{{ measureStatusLabels[measure.status] || measure.status }}</td>
                      <td>
                        <div class="text-muted fs-13">
                          {{ measure.expected_binding?.label || (measure.expected_measure_code || "Misura libera") }}
                        </div>
                        <div v-if="measure.expected_binding?.expected_title" class="text-muted fs-13">
                          {{ measure.expected_binding.expected_title }}
                        </div>
                      </td>
                      <td>{{ measure.due_date || "Non definita" }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>

      <BCol xl="5">
        <BCard no-body>
          <BCardHeader class="border-0">
            <h4 class="card-title mb-1">Decisione del consulente</h4>
            <p class="text-muted mb-0">Qui si congela il livello finale operativo del rischio senza rompere la traccia della deduzione automatica.</p>
          </BCardHeader>
          <BCardBody>
            <form @submit.prevent="submit" class="vstack gap-3">
              <div>
                <label class="form-label">Stato operativo finale</label>
                <select v-model="form.operational_status" class="form-select" :class="{ 'is-invalid': form.errors.operational_status }">
                  <option v-for="status in formOptions.operationalStatuses" :key="status.value" :value="status.value">
                    {{ status.label }}
                  </option>
                </select>
                <div v-if="form.errors.operational_status" class="invalid-feedback d-block">{{ form.errors.operational_status }}</div>
              </div>

              <div>
                <label class="form-label">Decisione registrata</label>
                <select v-model="form.consultant_decision" class="form-select" :class="{ 'is-invalid': form.errors.consultant_decision }">
                  <option v-for="decision in formOptions.decisions" :key="decision.value" :value="decision.value">
                    {{ decision.label }}
                  </option>
                </select>
                <div class="form-text">{{ decisionDescriptions[form.consultant_decision] }}</div>
                <div v-if="form.errors.consultant_decision" class="invalid-feedback d-block">{{ form.errors.consultant_decision }}</div>
              </div>

              <div>
                <label class="form-label">Priorita' finale</label>
                <select v-model="form.final_priority" class="form-select" :class="{ 'is-invalid': form.errors.final_priority }">
                  <option value="">Usa priorita' dedotta</option>
                  <option v-for="priority in formOptions.priorities" :key="priority.value" :value="priority.value">
                    {{ priority.label }}
                  </option>
                </select>
                <div v-if="form.errors.final_priority" class="invalid-feedback d-block">{{ form.errors.final_priority }}</div>
              </div>

              <div>
                <label class="form-label">Motivazione / nota professionale</label>
                <textarea v-model="form.consultant_notes" rows="5" class="form-control" :class="{ 'is-invalid': form.errors.consultant_notes }" placeholder="Motiva conferma, adattamento, esclusione o mantenimento manuale del rischio."></textarea>
                <div v-if="form.errors.consultant_notes" class="invalid-feedback d-block">{{ form.errors.consultant_notes }}</div>
              </div>

              <div>
                <label class="form-label">Prossima revisione pianificata</label>
                <input v-model="form.review_due_at" type="date" class="form-control" :class="{ 'is-invalid': form.errors.review_due_at }" />
                <div class="form-text">Usa questo campo per mettere il rischio in agenda consulenziale senza aprire workflow piu' pesanti.</div>
                <div v-if="form.errors.review_due_at" class="invalid-feedback d-block">{{ form.errors.review_due_at }}</div>
              </div>

              <div class="border rounded p-3 bg-light-subtle">
                <h6 class="mb-3">Presa in carico operativa</h6>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Referente operativo</label>
                    <select v-model="form.operational_owner_user_id" class="form-select" :class="{ 'is-invalid': form.errors.operational_owner_user_id }">
                      <option value="">Nessun referente assegnato</option>
                      <option v-for="owner in formOptions.owners" :key="owner.id" :value="owner.id">
                        {{ owner.name }} - {{ owner.role }}
                      </option>
                    </select>
                    <div v-if="form.errors.operational_owner_user_id" class="invalid-feedback d-block">{{ form.errors.operational_owner_user_id }}</div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Stato follow-up</label>
                    <select v-model="form.follow_up_status" class="form-select" :class="{ 'is-invalid': form.errors.follow_up_status }">
                      <option value="">Nessun follow-up aperto</option>
                      <option v-for="status in formOptions.followUpStatuses" :key="status.value" :value="status.value">
                        {{ status.label }}
                      </option>
                    </select>
                    <div v-if="form.errors.follow_up_status" class="invalid-feedback d-block">{{ form.errors.follow_up_status }}</div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Scadenza follow-up</label>
                    <input v-model="form.follow_up_due_at" type="date" class="form-control" :class="{ 'is-invalid': form.errors.follow_up_due_at }" />
                    <div v-if="form.errors.follow_up_due_at" class="invalid-feedback d-block">{{ form.errors.follow_up_due_at }}</div>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Note operative</label>
                    <textarea v-model="form.follow_up_notes" rows="3" class="form-control" :class="{ 'is-invalid': form.errors.follow_up_notes }" placeholder="Prossimo presidio, blocchi, dipendenze o attivita' da seguire."></textarea>
                    <div v-if="form.errors.follow_up_notes" class="invalid-feedback d-block">{{ form.errors.follow_up_notes }}</div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Esito operativo</label>
                    <select
                      v-model="form.follow_up_outcome_status"
                      class="form-select"
                      :disabled="form.follow_up_status !== 'closed'"
                      :class="{ 'is-invalid': form.errors.follow_up_outcome_status }"
                    >
                      <option value="">Nessun esito registrato</option>
                      <option v-for="outcome in formOptions.followUpOutcomes" :key="outcome.value" :value="outcome.value">
                        {{ outcome.label }}
                      </option>
                    </select>
                    <div class="form-text">Compila questo campo solo quando chiudi il follow-up e vuoi lasciare un outcome leggibile nel workspace.</div>
                    <div v-if="form.errors.follow_up_outcome_status" class="invalid-feedback d-block">{{ form.errors.follow_up_outcome_status }}</div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Nota esito operativo</label>
                    <textarea
                      v-model="form.follow_up_outcome_notes"
                      rows="3"
                      class="form-control"
                      :disabled="form.follow_up_status !== 'closed'"
                      :class="{ 'is-invalid': form.errors.follow_up_outcome_notes }"
                      placeholder="Sintesi della chiusura, rinvio o monitoraggio residuo."
                    ></textarea>
                    <div v-if="form.errors.follow_up_outcome_notes" class="invalid-feedback d-block">{{ form.errors.follow_up_outcome_notes }}</div>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">Salva valutazione</button>
              </div>
            </form>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4 mt-1">
      <BCol xl="6">
        <BCard no-body>
          <BCardHeader class="border-0">
            <h4 class="card-title mb-1">Timeline operativa</h4>
            <p class="text-muted mb-0">Lettura cronologica minima di review, follow-up, outcome e misure collegate a questo rischio.</p>
          </BCardHeader>
          <BCardBody class="pt-0">
            <div v-if="timeline.length === 0" class="text-muted py-4">
              Nessun evento operativo disponibile.
            </div>
            <div v-else class="vstack gap-3">
              <div v-for="event in timeline" :key="`${event.type}-${event.title}-${event.occurred_at}`" class="border rounded p-3">
                <div class="d-flex align-items-start justify-content-between gap-3">
                  <div>
                    <div class="fw-semibold">{{ event.title }}</div>
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
      </BCol>

      <BCol xl="6">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0">
            <h4 class="card-title mb-1">Storico decisioni</h4>
            <p class="text-muted mb-0">Timeline minima per capire come il rischio e' stato assunto dal sistema nel tempo.</p>
          </BCardHeader>
          <BCardBody class="pt-0">
            <div v-if="reviews.length === 0" class="text-muted py-4">
              Nessuna revisione storica disponibile.
            </div>
            <div v-else class="table-responsive">
              <table class="table align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Evento</th>
                    <th>Decisione</th>
                    <th>Stato finale</th>
                    <th>Prossima revisione</th>
                    <th>Follow-up</th>
                    <th>Esito</th>
                    <th>Attore</th>
                    <th>Quando</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="review in reviews" :key="review.id">
                    <td>
                      <div class="fw-medium">{{ historyEventLabels[review.event_type] || review.event_type }}</div>
                      <div v-if="review.consultant_notes" class="text-muted fs-13">{{ review.consultant_notes }}</div>
                    </td>
                    <td>{{ decisionLabels[review.consultant_decision] || review.consultant_decision || "Non definita" }}</td>
                    <td>{{ review.operational_status === "excluded" ? "Escluso" : "Attivo" }}</td>
                    <td>{{ review.review_due_at || "Non pianificata" }}</td>
                    <td>
                      <div>{{ followUpLabels[review.follow_up_status] || "Non aperto" }}</div>
                      <div v-if="review.operational_owner_name" class="text-muted fs-13">{{ review.operational_owner_name }}</div>
                      <div v-if="review.follow_up_due_at" class="text-muted fs-13">{{ review.follow_up_due_at }}</div>
                    </td>
                    <td>
                      <div>{{ followUpOutcomeLabels[review.follow_up_outcome_status] || "Non registrato" }}</div>
                      <div v-if="review.follow_up_outcome_notes" class="text-muted fs-13">{{ review.follow_up_outcome_notes }}</div>
                      <div v-if="review.follow_up_outcome_recorded_at" class="text-muted fs-13">{{ review.follow_up_outcome_recorded_at }}</div>
                    </td>
                    <td>{{ review.actor_name || "Sistema / seed" }}</td>
                    <td>{{ review.reviewed_at || "Non disponibile" }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>
  </Layout>
</template>
