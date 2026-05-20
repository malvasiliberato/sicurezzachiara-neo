<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

const props = defineProps({
  tenant: Object,
  company: Object,
  coreStarterPack: Object,
  contextBridge: Object,
  areaOneJourney: Object,
  configureForms: Object,
});

const sitesCount = computed(() => props.company.sites?.length ?? 0);
const workplaces = computed(() => (props.company.sites ?? []).flatMap((site) => site.workplaces ?? []));
const workplacesCount = computed(() => workplaces.value.length);
const equipmentCount = computed(() => props.company.equipment_assets?.length ?? 0);
const workersCount = computed(() => props.company.workers?.length ?? 0);
const jobRoleAssignmentsCount = computed(() =>
  (props.company.workers ?? []).reduce((count, worker) => count + (worker.job_role_assignments?.length ?? 0), 0)
);

const headerLocation = computed(() => {
  const headquarters = (props.company.sites ?? []).find((site) => site.is_headquarters) ?? props.company.sites?.[0];

  if (headquarters?.name) {
    return headquarters.city
      ? `${headquarters.name} - ${headquarters.city}${headquarters.province ? ` (${headquarters.province})` : ""}`
      : headquarters.name;
  }

  const fallback = [
    [props.company.address_line, props.company.street_number].filter(Boolean).join(" "),
    props.company.city ? `${props.company.city}${props.company.province ? ` (${props.company.province})` : ""}` : null,
  ]
    .filter(Boolean)
    .join(" - ");

  return fallback || "Sede principale non ancora indicata";
});

const headerMeta = computed(() => [
  {
    key: "industry",
    icon: "ri-briefcase-4-line",
    label: "Attivita'",
    value: props.company.industry || "Attivita' non impostata",
  },
  {
    key: "ateco",
    icon: "ri-bookmark-3-line",
    label: "ATECO orientativo",
    value: props.company.ateco_entry
      ? `${props.company.ateco_entry.codice} - ${props.company.ateco_entry.titolo_it}`
      : "ATECO orientativo non impostato",
  },
  {
    key: "location",
    icon: "ri-map-pin-line",
    label: "Sede principale",
    value: headerLocation.value,
  },
]);

const riskSourceCards = computed(() => {
  const workersReady = workersCount.value > 0 && jobRoleAssignmentsCount.value > 0;
  const firstSiteId = props.company.sites?.[0]?.id;

  return [
    {
      key: "workers",
      title: "Lavoratori e mansioni",
      icon: "ri-team-line",
      count: workersCount.value,
      statusLabel: workersReady ? "Pronti" : workersCount.value > 0 ? "Da completare" : "Da aggiungere",
      statusClass: workersReady ? "bg-success-subtle text-success" : "bg-warning-subtle text-warning",
      helper: workersReady
        ? `${jobRoleAssignmentsCount.value} mansioni collegate. Da qui nasce parte del profilo rischio.`
        : workersCount.value > 0
          ? "I lavoratori sono presenti, ma servono ancora mansioni per leggere meglio il rischio."
          : "Da qui nasce parte del profilo rischio.",
      actionLabel: workersCount.value > 0 ? "Apri lavoratori" : "Nuovo lavoratore",
      actionRoute: workersCount.value > 0
        ? route("workers.index", { company_id: props.company.id })
        : route("workers.create", { company: props.company.id }),
    },
    {
      key: "workplaces",
      title: "Luoghi",
      icon: "ri-map-pin-2-line",
      count: workplacesCount.value,
      statusLabel: workplacesCount.value > 0 ? "Presenti" : "Da aggiungere",
      statusClass: workplacesCount.value > 0 ? "bg-success-subtle text-success" : "bg-warning-subtle text-warning",
      helper: workplacesCount.value > 0
        ? "I luoghi sono presenti nel contesto. Da qui nasce parte del profilo rischio."
        : "Da qui nasce parte del profilo rischio.",
      actionLabel: workplacesCount.value > 0 ? "Apri luoghi" : "Nuovo luogo",
      actionRoute: workplacesCount.value > 0
        ? route("workplaces.index", { company_id: props.company.id })
        : route("workplaces.create", {
            company: props.company.id,
            ...(firstSiteId ? { site: firstSiteId } : {}),
          }),
    },
    {
      key: "equipment",
      title: "Macchinari",
      icon: "ri-settings-3-line",
      count: equipmentCount.value,
      statusLabel: equipmentCount.value > 0 ? "Presenti" : "Da aggiungere",
      statusClass: equipmentCount.value > 0 ? "bg-success-subtle text-success" : "bg-warning-subtle text-warning",
      helper: equipmentCount.value > 0
        ? "I macchinari sono presenti nel contesto. Da qui nasce parte del profilo rischio."
        : "Da qui nasce parte del profilo rischio.",
      actionLabel: equipmentCount.value > 0 ? "Apri macchinari" : "Nuovo macchinario",
      actionRoute: equipmentCount.value > 0
        ? route("equipment-assets.index", { company_id: props.company.id })
        : route("equipment-assets.create", {
            company: props.company.id,
            ...(firstSiteId ? { site: firstSiteId } : {}),
          }),
    },
  ];
});

const missingRiskSources = computed(() =>
  riskSourceCards.value
    .filter((card) => card.statusLabel !== "Presenti" && card.statusLabel !== "Pronti")
    .map((card) => card.title.toLowerCase())
);

const profileReadiness = computed(() => {
  const profileReady = props.areaOneJourney.setupComplete;
  const missingSummary = missingRiskSources.value.length > 0
    ? `Completa ${missingRiskSources.value.join(", ")} per generare un profilo piu' affidabile.`
    : "Il contesto minimo e' presente e il profilo puo' essere letto.";

  return {
    ready: profileReady,
    badgeLabel: profileReady ? "Pronto" : "Incompleto",
    badgeClass: profileReady ? "bg-success-subtle text-success" : "bg-warning-subtle text-warning",
    title: profileReady ? "Il primo profilo rischio e' pronto" : "Il primo profilo rischio sta prendendo forma",
    lead: profileReady
      ? "Il sistema ha letto lavoratori, luoghi e macchinari e ha generato il primo quadro dei rischi."
      : "Completa i produttori di rischio per generare un profilo piu' affidabile.",
    activeRisks: props.contextBridge.stats.activeRisks,
    expectedMeasures: props.coreStarterPack.summary.expectedMeasuresCount,
    uncoveredRisks: props.contextBridge.stats.uncoveredRisks,
    gaps: props.contextBridge.stats.missingExpectedMeasures,
    helper: profileReady ? props.areaOneJourney.nextMilestone : missingSummary,
  };
});
</script>

<template>
  <Layout>
    <Head :title="company.name" />
    <PageHeader title="Dashboard azienda" pageTitle="SicurezzaChiara" />

    <BAlert v-if="$page.props.flash.success" show variant="success" class="mb-4">
      <i class="ri-checkbox-circle-line me-2 align-middle"></i>{{ $page.props.flash.success }}
    </BAlert>

    <BCard no-body class="mb-4 shadow-sm border-0">
      <BCardBody class="p-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
          <div class="min-w-0">
            <div class="text-uppercase text-muted fw-semibold fs-12 mb-1">Header azienda</div>
            <h2 class="mb-2">{{ company.name }}</h2>
            <div class="d-flex flex-wrap gap-2">
              <span
                v-for="item in headerMeta"
                :key="item.key"
                class="d-inline-flex align-items-center gap-2 rounded-pill bg-light-subtle px-3 py-2"
              >
                <i :class="item.icon"></i>
                <span class="text-muted">{{ item.label }}:</span>
                <span class="fw-medium text-body">{{ item.value }}</span>
              </span>
            </div>
          </div>

          <div class="hstack gap-2 flex-wrap">
            <Link :href="route('companies.edit', company.id)" class="btn btn-primary btn-sm">
              Configura azienda
            </Link>
            <Link :href="route('companies.index')" class="btn btn-soft-secondary btn-sm">
              Torna ad aziende
            </Link>
          </div>
        </div>
      </BCardBody>
    </BCard>

    <BCard no-body class="mb-4 shadow-sm border-0">
      <BCardBody class="p-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <div class="text-uppercase text-muted fw-semibold fs-12 mb-1">Produttori di rischio</div>
            <h4 class="mb-1">Da questi elementi nasce il profilo rischio</h4>
            <p class="text-muted mb-0">
              Parti da lavoratori con mansione, luoghi e macchinari: pochi dati essenziali bastano per far emergere il primo quadro dei rischi.
            </p>
          </div>
          <span class="badge bg-light text-body">{{ areaOneJourney.progressLabel }}</span>
        </div>

        <BRow class="g-3">
          <BCol v-for="card in riskSourceCards" :key="card.key" xl="4" md="6">
            <div class="rounded-3 border bg-light-subtle h-100 p-3">
              <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                <div class="d-flex align-items-start gap-3">
                  <span class="avatar-sm flex-shrink-0">
                    <span class="avatar-title rounded-circle bg-white text-body fs-20 shadow-sm">
                      <i :class="card.icon"></i>
                    </span>
                  </span>
                  <div>
                    <h5 class="mb-1">{{ card.title }}</h5>
                    <span class="badge" :class="card.statusClass">{{ card.statusLabel }}</span>
                  </div>
                </div>
                <div class="fw-semibold fs-3 lh-1 text-body">{{ card.count }}</div>
              </div>

              <p class="text-muted fs-13 mb-3">{{ card.helper }}</p>

              <Link :href="card.actionRoute" class="btn btn-soft-secondary btn-sm">
                {{ card.actionLabel }}
              </Link>
            </div>
          </BCol>
        </BRow>
      </BCardBody>
    </BCard>

    <BCard no-body class="mb-4 shadow-sm border-0">
      <BCardBody class="p-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <div class="text-uppercase text-muted fw-semibold fs-12 mb-1">Primo profilo rischio</div>
            <h4 class="mb-1">{{ profileReadiness.title }}</h4>
            <p class="text-muted mb-0">{{ profileReadiness.lead }}</p>
          </div>
          <span class="badge" :class="profileReadiness.badgeClass">{{ profileReadiness.badgeLabel }}</span>
        </div>

        <BRow class="g-3 align-items-stretch mb-3">
          <BCol xl="3" sm="6">
            <div class="rounded-3 border bg-light-subtle h-100 p-3">
              <div class="text-uppercase text-muted fw-semibold fs-12 mb-1">Rischi attivi</div>
              <div class="fw-semibold fs-3 text-body">{{ profileReadiness.activeRisks }}</div>
            </div>
          </BCol>
          <BCol xl="3" sm="6">
            <div class="rounded-3 border bg-light-subtle h-100 p-3">
              <div class="text-uppercase text-muted fw-semibold fs-12 mb-1">Misure attese</div>
              <div class="fw-semibold fs-3 text-body">{{ profileReadiness.expectedMeasures }}</div>
            </div>
          </BCol>
          <BCol xl="3" sm="6">
            <div class="rounded-3 border bg-light-subtle h-100 p-3">
              <div class="text-uppercase text-muted fw-semibold fs-12 mb-1">Rischi scoperti</div>
              <div class="fw-semibold fs-3 text-body">{{ profileReadiness.uncoveredRisks }}</div>
            </div>
          </BCol>
          <BCol xl="3" sm="6">
            <div class="rounded-3 border bg-light-subtle h-100 p-3">
              <div class="text-uppercase text-muted fw-semibold fs-12 mb-1">Gap attesi</div>
              <div class="fw-semibold fs-3 text-body">{{ profileReadiness.gaps }}</div>
            </div>
          </BCol>
        </BRow>

        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
          <p class="text-muted mb-0">{{ profileReadiness.helper }}</p>
          <Link :href="contextBridge.actions.riskProfileRoute" class="btn btn-primary btn-sm">
            Apri profilo rischio
          </Link>
        </div>
      </BCardBody>
    </BCard>
  </Layout>
</template>
