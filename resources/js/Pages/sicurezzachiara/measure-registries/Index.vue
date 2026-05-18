<script setup>
import { computed, reactive } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

const props = defineProps({
  tenant: {
    type: Object,
    required: true,
  },
  activeFamily: {
    type: String,
    required: true,
  },
  activeScope: {
    type: String,
    required: true,
  },
  activeCompanyId: {
    type: Number,
    default: null,
  },
  activeOwnerUserId: {
    type: Number,
    default: null,
  },
  copy: {
    type: Object,
    required: true,
  },
  workspaceContext: {
    type: Object,
    required: true,
  },
  contextBridge: {
    type: Object,
    default: null,
  },
  coreStarterPack: {
    type: Object,
    required: true,
  },
  measures: {
    type: Array,
    required: true,
  },
  filters: {
    type: Object,
    required: true,
  },
  summary: {
    type: Object,
    required: true,
  },
});

const tabs = [
  { value: "all", label: "Tutte", icon: "ri-apps-2-line" },
  { value: "training", label: "Formazione", icon: "ri-graduation-cap-line" },
  { value: "medical", label: "Visite mediche", icon: "ri-stethoscope-line" },
  { value: "dpi", label: "DPI", icon: "ri-user-star-line" },
  { value: "operational", label: "Presidi", icon: "ri-tools-line" },
  { value: "follow_up", label: "In carico", icon: "ri-user-follow-line" },
];

const familyLabels = {
  organizational: "Organizzativa",
  technical: "Tecnica",
  dpi: "DPI",
  training: "Formazione",
  medical: "Visite mediche",
};

const statusLabels = {
  implemented: "Attuata",
  not_implemented: "Non attuata",
  to_verify: "Da verificare",
};

const statusBadges = {
  implemented: "bg-success-subtle text-success",
  not_implemented: "bg-danger-subtle text-danger",
  to_verify: "bg-warning-subtle text-warning",
};

const followUpLabels = {
  open: "Aperto",
  in_progress: "In lavorazione",
  blocked: "Bloccato",
  closed: "Chiuso",
};

const followUpBadges = {
  open: "bg-warning-subtle text-warning",
  in_progress: "bg-primary-subtle text-primary",
  blocked: "bg-danger-subtle text-danger",
  closed: "bg-success-subtle text-success",
};

const filterState = reactive({
  scope: props.activeScope,
  company_id: props.activeCompanyId ?? "",
  owner_user_id: props.activeOwnerUserId ?? "",
});

const isCompanyScopedRegistry = computed(() => props.workspaceContext.isCompanyScoped === true);

const activeFilterCount = computed(() => {
  return [
    filterState.scope !== "all",
    !isCompanyScopedRegistry.value && !!filterState.company_id,
    !!filterState.owner_user_id,
  ].filter(Boolean).length;
});

const contextBadges = computed(() => {
  return [
    props.workspaceContext.originLabel ? `Origine: ${props.workspaceContext.originLabel}` : null,
    props.workspaceContext.focusLabel ? `Focus: ${props.workspaceContext.focusLabel}` : null,
    props.workspaceContext.activeScopeLabel ? `Vista: ${props.workspaceContext.activeScopeLabel}` : null,
    props.workspaceContext.companyName ? `Azienda: ${props.workspaceContext.companyName}` : null,
    props.workspaceContext.originRisk?.riskName ? `Rischio: ${props.workspaceContext.originRisk.riskName}` : null,
    props.workspaceContext.ownerName ? `Referente: ${props.workspaceContext.ownerName}` : null,
  ].filter(Boolean);
});

const activeFamilyLabel = computed(() => {
  const activeTab = tabs.find((tab) => tab.value === props.activeFamily);

  return activeTab ? activeTab.label : "Tutte";
});

const heroCards = computed(() => {
  if (isCompanyScopedRegistry.value && props.contextBridge) {
    return [
      {
        key: "visible",
        label: "Misure nel contesto",
        value: props.summary.visibleMeasures,
        helper: `${props.workspaceContext.companyName} | ${activeFamilyLabel.value}`,
        avatarClass: "bg-primary-subtle text-primary",
        icon: "ri-shield-check-line",
      },
      {
        key: "overdue",
        label: "Scadute",
        value: props.contextBridge.stats.overdueMeasures,
        helper: "Presidi oltre data da chiudere nel registro contestuale",
        avatarClass: "bg-danger-subtle text-danger",
        icon: "ri-time-line",
      },
      {
        key: "follow-up",
        label: "Follow-up aperti",
        value: props.contextBridge.stats.followUpsOpen,
        helper: "Rischi ancora in carico operativo nel perimetro aziendale",
        avatarClass: "bg-warning-subtle text-warning",
        icon: "ri-user-follow-line",
      },
      {
        key: "uncovered",
        label: "Rischi scoperti",
        value: props.contextBridge.stats.uncoveredRisks,
        helper: "Profili che chiedono ancora un presidio o un riallineamento",
        avatarClass: "bg-info-subtle text-info",
        icon: "ri-alert-line",
      },
    ];
  }

  return [
    {
      key: "total",
      label: "Misure totali",
      value: props.summary.totalMeasures,
      helper: null,
      avatarClass: "bg-primary-subtle text-primary",
      icon: "ri-shield-check-line",
    },
    {
      key: "implemented",
      label: "Attuate",
      value: props.summary.implementedMeasures,
      helper: null,
      avatarClass: "bg-success-subtle text-success",
      icon: "ri-checkbox-circle-line",
    },
    {
      key: "to-verify",
      label: "Da verificare",
      value: props.summary.toVerifyMeasures,
      helper: null,
      avatarClass: "bg-warning-subtle text-warning",
      icon: "ri-time-line",
    },
    {
      key: "follow-up",
      label: "Misure in follow-up",
      value: props.summary.followUpMeasures,
      helper: `${props.summary.operationalMeasures} presidi | ${props.summary.trainingMeasures} formazione`,
      avatarClass: "bg-secondary-subtle text-secondary",
      icon: "ri-user-follow-line",
    },
  ];
});

const buildQuery = (overrides = {}) => {
  const family = overrides.family ?? props.activeFamily;
  const scope = overrides.scope ?? filterState.scope;
  const companyId = overrides.company_id ?? filterState.company_id;
  const ownerUserId = overrides.owner_user_id ?? filterState.owner_user_id;
  const query = {};

  if (family !== "all") {
    query.family = family;
  }

  if (scope && scope !== "all") {
    query.scope = scope;
  }

  if (companyId) {
    query.company_id = companyId;
  }

  if (ownerUserId) {
    query.owner_user_id = ownerUserId;
  }

  if (props.workspaceContext.origin) {
    query.origin = props.workspaceContext.origin;
  }

  if (props.workspaceContext.focus) {
    query.focus = props.workspaceContext.focus;
  }

  return query;
};

const visitWithFilters = (family = props.activeFamily, overrides = {}) => {
  router.get(route("measure-registries.index"), buildQuery({ family, ...overrides }), {
    preserveScroll: true,
    preserveState: true,
  });
};

const resetFilters = () => {
  filterState.scope = "all";
  filterState.company_id = props.activeCompanyId ?? "";
  filterState.owner_user_id = "";

  visitWithFilters();
};

const starterPriorityBadge = {
  high: "bg-danger-subtle text-danger",
  medium: "bg-warning-subtle text-warning",
  low: "bg-success-subtle text-success",
};

const queueToneClasses = {
  danger: "btn-soft-danger",
  warning: "btn-soft-warning",
  primary: "btn-soft-primary",
  info: "btn-soft-info",
};

const postureBadgeClasses = {
  danger: "bg-danger-subtle text-danger",
  warning: "bg-warning-subtle text-warning",
  primary: "bg-primary-subtle text-primary",
  info: "bg-info-subtle text-info",
  success: "bg-success-subtle text-success",
};

const nextStepButtonClasses = {
  danger: "btn-danger",
  warning: "btn-warning",
  primary: "btn-primary",
  secondary: "btn-soft-secondary",
};

const laneBadgeClasses = {
  overdue: "bg-danger-subtle text-danger",
  follow_up: "bg-warning-subtle text-warning",
  reviews: "bg-primary-subtle text-primary",
  coverage: "bg-info-subtle text-info",
  profile: "bg-secondary-subtle text-secondary",
  review: "bg-secondary-subtle text-secondary",
};
</script>

<template>
  <Layout>
    <Head title="Registri misure" />

    <PageHeader title="Registri misure" pageTitle="SicurezzaChiara" />

    <BRow class="g-4 mb-4">
      <BCol v-for="card in heroCards" :key="card.key" md="6" xl="3">
        <BCard no-body class="card-animate h-100">
          <BCardBody>
            <p class="text-uppercase fw-medium text-muted mb-3">{{ card.label }}</p>
            <div v-if="card.helper" class="text-muted fs-13 mb-2">
              {{ card.helper }}
            </div>
            <div class="d-flex align-items-center justify-content-between">
              <h3 class="mb-0">{{ card.value }}</h3>
              <div class="avatar-sm">
                <span class="avatar-title rounded fs-3" :class="card.avatarClass">
                  <i :class="card.icon"></i>
                </span>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BCard no-body>
      <BCardHeader class="border-0 pb-0">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <h4 class="card-title mb-1">{{ copy.workspaceTitle }}</h4>
            <p class="text-muted mb-0">
              {{ copy.workspaceHelper }}
            </p>
          </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="badge bg-soft-info text-info">
                {{ isCompanyScopedRegistry ? workspaceContext.contextLabel : tenant.name }}
              </span>
              <span class="badge bg-light text-body">{{ summary.trainingMeasures }} formazione</span>
              <span class="badge bg-light text-body">{{ summary.medicalMeasures }} visite</span>
              <span class="badge bg-light text-body">{{ summary.dpiMeasures }} DPI</span>
              <span class="badge bg-light text-body">{{ summary.operationalMeasures }} presidi</span>
              <span class="badge bg-success-subtle text-success">{{ summary.directMeasures }} dirette</span>
              <span class="badge bg-info-subtle text-info">{{ summary.substitutedMeasures }} equivalenti</span>
              <span class="badge bg-secondary-subtle text-secondary">{{ summary.freeMeasures }} libere</span>
              <span class="badge bg-danger-subtle text-danger">{{ summary.expectedGapRisks }} rischi con gap attesi</span>
            </div>
          </div>

        <ul class="nav nav-tabs nav-tabs-custom nav-success" role="tablist">
          <li v-for="tab in tabs" :key="tab.value" class="nav-item">
            <button
              type="button"
              class="nav-link"
              :class="{ active: activeFamily === tab.value }"
              @click="visitWithFilters(tab.value)"
            >
              <i :class="`${tab.icon} align-bottom me-1`"></i>{{ tab.label }}
            </button>
          </li>
        </ul>
      </BCardHeader>
      <BCardBody class="pt-3">
        <div class="bg-light rounded-3 p-3 mb-4">
          <div v-if="contextBridge" class="border rounded-3 bg-white p-3 mb-3">
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
              <div>
                <h6 class="mb-1">Raccordo contestuale azienda</h6>
                <p class="text-muted mb-0 fs-13">
                  Stai lavorando dentro {{ contextBridge.companyName }}. Il registro resta agganciato al profilo rischio aziendale e al contesto da cui sei partito.
                </p>
              </div>
              <span class="badge bg-soft-primary text-primary">{{ contextBridge.suggestedAction.label }}</span>
            </div>
            <div class="text-muted fs-13 mb-3">{{ contextBridge.suggestedAction.helper }}</div>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
              <span class="badge bg-light text-body">{{ contextBridge.stats.visibleMeasures }} misure visibili</span>
              <span class="badge bg-danger-subtle text-danger">{{ contextBridge.stats.overdueMeasures }} scadute</span>
              <span class="badge bg-primary-subtle text-primary">{{ contextBridge.stats.followUpsOpen }} follow-up aperti</span>
              <span class="badge bg-warning-subtle text-warning">{{ contextBridge.stats.uncoveredRisks }} rischi scoperti</span>
            </div>
            <div class="hstack gap-2 flex-wrap">
              <Link :href="contextBridge.actions.riskProfileRoute" class="btn btn-soft-primary btn-sm">
                Profilo rischio azienda
              </Link>
              <Link :href="contextBridge.actions.companyRoute" class="btn btn-soft-secondary btn-sm">
                Dettaglio azienda
              </Link>
              <Link v-if="contextBridge.actions.dashboardRoute" :href="contextBridge.actions.dashboardRoute" class="btn btn-soft-warning btn-sm">
                Torna alla dashboard
              </Link>
            </div>
            <div v-if="contextBridge.operationalQueue?.length" class="border-top pt-3 mt-3">
              <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-2">
                <div>
                  <h6 class="mb-1">Coda di chiusura</h6>
                  <p class="text-muted mb-0 fs-13">
                    Parti dalla pista piu' urgente e usa il registro solo come corsia di lavoro, non come portfolio generale.
                  </p>
                </div>
              </div>
              <div class="d-flex align-items-stretch gap-2 flex-wrap">
                <Link
                  v-for="item in contextBridge.operationalQueue"
                  :key="item.key"
                  :href="item.route"
                  class="btn btn-sm text-start"
                  :class="queueToneClasses[item.tone] || 'btn-soft-secondary'"
                >
                  <span class="fw-semibold d-block">{{ item.label }} <span class="ms-1">({{ item.count }})</span></span>
                  <span class="fs-12">{{ item.helper }}</span>
                </Link>
              </div>
            </div>
          </div>

          <div v-if="workspaceContext.originRisk" class="border rounded-3 bg-white p-3 mb-3">
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
              <div>
                <h6 class="mb-1">Rischio di origine</h6>
                <p class="text-muted mb-0 fs-13">
                  {{ workspaceContext.originRisk.riskName }} | {{ workspaceContext.originRisk.parentTypeLabel }}: {{ workspaceContext.originRisk.parentLabel }}
                </p>
              </div>
              <span class="badge bg-soft-primary text-primary">Rientro contestuale attivo</span>
            </div>
            <div class="text-muted fs-13 mb-3">{{ workspaceContext.originRisk.helper }}</div>
            <div class="hstack gap-2 flex-wrap">
              <Link :href="workspaceContext.originRisk.reviewRoute" class="btn btn-soft-primary btn-sm">
                Torna alla review
              </Link>
              <Link :href="workspaceContext.originRisk.measuresRoute" class="btn btn-soft-info btn-sm">
                Torna alle misure
              </Link>
              <Link :href="workspaceContext.originRisk.profileRoute" class="btn btn-soft-secondary btn-sm">
                Apri profilo rischio
              </Link>
            </div>
          </div>

          <div v-if="contextBadges.length > 0 || workspaceContext.backRoute" class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span v-for="badge in contextBadges" :key="badge" class="badge bg-soft-info text-info">
                {{ badge }}
              </span>
            </div>
            <Link v-if="workspaceContext.backRoute" :href="workspaceContext.backRoute" class="btn btn-soft-secondary btn-sm">
              Torna alla dashboard
            </Link>
          </div>

          <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
            <div>
              <h6 class="mb-1">Filtri workspace</h6>
              <p class="text-muted mb-0 fs-13">
                {{ workspaceContext.narrative }}
              </p>
              <div v-if="isCompanyScopedRegistry" class="text-muted fs-13 mt-2">
                Azienda fissata dal contesto: {{ workspaceContext.companyName }}.
              </div>
              <div class="text-muted fs-13 mt-2">
                {{ summary.visibleMeasures }} record visibili su {{ summary.contextMeasures }} nel contesto corrente | Registro attivo: {{ activeFamilyLabel }}
              </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="badge bg-soft-info text-info">{{ activeFilterCount }} filtri attivi</span>
              <button type="button" class="btn btn-soft-primary btn-sm" @click="visitWithFilters()">
                Applica
              </button>
              <button
                type="button"
                class="btn btn-soft-secondary btn-sm"
                :disabled="activeFilterCount === 0"
                @click="resetFilters"
              >
                Reset
              </button>
            </div>
          </div>

          <div class="d-flex align-items-stretch gap-2 flex-wrap mb-3">
            <Link
              v-for="shortcut in workspaceContext.shortcuts || []"
              :key="shortcut.label"
              :href="shortcut.route"
              class="btn btn-soft-primary btn-sm text-start"
            >
              <span class="fw-semibold d-block">{{ shortcut.label }}</span>
              <span class="fs-12">{{ shortcut.helper }}</span>
            </Link>
          </div>

          <div class="d-flex align-items-stretch gap-2 flex-wrap mb-3">
            <button
              v-for="scopeOption in filters.scopes"
              :key="scopeOption.value"
              type="button"
              class="btn btn-sm text-start"
              :class="filterState.scope === scopeOption.value ? 'btn-primary' : 'btn-soft-primary'"
              @click="filterState.scope = scopeOption.value; visitWithFilters(activeFamily, { scope: scopeOption.value })"
            >
              <span class="fw-semibold d-block">{{ scopeOption.label }}</span>
              <span class="fs-12">{{ scopeOption.count }} record</span>
            </button>
          </div>

          <BRow class="g-3">
            <BCol lg="4">
              <label class="form-label">Focalizzazione</label>
              <select v-model="filterState.scope" class="form-select">
                <option v-for="scope in filters.scopes" :key="scope.value" :value="scope.value">
                  {{ scope.label }} ({{ scope.count }})
                </option>
              </select>
            </BCol>
            <BCol v-if="workspaceContext.showCompanyFilter" lg="4">
              <label class="form-label">Azienda</label>
              <select v-model="filterState.company_id" class="form-select">
                <option value="">Tutte le aziende</option>
                <option v-for="company in filters.companies" :key="company.value" :value="company.value">
                  {{ company.label }}
                </option>
              </select>
            </BCol>
            <BCol :lg="workspaceContext.showCompanyFilter ? 4 : 8">
              <label class="form-label">Referente operativo</label>
              <select v-model="filterState.owner_user_id" class="form-select">
                <option value="">Tutti i referenti</option>
                <option v-for="owner in filters.owners" :key="owner.value" :value="owner.value">
                  {{ owner.label }}
                </option>
              </select>
            </BCol>
          </BRow>
        </div>

        <div class="border rounded-3 p-3 mb-4">
          <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
            <div>
              <h6 class="mb-1">Starter pack core nel workspace</h6>
              <p class="text-muted mb-0 fs-13">
                Il registro mostra anche quali segnali core stanno alimentando i rischi collegati ai presidi oggi visibili nel contesto corrente.
              </p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="badge bg-soft-primary text-primary">{{ coreStarterPack.summary.sourceCount }} sorgenti</span>
              <span class="badge bg-soft-info text-info">{{ coreStarterPack.summary.suggestedRisksCount }} rischi core</span>
              <span class="badge bg-soft-warning text-warning">{{ coreStarterPack.summary.expectedMeasuresCount }} presidi attesi</span>
            </div>
          </div>
          <div v-if="coreStarterPack.suggestedRisks.length" class="d-flex flex-wrap gap-2">
            <span v-for="risk in coreStarterPack.suggestedRisks.slice(0, 10)" :key="risk.id" class="badge bg-light text-body">
              {{ risk.name }}
              <span class="ms-1 badge" :class="starterPriorityBadge[risk.default_priority] || 'bg-light text-body'">
                {{ risk.default_priority === "high" ? "Alta" : risk.default_priority === "medium" ? "Media" : "Bassa" }}
              </span>
            </span>
          </div>
          <div v-else class="text-muted fs-13">Nessun segnale core leggibile nel perimetro del registro corrente.</div>
        </div>

        <div v-if="measures.length === 0" class="text-center py-5">
          <div class="avatar-md mx-auto mb-3">
            <div class="avatar-title bg-light text-info rounded-circle fs-2">
              <i class="ri-folder-open-line"></i>
            </div>
          </div>
          <h5 class="mb-2">Nessuna misura nel registro selezionato</h5>
          <p class="text-muted mb-0">
            Collega una misura da un profilo rischio per popolare questo workspace operativo.
          </p>
        </div>

        <div v-else class="table-responsive">
          <table class="table align-middle table-nowrap mb-0">
            <thead class="table-light">
              <tr>
                <th>Misura</th>
                <th>{{ copy.familyColumnLabel }}</th>
                <th>Contesto</th>
                <th>Rischio</th>
                <th>Lettura motore</th>
                <th>Stato</th>
                <th>Follow-up</th>
                <th>Scadenza</th>
                <th class="text-end">Azioni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="measure in measures" :key="measure.id">
                <td>
                  <div class="fw-semibold">{{ measure.title }}</div>
                  <div class="text-muted fs-13">
                    {{ measure.details_summary || measure.description || measure.notes || "Nessun dettaglio aggiuntivo." }}
                  </div>
                </td>
                <td>
                  <span class="badge bg-light text-body">
                    {{ familyLabels[measure.family] || measure.family }}
                  </span>
                </td>
                <td>
                  <div class="fw-medium">{{ measure.profile_label }}</div>
                  <div class="text-muted fs-13">
                    {{ measure.profile_type_label }}
                    <template v-if="measure.company_name"> | {{ measure.company_name }}</template>
                  </div>
                </td>
                <td>
                  <div>{{ measure.risk_name || "Rischio non disponibile" }}</div>
                  <div class="text-muted fs-13">{{ measure.risk_category || "Categoria non disponibile" }}</div>
                  <div v-if="measure.is_origin_risk" class="mt-1">
                    <span class="badge bg-soft-primary text-primary">Rischio origine</span>
                  </div>
                </td>
                <td>
                  <span
                    class="badge"
                    :class="
                      measure.expected_binding?.binding === 'direct_expected'
                        ? 'bg-success-subtle text-success'
                        : measure.expected_binding?.binding === 'family_substitution'
                          ? 'bg-info-subtle text-info'
                          : measure.expected_binding?.binding === 'free_measure'
                            ? 'bg-secondary-subtle text-secondary'
                            : 'bg-light text-body'
                    "
                  >
                    {{ measure.expected_binding?.label || "Nessuna attesa esplicita" }}
                  </span>
                  <div v-if="measure.expected_binding?.expected_title" class="text-muted fs-13 mt-1">
                    {{ measure.expected_binding.expected_title }}
                  </div>
                  <div v-else-if="measure.expected_binding?.detail" class="text-muted fs-13 mt-1">
                    {{ measure.expected_binding.detail }}
                  </div>
                  <div class="text-muted fs-13 mt-1">
                    {{ measure.bridge_summary }}
                  </div>
                </td>
                <td>
                  <span class="badge" :class="statusBadges[measure.status] || 'bg-light text-body'">
                    {{ statusLabels[measure.status] || measure.status }}
                  </span>
                </td>
                <td>
                  <div v-if="measure.has_open_follow_up">
                    <Link
                      v-if="measure.review_route"
                      :href="measure.review_route"
                      class="text-reset text-decoration-none"
                    >
                      <span class="badge" :class="followUpBadges[measure.follow_up_status] || 'bg-light text-body'">
                        {{ followUpLabels[measure.follow_up_status] || measure.follow_up_status }}
                      </span>
                    </Link>
                    <div class="text-muted fs-13 mt-1">{{ measure.follow_up_owner_name || "Da assegnare" }}</div>
                    <div v-if="measure.follow_up_notes" class="text-muted fs-13">{{ measure.follow_up_notes }}</div>
                  </div>
                  <span v-else class="text-muted fs-13">Nessun follow-up</span>
                </td>
                <td>{{ measure.due_date || "Non definita" }}</td>
                <td class="text-end">
                  <div
                    v-if="measure.operational_posture || measure.next_step?.lane"
                    class="mb-2 d-flex align-items-center justify-content-end gap-2 flex-wrap"
                  >
                    <span
                      v-if="measure.next_step?.lane"
                      class="badge"
                      :class="laneBadgeClasses[measure.next_step.lane.key] || 'bg-light text-body'"
                    >
                      {{ measure.next_step.lane.label }}
                    </span>
                    <span
                      v-if="measure.operational_posture"
                      class="badge"
                      :class="postureBadgeClasses[measure.operational_posture.tone] || 'bg-light text-body'"
                    >
                      {{ measure.operational_posture.label }}
                    </span>
                  </div>
                  <div class="hstack gap-2 justify-content-end flex-wrap">
                    <Link :href="measure.profile_route" class="btn btn-soft-secondary btn-sm">Profilo</Link>
                    <Link
                      v-if="measure.next_step?.route"
                      :href="measure.next_step.route"
                      class="btn btn-sm"
                      :class="nextStepButtonClasses[measure.next_step.tone] || 'btn-soft-primary'"
                    >
                      {{ measure.next_step.label }}
                    </Link>
                    <Link
                      v-if="measure.measures_route"
                      :href="measure.measures_route"
                      class="btn btn-soft-info btn-sm"
                    >
                      Misure rischio
                    </Link>
                  </div>
                  <div class="text-muted fs-12 mt-2 text-end">
                    {{ measure.operational_posture?.helper || measure.next_step?.helper }}
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
