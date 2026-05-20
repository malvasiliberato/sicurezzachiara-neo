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

const headquarters = computed(
  () => (props.company.sites ?? []).find((site) => site.is_headquarters) ?? props.company.sites?.[0] ?? null
);

const headerLocation = computed(() => {
  if (headquarters.value?.name) {
    return headquarters.value.city
      ? `${headquarters.value.name} - ${headquarters.value.city}${headquarters.value.province ? ` (${headquarters.value.province})` : ""}`
      : headquarters.value.name;
  }

  const fallback = [
    [props.company.address_line, props.company.street_number].filter(Boolean).join(" "),
    props.company.city ? `${props.company.city}${props.company.province ? ` (${props.company.province})` : ""}` : null,
  ]
    .filter(Boolean)
    .join(" - ");

  return fallback || "Sede principale non ancora indicata";
});

const headerDetails = computed(() =>
  [
    {
      key: "industry",
      icon: "ri-briefcase-4-line",
      label: null,
      value: props.company.industry || "Attivita' non impostata",
    },
    props.company.ateco_entry
      ? {
          key: "ateco",
          icon: "ri-bookmark-3-line",
          label: "ATECO",
          value: `${props.company.ateco_entry.codice} - ${props.company.ateco_entry.titolo_it}`,
        }
      : null,
    {
      key: "location",
      icon: "ri-map-pin-line",
      label: null,
      value: headerLocation.value,
    },
  ].filter(Boolean)
);

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
        ? `${jobRoleAssignmentsCount.value} ${jobRoleAssignmentsCount.value === 1 ? "mansione collegata" : "mansioni collegate"}`
        : workersCount.value > 0
          ? "Mansioni da completare"
          : "Nessun lavoratore censito",
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
      helper: workplacesCount.value > 0 ? `${workplacesCount.value} ${workplacesCount.value === 1 ? "luogo censito" : "luoghi censiti"}` : "Nessun luogo censito",
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
      helper: equipmentCount.value > 0 ? `${equipmentCount.value} ${equipmentCount.value === 1 ? "macchinario censito" : "macchinari censiti"}` : "Nessun macchinario censito",
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
    .filter((card) => !["Presenti", "Pronti"].includes(card.statusLabel))
    .map((card) => card.title.toLowerCase())
);

const profileReadiness = computed(() => {
  const profileReady = props.areaOneJourney.setupComplete;
  const missingSummary =
    missingRiskSources.value.length > 0
      ? `Completa ${missingRiskSources.value.join(", ")} per ottenere una lettura piu' affidabile del rischio.`
      : "Il contesto minimo e' presente e il profilo puo' gia' essere letto.";

  return {
    ready: profileReady,
    badgeLabel: profileReady ? "Pronto" : "In costruzione",
    badgeClass: profileReady ? "bg-success-subtle text-success" : "bg-warning-subtle text-warning",
    title: profileReady ? "Prima lettura del rischio" : "Stato del profilo rischio",
    lead: "Il sistema legge lavoratori, luoghi e macchinari e genera il primo quadro dei rischi.",
    metrics: [
      {
        key: "active-risks",
        label: "Rischi nel profilo",
        value: props.contextBridge.stats.activeRisks,
        emphasis: "neutral",
      },
      {
        key: "missing-measures",
        label: "Misure/presidi da collegare",
        value: props.contextBridge.stats.missingExpectedMeasures,
        emphasis: props.contextBridge.stats.missingExpectedMeasures > 0 ? "warning" : "neutral",
      },
      {
        key: "reviews-due",
        label: "Rischi da verificare",
        value: props.contextBridge.stats.reviewsDue,
        emphasis: props.contextBridge.stats.reviewsDue > 0 ? "info" : "neutral",
      },
      {
        key: "gaps",
        label: "Gap da gestire",
        value: props.contextBridge.stats.uncoveredRisks,
        emphasis: props.contextBridge.stats.uncoveredRisks > 0 ? "danger" : "neutral",
      },
    ],
    helper: profileReady ? props.areaOneJourney.nextMilestone : missingSummary,
  };
});

const operationalAlerts = computed(() =>
  (props.contextBridge.workQueue ?? []).map((item) => ({
    ...item,
    title:
      item.key === "deadlines"
        ? `${item.count} ${item.count === 1 ? "scadenza aperta" : "scadenze aperte"}`
        : item.key === "reviews"
          ? `${item.count} ${item.count === 1 ? "review da chiudere" : "review da chiudere"}`
          : item.key === "follow_up"
            ? `${item.count} ${item.count === 1 ? "follow-up attivo" : "follow-up attivi"}`
            : `${item.count} ${item.count === 1 ? "gap di copertura da chiudere" : "gap di copertura da chiudere"}`,
    shortHelper:
      item.key === "deadlines"
        ? "Ci sono misure o attivita' oltre la data prevista."
        : item.key === "reviews"
          ? "Il giudizio consulenziale richiede un riallineamento."
          : item.key === "follow_up"
            ? "Alcuni rischi restano in carico operativo."
            : "Ci sono presidi attesi non ancora collegati ai rischi.",
  }))
);

const visibleAlerts = computed(() => operationalAlerts.value.slice(0, 3));

const hiddenAlertsCount = computed(() => Math.max(operationalAlerts.value.length - visibleAlerts.value.length, 0));

const primaryOperationalAction = computed(() => {
  const firstAlert = visibleAlerts.value[0];

  if (firstAlert) {
    return {
      label: firstAlert.key === "expected_gaps" ? "Apri misure" : firstAlert.actionLabel,
      route: firstAlert.key === "expected_gaps" ? props.contextBridge.actions.measuresRoute : firstAlert.actionRoute,
    };
  }

  return {
    label: "Apri profilo rischio",
    route: props.contextBridge.actions.riskProfileRoute,
  };
});

const secondaryOperationalLinks = computed(() =>
  [
    {
      key: "measures",
      label: "Apri misure",
      route: props.contextBridge.actions.measuresRoute,
    },
    {
      key: "registries",
      label: "Apri registri",
      route: props.contextBridge.actions.registryRoute,
    },
    {
      key: "dvr",
      label: "DVR light",
      route: props.contextBridge.actions.dvrRoute,
    },
  ].filter((link, index, collection) => collection.findIndex((item) => item.route === link.route) === index)
);
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
            <h2 class="mb-2">{{ company.name }}</h2>
          <div class="vstack gap-2 text-muted">
              <div
                v-for="item in headerDetails"
                :key="item.key"
                class="d-flex align-items-start gap-2"
              >
                <i :class="item.icon" class="fs-5 text-body"></i>
                <div class="min-w-0">
                  <span v-if="item.label" class="fw-semibold text-body">{{ item.label }}:</span>
                  <span :class="item.label ? 'ms-1' : ''">{{ item.value }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="hstack gap-2 flex-wrap">
            <Link :href="route('companies.edit', company.id)" class="btn btn-primary btn-sm">
              Configura azienda
            </Link>
            <Link :href="route('companies.index')" class="btn btn-soft-secondary btn-sm">
              Torna al portfolio
            </Link>
          </div>
        </div>
      </BCardBody>
    </BCard>

    <BCard no-body class="mb-4 shadow-sm border-0">
      <BCardBody class="p-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <h4 class="mb-1">Contesto che genera rischio</h4>
            <p class="text-muted mb-0">
              Mansioni, luoghi e macchinari sono le sorgenti di rischio da cui nasce il primo profilo rischio dell'azienda.
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
                <div class="fw-semibold fs-1 lh-1 text-body">{{ card.count }}</div>
              </div>

              <p class="text-muted fs-13 mb-3 text-truncate">{{ card.helper }}</p>

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
            <h4 class="mb-1">{{ profileReadiness.title }}</h4>
            <p class="text-muted mb-0">{{ profileReadiness.lead }}</p>
          </div>
          <span class="badge" :class="profileReadiness.badgeClass">{{ profileReadiness.badgeLabel }}</span>
        </div>

        <BRow class="g-3 align-items-stretch mb-3">
          <BCol v-for="metric in profileReadiness.metrics" :key="metric.key" xl="3" sm="6">
            <div
              class="rounded-3 border h-100 p-3"
              :class="{
                'bg-light-subtle': metric.emphasis === 'neutral',
                'bg-warning-subtle': metric.emphasis === 'warning',
                'bg-info-subtle': metric.emphasis === 'info',
                'bg-danger-subtle': metric.emphasis === 'danger',
              }"
            >
              <div
                class="fw-semibold fs-2 mb-1"
                :class="{
                  'text-body': metric.emphasis === 'neutral',
                  'text-warning': metric.emphasis === 'warning',
                  'text-info': metric.emphasis === 'info',
                  'text-danger': metric.emphasis === 'danger',
                }"
              >
                {{ metric.value }}
              </div>
              <div class="text-muted fs-13">{{ metric.label }}</div>
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

    <BCard no-body class="mb-4 shadow-sm border-0">
      <BCardBody class="p-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <h4 class="mb-1">Da gestire ora</h4>
            <p class="text-muted mb-0">
              Qui trovi solo le attenzioni operative che richiedono un passaggio del consulente.
            </p>
          </div>
          <span v-if="operationalAlerts.length > 0" class="badge bg-warning-subtle text-warning">
            {{ operationalAlerts.length }} alert
          </span>
        </div>

        <div v-if="visibleAlerts.length > 0" class="vstack gap-3 mb-3">
          <div
            v-for="alert in visibleAlerts"
            :key="alert.key"
            class="d-flex align-items-start justify-content-between gap-3 rounded-3 border bg-light-subtle p-3"
          >
            <div class="min-w-0">
              <div class="fw-semibold text-body">{{ alert.title }}</div>
              <div class="text-muted fs-13">{{ alert.shortHelper }}</div>
            </div>
            <span class="badge bg-light text-body flex-shrink-0">{{ alert.laneLabel }}</span>
          </div>

          <div v-if="hiddenAlertsCount > 0" class="text-muted fs-13">
            +{{ hiddenAlertsCount }} altri alert nel profilo operativo.
          </div>
        </div>

        <div v-else class="rounded-3 border bg-light-subtle p-3 mb-3">
          <div class="fw-semibold text-body mb-1">Nessun alert operativo</div>
          <div class="text-muted fs-13">
            Il contesto e il profilo non mostrano urgenze immediate. Puoi entrare nel profilo per una verifica generale.
          </div>
        </div>

        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
          <div class="d-flex flex-wrap gap-3 fs-13">
            <Link
              v-for="link in secondaryOperationalLinks"
              :key="link.key"
              :href="link.route"
              class="text-decoration-none"
            >
              {{ link.label }}
            </Link>
          </div>
          <Link :href="primaryOperationalAction.route" class="btn btn-primary btn-sm">
            {{ primaryOperationalAction.label }}
          </Link>
        </div>
      </BCardBody>
    </BCard>
  </Layout>
</template>
