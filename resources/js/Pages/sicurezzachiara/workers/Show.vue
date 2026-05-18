<script setup>
import { computed, ref } from "vue";
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
  coreStarterPack: {
    type: Object,
    required: true,
  },
  contextBridge: {
    type: Object,
    required: true,
  },
  governanceContext: {
    type: Object,
    required: true,
  },
  jobRoleOptions: {
    type: Array,
    required: true,
  },
  equipmentAssetOptions: {
    type: Array,
    required: true,
  },
  workplaceOptions: {
    type: Array,
    required: true,
  },
});

const starterPriorityBadge = {
  high: "bg-danger-subtle text-danger",
  medium: "bg-warning-subtle text-warning",
  low: "bg-success-subtle text-success",
};

const sourceBadge = {
  core: "bg-primary-subtle text-primary",
  tenant: "bg-light text-body",
};

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

const workspaceTabs = [
  { key: "anagrafica", label: "Anagrafica", icon: "ri-id-card-line" },
  { key: "mansioni", label: "Mansioni", icon: "ri-briefcase-line" },
  { key: "rischi", label: "Rischi", icon: "ri-shield-line" },
  { key: "misure", label: "Misure", icon: "ri-shield-check-line" },
  { key: "scadenze", label: "Scadenze", icon: "ri-calendar-check-line" },
  { key: "macchinari", label: "Macchinari", icon: "ri-settings-3-line" },
  { key: "luoghi", label: "Luoghi", icon: "ri-map-pin-2-line" },
];

const activeWorkspaceTab = ref("anagrafica");

const summaryCards = computed(() => ([
  { label: "Sorgenti rilevate", value: props.coreStarterPack.summary.sourceCount },
  { label: "Sorgenti core", value: props.coreStarterPack.summary.coreSourceCount },
  { label: "Rischi core suggeriti", value: props.coreStarterPack.summary.suggestedRisksCount },
  { label: "Presidi attesi core", value: props.coreStarterPack.summary.expectedMeasuresCount },
]));

const profileDetails = computed(() => ([
  { label: "Codice fiscale", value: props.worker.tax_code || "Non indicato" },
  { label: "Email", value: props.worker.email || "Non indicata" },
  { label: "Telefono", value: props.worker.phone || "Non indicato" },
  { label: "Data di nascita", value: props.worker.birth_date || "Non indicata" },
  { label: "Data assunzione", value: props.worker.hire_date || "Non indicata" },
]));

const primaryJobRoleName = computed(() => props.worker.job_role_assignments?.find((assignment) => assignment.is_primary)?.job_role?.name
  || props.worker.job_role_assignments?.[0]?.job_role?.name
  || "Mansione prevalente non assegnata");

const headerMeta = computed(() => ([
  {
    icon: "ri-building-line",
    label: "Azienda",
    value: props.worker.company?.name || "Azienda non disponibile",
  },
  {
    icon: "ri-community-line",
    label: "Sede prevalente",
    value: props.worker.primary_site?.name || "Non assegnata",
  },
  {
    icon: "ri-briefcase-line",
    label: "Mansione prevalente",
    value: primaryJobRoleName.value,
  },
  {
    icon: "ri-user-settings-line",
    label: "Stato",
    value: props.worker.status === "active" ? "Attivo" : "Non attivo",
  },
]));

const workerOverviewBadges = computed(() => ([
  `${props.worker.job_role_assignments.length} mansioni`,
  `${props.worker.equipment_exposures.length} macchinari`,
  `${props.worker.workplace_exposures.length} luoghi`,
]));

const governanceHighlights = computed(() => ([
  { label: "Rischi attivi", value: props.contextBridge.stats.activeRisks, badgeClass: "bg-light text-body" },
  { label: "Scadute", value: props.contextBridge.stats.overdueMeasures, badgeClass: "bg-danger-subtle text-danger" },
  { label: "Follow-up", value: props.contextBridge.stats.followUpsOpen, badgeClass: "bg-primary-subtle text-primary" },
  { label: "Gap", value: props.contextBridge.stats.missingExpectedMeasures, badgeClass: "bg-warning-subtle text-warning" },
]));

const assignmentForm = useForm({
  job_role_id: "",
  is_primary: props.worker.job_role_assignments.length === 0,
  assigned_on: "",
  notes: "",
});

const equipmentExposureForm = useForm({
  equipment_asset_id: "",
  is_primary: props.worker.equipment_exposures.length === 0,
  notes: "",
});

const workplaceExposureForm = useForm({
  workplace_id: "",
  is_primary: props.worker.workplace_exposures.length === 0,
  notes: "",
});

const submitAssignment = () => {
  assignmentForm.post(route("workers.job-role-assignments.store", props.worker.id), {
    preserveScroll: true,
    onSuccess: () => {
      assignmentForm.reset("job_role_id", "assigned_on", "notes");
      assignmentForm.is_primary = false;
    },
  });
};

const setPrimary = (assignment) => {
  useForm({
    job_role_id: assignment.job_role_id,
    is_primary: true,
    assigned_on: assignment.assigned_on ?? "",
    notes: assignment.notes ?? "",
  }).put(route("workers.job-role-assignments.update", [props.worker.id, assignment.id]), {
    preserveScroll: true,
  });
};

const removeAssignment = (assignment) => {
  useForm({}).delete(route("workers.job-role-assignments.destroy", [props.worker.id, assignment.id]), {
    preserveScroll: true,
  });
};

const submitEquipmentExposure = () => {
  equipmentExposureForm.post(route("workers.equipment-exposures.store", props.worker.id), {
    preserveScroll: true,
    onSuccess: () => {
      equipmentExposureForm.reset("equipment_asset_id", "notes");
      equipmentExposureForm.is_primary = false;
    },
  });
};

const setPrimaryEquipmentExposure = (exposure) => {
  useForm({
    equipment_asset_id: exposure.equipment_asset_id,
    is_primary: true,
    notes: exposure.notes ?? "",
  }).put(route("workers.equipment-exposures.update", [props.worker.id, exposure.id]), {
    preserveScroll: true,
  });
};

const removeEquipmentExposure = (exposure) => {
  useForm({}).delete(route("workers.equipment-exposures.destroy", [props.worker.id, exposure.id]), {
    preserveScroll: true,
  });
};

const submitWorkplaceExposure = () => {
  workplaceExposureForm.post(route("workers.workplace-exposures.store", props.worker.id), {
    preserveScroll: true,
    onSuccess: () => {
      workplaceExposureForm.reset("workplace_id", "notes");
      workplaceExposureForm.is_primary = false;
    },
  });
};

const setPrimaryWorkplaceExposure = (exposure) => {
  useForm({
    workplace_id: exposure.workplace_id,
    is_primary: true,
    notes: exposure.notes ?? "",
  }).put(route("workers.workplace-exposures.update", [props.worker.id, exposure.id]), {
    preserveScroll: true,
  });
};

const removeWorkplaceExposure = (exposure) => {
  useForm({}).delete(route("workers.workplace-exposures.destroy", [props.worker.id, exposure.id]), {
    preserveScroll: true,
  });
};
</script>

<template>
  <Layout>
    <Head :title="worker.full_name" />

    <PageHeader title="Dettaglio lavoratore" pageTitle="SicurezzaChiara" />

    <BAlert v-if="$page.props.flash.success" show variant="success" class="mb-4">
      <i class="ri-checkbox-circle-line me-2 align-middle"></i>
      {{ $page.props.flash.success }}
    </BAlert>

    <BCard no-body class="overflow-hidden mb-4">
      <BCardBody class="p-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
          <div class="d-flex align-items-start gap-3 flex-grow-1">
            <span class="avatar-sm flex-shrink-0">
              <span class="avatar-title rounded-circle bg-secondary-subtle text-secondary fs-20">
                <i class="ri-team-line"></i>
              </span>
            </span>
            <div class="flex-grow-1">
              <h2 class="mb-1">{{ worker.full_name }}</h2>
              <div class="text-muted mb-3">{{ worker.company?.name || "Azienda non disponibile" }}</div>
              <BRow class="g-3">
                <BCol v-for="item in headerMeta" :key="item.label" md="6" xl="3">
                  <div class="border rounded-3 px-3 py-2 h-100 bg-light-subtle">
                    <div class="d-flex align-items-center gap-2 text-muted fs-13 mb-1">
                      <i :class="item.icon"></i>
                      <span>{{ item.label }}</span>
                    </div>
                    <div class="fw-medium">{{ item.value }}</div>
                  </div>
                </BCol>
              </BRow>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
            <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data" :href="route('workers.edit', worker.id)" class="btn btn-soft-secondary rounded-circle btn-icon" title="Modifica lavoratore">
              <i class="ri-pencil-line"></i>
            </Link>
            <Link :href="contextBridge.actions.riskProfileRoute" class="btn btn-primary">
              {{ contextBridge.suggestedAction.label }}
            </Link>
            <Link :href="contextBridge.actions.companyRoute" class="btn btn-soft-secondary">
              Apri azienda
            </Link>
            <Link :href="contextBridge.actions.workersRoute" class="btn btn-soft-secondary">
              Torna ai lavoratori
            </Link>
          </div>
        </div>

        <div class="border rounded-3 px-3 py-3 bg-light-subtle">
          <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
              <div class="text-uppercase text-muted fw-medium fs-12 mb-1">Focus operativo</div>
              <div class="fw-semibold mb-1">{{ contextBridge.suggestedAction.label }}</div>
              <div class="text-muted fs-13">{{ contextBridge.suggestedAction.helper }}</div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span v-for="item in governanceHighlights" :key="item.label" class="badge" :class="item.badgeClass">
                {{ item.value }} {{ item.label.toLowerCase() }}
              </span>
            </div>
          </div>
        </div>
      </BCardBody>
    </BCard>

    <BCard no-body>
      <BCardHeader class="border-0 pb-0">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <h4 class="card-title mb-1">Profilo lavoratore</h4>
            <p class="text-muted mb-0">Anagrafica, sorgenti operative e governo del rischio nello stesso punto.</p>
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span v-for="badge in workerOverviewBadges" :key="badge" class="badge bg-light text-body">{{ badge }}</span>
          </div>
        </div>

        <ul class="nav nav-tabs nav-tabs-custom nav-success" role="tablist">
          <li v-for="tab in workspaceTabs" :key="tab.key" class="nav-item">
            <button type="button" class="nav-link" :class="{ active: activeWorkspaceTab === tab.key }" @click="activeWorkspaceTab = tab.key">
              <i :class="`${tab.icon} align-bottom me-1`"></i>{{ tab.label }}
            </button>
          </li>
        </ul>
      </BCardHeader>

      <BCardBody class="pt-4">
        <div v-show="activeWorkspaceTab === 'anagrafica'">
          <BRow class="g-4">
            <BCol xl="8">
              <BCard no-body class="border h-100">
                <BCardHeader class="border-0">
                  <div>
                    <h5 class="mb-1">Contesto del lavoratore</h5>
                    <p class="text-muted mb-0">Mansioni, macchinari e luoghi collegati rendono leggibile il rischio atteso del lavoratore.</p>
                  </div>
                </BCardHeader>
                <BCardBody class="pt-0">
                  <BRow class="g-3 mb-4">
                    <BCol v-for="card in summaryCards" :key="card.label" md="6" xl="3">
                      <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                        <div class="text-uppercase fw-medium text-muted fs-12 mb-2">{{ card.label }}</div>
                        <h4 class="mb-0">{{ card.value }}</h4>
                      </div>
                    </BCol>
                  </BRow>

                  <BRow class="g-4">
                    <BCol xl="7">
                      <div v-if="coreStarterPack.families.length === 0" class="text-muted">
                        Nessuna sorgente core ancora leggibile nel contesto del lavoratore.
                      </div>
                      <div v-else class="table-responsive">
                        <table class="table align-middle table-nowrap mb-0">
                          <thead class="table-light">
                            <tr>
                              <th>Famiglia</th>
                              <th>Sorgente</th>
                              <th>Origine</th>
                              <th>Rischi core suggeriti</th>
                            </tr>
                          </thead>
                          <tbody>
                            <template v-for="family in coreStarterPack.families" :key="family.key">
                              <tr v-for="item in family.items" :key="`${family.key}-${item.id}`">
                                <td>{{ family.label }}</td>
                                <td>
                                  <div class="fw-semibold">{{ item.name }}</div>
                                  <div class="text-muted fs-13">{{ item.description || "Descrizione non disponibile." }}</div>
                                </td>
                                <td>
                                  <span class="badge" :class="sourceBadge[item.source] || 'bg-light text-body'">
                                    {{ item.source === "core" ? "Core" : "Tenant" }}
                                  </span>
                                </td>
                                <td>
                                  <div v-if="item.linked_risks.length" class="d-flex flex-wrap gap-2">
                                    <span v-for="risk in item.linked_risks" :key="`${item.id}-${risk.id}`" class="badge bg-light text-body">
                                      {{ risk.name }}
                                    </span>
                                  </div>
                                  <span v-else class="text-muted fs-13">Nessun mapping core esplicito</span>
                                </td>
                              </tr>
                            </template>
                          </tbody>
                        </table>
                      </div>
                    </BCol>
                    <BCol xl="5">
                      <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                        <h6 class="mb-3">Rischi core suggeriti dal contesto</h6>
                        <div v-if="coreStarterPack.suggestedRisks.length === 0" class="text-muted">
                          Nessun rischio core ancora leggibile dal contesto del lavoratore.
                        </div>
                        <div v-else class="vstack gap-3">
                          <div v-for="risk in coreStarterPack.suggestedRisks.slice(0, 6)" :key="risk.id" class="border rounded p-3 bg-white">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                              <div>
                                <div class="fw-semibold">{{ risk.name }}</div>
                                <div class="text-muted fs-13">{{ risk.category_name || 'Categoria non disponibile' }}</div>
                              </div>
                              <span class="badge" :class="starterPriorityBadge[risk.default_priority] || 'bg-light text-body'">
                                {{ risk.default_priority === "high" ? "Alta" : risk.default_priority === "medium" ? "Media" : "Bassa" }}
                              </span>
                            </div>
                            <div class="text-muted fs-13 mt-2">
                              {{ risk.expected_measures_count }} presidi attesi | {{ risk.trigger_count }} trigger sorgente
                            </div>
                          </div>
                        </div>
                      </div>
                    </BCol>
                  </BRow>
                </BCardBody>
              </BCard>
            </BCol>

            <BCol xl="4">
              <BCard no-body class="border h-100">
                <BCardHeader class="border-0">
                  <h5 class="mb-0">Anagrafica essenziale</h5>
                </BCardHeader>
                <BCardBody class="pt-0">
                  <div class="vstack gap-3">
                    <div>
                      <span class="text-muted d-block fs-13">Azienda</span>
                      <span class="fw-medium">{{ worker.company?.name || "Non disponibile" }}</span>
                    </div>
                    <div>
                      <span class="text-muted d-block fs-13">Sede prevalente</span>
                      <span class="fw-medium">{{ worker.primary_site?.name || "Non assegnata" }}</span>
                    </div>
                    <div>
                      <span class="text-muted d-block fs-13">Localita'</span>
                      <span class="fw-medium">
                        {{ worker.primary_site?.city || worker.company?.city || "Non indicata" }}<template v-if="worker.primary_site?.province || worker.company?.province"> ({{ worker.primary_site?.province || worker.company?.province }})</template>
                      </span>
                    </div>
                    <div class="border-top pt-3">
                      <span class="text-muted d-block fs-13 mb-2">Note operative</span>
                      <p class="mb-0 text-muted">{{ worker.notes || "Nessuna nota operativa presente." }}</p>
                    </div>
                  </div>
                </BCardBody>
              </BCard>
            </BCol>
          </BRow>
        </div>

        <div v-show="activeWorkspaceTab === 'rischi'">
          <BRow class="g-4">
            <BCol xl="8">
              <BCard no-body class="border h-100">
                <BCardHeader class="border-0">
                  <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                    <div>
                      <h5 class="mb-1">Rischi nel profilo</h5>
                      <p class="text-muted mb-0">Il motore rende leggibili i rischi gia' presenti sul lavoratore.</p>
                    </div>
                    <Link :href="contextBridge.actions.riskProfileRoute" class="btn btn-soft-primary btn-sm">Apri profilo rischio</Link>
                  </div>
                </BCardHeader>
                <BCardBody class="pt-0">
                  <BRow class="g-3 mb-4">
                    <BCol md="6" xl="4">
                      <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                        <div class="text-uppercase fw-medium text-muted fs-12 mb-2">Rischi attivi</div>
                        <h4 class="mb-0">{{ governanceContext.summary.activeRisks }}</h4>
                      </div>
                    </BCol>
                    <BCol md="6" xl="4">
                      <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                        <div class="text-uppercase fw-medium text-muted fs-12 mb-2">Review dovute</div>
                        <h4 class="mb-0">{{ governanceContext.summary.reviewsDue }}</h4>
                      </div>
                    </BCol>
                    <BCol md="6" xl="4">
                      <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                        <div class="text-uppercase fw-medium text-muted fs-12 mb-2">Gap attesi</div>
                        <h4 class="mb-0">{{ contextBridge.stats.missingExpectedMeasures }}</h4>
                      </div>
                    </BCol>
                  </BRow>
                  <div v-if="governanceContext.reviewAlerts.length === 0" class="text-muted">
                    Nessun segnale di rischio aperto nel profilo del lavoratore.
                  </div>
                  <div v-else class="vstack gap-3">
                    <div v-for="item in governanceContext.reviewAlerts" :key="item.id" class="border rounded-3 p-3 bg-light-subtle">
                      <div class="fw-semibold mb-1">{{ item.riskName || "Rischio non disponibile" }}</div>
                      <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                        <span v-if="item.reviewDueAt" class="badge bg-warning-subtle text-warning">Review entro {{ item.reviewDueAt }}</span>
                        <span v-if="item.followUpStatus" class="badge" :class="followUpBadges[item.followUpStatus] || 'bg-light text-body'">
                          Follow-up {{ followUpLabels[item.followUpStatus] || item.followUpStatus }}
                        </span>
                      </div>
                    </div>
                  </div>
                </BCardBody>
              </BCard>
            </BCol>
            <BCol xl="4">
              <BCard no-body class="border h-100">
                <BCardHeader class="border-0">
                  <h5 class="mb-0">Sintesi del rischio</h5>
                </BCardHeader>
                <BCardBody class="pt-0">
                  <div v-if="coreStarterPack.suggestedRisks.length === 0" class="text-muted">
                    Nessun rischio ancora leggibile dal contesto del lavoratore.
                  </div>
                  <div v-else class="vstack gap-3">
                    <div v-for="risk in coreStarterPack.suggestedRisks.slice(0, 5)" :key="risk.id" class="border rounded-3 p-3 bg-light-subtle">
                      <div class="fw-semibold">{{ risk.name }}</div>
                      <div class="text-muted fs-13">{{ risk.category_name || "Categoria non disponibile" }}</div>
                    </div>
                  </div>
                </BCardBody>
              </BCard>
            </BCol>
          </BRow>
        </div>

        <div v-show="activeWorkspaceTab === 'misure'">
          <BRow class="g-4">
            <BCol xl="8">
              <BCard no-body class="border h-100">
                <BCardHeader class="border-0">
                  <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                    <div>
                      <h5 class="mb-1">Misure collegate</h5>
                      <p class="text-muted mb-0">Presidi gia' presenti o attesi nel contesto del lavoratore.</p>
                    </div>
                    <Link :href="contextBridge.actions.registryRoute" class="btn btn-soft-secondary btn-sm">Apri registri azienda</Link>
                  </div>
                </BCardHeader>
                <BCardBody class="pt-0">
                  <div v-if="governanceContext.previewMeasures.length === 0" class="text-center py-4">
                    <h5 class="mb-2">Nessuna misura disponibile</h5>
                    <p class="text-muted mb-0">Il profilo del lavoratore non mostra ancora misure operative collegate.</p>
                  </div>
                  <div v-else class="table-responsive">
                    <table class="table align-middle mb-0">
                      <thead class="table-light">
                        <tr>
                          <th>Misura</th>
                          <th>Rischio</th>
                          <th>Famiglia</th>
                          <th>Stato</th>
                          <th>Scadenza</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="measure in governanceContext.previewMeasures" :key="measure.id">
                          <td class="fw-semibold">{{ measure.title }}</td>
                          <td>{{ measure.riskName || "Rischio non disponibile" }}</td>
                          <td>{{ measure.family || "N.D." }}</td>
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
                </BCardBody>
              </BCard>
            </BCol>
            <BCol xl="4">
              <BCard no-body class="border h-100">
                <BCardHeader class="border-0">
                  <h5 class="mb-0">Sintesi misure</h5>
                </BCardHeader>
                <BCardBody class="pt-0">
                  <div class="vstack gap-3">
                    <div class="border rounded-3 p-3 bg-light-subtle">
                      <div class="text-muted fs-13 mb-1">Misure collegate</div>
                      <div class="fw-semibold fs-4">{{ governanceContext.summary.totalMeasures }}</div>
                    </div>
                    <div class="border rounded-3 p-3 bg-light-subtle">
                      <div class="text-muted fs-13 mb-1">Da verificare</div>
                      <div class="fw-semibold fs-4">{{ governanceContext.summary.toVerifyMeasures }}</div>
                    </div>
                    <div class="border rounded-3 p-3 bg-light-subtle">
                      <div class="text-muted fs-13 mb-1">Attese mancanti</div>
                      <div class="fw-semibold fs-4">{{ contextBridge.stats.missingExpectedMeasures }}</div>
                    </div>
                  </div>
                </BCardBody>
              </BCard>
            </BCol>
          </BRow>
        </div>

        <div v-show="activeWorkspaceTab === 'scadenze'">
          <BRow class="g-4">
            <BCol xl="8">
              <BCard no-body class="border h-100">
                <BCardHeader class="border-0">
                  <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                    <div>
                      <h5 class="mb-1">Scadenze e follow-up</h5>
                      <p class="text-muted mb-0">Review e attenzioni ancora aperte sul lavoratore.</p>
                    </div>
                    <Link :href="contextBridge.actions.riskProfileRoute" class="btn btn-soft-primary btn-sm">Apri review</Link>
                  </div>
                </BCardHeader>
                <BCardBody class="pt-0">
                  <div v-if="governanceContext.reviewAlerts.length === 0" class="text-muted">
                    Nessuna scadenza o follow-up aperto nel profilo del lavoratore.
                  </div>
                  <div v-else class="vstack gap-3">
                    <div v-for="item in governanceContext.reviewAlerts" :key="`${item.id}-deadline`" class="border rounded-3 p-3 bg-light-subtle">
                      <div class="fw-semibold mb-1">{{ item.riskName || "Rischio non disponibile" }}</div>
                      <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                        <span v-if="item.reviewDueAt" class="badge bg-warning-subtle text-warning">Review entro {{ item.reviewDueAt }}</span>
                        <span v-if="item.followUpStatus" class="badge" :class="followUpBadges[item.followUpStatus] || 'bg-light text-body'">
                          Follow-up {{ followUpLabels[item.followUpStatus] || item.followUpStatus }}
                        </span>
                      </div>
                      <div class="text-muted fs-13">Il governo operativo resta nel profilo rischio lavoratore e nei registri aziendali.</div>
                    </div>
                  </div>
                </BCardBody>
              </BCard>
            </BCol>
            <BCol xl="4">
              <BCard no-body class="border h-100">
                <BCardHeader class="border-0">
                  <h5 class="mb-0">Stato scadenze</h5>
                </BCardHeader>
                <BCardBody class="pt-0">
                  <div class="vstack gap-3">
                    <div class="border rounded-3 p-3 bg-light-subtle">
                      <div class="text-muted fs-13 mb-1">Review dovute</div>
                      <div class="fw-semibold fs-4">{{ governanceContext.summary.reviewsDue }}</div>
                    </div>
                    <div class="border rounded-3 p-3 bg-light-subtle">
                      <div class="text-muted fs-13 mb-1">Follow-up aperti</div>
                      <div class="fw-semibold fs-4">{{ governanceContext.summary.followUpsOpen }}</div>
                    </div>
                    <div class="border rounded-3 p-3 bg-light-subtle">
                      <div class="text-muted fs-13 mb-1">Misure scadute</div>
                      <div class="fw-semibold fs-4">{{ governanceContext.summary.overdueMeasures }}</div>
                    </div>
                  </div>
                </BCardBody>
              </BCard>
            </BCol>
          </BRow>
        </div>

        <div v-show="activeWorkspaceTab === 'mansioni'">
          <BCard no-body>
      <BCardHeader class="border-0">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <h4 class="card-title mb-1">Mansioni assegnate</h4>
            <p class="text-muted mb-0">Qui il catalogo diventa istanza operativa reale del lavoratore.</p>
          </div>
          <Link :href="route('job-roles.index')" class="btn btn-soft-secondary btn-sm">Apri catalogo</Link>
        </div>
      </BCardHeader>
      <BCardBody class="pt-0">
        <BRow class="g-4">
          <BCol xl="7">
            <div v-if="worker.job_role_assignments.length === 0" class="text-center py-4">
              <div class="avatar-md mx-auto mb-3">
                <div class="avatar-title bg-light text-primary rounded-circle fs-2">
                  <i class="ri-briefcase-line"></i>
                </div>
              </div>
              <h5 class="mb-2">Nessuna mansione assegnata</h5>
              <p class="text-muted mb-0">Assegna almeno una mansione per iniziare a leggere il lavoratore come sorgente del rischio.</p>
            </div>
            <div v-else class="table-responsive">
              <table class="table align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Mansione</th>
                    <th>Origine</th>
                    <th>Stato</th>
                    <th>Data</th>
                    <th class="text-end">Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="assignment in worker.job_role_assignments" :key="assignment.id">
                    <td>
                      <div class="fw-semibold">{{ assignment.job_role?.name }}</div>
                      <div class="text-muted fs-13">{{ assignment.notes || "Nessuna nota" }}</div>
                    </td>
                    <td>
                      <span v-if="assignment.job_role?.source === 'tenant'" class="badge bg-primary-subtle text-primary">Tenant</span>
                      <span v-else class="badge bg-light text-body">Core</span>
                    </td>
                    <td>
                      <span v-if="assignment.is_primary" class="badge bg-success-subtle text-success">Prevalente</span>
                      <span v-else class="badge bg-light text-body">Secondaria</span>
                    </td>
                    <td>{{ assignment.assigned_on || "Non indicata" }}</td>
                    <td class="text-end">
                      <div class="hstack gap-2 justify-content-end">
                        <button v-if="$page.props.tenantContext?.permissions?.can_manage_data && !assignment.is_primary" type="button" class="btn btn-soft-primary btn-sm" @click="setPrimary(assignment)">
                          Rendi prevalente
                        </button>
                        <button v-if="$page.props.tenantContext?.permissions?.can_manage_data" type="button" class="btn btn-soft-danger btn-sm" @click="removeAssignment(assignment)">
                          Rimuovi
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </BCol>
          <BCol v-if="$page.props.tenantContext?.permissions?.can_manage_data" xl="5">
            <BCard no-body class="border">
              <BCardHeader class="bg-light-subtle border-0">
                <h5 class="mb-0">Assegna mansione</h5>
              </BCardHeader>
              <BCardBody>
                <form @submit.prevent="submitAssignment">
                  <div class="mb-3">
                    <label for="job_role_id" class="form-label">Mansione *</label>
                    <select id="job_role_id" v-model="assignmentForm.job_role_id" class="form-select" :class="{ 'is-invalid': assignmentForm.errors.job_role_id }">
                      <option value="">Seleziona mansione</option>
                      <option v-for="jobRole in jobRoleOptions" :key="jobRole.id" :value="jobRole.id">
                        {{ jobRole.name }}{{ jobRole.source === "core" ? " - core" : "" }}
                      </option>
                    </select>
                    <div v-if="assignmentForm.errors.job_role_id" class="invalid-feedback d-block">{{ assignmentForm.errors.job_role_id }}</div>
                  </div>
                  <div class="mb-3">
                    <label for="assigned_on" class="form-label">Data assegnazione</label>
                    <input id="assigned_on" v-model="assignmentForm.assigned_on" type="date" class="form-control" :class="{ 'is-invalid': assignmentForm.errors.assigned_on }" />
                    <div v-if="assignmentForm.errors.assigned_on" class="invalid-feedback d-block">{{ assignmentForm.errors.assigned_on }}</div>
                  </div>
                  <div class="mb-3">
                    <label for="assignment_notes" class="form-label">Note operative</label>
                    <textarea id="assignment_notes" v-model="assignmentForm.notes" rows="3" class="form-control" :class="{ 'is-invalid': assignmentForm.errors.notes }"></textarea>
                    <div v-if="assignmentForm.errors.notes" class="invalid-feedback d-block">{{ assignmentForm.errors.notes }}</div>
                  </div>
                  <div class="form-check form-switch form-switch-md mb-3">
                    <input id="assignment_primary" v-model="assignmentForm.is_primary" class="form-check-input" type="checkbox" />
                    <label class="form-check-label" for="assignment_primary">Imposta come mansione prevalente</label>
                  </div>
                  <BButton variant="primary" type="submit" :disabled="assignmentForm.processing">Aggiungi mansione</BButton>
                </form>
              </BCardBody>
            </BCard>
          </BCol>
        </BRow>
      </BCardBody>
          </BCard>
        </div>

        <div v-show="activeWorkspaceTab === 'macchinari'">
          <BCard no-body>
      <BCardHeader class="border-0">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <h4 class="card-title mb-1">Macchinari associati</h4>
            <p class="text-muted mb-0">I macchinari collegano il lavoratore a istanze operative reali presenti in azienda.</p>
          </div>
          <Link :href="route('equipment-assets.index')" class="btn btn-soft-secondary btn-sm">Apri registro</Link>
        </div>
      </BCardHeader>
      <BCardBody class="pt-0">
        <BRow class="g-4">
          <BCol xl="7">
            <div v-if="worker.equipment_exposures.length === 0" class="text-center py-4">
              <div class="avatar-md mx-auto mb-3">
                <div class="avatar-title bg-light text-primary rounded-circle fs-2">
                  <i class="ri-settings-3-line"></i>
                </div>
              </div>
              <h5 class="mb-2">Nessun macchinario associato</h5>
              <p class="text-muted mb-0">Collega il lavoratore ai macchinari aziendali per costruire il suo contesto operativo reale.</p>
            </div>
            <div v-else class="table-responsive">
              <table class="table align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Macchinario</th>
                    <th>Tipologia</th>
                    <th>Stato</th>
                    <th class="text-end">Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="exposure in worker.equipment_exposures" :key="exposure.id">
                    <td>
                      <div class="fw-semibold">{{ exposure.equipment_asset?.name }}</div>
                      <div class="text-muted fs-13">{{ exposure.notes || "Nessuna nota" }}</div>
                    </td>
                    <td>{{ exposure.equipment_asset?.equipment_type?.name || "Non disponibile" }}</td>
                    <td>
                      <span v-if="exposure.is_primary" class="badge bg-primary-subtle text-primary">Prevalente</span>
                      <span v-else class="badge bg-light text-body">Secondaria</span>
                    </td>
                    <td class="text-end">
                      <div class="hstack gap-2 justify-content-end">
                        <button v-if="$page.props.tenantContext?.permissions?.can_manage_data && !exposure.is_primary" type="button" class="btn btn-soft-primary btn-sm" @click="setPrimaryEquipmentExposure(exposure)">
                          Rendi prevalente
                        </button>
                        <button v-if="$page.props.tenantContext?.permissions?.can_manage_data" type="button" class="btn btn-soft-danger btn-sm" @click="removeEquipmentExposure(exposure)">
                          Rimuovi
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </BCol>
          <BCol v-if="$page.props.tenantContext?.permissions?.can_manage_data" xl="5">
            <BCard no-body class="border">
              <BCardHeader class="bg-light-subtle border-0">
                <h5 class="mb-0">Associa macchinario</h5>
              </BCardHeader>
              <BCardBody>
                <form @submit.prevent="submitEquipmentExposure">
                  <div class="mb-3">
                    <label for="equipment_asset_id" class="form-label">Macchinario *</label>
                    <select id="equipment_asset_id" v-model="equipmentExposureForm.equipment_asset_id" class="form-select" :class="{ 'is-invalid': equipmentExposureForm.errors.equipment_asset_id }">
                      <option value="">Seleziona macchinario</option>
                      <option v-for="asset in equipmentAssetOptions" :key="asset.id" :value="asset.id">
                        {{ asset.name }}{{ asset.equipment_type_name ? ` - ${asset.equipment_type_name}` : "" }}
                      </option>
                    </select>
                    <div v-if="equipmentExposureForm.errors.equipment_asset_id" class="invalid-feedback d-block">{{ equipmentExposureForm.errors.equipment_asset_id }}</div>
                  </div>
                  <div class="mb-3">
                    <label for="equipment_exposure_notes" class="form-label">Note operative</label>
                    <textarea id="equipment_exposure_notes" v-model="equipmentExposureForm.notes" rows="3" class="form-control" :class="{ 'is-invalid': equipmentExposureForm.errors.notes }"></textarea>
                    <div v-if="equipmentExposureForm.errors.notes" class="invalid-feedback d-block">{{ equipmentExposureForm.errors.notes }}</div>
                  </div>
                  <div class="form-check form-switch form-switch-md mb-3">
                    <input id="equipment_exposure_primary" v-model="equipmentExposureForm.is_primary" class="form-check-input" type="checkbox" />
                    <label class="form-check-label" for="equipment_exposure_primary">Imposta come esposizione prevalente</label>
                  </div>
                  <BButton variant="primary" type="submit" :disabled="equipmentExposureForm.processing">Aggiungi macchinario</BButton>
                </form>
              </BCardBody>
            </BCard>
          </BCol>
        </BRow>
      </BCardBody>
          </BCard>
        </div>

        <div v-show="activeWorkspaceTab === 'luoghi'">
          <BCard no-body>
      <BCardHeader class="border-0">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <h4 class="card-title mb-1">Luoghi associati</h4>
            <p class="text-muted mb-0">I luoghi descrivono gli ambienti in cui il lavoratore opera abitualmente o in modo secondario.</p>
          </div>
          <Link :href="route('workplaces.index')" class="btn btn-soft-secondary btn-sm">Apri registro</Link>
        </div>
      </BCardHeader>
      <BCardBody class="pt-0">
        <BRow class="g-4">
          <BCol xl="7">
            <div v-if="worker.workplace_exposures.length === 0" class="text-center py-4">
              <div class="avatar-md mx-auto mb-3">
                <div class="avatar-title bg-light text-info rounded-circle fs-2">
                  <i class="ri-map-pin-2-line"></i>
                </div>
              </div>
              <h5 class="mb-2">Nessun luogo associato</h5>
              <p class="text-muted mb-0">Collega il lavoratore ai luoghi operativi per leggere meglio il suo contesto reale.</p>
            </div>
            <div v-else class="table-responsive">
              <table class="table align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Luogo</th>
                    <th>Tipologia</th>
                    <th>Stato</th>
                    <th class="text-end">Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="exposure in worker.workplace_exposures" :key="exposure.id">
                    <td>
                      <div class="fw-semibold">{{ exposure.workplace?.name }}</div>
                      <div class="text-muted fs-13">{{ exposure.notes || "Nessuna nota" }}</div>
                    </td>
                    <td>{{ exposure.workplace?.workplace_type?.name || "Non disponibile" }}</td>
                    <td>
                      <span v-if="exposure.is_primary" class="badge bg-primary-subtle text-primary">Prevalente</span>
                      <span v-else class="badge bg-light text-body">Secondaria</span>
                    </td>
                    <td class="text-end">
                      <div class="hstack gap-2 justify-content-end">
                        <button v-if="$page.props.tenantContext?.permissions?.can_manage_data && !exposure.is_primary" type="button" class="btn btn-soft-primary btn-sm" @click="setPrimaryWorkplaceExposure(exposure)">
                          Rendi prevalente
                        </button>
                        <button v-if="$page.props.tenantContext?.permissions?.can_manage_data" type="button" class="btn btn-soft-danger btn-sm" @click="removeWorkplaceExposure(exposure)">
                          Rimuovi
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </BCol>
          <BCol v-if="$page.props.tenantContext?.permissions?.can_manage_data" xl="5">
            <BCard no-body class="border">
              <BCardHeader class="bg-light-subtle border-0">
                <h5 class="mb-0">Associa luogo</h5>
              </BCardHeader>
              <BCardBody>
                <form @submit.prevent="submitWorkplaceExposure">
                  <div class="mb-3">
                    <label for="workplace_id" class="form-label">Luogo *</label>
                    <select id="workplace_id" v-model="workplaceExposureForm.workplace_id" class="form-select" :class="{ 'is-invalid': workplaceExposureForm.errors.workplace_id }">
                      <option value="">Seleziona luogo</option>
                      <option v-for="workplace in workplaceOptions" :key="workplace.id" :value="workplace.id">
                        {{ workplace.name }}{{ workplace.site_name ? ` - ${workplace.site_name}` : "" }}
                      </option>
                    </select>
                    <div v-if="workplaceExposureForm.errors.workplace_id" class="invalid-feedback d-block">{{ workplaceExposureForm.errors.workplace_id }}</div>
                  </div>
                  <div class="mb-3">
                    <label for="workplace_exposure_notes" class="form-label">Note operative</label>
                    <textarea id="workplace_exposure_notes" v-model="workplaceExposureForm.notes" rows="3" class="form-control" :class="{ 'is-invalid': workplaceExposureForm.errors.notes }"></textarea>
                    <div v-if="workplaceExposureForm.errors.notes" class="invalid-feedback d-block">{{ workplaceExposureForm.errors.notes }}</div>
                  </div>
                  <div class="form-check form-switch form-switch-md mb-3">
                    <input id="workplace_exposure_primary" v-model="workplaceExposureForm.is_primary" class="form-check-input" type="checkbox" />
                    <label class="form-check-label" for="workplace_exposure_primary">Imposta come esposizione prevalente</label>
                  </div>
                  <BButton variant="primary" type="submit" :disabled="workplaceExposureForm.processing">Aggiungi luogo</BButton>
                </form>
              </BCardBody>
            </BCard>
          </BCol>
        </BRow>
      </BCardBody>
          </BCard>
        </div>
      </BCardBody>
    </BCard>
  </Layout>
</template>
