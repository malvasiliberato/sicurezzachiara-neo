<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

const props = defineProps({
  tenant: {
    type: Object,
    required: true,
  },
  worker: {
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

const familyLabels = {
  job_role: "Mansione",
  equipment_type: "Macchinario",
  workplace_type: "Luogo",
};

const manualForm = useForm({
  risk_catalog_item_id: "",
  final_priority: "",
  consultant_notes: "",
  review_due_at: "",
});

const submitManualRisk = () => {
  manualForm.post(route("workers.risk-profile.manual.store", props.worker.id), {
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
</script>

<template>
  <Layout>
    <Head :title="`Profilo rischio - ${worker.full_name}`" />

    <PageHeader title="Profilo rischio lavoratore" pageTitle="SicurezzaChiara" />

    <BRow class="g-4 mb-4">
      <BCol xxl="8">
        <BCard no-body class="overflow-hidden">
          <BCardBody class="p-4">
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
              <div>
                <span class="badge bg-danger-subtle text-danger text-uppercase mb-3">Profilo rischio operativo</span>
                <h2 class="mb-1">{{ worker.full_name }}</h2>
                <p class="text-muted mb-3">
                  Lettura operativa del profilo: il sistema deduce dal contesto del lavoratore e il consulente porta il rischio allo stato finale utile al presidio.
                </p>
              <div class="hstack gap-2 flex-wrap">
                  <span class="badge bg-light text-body">{{ tenant.name }}</span>
                  <span class="badge bg-soft-secondary text-secondary">{{ worker.company?.name || "Azienda non disponibile" }}</span>
                  <span class="badge bg-soft-info text-info">{{ worker.primary_site?.name || "Sede prevalente non assegnata" }}</span>
                  <span class="badge bg-soft-primary text-primary">
                    {{ worker.job_role_assignments?.[0]?.job_role?.name || "Mansione prevalente non assegnata" }}
                  </span>
                  <span class="badge bg-soft-success text-success">{{ summary.totalMeasures }} misure</span>
                  <span v-if="summary.reviewsDue > 0" class="badge bg-soft-info text-info">{{ summary.reviewsDue }} revisioni in agenda</span>
                  <span v-if="summary.excludedRisks > 0" class="badge bg-soft-secondary text-secondary">{{ summary.excludedRisks }} esclusi</span>
                  <span class="badge bg-soft-primary text-primary">Focus: {{ workspaceBridge.focusLabel }}</span>
                </div>
              </div>
              <div class="hstack gap-2 flex-wrap">
                <Link :href="workspaceBridge.actions.workerRoute" class="btn btn-soft-secondary">
                  Torna lavoratore
                </Link>
                <Link :href="workspaceBridge.actions.registryRoute" class="btn btn-soft-info">
                  Registri azienda
                </Link>
                <Link :href="workspaceBridge.actions.dashboardRoute" class="btn btn-soft-warning">
                  Focus dashboard
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
            <h4 class="card-title mb-0">Riepilogo e bridge operativo</h4>
          </BCardHeader>
          <BCardBody>
            <div class="vstack gap-3">
              <div class="border rounded-3 p-3 bg-light-subtle">
                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
                  <span v-if="workspaceBridge.originLabel" class="badge bg-soft-info text-info">{{ workspaceBridge.originLabel }}</span>
                  <span class="badge bg-light text-body">Focus consigliato: {{ workspaceBridge.suggestedFocusLabel }}</span>
                </div>
                <div class="fw-semibold mb-1">{{ workspaceBridge.suggestedAction.label }}</div>
                <div class="text-muted fs-13 mb-3">{{ workspaceBridge.suggestedAction.helper }}</div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <span class="badge bg-info-subtle text-info">{{ workspaceBridge.stats.reviewsDue }} review dovute</span>
                  <span class="badge bg-primary-subtle text-primary">{{ workspaceBridge.stats.followUpsOpen }} follow-up aperti</span>
                  <span class="badge bg-warning-subtle text-warning">{{ workspaceBridge.stats.missingExpectedMeasures }} gap attesi</span>
                  <span class="badge bg-danger-subtle text-danger">{{ workspaceBridge.stats.uncoveredRisks }} rischi scoperti</span>
                </div>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Rischi dedotti</span>
                <span class="fw-semibold fs-5">{{ summary.totalRisks }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Rischi esclusi</span>
                <span class="fw-semibold fs-5">{{ summary.excludedRisks }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Da presidiare</span>
                <span class="fw-semibold fs-5">{{ summary.uncoveredRisks }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Priorita' alta</span>
                <span class="fw-semibold fs-5">{{ summary.highPriorityRisks }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Famiglie sorgente</span>
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
              <div class="border-top pt-3">
                <div class="d-flex flex-column gap-2">
                  <Link :href="workspaceBridge.actions.registryRoute" class="btn btn-soft-info btn-sm">Apri registro contestuale</Link>
                  <Link :href="workspaceBridge.actions.companyRoute" class="btn btn-soft-secondary btn-sm">Torna al contesto azienda</Link>
                  <Link :href="workspaceBridge.actions.dashboardRoute" class="btn btn-soft-warning btn-sm">Rientra nel focus dashboard</Link>
                </div>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BAlert show variant="info" class="mb-4">
      <div class="fw-semibold mb-1">Come leggere questa pagina</div>
      <div class="text-muted">
        Parti dai rischi in presidio, usa la valutazione consulente per congelare la decisione professionale e scendi su misure e registri per capire dove intervenire davvero.
      </div>
    </BAlert>

    <BCard no-body class="mb-4">
      <BCardHeader class="border-0 pb-0">
        <h4 class="card-title mb-1">Motore v1 del profilo</h4>
        <p class="text-muted mb-0">Il profilo del lavoratore nasce da sorgenti attive, viene consolidato dal consulente e si riflette su presidi e copertura.</p>
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
        <p class="text-muted mb-0">Qui si vede quali segnali il core standard sta leggendo dalla mansione, dai macchinari e dai luoghi realmente associati al lavoratore.</p>
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
        <div v-else class="text-muted">Nessun segnale core leggibile dal contesto del lavoratore.</div>
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
                  <i class="ri-alarm-warning-line"></i>
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
              In questa fase il sistema mostra il profilo dedotto, la decisione professionale e il primo livello di presidio operativo collegato a ciascun rischio.
            </p>
          </div>
          <span class="badge bg-soft-danger text-danger">{{ summary.totalRisks }} elementi</span>
        </div>
      </BCardHeader>
      <BCardBody class="pt-0">
        <div v-if="worker.risk_profile_items.length === 0" class="text-center py-5">
          <div class="avatar-md mx-auto mb-3">
            <div class="avatar-title bg-light text-info rounded-circle fs-2">
              <i class="ri-radar-line"></i>
            </div>
          </div>
          <h5 class="mb-2">Nessun rischio ancora dedotto</h5>
          <p class="text-muted mb-0">
            Aggiungi o completa mansioni, macchinari e luoghi per rendere leggibile il profilo operativo del lavoratore.
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
              <tr v-for="profileItem in worker.risk_profile_items" :key="profileItem.id">
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
                  <div class="vstack gap-2">
                    <div v-for="source in profileItem.sources" :key="source.id" class="d-flex align-items-center gap-2 flex-wrap">
                      <span class="badge bg-soft-info text-info">
                        {{ familyLabels[source.source_family] || source.source_family }}
                      </span>
                      <span class="fw-medium">{{ source.source_label }}</span>
                      <span class="badge bg-light text-body">
                        {{ source.relevance === 'primary' ? 'Primaria' : 'Secondaria' }}
                      </span>
                    </div>
                    <div v-if="profileItem.sources.length === 0" class="text-muted fs-13">Nessuna sorgente attiva: mantenuto dal consulente.</div>
                    <div v-if="!profileItem.is_currently_derived" class="text-muted fs-13">Derivazione automatica non piu' attiva.</div>
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
                    <Link :href="route('measure-registries.index', { company_id: worker.company?.id, scope: 'attention', origin: 'worker_risk_profile' })" class="btn btn-soft-info btn-sm">
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
            Il consulente puo' integrare il profilo finale del lavoratore con un rischio non suggerito automaticamente.
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
