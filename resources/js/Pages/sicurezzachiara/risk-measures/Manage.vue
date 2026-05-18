<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

const props = defineProps({
  tenant: {
    type: Object,
    required: true,
  },
  parentType: {
    type: String,
    required: true,
  },
  parent: {
    type: Object,
    required: true,
  },
  profileItem: {
    type: Object,
    required: true,
  },
  measures: {
    type: Array,
    required: true,
  },
  expectedMeasures: {
    type: Object,
    required: true,
  },
  formOptions: {
    type: Object,
    required: true,
  },
  summary: {
    type: Object,
    required: true,
  },
  measureBridge: {
    type: Object,
    required: true,
  },
  copy: {
    type: Object,
    required: true,
  },
});

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

const queueToneBadgeClasses = {
  danger: "bg-danger-subtle text-danger",
  warning: "bg-warning-subtle text-warning",
  primary: "bg-primary-subtle text-primary",
  info: "bg-info-subtle text-info",
  success: "bg-success-subtle text-success",
  secondary: "bg-light text-body",
};

const queueToneButtonClasses = {
  danger: "btn-soft-danger",
  warning: "btn-soft-warning",
  primary: "btn-soft-primary",
  info: "btn-soft-info",
  success: "btn-soft-success",
  secondary: "btn-soft-secondary",
};

const form = useForm({
  family: props.formOptions.families[0]?.value ?? "organizational",
  title: "",
  description: "",
  status: "to_verify",
  details: {
    provider: "",
    delivery_mode: "",
    valid_until: "",
    physician: "",
    protocol: "",
    item_name: "",
    category: "",
    owner: "",
    verification_method: "",
  },
  expected_measure_code: "",
  due_date: "",
  notes: "",
});

const measuresBaseRoute =
  props.parentType === "company"
    ? route("companies.risk-profile.measures.show", [props.parent.id, props.profileItem.id])
    : route("workers.risk-profile.measures.show", [props.parent.id, props.profileItem.id]);

const submit = () => {
  form.post(measuresBaseRoute, {
    preserveScroll: true,
    onSuccess: () => {
      form.reset("title", "description", "due_date", "notes");
      form.status = "to_verify";
      form.family = props.formOptions.families[0]?.value ?? "organizational";
      form.details = {
        provider: "",
        delivery_mode: "",
        valid_until: "",
        physician: "",
        protocol: "",
        item_name: "",
        category: "",
        owner: "",
        verification_method: "",
      };
      form.expected_measure_code = "";
    },
  });
};

const backRoute =
  props.parentType === "company"
    ? route("companies.risk-profile.show", props.parent.id)
    : route("workers.risk-profile.show", props.parent.id);

const updateMeasureStatus = (measure, status) => {
  useForm({
    family: measure.family,
    expected_measure_code: measure.expected_measure_code ?? "",
    title: measure.title,
    description: measure.description ?? "",
    status,
    details: measure.details ?? {},
    due_date: measure.due_date ?? "",
    notes: measure.notes ?? "",
  }).put(`${measuresBaseRoute}/${measure.id}`, {
    preserveScroll: true,
  });
};

const removeMeasure = (measure) => {
  useForm({}).delete(`${measuresBaseRoute}/${measure.id}`, {
    preserveScroll: true,
  });
};

const isTraining = () => form.family === "training";
const isMedical = () => form.family === "medical";
const isDpi = () => form.family === "dpi";
const isOperational = () => ["organizational", "technical"].includes(form.family);

const applyExpectedMeasure = (expectedMeasure) => {
  form.family = expectedMeasure.family;
  form.title = expectedMeasure.title;
  form.description = expectedMeasure.description ?? "";
  form.expected_measure_code = expectedMeasure.code;
};
</script>

<template>
  <Layout>
    <Head :title="`Misure - ${profileItem.risk_catalog_item?.name}`" />

    <PageHeader title="Misure collegate al rischio" pageTitle="SicurezzaChiara" />

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
                <span class="badge bg-success-subtle text-success text-uppercase mb-3">Presidio operativo del rischio</span>
                <h2 class="mb-1">{{ profileItem.risk_catalog_item?.name }}</h2>
                <p class="text-muted mb-3">
                  {{ profileItem.risk_catalog_item?.description || "Descrizione rischio non disponibile." }}
                </p>
                <div class="hstack gap-2 flex-wrap">
                  <span class="badge bg-light text-body">{{ tenant.name }}</span>
                  <span class="badge bg-soft-secondary text-secondary">{{ parent.name }}</span>
                  <span class="badge bg-soft-danger text-danger">{{ profileItem.risk_catalog_item?.category?.name }}</span>
                  <span class="badge bg-soft-warning text-warning">
                    {{ profileItem.status === "covered" ? "Coperto" : "Da presidiare" }}
                  </span>
                </div>
              </div>
              <div class="hstack gap-2 flex-wrap">
                <Link :href="backRoute" class="btn btn-soft-secondary">
                  Torna al profilo rischio
                </Link>
                <Link v-if="measureBridge.actions.workerRoute" :href="measureBridge.actions.workerRoute" class="btn btn-soft-primary">
                  Apri lavoratore
                </Link>
                <Link v-if="measureBridge.actions.companyRoute" :href="measureBridge.actions.companyRoute" class="btn btn-primary">
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
            <h4 class="card-title mb-0">Sintesi misure</h4>
          </BCardHeader>
          <BCardBody>
            <div class="vstack gap-3">
              <div>
                <span class="text-muted d-block fs-13">Misure totali</span>
                <span class="fw-semibold fs-5">{{ summary.totalMeasures }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Attuate</span>
                <span class="fw-semibold fs-5">{{ summary.implementedMeasures }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Da verificare</span>
                <span class="fw-semibold fs-5">{{ summary.toVerifyMeasures }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Non attuate</span>
                <span class="fw-semibold fs-5">{{ summary.notImplementedMeasures }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Presidi attesi</span>
                <span class="fw-semibold fs-5">{{ expectedMeasures.summary.expected_count }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Gap attesi</span>
                <span class="fw-semibold fs-5">{{ expectedMeasures.summary.missing_count + expectedMeasures.summary.partial_count }}</span>
              </div>
              <div v-if="expectedMeasures.summary.substituted_count">
                <span class="text-muted d-block fs-13">Coperture per sostituzione</span>
                <span class="fw-semibold fs-5">{{ expectedMeasures.summary.substituted_count }}</span>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4">
      <BCol xl="7">
        <BCard no-body class="mb-4">
          <BCardBody class="p-4">
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
              <div>
                <span class="badge bg-info-subtle text-info text-uppercase mb-2">Bridge operativo del rischio</span>
                <h4 class="mb-1">{{ measureBridge.decision.label }}</h4>
                <p class="text-muted mb-0">{{ measureBridge.decision.helper }}</p>
                <div v-if="measureBridge.decision.laneLabel" class="mt-2">
                  <span class="badge" :class="queueToneBadgeClasses[measureBridge.decision.tone] || 'bg-light text-body'">
                    {{ measureBridge.decision.laneLabel }}
                  </span>
                </div>
              </div>
              <div class="hstack gap-2 flex-wrap">
                <Link
                  :href="measureBridge.decision.actionRoute || measureBridge.actions.reviewRoute"
                  class="btn btn-sm"
                  :class="queueToneButtonClasses[measureBridge.decision.tone] || 'btn-soft-primary'"
                >
                  {{ measureBridge.decision.actionLabel || "Torna alla review" }}
                </Link>
                <Link :href="measureBridge.actions.workspaceRoute" class="btn btn-soft-info btn-sm">Apri registri</Link>
                <Link v-if="measureBridge.actions.workerRoute" :href="measureBridge.actions.workerRoute" class="btn btn-soft-primary btn-sm">Apri lavoratore</Link>
                <Link v-if="measureBridge.actions.companyRoute" :href="measureBridge.actions.companyRoute" class="btn btn-soft-secondary btn-sm">Apri azienda</Link>
              </div>
            </div>
            <BRow class="g-3">
              <BCol md="4">
                <div class="border rounded p-3 h-100">
                  <div class="text-uppercase text-muted fs-12 fw-semibold mb-2">Misure pendenti</div>
                  <div class="fw-semibold fs-4">{{ measureBridge.stats.pendingMeasures }}</div>
                  <div class="text-muted fs-13">Tra non attuate e ancora da verificare.</div>
                </div>
              </BCol>
              <BCol md="4">
                <div class="border rounded p-3 h-100">
                  <div class="text-uppercase text-muted fs-12 fw-semibold mb-2">Gap attesi</div>
                  <div class="fw-semibold fs-4">{{ measureBridge.stats.gapCount }}</div>
                  <div class="text-muted fs-13">{{ measureBridge.coverageLabel }}</div>
                </div>
              </BCol>
              <BCol md="4">
                <div class="border rounded p-3 h-100">
                  <div class="text-uppercase text-muted fs-12 fw-semibold mb-2">Presidi attuati</div>
                  <div class="fw-semibold fs-4">{{ measureBridge.stats.implementedMeasures }}</div>
                  <div class="text-muted fs-13">{{ measureBridge.stats.expectedMeasures }} attesi dal motore su questo rischio.</div>
                </div>
              </BCol>
            </BRow>
            <div v-if="measureBridge.operationalQueue?.length" class="border-top pt-3 mt-3">
              <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-2">
                <div>
                  <h6 class="mb-1">Corsie operative delle misure</h6>
                  <p class="text-muted mb-0 fs-13">
                    Questa pagina resta la corsia di copertura del rischio, ma il passo finale va chiuso tornando in review o nei registri contestuali.
                  </p>
                </div>
              </div>
              <div class="d-flex align-items-stretch gap-2 flex-wrap">
                <Link
                  v-for="item in measureBridge.operationalQueue"
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
          <BCardHeader class="border-0">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
              <div>
                <h4 class="card-title mb-1">Presidi attesi dal rischio</h4>
                <p class="text-muted mb-0">
                  {{ copy.expectedMeasuresHelper }}
                </p>
              </div>
              <span class="badge bg-soft-warning text-warning">
                {{ expectedMeasures.summary.expected_count }} attesi
              </span>
            </div>
          </BCardHeader>
          <BCardBody class="pt-0">
            <div v-if="expectedMeasures.summary.expected_count === 0" class="text-muted">
              Nessun presidio atteso configurato su questo rischio. La copertura continuera' a leggere le sole misure registrate.
            </div>
            <div v-else class="table-responsive">
              <table class="table align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Presidio atteso</th>
                    <th>Famiglia</th>
                    <th>Stato</th>
                    <th>Misure collegate</th>
                    <th class="text-end">Azione</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="expectedMeasure in expectedMeasures.templates" :key="expectedMeasure.code">
                    <td>
                      <div class="fw-semibold">{{ expectedMeasure.title }}</div>
                      <div class="text-muted fs-13">{{ expectedMeasure.description || "Nessuna descrizione aggiuntiva." }}</div>
                    </td>
                    <td>
                      <span class="badge bg-light text-body">{{ familyLabels[expectedMeasure.family] || expectedMeasure.family }}</span>
                    </td>
                    <td>
                      <span class="badge" :class="expectedMeasure.status === 'covered' ? 'bg-success-subtle text-success' : expectedMeasure.status === 'partial' ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger'">
                        {{
                          expectedMeasure.status === "covered"
                            ? "Coperto"
                            : expectedMeasure.status === "partial"
                              ? "Parziale"
                            : "Mancante"
                        }}
                      </span>
                      <div v-if="expectedMeasure.coverage_mode === 'family_substitution'" class="text-info fs-13 mt-1">
                        Copertura per sostituzione di famiglia
                      </div>
                      <div v-else-if="expectedMeasure.allows_family_substitution" class="text-muted fs-13 mt-1">
                        Ammette equivalenze della stessa famiglia
                      </div>
                    </td>
                    <td>
                      <span v-if="expectedMeasure.linked_measures_count" class="text-muted fs-13">
                        {{ expectedMeasure.linked_measures_count }} misura/e collegate
                      </span>
                      <span v-else class="text-muted fs-13">Nessuna misura collegata</span>
                    </td>
                    <td class="text-end">
                      <button v-if="$page.props.tenantContext?.permissions?.can_manage_data" type="button" class="btn btn-soft-primary btn-sm" @click="applyExpectedMeasure(expectedMeasure)">
                        Usa come preset
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </BCardBody>
        </BCard>

        <BCard no-body>
          <BCardHeader class="border-0">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
              <div>
                <h4 class="card-title mb-1">Misure gia' collegate</h4>
                <p class="text-muted mb-0">
                  {{ copy.linkedMeasuresHelper }}
                </p>
              </div>
              <span class="badge bg-soft-success text-success">{{ summary.totalMeasures }} misure</span>
            </div>
          </BCardHeader>
          <BCardBody class="pt-0">
            <div v-if="measures.length === 0" class="text-center py-5">
              <div class="avatar-md mx-auto mb-3">
                <div class="avatar-title bg-light text-success rounded-circle fs-2">
                  <i class="ri-shield-check-line"></i>
                </div>
              </div>
              <h5 class="mb-2">Nessuna misura ancora collegata</h5>
              <p class="text-muted mb-0">
                Inserisci la prima misura per rendere verificabile la copertura di questo rischio.
              </p>
            </div>

            <div v-else class="table-responsive">
              <table class="table align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Misura</th>
                    <th>Famiglia</th>
                    <th>Stato</th>
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
                      <div v-if="measure.expected_measure_code" class="text-muted fs-13">
                        Copre presidio atteso: {{ measure.expected_measure_code }}
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-light text-body">
                        {{ familyLabels[measure.family] || measure.family }}
                      </span>
                    </td>
                    <td>
                      <span class="badge" :class="statusBadges[measure.status] || 'bg-light text-body'">
                        {{ statusLabels[measure.status] || measure.status }}
                      </span>
                    </td>
                    <td>{{ measure.due_date || "Non definita" }}</td>
                    <td class="text-end">
                      <div class="hstack gap-2 justify-content-end flex-wrap">
                        <button v-if="$page.props.tenantContext?.permissions?.can_manage_data" type="button" class="btn btn-soft-success btn-sm" @click="updateMeasureStatus(measure, 'implemented')">
                          Attuata
                        </button>
                        <button v-if="$page.props.tenantContext?.permissions?.can_manage_data" type="button" class="btn btn-soft-warning btn-sm" @click="updateMeasureStatus(measure, 'to_verify')">
                          Verifica
                        </button>
                        <button v-if="$page.props.tenantContext?.permissions?.can_manage_data" type="button" class="btn btn-soft-danger btn-sm" @click="updateMeasureStatus(measure, 'not_implemented')">
                          Non attuata
                        </button>
                        <button v-if="$page.props.tenantContext?.permissions?.can_manage_data" type="button" class="btn btn-soft-secondary btn-sm" @click="removeMeasure(measure)">
                          Rimuovi
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </BCardBody>
        </BCard>
      </BCol>

      <BCol v-if="$page.props.tenantContext?.permissions?.can_manage_data" xl="5">
        <BCard no-body>
          <BCardHeader class="border-0">
            <h4 class="card-title mb-1">Nuova misura</h4>
            <p class="text-muted mb-0">{{ copy.newMeasureHelper }}</p>
          </BCardHeader>
          <BCardBody>
            <form @submit.prevent="submit" class="vstack gap-3">
              <div>
                <label class="form-label">Famiglia misura</label>
                <select v-model="form.family" class="form-select" :class="{ 'is-invalid': form.errors.family }">
                  <option v-for="family in formOptions.families" :key="family.value" :value="family.value">
                    {{ family.label }}
                  </option>
                </select>
                <div v-if="form.errors.family" class="invalid-feedback d-block">{{ form.errors.family }}</div>
              </div>

              <div v-if="expectedMeasures.summary.expected_count > 0">
                <label class="form-label">Collega a presidio atteso</label>
                <select v-model="form.expected_measure_code" class="form-select" :class="{ 'is-invalid': form.errors.expected_measure_code }">
                  <option value="">Misura libera / non agganciata</option>
                  <option v-for="expectedMeasure in expectedMeasures.templates" :key="expectedMeasure.code" :value="expectedMeasure.code">
                    {{ expectedMeasure.title }} - {{ familyLabels[expectedMeasure.family] || expectedMeasure.family }}
                  </option>
                </select>
                <div v-if="form.errors.expected_measure_code" class="invalid-feedback d-block">{{ form.errors.expected_measure_code }}</div>
              </div>

              <div>
                <label class="form-label">Titolo</label>
                <input v-model="form.title" type="text" class="form-control" :class="{ 'is-invalid': form.errors.title }" />
                <div v-if="form.errors.title" class="invalid-feedback d-block">{{ form.errors.title }}</div>
              </div>

              <div>
                <label class="form-label">Descrizione</label>
                <textarea v-model="form.description" rows="3" class="form-control" :class="{ 'is-invalid': form.errors.description }"></textarea>
                <div v-if="form.errors.description" class="invalid-feedback d-block">{{ form.errors.description }}</div>
              </div>

              <BRow v-if="isTraining()" class="g-3">
                <BCol md="6">
                  <label class="form-label">Ente / provider</label>
                  <input v-model="form.details.provider" type="text" class="form-control" :class="{ 'is-invalid': form.errors['details.provider'] }" />
                  <div v-if="form.errors['details.provider']" class="invalid-feedback d-block">{{ form.errors["details.provider"] }}</div>
                </BCol>
                <BCol md="6">
                  <label class="form-label">Modalita'</label>
                  <input v-model="form.details.delivery_mode" type="text" class="form-control" placeholder="Aula, affiancamento, e-learning" :class="{ 'is-invalid': form.errors['details.delivery_mode'] }" />
                  <div v-if="form.errors['details.delivery_mode']" class="invalid-feedback d-block">{{ form.errors["details.delivery_mode"] }}</div>
                </BCol>
                <BCol md="12">
                  <label class="form-label">Validita' fino al</label>
                  <input v-model="form.details.valid_until" type="date" class="form-control" :class="{ 'is-invalid': form.errors['details.valid_until'] }" />
                  <div v-if="form.errors['details.valid_until']" class="invalid-feedback d-block">{{ form.errors["details.valid_until"] }}</div>
                </BCol>
              </BRow>

              <BRow v-if="isMedical()" class="g-3">
                <BCol md="6">
                  <label class="form-label">Medico competente</label>
                  <input v-model="form.details.physician" type="text" class="form-control" :class="{ 'is-invalid': form.errors['details.physician'] }" />
                  <div v-if="form.errors['details.physician']" class="invalid-feedback d-block">{{ form.errors["details.physician"] }}</div>
                </BCol>
                <BCol md="6">
                  <label class="form-label">Protocollo</label>
                  <input v-model="form.details.protocol" type="text" class="form-control" :class="{ 'is-invalid': form.errors['details.protocol'] }" />
                  <div v-if="form.errors['details.protocol']" class="invalid-feedback d-block">{{ form.errors["details.protocol"] }}</div>
                </BCol>
                <BCol md="12">
                  <label class="form-label">Scadenza idoneita'</label>
                  <input v-model="form.details.valid_until" type="date" class="form-control" :class="{ 'is-invalid': form.errors['details.valid_until'] }" />
                  <div v-if="form.errors['details.valid_until']" class="invalid-feedback d-block">{{ form.errors["details.valid_until"] }}</div>
                </BCol>
              </BRow>

              <BRow v-if="isDpi()" class="g-3">
                <BCol md="6">
                  <label class="form-label">Dispositivo</label>
                  <input v-model="form.details.item_name" type="text" class="form-control" :class="{ 'is-invalid': form.errors['details.item_name'] }" />
                  <div v-if="form.errors['details.item_name']" class="invalid-feedback d-block">{{ form.errors["details.item_name"] }}</div>
                </BCol>
                <BCol md="6">
                  <label class="form-label">Categoria</label>
                  <input v-model="form.details.category" type="text" class="form-control" placeholder="Protezione mani, occhi, udito..." :class="{ 'is-invalid': form.errors['details.category'] }" />
                  <div v-if="form.errors['details.category']" class="invalid-feedback d-block">{{ form.errors["details.category"] }}</div>
                </BCol>
                <BCol md="12">
                  <label class="form-label">Sostituzione / verifica</label>
                  <input v-model="form.details.valid_until" type="date" class="form-control" :class="{ 'is-invalid': form.errors['details.valid_until'] }" />
                  <div v-if="form.errors['details.valid_until']" class="invalid-feedback d-block">{{ form.errors["details.valid_until"] }}</div>
                </BCol>
              </BRow>

              <BRow v-if="isOperational()" class="g-3">
                <BCol md="6">
                  <label class="form-label">Responsabile presidio</label>
                  <input v-model="form.details.owner" type="text" class="form-control" :class="{ 'is-invalid': form.errors['details.owner'] }" />
                  <div v-if="form.errors['details.owner']" class="invalid-feedback d-block">{{ form.errors["details.owner"] }}</div>
                </BCol>
                <BCol md="6">
                  <label class="form-label">Metodo verifica</label>
                  <input v-model="form.details.verification_method" type="text" class="form-control" placeholder="Checklist, sopralluogo, audit interno" :class="{ 'is-invalid': form.errors['details.verification_method'] }" />
                  <div v-if="form.errors['details.verification_method']" class="invalid-feedback d-block">{{ form.errors["details.verification_method"] }}</div>
                </BCol>
              </BRow>

              <BRow class="g-3">
                <BCol md="6">
                  <label class="form-label">Stato iniziale</label>
                  <select v-model="form.status" class="form-select" :class="{ 'is-invalid': form.errors.status }">
                    <option v-for="status in formOptions.statuses" :key="status.value" :value="status.value">
                      {{ status.label }}
                    </option>
                  </select>
                  <div v-if="form.errors.status" class="invalid-feedback d-block">{{ form.errors.status }}</div>
                </BCol>
                <BCol md="6">
                  <label class="form-label">Data di riferimento</label>
                  <input v-model="form.due_date" type="date" class="form-control" :class="{ 'is-invalid': form.errors.due_date }" />
                  <div v-if="form.errors.due_date" class="invalid-feedback d-block">{{ form.errors.due_date }}</div>
                </BCol>
              </BRow>

              <div>
                <label class="form-label">Note operative</label>
                <textarea v-model="form.notes" rows="3" class="form-control" :class="{ 'is-invalid': form.errors.notes }"></textarea>
                <div v-if="form.errors.notes" class="invalid-feedback d-block">{{ form.errors.notes }}</div>
              </div>

              <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                  Collega misura
                </button>
              </div>
            </form>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>
  </Layout>
</template>
