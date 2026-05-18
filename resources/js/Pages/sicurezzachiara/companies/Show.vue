<script setup>
import { computed, ref, watch } from "vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import SiteForm from "./Partials/SiteForm.vue";

const props = defineProps({
  tenant: Object,
  company: Object,
  coreStarterPack: Object,
  contextBridge: Object,
  areaOneJourney: Object,
  configureForms: Object,
});

const page = usePage();
const activeTrack = ref("company-context");
const showAllSites = ref(false);
const openSiteOffcanvas = ref(false);
const editingSiteId = ref(null);
const siteComuneOption = ref(null);
const siteActionTooltipId = (siteId, action) => `company-site-${siteId}-${action}-tooltip`;

const sitesCount = computed(() => props.company.sites?.length ?? 0);
const workplaces = computed(() => (props.company.sites ?? []).flatMap((site) => site.workplaces ?? []));
const workplacesCount = computed(() => workplaces.value.length);
const equipmentCount = computed(() => props.company.equipment_assets?.length ?? 0);
const workersCount = computed(() => props.company.workers?.length ?? 0);
const jobRoleAssignmentsCount = computed(() =>
  (props.company.workers ?? []).reduce((count, worker) => count + (worker.job_role_assignments?.length ?? 0), 0)
);

const headerMeta = computed(() => [
  {
    label: "Settore / attivita'",
    value: props.company.industry || "Non impostato",
  },
  {
    label: "ATECO orientativo",
    value: props.company.ateco_entry
      ? `${props.company.ateco_entry.codice} - ${props.company.ateco_entry.titolo_it}`
      : "Non impostato",
  },
  {
    label: "Contatti",
    value: [props.company.contact_email, props.company.contact_phone].filter(Boolean).join(" • ") || "Non disponibili",
  },
  {
    label: "Localizzazione",
    value:
      [
        [props.company.address_line, props.company.street_number].filter(Boolean).join(" "),
        props.company.city ? `${props.company.city}${props.company.province ? ` (${props.company.province})` : ""}` : null,
        props.company.postal_code || null,
      ]
        .filter(Boolean)
        .join(" • ") || "Non disponibile",
  },
]);

const headerMetaCompact = computed(() => {
  const items = [];

  if (props.company.industry || props.company.ateco_entry) {
    items.push({
      key: "industry",
      icon: "ri-briefcase-4-line",
      value: props.company.industry || props.company.ateco_entry?.titolo_it,
      helper: props.company.ateco_entry ? `ATECO ${props.company.ateco_entry.codice}` : null,
    });
  }

  const contacts = [props.company.contact_email, props.company.contact_phone].filter(Boolean).join(" • ");
  if (contacts) {
    items.push({
      key: "contacts",
      icon: "ri-contacts-book-3-line",
      value: contacts,
      helper: null,
    });
  }

  const location = [
    [props.company.address_line, props.company.street_number].filter(Boolean).join(" "),
    props.company.city ? `${props.company.city}${props.company.province ? ` (${props.company.province})` : ""}` : null,
    props.company.postal_code || null,
  ]
    .filter(Boolean)
    .join(" • ");

  if (location) {
    items.push({
      key: "location",
      icon: "ri-map-pin-line",
      value: location,
      helper: null,
    });
  }

  return items;
});

const sitesSummary = computed(() => {
  if (sitesCount.value === 0) {
    return "Nessuna sede censita";
  }

  const preview = (props.company.sites ?? []).slice(0, 2).map((site) => site.name).join(" • ");
  return `${sitesCount.value} sedi${preview ? ` • ${preview}` : ""}`;
});

const headerSitePreview = computed(() => (props.company.sites ?? []).slice(0, 3));
const visibleSites = computed(() => (showAllSites.value ? props.company.sites ?? [] : headerSitePreview.value));
const focusSignals = computed(() => [
  {
    key: "uncovered",
    label: "Rischi scoperti",
    value: props.contextBridge.stats.uncoveredRisks,
  },
  {
    key: "gaps",
    label: "Gap attesi",
    value: props.contextBridge.stats.missingExpectedMeasures,
  },
  {
    key: "reviews",
    label: "Review dovute",
    value: props.contextBridge.stats.reviewsDue,
  },
  {
    key: "followups",
    label: "Follow-up aperti",
    value: props.contextBridge.stats.followUpsOpen,
  },
  {
    key: "deadlines",
    label: "Scadenze aperte",
    value: props.contextBridge.stats.overdueMeasures,
  },
]);
const focusWorkQueue = computed(() => props.contextBridge.workQueue ?? []);
const engineHighlights = computed(() => [
  {
    key: "risk-priority",
    title: "Priorita' rischio",
    value: `${props.contextBridge.stats.highPriorityRisks} alti`,
    helper: `${props.contextBridge.stats.uncoveredRisks} rischi restano ancora scoperti.`,
  },
  {
    key: "expected-coverage",
    title: "Copertura attesa",
    value: `${props.contextBridge.stats.coveredExpectedMeasures} coperti`,
    helper: `${props.contextBridge.stats.substitutedExpectedMeasures} coperture equivalenti su ${props.coreStarterPack.summary.expectedMeasuresCount} attese.`,
  },
  {
    key: "coverage-rate",
    title: "Stato misure",
    value: `${props.contextBridge.stats.pendingMeasures} aperte`,
    helper: `${props.contextBridge.stats.implementedMeasures} gia' attuate | copertura ${props.contextBridge.stats.coverageRate}%.`,
  },
  {
    key: "expected-gaps",
    title: "Gap residui",
    value: `${props.contextBridge.stats.missingExpectedMeasures} mancanti`,
    helper: `${props.contextBridge.stats.risksWithExpectedGaps} rischi hanno ancora gap attesi.`,
  },
]);
const operationsQueue = computed(() => props.contextBridge.operationalQueue ?? []);

const riskSourceCards = computed(() => [
  {
    key: "workers",
    title: "Lavoratori",
    icon: "ri-team-line",
    count: workersCount.value,
    summary: workersCount.value > 0
      ? `${jobRoleAssignmentsCount.value} mansioni assegnate`
      : "Nessun lavoratore ancora censito",
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
    summary: workplacesCount.value > 0
      ? workplaces.value.slice(0, 2).map((workplace) => workplace.name).join(" • ")
      : "Nessun luogo ancora censito",
    actionLabel: workplacesCount.value > 0 ? "Apri luoghi" : "Nuovo luogo",
    actionRoute: workplacesCount.value > 0
      ? route("workplaces.index", { company_id: props.company.id })
      : route("workplaces.create", {
          company: props.company.id,
          ...(props.company.sites?.[0]?.id ? { site: props.company.sites[0].id } : {}),
        }),
  },
  {
    key: "equipment",
    title: "Macchinari",
    icon: "ri-settings-3-line",
    count: equipmentCount.value,
    summary: equipmentCount.value > 0
      ? (props.company.equipment_assets ?? []).slice(0, 2).map((asset) => asset.name).join(" • ")
      : "Nessun macchinario ancora censito",
    actionLabel: equipmentCount.value > 0 ? "Apri macchinari" : "Nuovo macchinario",
    actionRoute: equipmentCount.value > 0
      ? route("equipment-assets.index", { company_id: props.company.id })
      : route("equipment-assets.create", {
          company: props.company.id,
          ...(props.company.sites?.[0]?.id ? { site: props.company.sites[0].id } : {}),
        }),
  },
]);

const riskSourceCardUi = computed(() =>
  riskSourceCards.value.map((card) => ({
    ...card,
    avatarClass: "bg-secondary-subtle text-secondary",
    countClass: "text-body",
    surfaceClass: "bg-light-subtle",
    countBadgeClass: "bg-white text-body",
    buttonVariant: "soft-secondary",
  }))
);

const escapeHtml = (value) =>
  String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");

const buildDependencyHtml = (references = []) =>
  references.length > 0
    ? `<ul class="text-start ps-3 mb-0">${references
        .map((reference) => {
          const items = reference.items ?? [];
          const names = items.length > 0 ? `: ${items.map((item) => escapeHtml(item)).join(", ")}` : "";
          const more = reference.has_more ? " e altri collegamenti" : "";

          return `<li><strong>${escapeHtml(reference.label)}</strong> (${reference.count})${names}${more}</li>`;
        })
        .join("")}</ul>`
    : "";

const siteForm = useForm({
  name: "",
  site_code: "",
  is_headquarters: sitesCount.value === 0,
  address_line: "",
  street_number: "",
  postal_code: props.company.postal_code ?? "",
  city: props.company.city ?? "",
  province: props.company.province ?? "",
  notes: "",
  redirect_to_company_edit: false,
});

const deleteSiteForm = useForm({
  redirect_to_company_edit: false,
});

const siteOffcanvasTitle = computed(() => (editingSiteId.value ? "Modifica sede" : "Nuova sede"));
const siteSubmitLabel = computed(() => (editingSiteId.value ? "Aggiorna sede" : "Salva sede"));

const resetSiteEditor = () => {
  editingSiteId.value = null;
  siteComuneOption.value = null;
  siteForm.name = "";
  siteForm.site_code = "";
  siteForm.is_headquarters = sitesCount.value === 0;
  siteForm.address_line = "";
  siteForm.street_number = "";
  siteForm.postal_code = props.company.postal_code ?? "";
  siteForm.city = props.company.city ?? "";
  siteForm.province = props.company.province ?? "";
  siteForm.notes = "";
  siteForm.redirect_to_company_edit = false;
  siteForm.clearErrors();
};

const openCreateSiteOffcanvas = () => {
  resetSiteEditor();
  openSiteOffcanvas.value = true;
};

const openEditSiteOffcanvas = (site) => {
  editingSiteId.value = site.id;
  siteComuneOption.value = site.comune_option ?? null;
  siteForm.name = site.name ?? "";
  siteForm.site_code = site.site_code ?? "";
  siteForm.is_headquarters = !!site.is_headquarters;
  siteForm.address_line = site.address_line ?? "";
  siteForm.street_number = site.street_number ?? "";
  siteForm.postal_code = site.postal_code ?? "";
  siteForm.city = site.city ?? "";
  siteForm.province = site.province ?? "";
  siteForm.notes = site.notes ?? "";
  siteForm.redirect_to_company_edit = false;
  siteForm.clearErrors();
  openSiteOffcanvas.value = true;
};

const submitSite = () => {
  if (editingSiteId.value) {
    siteForm.put(route("companies.sites.update", [props.company.id, editingSiteId.value]), {
      preserveScroll: true,
      onSuccess: () => {
        resetSiteEditor();
        openSiteOffcanvas.value = false;
      },
    });

    return;
  }

  siteForm.post(route("companies.sites.store", props.company.id), {
    preserveScroll: true,
    onSuccess: () => {
      resetSiteEditor();
      openSiteOffcanvas.value = false;
    },
  });
};

const deleteSite = (site) => {
  Swal.fire({
    title: "Cancellare la sede?",
    text: `La sede "${site.name}" verra' rimossa dall'azienda.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Si, cancella",
    cancelButtonText: "Annulla",
    reverseButtons: true,
    customClass: {
      confirmButton: "btn btn-danger w-xs me-2 mt-2",
      cancelButton: "btn btn-soft-secondary w-xs mt-2",
    },
    buttonsStyling: false,
  }).then((result) => {
    if (!result.isConfirmed) {
      return;
    }

    deleteSiteForm.delete(route("companies.sites.destroy", [props.company.id, site.id]), {
      preserveScroll: true,
    });
  });
};

watch(
  () => page.props.flash.error,
  (error) => {
    if (!error) {
      return;
    }

    Swal.fire({
      title: error.title || "Operazione non disponibile",
      html: `${escapeHtml(error.message || "")}${buildDependencyHtml(error.references ?? [])}`,
      icon: "info",
      confirmButtonText: "Ho capito",
      customClass: {
        confirmButton: "btn btn-primary w-xs mt-2",
      },
      buttonsStyling: false,
    });
  },
  { immediate: true }
);

const domainTracks = [
  { key: "company-context", label: "Contesto aziendale", icon: "ri-building-line" },
  { key: "engine-output", label: "Esito del motore", icon: "ri-radar-line" },
  { key: "operations", label: "Governo operativo", icon: "ri-shield-check-line" },
];

const companyContextRows = computed(() => [
  {
    key: "company",
    title: "Anagrafica azienda",
    status: props.company.ateco_2025_id ? "Presente" : "Da completare",
    summary: [
      props.company.industry || "Settore non impostato",
      props.company.city ? `${props.company.city}${props.company.province ? ` (${props.company.province})` : ""}` : null,
      props.company.contact_email || null,
    ].filter(Boolean).join(" • ") || "Dati anagrafici da completare",
    actionLabel: "Apri anagrafica",
    actionRoute: route("companies.edit", props.company.id),
  },
  {
    key: "sites",
    title: "Sedi",
    status: sitesCount.value > 0 ? `${sitesCount.value}` : "Vuote",
    summary: sitesCount.value > 0
      ? (props.company.sites ?? []).slice(0, 2).map((site) => site.name).join(" • ")
      : "Nessuna sede operativa censita",
    actionLabel: "Nuova sede",
    actionRoute: "__open_site_offcanvas__",
  },
]);

const engineRows = computed(() => [
  {
    key: "risk-profile",
    title: "Rischi nel profilo",
    status: `${props.contextBridge.stats.activeRisks} attivi`,
    summary: props.contextBridge.stats.uncoveredRisks > 0
      ? `${props.contextBridge.stats.uncoveredRisks} rischi restano scoperti | ${props.contextBridge.stats.highPriorityRisks} ad alta priorita' richiedono lettura immediata.`
      : "Il profilo rischio aziendale emerge dai produttori oggi presenti nel contesto.",
    actionLabel: "Apri rischi",
    actionRoute: props.contextBridge.actions.riskProfileRoute,
  },
  {
    key: "expected-measures",
    title: "Presidi attesi",
    status: props.contextBridge.stats.missingExpectedMeasures > 0
      ? `${props.contextBridge.stats.missingExpectedMeasures} gap`
      : "Allineati",
    summary: props.coreStarterPack.summary.expectedMeasuresCount > 0
      ? `${props.contextBridge.stats.coveredExpectedMeasures} coperti diretti${props.contextBridge.stats.substitutedExpectedMeasures > 0 ? ` | ${props.contextBridge.stats.substitutedExpectedMeasures} per equivalenza` : ""} su ${props.coreStarterPack.summary.expectedMeasuresCount} attesi.`
      : "Il motore non ha ancora espresso presidi attesi nel perimetro corrente.",
    actionLabel: "Apri misure attese",
    actionRoute: props.contextBridge.actions.measuresRoute,
  },
  {
    key: "coverage",
    title: "Copertura e stato operativo",
    status: `${props.contextBridge.stats.coverageRate}%`,
    summary: props.contextBridge.stats.pendingMeasures > 0
      ? `${props.contextBridge.stats.pendingMeasures} misure restano aperte o da verificare | ${props.contextBridge.stats.risksWithExpectedGaps} rischi hanno ancora gap attesi.`
      : props.contextBridge.suggestedAction.helper,
    actionLabel: "Apri registri famiglia",
    actionRoute: props.contextBridge.actions.registryRoute,
  },
]);

const operationsRows = computed(() => [
  {
    key: "review",
    title: "Review",
    status: props.contextBridge.stats.reviewsDue > 0 ? `${props.contextBridge.stats.reviewsDue} dovute` : "Allineate",
    summary: props.contextBridge.stats.reviewsDue > 0
      ? "Ci sono rischi che richiedono una review consulenziale nel profilo aziendale."
      : "Il giudizio consulenziale sul profilo aziendale risulta allineato.",
    actionLabel: "Apri review",
    actionRoute: props.contextBridge.actions.reviewRoute,
  },
  {
    key: "follow-up",
    title: "Follow-up",
    status: props.contextBridge.stats.followUpsOpen > 0 ? `${props.contextBridge.stats.followUpsOpen} aperti` : "Allineati",
    summary: props.contextBridge.stats.followUpsOpen > 0
      ? "Alcuni rischi restano in carico operativo e vanno chiusi tra review e registri."
      : "Non ci sono follow-up aperti nel perimetro aziendale corrente.",
    actionLabel: "Apri follow-up",
    actionRoute: props.contextBridge.operationalQueue?.find((item) => item.key === "follow_up")?.actionRoute ?? props.contextBridge.actions.registryRoute,
  },
  {
    key: "registries",
    title: "Registri famiglia",
    status: props.contextBridge.stats.pendingMeasures > 0
      ? `${props.contextBridge.stats.pendingMeasures} aperte`
      : "Presidiati",
    summary: props.contextBridge.stats.overdueMeasures > 0
      ? `${props.contextBridge.stats.overdueMeasures} misure risultano oltre data e richiedono un passaggio immediato.`
      : "Controlla stato, scadenze, owner e copertura delle misure per famiglia.",
    actionLabel: "Apri registri",
    actionRoute: props.contextBridge.actions.registryRoute,
  },
  {
    key: "workspace",
    title: "Workspace operativo",
    status: props.contextBridge.focusLabel,
    summary: "Apri la vista operativa coerente con il focus oggi piu' rilevante.",
    actionLabel: "Apri workspace",
    actionRoute: props.contextBridge.actions.dashboardRoute,
  },
  {
    key: "dvr",
    title: "DVR",
    status: "Output vivo",
    summary: "Consulta il DVR iniziale come conseguenza del contesto e del rischio attuale.",
    actionLabel: "Apri DVR",
    actionRoute: props.contextBridge.actions.dvrRoute,
  },
]);

const trackTitle = computed(() => domainTracks.find((track) => track.key === activeTrack.value)?.label ?? "");
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
        <BRow class="g-4 align-items-start">
          <BCol xl="5" lg="5">
            <div class="rounded-3 bg-light-subtle p-3 h-100">
              <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                <Link
                  :href="route('companies.edit', company.id)"
                  class="btn btn-icon btn-soft-secondary rounded-circle"
                  title="Modifica anagrafica"
                  aria-label="Modifica anagrafica"
                >
                  <i class="ri-pencil-line"></i>
                </Link>
                <h2 class="mb-0">{{ company.name }}</h2>
              </div>
              <div v-if="headerMetaCompact.length > 0" class="vstack gap-2">
                <div
                  v-for="item in headerMetaCompact"
                  :key="item.key"
                  class="d-flex align-items-center gap-2 rounded-3 bg-white px-3 py-2 shadow-sm"
                >
                  <span class="avatar-xs flex-shrink-0">
                    <span class="avatar-title rounded-circle bg-light text-body">
                      <i :class="item.icon"></i>
                    </span>
                  </span>
                  <div class="min-w-0">
                    <div class="fw-medium">{{ item.value }}</div>
                    <div v-if="item.helper" class="text-muted fs-13">{{ item.helper }}</div>
                  </div>
                </div>
              </div>
            </div>
          </BCol>

          <BCol xl="4" lg="4">
            <div class="rounded-3 bg-light-subtle p-3 h-100">
              <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                <div>
                  <h5 class="mb-0">Sedi ({{ sitesCount }})</h5>
                </div>
                <div class="hstack gap-2 align-items-center">
                  <button
                    v-if="sitesCount > 3"
                    type="button"
                    class="btn btn-link btn-sm text-primary text-decoration-none px-0"
                    @click="showAllSites = !showAllSites"
                  >
                    {{ showAllSites ? "Mostra meno" : "Mostra tutte" }}
                  </button>
                  <BButton variant="primary" size="sm" @click="openCreateSiteOffcanvas">Nuova sede</BButton>
                </div>
              </div>

              <div v-if="visibleSites.length === 0" class="text-muted fs-13">
                Nessuna sede ancora censita.
              </div>

              <div v-else class="list-group list-group-flush">
                <div
                  v-for="site in visibleSites"
                  :key="site.id"
                  class="list-group-item px-3 py-2 border-0 rounded-3 bg-white shadow-sm mb-2"
                >
                  <div class="d-flex align-items-start justify-content-between gap-3">
                    <div class="min-w-0">
                      <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <div class="fw-medium">{{ site.name }}</div>
                        <span
                          class="badge"
                          :class="site.is_headquarters ? 'bg-primary-subtle text-primary' : 'bg-light text-body'"
                        >
                          {{ site.is_headquarters ? "Principale" : "Operativa" }}
                        </span>
                      </div>
                      <div class="text-muted fs-13">
                        {{ site.city || "Citta' non indicata" }}<template v-if="site.province"> ({{ site.province }})</template>
                      </div>
                    </div>
                    <div class="d-inline-flex align-items-center gap-1 flex-shrink-0">
                      <BButton
                        :id="siteActionTooltipId(site.id, 'edit')"
                        variant="soft-secondary"
                        size="sm"
                        class="btn-icon"
                        style="width: 2rem; height: 2rem;"
                        aria-label="Modifica sede"
                        @click="openEditSiteOffcanvas(site)"
                      >
                        <i class="ri-pencil-line"></i>
                      </BButton>
                      <BTooltip :target="siteActionTooltipId(site.id, 'edit')" title="Modifica sede" />

                      <BButton
                        :id="siteActionTooltipId(site.id, 'delete')"
                        variant="soft-danger"
                        size="sm"
                        class="btn-icon"
                        style="width: 2rem; height: 2rem;"
                        aria-label="Elimina sede"
                        @click="deleteSite(site)"
                      >
                        <i class="ri-delete-bin-line"></i>
                      </BButton>
                      <BTooltip :target="siteActionTooltipId(site.id, 'delete')" title="Elimina sede" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </BCol>

          <BCol xl="3" lg="3">
            <div class="rounded-3 bg-light-subtle p-3 h-100 d-flex justify-content-lg-end justify-content-start align-items-start">
              <Link :href="route('companies.index')" class="btn btn-soft-secondary">
                <i class="ri-arrow-left-line me-1 align-bottom"></i>Torna ad aziende
              </Link>
            </div>
          </BCol>
        </BRow>
      </BCardBody>
    </BCard>


    <BRow class="g-3 mb-4">
      <BCol v-for="card in riskSourceCardUi" :key="card.key" md="6" xl="4">
        <BCard no-body class="h-100 shadow-sm border-0 overflow-hidden">
          <BCardBody class="p-4 d-flex flex-column" :class="card.surfaceClass">
            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title rounded-circle fs-20" :class="card.avatarClass">
                    <i :class="card.icon"></i>
                  </span>
                </div>
                <div>
                  <h5 class="mb-1">{{ card.title }}</h5>
                  <div class="text-muted fs-13">{{ card.summary }}</div>
                </div>
              </div>
              <div class="text-end">
                <div class="d-inline-flex align-items-center rounded-pill px-3 py-2 shadow-sm" :class="card.countBadgeClass">
                  <div class="fw-semibold fs-2 lh-1" :class="card.countClass">{{ card.count }}</div>
                </div>
              </div>
            </div>

            <div class="mt-auto">
              <Link :href="card.actionRoute" class="btn btn-sm w-100 shadow-sm" :class="`btn-${card.buttonVariant}`">{{ card.actionLabel }}</Link>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BCard no-body>
      <BCardBody class="p-4">
        <div class="rounded-3 border bg-light-subtle p-3 p-lg-4 mb-4">
          <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
              <div class="text-uppercase text-muted fw-semibold fs-12 mb-1">Focus operativo di oggi</div>
              <h4 class="mb-1">{{ contextBridge.suggestedAction.label }}</h4>
              <p class="text-muted mb-0">{{ contextBridge.suggestedAction.helper }}</p>
            </div>
            <div class="hstack gap-2 flex-wrap">
              <Link :href="contextBridge.actions.riskProfileRoute" class="btn btn-primary btn-sm">
                Apri profilo rischio
              </Link>
              <Link :href="contextBridge.actions.registryRoute" class="btn btn-soft-primary btn-sm">
                Apri registri
              </Link>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2 mt-3">
            <div
              v-for="signal in focusSignals"
              :key="signal.key"
              class="d-inline-flex align-items-center gap-2 rounded-pill bg-white px-3 py-2 shadow-sm"
            >
              <span class="fw-semibold">{{ signal.value }}</span>
              <span class="text-muted fs-13">{{ signal.label }}</span>
            </div>
          </div>

          <div v-if="focusWorkQueue.length" class="rounded-3 bg-white border mt-3 overflow-hidden">
            <div class="px-3 py-2 border-bottom text-uppercase text-muted fw-semibold fs-12">
              Coda di lavoro minima
            </div>
            <div
              v-for="(item, index) in focusWorkQueue"
              :key="item.key"
              class="d-flex align-items-start justify-content-between gap-3 px-3 py-3"
              :class="{ 'border-top': index > 0 }"
            >
              <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                  <h6 class="mb-0">{{ item.label }}</h6>
                  <span class="badge bg-light text-body">{{ item.count }}</span>
                </div>
                <div class="text-muted fs-13">{{ item.helper }}</div>
              </div>
              <div class="flex-shrink-0">
                <Link :href="item.actionRoute" class="btn btn-sm btn-soft-primary">{{ item.actionLabel }}</Link>
              </div>
            </div>
          </div>
        </div>

        <div class="nav nav-pills nav-success gap-2 mb-4">
          <button
            v-for="track in domainTracks"
            :key="track.key"
            type="button"
            class="btn"
            :class="activeTrack === track.key ? 'btn-primary' : 'btn-soft-primary'"
            @click="activeTrack = track.key"
          >
            <i :class="`${track.icon} align-bottom me-1`"></i>{{ track.label }}
          </button>
        </div>

        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <h4 class="mb-1">{{ trackTitle }}</h4>
            <p v-if="activeTrack === 'company-context'" class="text-muted mb-0">
              Qui tieni allineati anagrafica, sedi e ATECO come riferimento orientativo dell'azienda.
            </p>
            <p v-else-if="activeTrack === 'engine-output'" class="text-muted mb-0">
              Qui leggi cosa restituisce il motore: rischi dedotti, misure proposte e copertura iniziale.
            </p>
            <p v-else class="text-muted mb-0">
              Qui passi al governo del rischio: review, registri, workspace e DVR.
            </p>
          </div>

          <Link
            v-if="activeTrack === 'company-context'"
            :href="route('companies.edit', company.id)"
            class="btn btn-primary btn-sm"
          >
            Configura azienda
          </Link>
          <Link
            v-else-if="activeTrack === 'engine-output'"
            :href="contextBridge.actions.riskProfileRoute"
            class="btn btn-primary btn-sm"
          >
            Apri profilo rischio
          </Link>
          <Link
            v-else
            :href="contextBridge.actions.registryRoute"
            class="btn btn-warning btn-sm"
          >
            Apri registri
          </Link>
        </div>

        <div class="border rounded-3 overflow-hidden">
          <template v-if="activeTrack === 'company-context'">
            <div
              v-for="(row, index) in companyContextRows"
              :key="row.key"
              class="d-flex align-items-start justify-content-between gap-3 p-3"
              :class="{ 'border-top': index > 0 }"
            >
              <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                  <h5 class="mb-0">{{ row.title }}</h5>
                  <span class="badge bg-light text-body">{{ row.status }}</span>
                </div>
                <div class="text-muted fs-13">{{ row.summary }}</div>
              </div>
              <div class="flex-shrink-0">
                <a
                  v-if="row.actionRoute === '__open_site_offcanvas__'"
                  href="#"
                  class="btn btn-sm btn-soft-primary"
                  @click.prevent="openCreateSiteOffcanvas"
                >
                  {{ row.actionLabel }}
                </a>
                <Link v-else :href="row.actionRoute" class="btn btn-sm btn-soft-primary">{{ row.actionLabel }}</Link>
              </div>
            </div>
          </template>

          <template v-else-if="activeTrack === 'engine-output'">
            <div class="p-3 border-bottom bg-light-subtle">
              <div class="row g-3">
                <div v-for="item in engineHighlights" :key="item.key" class="col-xl-3 col-md-6">
                  <div class="rounded-3 bg-white border h-100 px-3 py-3">
                    <div class="text-uppercase text-muted fw-semibold fs-12 mb-1">{{ item.title }}</div>
                    <div class="fw-semibold fs-5 text-body mb-1">{{ item.value }}</div>
                    <div class="text-muted fs-13">{{ item.helper }}</div>
                  </div>
                </div>
              </div>
            </div>
            <div
              v-for="(row, index) in engineRows"
              :key="row.key"
              class="d-flex align-items-start justify-content-between gap-3 p-3"
              :class="{ 'border-top': index > 0 }"
            >
              <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                  <h5 class="mb-0">{{ row.title }}</h5>
                  <span class="badge bg-light text-body">{{ row.status }}</span>
                </div>
                <div class="text-muted fs-13">{{ row.summary }}</div>
              </div>
              <div class="flex-shrink-0">
                <Link :href="row.actionRoute" class="btn btn-sm btn-soft-primary">{{ row.actionLabel }}</Link>
              </div>
            </div>
          </template>

          <template v-else>
            <div class="p-3 border-bottom bg-light-subtle">
              <div class="row g-3">
                <div v-for="item in operationsQueue" :key="item.key" class="col-xl-3 col-md-6">
                  <div class="rounded-3 bg-white border h-100 px-3 py-3">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                      <div class="text-uppercase text-muted fw-semibold fs-12">{{ item.label }}</div>
                      <span class="badge" :class="item.status === 'aligned' ? 'bg-success-subtle text-success' : 'bg-light text-body'">
                        {{ item.count }}
                      </span>
                    </div>
                    <div class="text-muted fs-13 mb-3">{{ item.helper }}</div>
                    <Link :href="item.actionRoute" class="btn btn-sm btn-soft-primary">{{ item.actionLabel }}</Link>
                  </div>
                </div>
              </div>
            </div>
            <div
              v-for="(row, index) in operationsRows"
              :key="row.key"
              class="d-flex align-items-start justify-content-between gap-3 p-3"
              :class="{ 'border-top': index > 0 }"
            >
              <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                  <h5 class="mb-0">{{ row.title }}</h5>
                  <span class="badge bg-light text-body">{{ row.status }}</span>
                </div>
                <div class="text-muted fs-13">{{ row.summary }}</div>
              </div>
              <div class="flex-shrink-0">
                <Link :href="row.actionRoute" class="btn btn-sm btn-soft-primary">{{ row.actionLabel }}</Link>
              </div>
            </div>
          </template>
        </div>
      </BCardBody>
    </BCard>

    <BOffcanvas
      v-model="openSiteOffcanvas"
      placement="end"
      body-class="p-4"
      style="--bs-offcanvas-width: min(1320px, 98vw);"
    >
      <template #header><div><h5 class="mb-0">{{ siteOffcanvasTitle }}</h5></div></template>
      <form @submit.prevent="submitSite">
        <SiteForm
          :form="siteForm"
          :submit-label="siteSubmitLabel"
          :comuni-config="{ searchRoute: route('comuni.search'), initialOption: siteComuneOption }"
          embedded
          @cancel="resetSiteEditor(); openSiteOffcanvas = false"
        />
      </form>
    </BOffcanvas>
  </Layout>
</template>
