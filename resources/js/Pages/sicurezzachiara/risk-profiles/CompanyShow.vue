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

const sourceBadge = {
  primary: "bg-primary-subtle text-primary",
  secondary: "bg-info-subtle text-info",
};

const decisionLabels = {
  confirmed: "Confermato",
  customized: "Personalizzato",
  excluded: "Escluso",
  manual_addition: "Aggiunta manuale",
};

const manualForm = useForm({
  risk_catalog_item_id: "",
  final_priority: "",
  consultant_notes: "",
  review_due_at: "",
});

const openManualRiskOffcanvas = ref(false);

const submitManualRisk = () => {
  manualForm.post(route("companies.risk-profile.manual.store", props.company.id), {
    preserveScroll: true,
    onSuccess: () => {
      manualForm.reset();
      openManualRiskOffcanvas.value = false;
    },
    onError: () => {
      openManualRiskOffcanvas.value = true;
    },
  });
};

const hasRisks = computed(() => (props.company.risk_profile_items?.length ?? 0) > 0);
const hasManualRisks = computed(() => (props.company.risk_profile_items ?? []).some((profileItem) => profileItem.is_manual));

const emptyStateMessage = computed(() =>
  "Per generare il primo profilo aggiungi almeno lavoratori con mansione, luoghi o macchinari."
);

const registryRoute = computed(
  () =>
    props.workspaceBridge.actions?.registryRoute ??
    route("measure-registries.index", { company_id: props.company.id, origin: "company_risk_profile", scope: "attention" })
);

const measuresRoute = computed(
  () =>
    props.workspaceBridge.actions?.allMeasuresRoute ??
    route("measure-registries.index", { company_id: props.company.id, origin: "company_risk_profile", scope: "attention" })
);

const dvrRoute = computed(() => route("companies.dvr.show", props.company.id));

const profileStateTitle = computed(() =>
  hasRisks.value ? "Il profilo rischio e' pronto per la verifica consulenziale" : "Nessun rischio ancora dedotto"
);

const profileStateLead = computed(() =>
  hasRisks.value
    ? "Il sistema ha letto lavoratori, mansioni, luoghi e macchinari presenti nel contesto e ha generato il primo quadro dei rischi."
    : emptyStateMessage.value
);

const riskSectionTitle = computed(() =>
  hasManualRisks.value ? "Rischi nel profilo aziendale" : "Rischi dedotti dal contesto aziendale"
);

const riskSectionLead = computed(() =>
  hasManualRisks.value
    ? "Qui trovi i rischi emersi dal contesto e quelli aggiunti dal consulente. Verifica cosa confermare e quali misure collegare."
    : "Qui il consulente verifica i rischi emersi, conferma o corregge il giudizio e apre le misure collegate quando serve."
);

const measuresHelper = (profileItem) => {
  const count = profileItem.measure_summary?.count || 0;

  if (count === 0) {
    return "Collega DPI, corsi, visite o misure operative per presidiare il rischio.";
  }

  const expectedCount = profileItem.engine_coverage?.summary?.expected?.expected_count;
  if (expectedCount) {
    return `${profileItem.engine_coverage.summary.expected.covered_count} / ${expectedCount} presidi attesi coperti`;
  }

  return "Verifica che le misure collegate siano coerenti con il rischio.";
};

const riskActionLabel = (profileItem) => {
  const count = profileItem.measure_summary?.count || 0;

  if (profileItem.is_manual && count === 0) {
    return "Collega misure";
  }

  if (profileItem.is_manual) {
    return "Verifica e collega misure";
  }

  return "Verifica copertura";
};

const riskCardTitle = (profileItem) => {
  if (profileItem.is_manual) {
    return "Rischio aggiunto dal consulente";
  }

  return "Rischio emerso dal contesto aziendale";
};

const riskCardLead = (profileItem) => {
  if (profileItem.is_manual) {
    return "Questo rischio non e' emerso automaticamente da mansioni, luoghi o macchinari. Ora va verificato e collegato alle misure di presidio.";
  }

  return "Il rischio e' stato letto dal contesto aziendale attuale e va verificato prima di considerarlo presidiato.";
};

const sourceOriginSummary = (profileItem) => {
  if (profileItem.is_manual) {
    return "Non deriva automaticamente dal contesto aziendale.";
  }

  if (profileItem.sources.length > 0) {
    return profileItem.sources.map((source) => source.source_label).join(" · ");
  }

  return "Origine automatica non disponibile nel contesto attuale.";
};

const riskContextLine = (profileItem) => {
  if (profileItem.is_manual) {
    return "Aggiunto dal consulente. Non deriva automaticamente dal contesto che genera rischio.";
  }

  if (profileItem.sources.length > 0) {
    return `Dedotto dal contesto aziendale. Origine: ${profileItem.sources.map((source) => source.source_label).join(" · ")}`;
  }

  return "Dedotto dal contesto aziendale. Le sorgenti operative non sono piu' attive nel contesto attuale.";
};

const measuresCoverageSummary = (profileItem) => {
  const count = profileItem.measure_summary?.count || 0;
  const expectedCount = profileItem.engine_coverage?.summary?.expected?.expected_count || 0;
  const coveredCount = profileItem.engine_coverage?.summary?.expected?.covered_count || 0;

  if (count === 0) {
    return "Misure non ancora collegate";
  }

  if (expectedCount > 0) {
    return `${coveredCount}/${expectedCount} presidi attesi gia' coperti`;
  }

  return `${count} misure collegate`;
};

const rowOperationalLabel = (profileItem) => {
  if (profileItem.operational_status === "excluded") {
    return "Escluso dal profilo finale";
  }

  if (profileItem.status === "uncovered") {
    return "Da presidiare";
  }

  return "Coperto";
};

const rowOperationalBadge = (profileItem) => {
  if (profileItem.operational_status === "excluded") {
    return "bg-secondary-subtle text-secondary";
  }

  if (profileItem.status === "uncovered") {
    return "bg-warning-subtle text-warning";
  }

  return "bg-success-subtle text-success";
};

const profileReadiness = computed(() => ({
  label: hasRisks.value ? "Leggibile" : "Da completare",
  helper: hasRisks.value ? "Prima lettura disponibile" : "Prima lettura da completare",
  badgeClass: hasRisks.value ? "bg-success-subtle text-success" : "bg-warning-subtle text-warning",
}));

const minimalSummaryCards = computed(() => [
  {
    key: "to-verify",
    label: "Rischi da confermare",
    icon: "ri-alarm-warning-line",
    value: props.summary.uncoveredRisks ?? props.summary.totalRisks ?? 0,
    helper: "Da confermare o presidiare",
    tone: "warning",
  },
  {
    key: "missing",
    label: "Misure attese mancanti",
    icon: "ri-shield-line",
    value: props.summary.missingExpectedMeasures ?? 0,
    helper: "Misure da collegare",
    tone: "primary",
  },
  {
    key: "profile-state",
    label: "Stato profilo",
    icon: "ri-checkbox-circle-line",
    value: profileReadiness.value.label,
    helper: profileReadiness.value.helper,
    isStatus: true,
    statusClass: profileReadiness.value.badgeClass,
    tone: hasRisks.value ? "success" : "warning",
  },
  {
    key: "reviews",
    label: "Review aperte",
    icon: "ri-calendar-check-line",
    value: props.summary.reviewsDue ?? 0,
    helper: "Decisioni da chiudere",
    tone: "info",
  },
]);
</script>

<template>
  <Layout>
    <Head :title="`Profilo rischio - ${company.name}`" />

    <PageHeader title="Profilo rischio azienda" pageTitle="SicurezzaChiara" />

    <BCard no-body class="mb-4 shadow-sm border-0">
      <BCardBody class="p-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
          <div class="min-w-0">
            <span class="badge bg-danger-subtle text-danger text-uppercase mb-3">Profilo rischio azienda</span>
            <h2 class="mb-2">{{ company.name }}</h2>
            <p class="text-muted mb-0">
              Il sistema deduce i rischi da lavoratori, mansioni, luoghi e macchinari. Verifica il profilo e applica le misure.
            </p>
          </div>

          <Link :href="route('companies.show', company.id)" class="btn btn-soft-secondary btn-sm">
            Torna azienda
          </Link>
        </div>
      </BCardBody>
    </BCard>

    <BCard no-body class="mb-4 shadow-sm border-0">
      <BCardBody class="p-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <div class="text-uppercase text-muted fw-semibold fs-12 mb-1">Riepilogo minimo</div>
            <h4 class="mb-1">{{ profileStateTitle }}</h4>
            <p class="text-muted mb-0">{{ profileStateLead }}</p>
          </div>
          <span class="badge" :class="profileReadiness.badgeClass">
            {{ profileReadiness.label }}
          </span>
        </div>

        <BRow class="g-3">
          <BCol v-for="card in minimalSummaryCards" :key="card.key" xl="3" sm="6">
            <div class="rounded-3 border h-100 p-3">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar-sm flex-shrink-0">
                  <span :class="`avatar-title rounded-circle bg-${card.tone}-subtle text-${card.tone}`">
                    <i :class="card.icon"></i>
                  </span>
                </div>
                <div class="min-w-0">
                  <div class="text-uppercase text-muted fw-semibold fs-12 mb-1">{{ card.label }}</div>
                  <div v-if="card.isStatus" class="mb-1">
                    <span class="badge" :class="card.statusClass">{{ card.value }}</span>
                  </div>
                  <div v-else class="fw-semibold fs-3 text-body lh-1">{{ card.value }}</div>
                  <div class="text-muted fs-13 mt-2">{{ card.helper }}</div>
                </div>
              </div>
            </div>
          </BCol>
        </BRow>
      </BCardBody>
    </BCard>

    <BCard no-body class="mb-4 shadow-sm border-0">
      <BCardHeader class="border-0 pb-0">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
          <div>
            <div class="text-uppercase text-muted fw-semibold fs-12 mb-1">Rischi e decisioni</div>
            <h4 class="card-title mb-1">{{ riskSectionTitle }}</h4>
            <p class="text-muted mb-0">{{ riskSectionLead }}</p>
          </div>
          <span v-if="hasRisks" class="badge bg-soft-danger text-danger">{{ summary.totalRisks }} rischi</span>
        </div>
      </BCardHeader>
      <BCardBody class="pt-3">
        <div v-if="!hasRisks" class="border rounded-3 p-4 text-center">
          <div class="avatar-md mx-auto mb-3">
            <div class="avatar-title bg-light text-info rounded-circle fs-2">
              <i class="ri-radar-line"></i>
            </div>
          </div>
          <h5 class="mb-2">Nessun rischio nel profilo</h5>
          <p class="text-muted mb-3">
            Completa lavoratori, luoghi o macchinari per generare il primo profilo rischio, oppure aggiungi un rischio manualmente.
          </p>
          <div class="hstack gap-2 justify-content-center flex-wrap">
            <Link :href="route('companies.edit', company.id)" class="btn btn-primary btn-sm">
              Completa contesto azienda
            </Link>
            <button type="button" class="btn btn-soft-secondary btn-sm" @click="openManualRiskOffcanvas = true">
              Aggiungi rischio manuale
            </button>
          </div>
        </div>

        <div v-else class="vstack gap-3">
          <div
            v-for="profileItem in company.risk_profile_items"
            :key="profileItem.id"
            class="border rounded-3 bg-light-subtle p-4"
          >
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
              <div class="min-w-0">
                <h5 class="mb-1">{{ profileItem.risk_catalog_item?.name }}</h5>
                <p class="text-muted mb-2">
                  {{ profileItem.risk_catalog_item?.description || "Descrizione non disponibile." }}
                </p>
              </div>

              <div class="d-flex flex-wrap gap-2">
                <span class="badge" :class="priorityBadge[profileItem.effective_priority] || 'bg-light text-body'">
                  {{ profileItem.effective_priority === "high" ? "Alta" : profileItem.effective_priority === "medium" ? "Media" : "Bassa" }}
                </span>
                <span class="badge" :class="profileItem.is_manual ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info'">
                  {{ profileItem.is_manual ? "Aggiunto dal consulente" : "Dedotto dal contesto" }}
                </span>
                <span class="badge" :class="rowOperationalBadge(profileItem)">
                  {{ rowOperationalLabel(profileItem) }}
                </span>
              </div>
            </div>

            <div class="text-muted fs-13 mb-2">
              {{ riskContextLine(profileItem) }}
            </div>

            <div v-if="!profileItem.is_manual && profileItem.sources.length > 0" class="d-flex flex-wrap gap-2 mb-2">
              <span
                v-for="source in profileItem.sources"
                :key="source.id"
                class="badge"
                :class="sourceBadge[source.relevance] || 'bg-light text-body'"
              >
                {{ source.source_label }}
              </span>
            </div>

            <div class="fw-medium fs-13 mb-1">{{ measuresCoverageSummary(profileItem) }}</div>
            <div class="text-muted fs-13 mb-3">{{ measuresHelper(profileItem) }}</div>

            <div class="hstack gap-2 flex-wrap">
              <Link :href="profileItem.measures_route" class="btn btn-primary btn-sm">
                {{ profileItem.is_manual ? "Collega misure" : "Apri misure" }}
              </Link>
              <Link
                v-if="!(profileItem.is_manual && (profileItem.measure_summary?.count || 0) === 0)"
                :href="profileItem.review_route"
                class="btn btn-soft-secondary btn-sm"
              >
                {{ riskActionLabel(profileItem) }}
              </Link>
            </div>

            <div class="d-flex flex-wrap gap-3 mt-3 text-muted fs-13">
              <span v-if="profileItem.consultant_decision">
                {{ decisionLabels[profileItem.consultant_decision] || profileItem.consultant_decision }}
              </span>
              <span v-if="profileItem.review_due_at">Revisione {{ profileItem.review_due_at }}</span>
              <span v-if="profileItem.follow_up_status">Follow-up {{ profileItem.follow_up_due_at || profileItem.follow_up_status }}</span>
              <span v-if="profileItem.operational_owner_name">In carico a {{ profileItem.operational_owner_name }}</span>
              <span v-if="profileItem.last_reviewed_at">Ultima review {{ profileItem.last_reviewed_at }}</span>
            </div>

            <div v-if="profileItem.consultant_notes" class="text-muted fs-13 mt-2">
              {{ profileItem.consultant_notes }}
            </div>
          </div>
        </div>
      </BCardBody>
    </BCard>

    <BCard no-body class="shadow-sm border-0">
      <BCardHeader class="border-0 pb-0">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
          <div>
            <div class="text-uppercase text-muted fw-semibold fs-12 mb-1">Aggiunta manuale</div>
            <h4 class="card-title mb-1">Aggiungi un rischio non emerso dal contesto</h4>
            <p class="text-muted mb-0">
              Usalo solo se il consulente deve inserire un rischio non generato da lavoratori, luoghi o macchinari.
            </p>
          </div>
          <button type="button" class="btn btn-soft-secondary btn-sm" @click="openManualRiskOffcanvas = true">Aggiungi rischio manuale</button>
        </div>
      </BCardHeader>
      <BCardBody class="pt-3">
        <div class="d-flex flex-wrap gap-3 pt-2 border-top">
          <Link :href="measuresRoute" class="link-primary text-decoration-none fw-medium">Apri misure azienda</Link>
          <Link :href="registryRoute" class="link-primary text-decoration-none fw-medium">Apri registri azienda</Link>
          <Link :href="dvrRoute" class="link-primary text-decoration-none fw-medium">Consulta DVR light</Link>
        </div>
      </BCardBody>
    </BCard>

    <BOffcanvas
      v-if="$page.props.tenantContext?.permissions?.can_manage_data"
      v-model="openManualRiskOffcanvas"
      placement="end"
      body-class="p-4"
      style="--bs-offcanvas-width: min(720px, 96vw);"
    >
      <template #header><div><h5 class="mb-0">Aggiungi rischio non emerso dal contesto</h5></div></template>
      <p class="text-muted mb-4">
        Usalo quando il consulente deve inserire un rischio non generato da lavoratori, luoghi o macchinari.
      </p>

      <form @submit.prevent="submitManualRisk" class="row g-3">
        <div class="col-12">
          <label class="form-label">Rischio</label>
          <select v-model="manualForm.risk_catalog_item_id" class="form-select" :class="{ 'is-invalid': manualForm.errors.risk_catalog_item_id }">
            <option value="">Seleziona rischio</option>
            <option v-for="risk in manualRiskOptions" :key="risk.id" :value="risk.id">
              {{ risk.name }}{{ risk.category_name ? ` - ${risk.category_name}` : "" }}{{ risk.already_present ? " (gia' presente)" : "" }}
            </option>
          </select>
          <div v-if="manualForm.errors.risk_catalog_item_id" class="invalid-feedback d-block">{{ manualForm.errors.risk_catalog_item_id }}</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Priorita' finale</label>
          <select v-model="manualForm.final_priority" class="form-select" :class="{ 'is-invalid': manualForm.errors.final_priority }">
            <option value="">Usa priorita' catalogo</option>
            <option v-for="priority in formOptions.priorities" :key="priority.value" :value="priority.value">
              {{ priority.label }}
            </option>
          </select>
          <div v-if="manualForm.errors.final_priority" class="invalid-feedback d-block">{{ manualForm.errors.final_priority }}</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Prossima revisione</label>
          <input v-model="manualForm.review_due_at" type="date" class="form-control" :class="{ 'is-invalid': manualForm.errors.review_due_at }" />
          <div v-if="manualForm.errors.review_due_at" class="invalid-feedback d-block">{{ manualForm.errors.review_due_at }}</div>
        </div>
        <div class="col-12">
          <label class="form-label">Nota consulente</label>
          <input
            v-model="manualForm.consultant_notes"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': manualForm.errors.consultant_notes }"
            placeholder="Motivazione sintetica"
          />
          <div v-if="manualForm.errors.consultant_notes" class="invalid-feedback d-block">{{ manualForm.errors.consultant_notes }}</div>
        </div>
        <div class="col-12 d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-soft-secondary btn-sm" @click="openManualRiskOffcanvas = false">Annulla</button>
          <button type="submit" class="btn btn-primary btn-sm" :disabled="manualForm.processing">Aggiungi al profilo</button>
        </div>
      </form>
    </BOffcanvas>
  </Layout>
</template>
