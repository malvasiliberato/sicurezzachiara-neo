<script setup>
import { ref } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import SicurezzaDataTable from "@/Components/SicurezzaDataTable.vue";

const actionTooltipId = (companyId, action) => `company-${companyId}-${action}-tooltip`;
const statusTooltipId = (companyId) => `company-${companyId}-status-tooltip`;
const industryTooltipId = (companyId) => `company-${companyId}-industry-tooltip`;
const portfolioKpiTooltipId = (key) => `portfolio-kpi-${key}-tooltip`;

const deleteCompanyForm = useForm({});
const restoreCompanyForm = useForm({});
const expandedAlertRows = ref(new Set());

const companyLocation = (company) => company.location_label || "Sede non ancora indicata";

const setupReady = (company) => company.area_one_journey.completedSteps >= company.area_one_journey.totalSteps;

const stateBadge = (company) => {
  if (company.is_deleted) {
    return {
      label: "Eliminata",
      icon: "ri-delete-bin-line",
      class: "bg-secondary-subtle text-secondary",
      detail: "Azienda eliminata, puoi ripristinarla nel portfolio attivo.",
    };
  }

  if (!setupReady(company)) {
    return {
      label: "Da completare",
      icon: "ri-error-warning-line",
      class: "bg-warning-subtle text-warning",
      detail: "Mancano ancora alcuni produttori di rischio: sedi, luoghi, macchinari o lavoratori.",
    };
  }

  return {
    label: "Pronta",
    icon: "ri-check-line",
    class: "bg-success-subtle text-success",
    detail: "Il contesto minimo e' pronto: puoi verificare rischi, misure e registri.",
  };
};

const visibleAlerts = (company) => {
  if (company.is_deleted) {
    return [];
  }

  const alerts = [];
  const governance = company.portfolio_governance || {};

  if ((governance.overdueMeasures ?? 0) > 0) {
    alerts.push({
      text: `${governance.overdueMeasures} ${governance.overdueMeasures === 1 ? "misura scaduta" : "misure scadute"}`,
      class: "text-danger",
      icon: "ri-alarm-warning-line",
      priority: 1,
    });
  }

  if ((governance.reviewsOpen ?? 0) > 0) {
    alerts.push({
      text: `${governance.reviewsOpen} ${governance.reviewsOpen === 1 ? "review aperta" : "review aperte"}`,
      class: "text-info",
      icon: "ri-calendar-check-line",
      priority: 2,
    });
  }

  if ((governance.pendingMeasures ?? 0) > 0) {
    alerts.push({
      text: `${governance.pendingMeasures} ${governance.pendingMeasures === 1 ? "misura mancante" : "misure mancanti"}`,
      class: "text-warning",
      icon: "ri-shield-line",
      priority: 2,
    });
  }

  if ((governance.risksToVerify ?? 0) > 0) {
    alerts.push({
      text: `${governance.risksToVerify} ${governance.risksToVerify === 1 ? "rischio da verificare" : "rischi da verificare"}`,
      class: "text-primary",
      icon: "ri-alert-line",
      priority: 3,
    });
  }

  return alerts.sort((left, right) => left.priority - right.priority);
};

const primaryAlerts = (company) => visibleAlerts(company).slice(0, 2);

const hiddenAlerts = (company) => visibleAlerts(company).slice(2);

const areHiddenAlertsExpanded = (companyId) => expandedAlertRows.value.has(companyId);

const toggleHiddenAlerts = (companyId) => {
  const next = new Set(expandedAlertRows.value);

  if (next.has(companyId)) {
    next.delete(companyId);
  } else {
    next.add(companyId);
  }

  expandedAlertRows.value = next;
};

const confirmDeleteCompany = (company) => {
  Swal.fire({
    title: "Eliminare questa azienda?",
    text: "I dati non saranno rimossi definitivamente, ma l'azienda non sara' piu' visibile nel portfolio.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Si, elimina",
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

    deleteCompanyForm.delete(route("companies.destroy", company.id), {
      preserveScroll: true,
    });
  });
};

const confirmRestoreCompany = (company) => {
  Swal.fire({
    title: "Ripristinare questa azienda?",
    text: "Tornera' visibile nel portfolio aziende.",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Si, ripristina",
    cancelButtonText: "Annulla",
    reverseButtons: true,
    customClass: {
      confirmButton: "btn btn-success w-xs me-2 mt-2",
      cancelButton: "btn btn-soft-secondary w-xs mt-2",
    },
    buttonsStyling: false,
  }).then((result) => {
    if (!result.isConfirmed) {
      return;
    }

    restoreCompanyForm.post(route("companies.restore", company.id), {
      preserveScroll: true,
    });
  });
};

const columns = [
  {
    id: "status",
    accessorFn: (row) => row.area_one_journey?.nextStep?.label || "",
    header: "Stato operativo",
    enableSorting: true,
    meta: {
      width: "22%",
      slot: "status",
    },
  },
  {
    id: "company",
    accessorFn: (row) => row.name,
    header: "Azienda",
    enableSorting: true,
    meta: {
      width: "24%",
      slot: "company",
    },
  },
  {
    id: "alerts",
    accessorFn: (row) => row.portfolio_governance?.headline || "",
    header: "Alert",
    enableSorting: true,
    meta: {
      width: "26%",
      slot: "alerts",
    },
  },
  {
    id: "actions",
    accessorFn: (row) => row.id,
    header: "Azioni",
    enableSorting: false,
    meta: {
      width: "14%",
      slot: "actions",
      thClass: "text-end",
      tdClass: "text-end",
      tdStyle: "white-space: nowrap;",
    },
  },
];

defineProps({
  tenant: {
    type: Object,
    required: true,
  },
  companies: {
    type: Array,
    required: true,
  },
  summary: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    required: true,
  },
  areaOne: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <Layout>
    <Head title="Aziende" />

    <PageHeader title="Aziende" pageTitle="SicurezzaChiara" />

    <BAlert v-if="$page.props.flash.success" show variant="success" class="mb-4">
      <i class="ri-checkbox-circle-line me-2 align-middle"></i>
      {{ $page.props.flash.success }}
    </BAlert>

    <BRow class="g-4">
      <BCol lg="12">
        <BCard no-body>
          <BCardBody class="py-2">
            <BRow class="g-2">
              <BCol sm="6" xl="3">
                <div
                  :id="portfolioKpiTooltipId('active')"
                  class="border rounded-3 bg-light-subtle h-100 px-3 py-1"
                  style="cursor: help;"
                >
                  <div class="d-flex align-items-center gap-3 h-100">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary flex-shrink-0" style="width: 2.5rem; height: 2.5rem;">
                      <i class="ri-building-line"></i>
                    </span>
                    <div class="min-w-0">
                      <div class="fw-semibold fs-3 lh-1 text-body mb-1">{{ summary.activeCompaniesCount }}</div>
                      <div class="text-muted fs-12 lh-sm">Aziende attive</div>
                    </div>
                  </div>
                </div>
                <BTooltip
                  :target="portfolioKpiTooltipId('active')"
                  title="Aziende visibili nel portfolio operativo."
                />
              </BCol>
              <BCol sm="6" xl="3">
                <div
                  :id="portfolioKpiTooltipId('ready')"
                  class="border rounded-3 bg-light-subtle h-100 px-3 py-1"
                  style="cursor: help;"
                >
                  <div class="d-flex align-items-center gap-3 h-100">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success flex-shrink-0" style="width: 2.5rem; height: 2.5rem;">
                      <i class="ri-check-line"></i>
                    </span>
                    <div class="min-w-0">
                      <div class="fw-semibold fs-3 lh-1 text-body mb-1">{{ summary.readyCompaniesCount }}</div>
                      <div class="text-muted fs-12 lh-sm">Pronte per governance</div>
                    </div>
                  </div>
                </div>
                <BTooltip
                  :target="portfolioKpiTooltipId('ready')"
                  title="Aziende con contesto minimo completo."
                />
              </BCol>
              <BCol sm="6" xl="3">
                <div
                  :id="portfolioKpiTooltipId('incomplete')"
                  class="border rounded-3 bg-light-subtle h-100 px-3 py-1"
                  style="cursor: help;"
                >
                  <div class="d-flex align-items-center gap-3 h-100">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning-subtle text-warning flex-shrink-0" style="width: 2.5rem; height: 2.5rem;">
                      <i class="ri-error-warning-line"></i>
                    </span>
                    <div class="min-w-0">
                      <div class="fw-semibold fs-3 lh-1 text-body mb-1">{{ summary.incompleteCompaniesCount }}</div>
                      <div class="text-muted fs-12 lh-sm">Da completare</div>
                    </div>
                  </div>
                </div>
                <BTooltip
                  :target="portfolioKpiTooltipId('incomplete')"
                  title="Aziende che richiedono ancora dati minimi o produttori di rischio."
                />
              </BCol>
              <BCol sm="6" xl="3">
                <div
                  :id="portfolioKpiTooltipId('alerts')"
                  class="border rounded-3 bg-light-subtle h-100 px-3 py-1"
                  style="cursor: help;"
                >
                  <div class="d-flex align-items-center gap-3 h-100">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-info-subtle text-info flex-shrink-0" style="width: 2.5rem; height: 2.5rem;">
                      <i class="ri-alert-line"></i>
                    </span>
                    <div class="min-w-0">
                      <div class="fw-semibold fs-3 lh-1 text-body mb-1">{{ summary.companiesWithAlertsCount }}</div>
                      <div class="text-muted fs-12 lh-sm">Con alert</div>
                    </div>
                  </div>
                </div>
                <BTooltip
                  :target="portfolioKpiTooltipId('alerts')"
                  title="Aziende con almeno un rischio, misura, review o scadenza da gestire."
                />
              </BCol>
            </BRow>
          </BCardBody>
        </BCard>

        <BCard no-body>
          <BCardHeader class="border-0">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
              <div>
                <h4 class="card-title mb-1">Portfolio aziende</h4>
              </div>
              <div class="d-flex flex-wrap align-items-center gap-2">
                <div
                  class="d-inline-flex align-items-center rounded-pill p-1 bg-light-subtle border border-light-subtle shadow-sm"
                  role="group"
                  aria-label="Filtro aziende"
                >
                  <Link
                    :href="route('companies.index')"
                    class="btn btn-sm d-inline-flex align-items-center justify-content-center gap-2 rounded-pill px-3"
                    style="min-width: 8.5rem;"
                    :class="
                      filters.view === 'trashed'
                        ? 'btn-link text-body text-decoration-none opacity-75'
                        : 'btn-white text-primary fw-semibold shadow-sm border border-primary-subtle'
                    "
                    aria-label="Mostra aziende attive"
                    title="Mostra aziende attive"
                  >
                    <span>Attive</span>
                    <span
                      class="badge rounded-pill"
                      :class="
                        filters.view === 'trashed'
                          ? 'bg-secondary-subtle text-body'
                          : 'bg-primary text-white'
                      "
                    >
                      {{ summary.activeCompaniesCount }}
                    </span>
                  </Link>
                  <Link
                    :href="route('companies.index', { view: 'trashed' })"
                    class="btn btn-sm d-inline-flex align-items-center justify-content-center gap-2 rounded-pill px-3"
                    style="min-width: 8.5rem;"
                    :class="
                      filters.view === 'trashed'
                        ? 'btn-white text-danger fw-semibold shadow-sm border border-danger-subtle'
                        : 'btn-link text-body text-decoration-none opacity-75'
                    "
                    aria-label="Mostra aziende eliminate"
                    title="Mostra aziende eliminate"
                  >
                    <span>Eliminate</span>
                    <span
                      class="badge rounded-pill"
                      :class="
                        filters.view === 'trashed'
                          ? 'bg-danger text-white'
                          : 'bg-secondary-subtle text-body'
                      "
                    >
                      {{ summary.trashedCompaniesCount }}
                    </span>
                  </Link>
                </div>
                <Link
                  v-if="$page.props.tenantContext?.permissions?.can_manage_data"
                  :href="route('companies.create')"
                  class="btn btn-primary btn-sm d-inline-flex align-items-center justify-content-center gap-1"
                  style="min-width: 9.5rem;"
                  aria-label="Crea nuova azienda"
                  title="Crea nuova azienda"
                >
                  <i class="ri-add-line me-1 align-bottom"></i>
                  Nuova azienda
                </Link>
              </div>
            </div>
          </BCardHeader>
          <BCardBody class="pt-0">
            <div v-if="companies.length === 0" class="text-center py-5">
              <div class="avatar-md mx-auto mb-3">
                <div class="avatar-title bg-light text-primary rounded-circle fs-2">
                  <i class="ri-building-line"></i>
                </div>
              </div>
              <h5 class="mb-2">{{ filters.view === "trashed" ? "Nessuna azienda eliminata" : "Nessuna azienda ancora presente" }}</h5>
              <p class="text-muted mb-4">
                {{
                  filters.view === "trashed"
                    ? "Le aziende rimosse dal portfolio compariranno qui e potranno essere ripristinate."
                    : "Crea la prima azienda del tenant per iniziare a costruire il contesto operativo del progetto."
                }}
              </p>
              <Link
                v-if="filters.view !== 'trashed' && $page.props.tenantContext?.permissions?.can_manage_data"
                :href="route('companies.create')"
                class="btn btn-primary"
              >
                Crea prima azienda
              </Link>
            </div>

            <SicurezzaDataTable
              v-else
              :columns="columns"
              :data="companies"
              search-placeholder="Cerca azienda"
              empty-title="Nessuna azienda trovata"
              empty-text="Nessuna azienda corrisponde ai filtri correnti."
            >
              <template #cell-company="{ row }">
                <div class="fw-semibold text-break">{{ row.name }}</div>
                <div class="text-muted fs-12 text-break">{{ companyLocation(row) }}</div>
                <div
                  :id="industryTooltipId(row.id)"
                  class="text-truncate text-muted fs-12"
                  style="max-width: 260px;"
                >
                  {{ row.industry || "Non indicato" }}
                </div>
                <BTooltip :target="industryTooltipId(row.id)" :title="row.industry || 'Settore non indicato'" />
              </template>

              <template #cell-status="{ row }">
                <span :id="statusTooltipId(row.id)" class="badge" :class="stateBadge(row).class">
                  <i :class="[stateBadge(row).icon, 'me-1 align-bottom']"></i>
                  {{ stateBadge(row).label }}
                </span>
                <BTooltip :target="statusTooltipId(row.id)" :title="stateBadge(row).detail" />
              </template>

              <template #cell-alerts="{ row }">
                <div v-if="row.is_deleted" class="text-muted fs-13">
                  -
                </div>
                <div v-else-if="visibleAlerts(row).length === 0" class="text-muted fs-13">
                  -
                </div>
                <div v-else class="d-flex flex-column gap-1">
                  <div
                    v-for="alert in primaryAlerts(row)"
                    :key="alert.text"
                    class="d-flex align-items-center gap-2 fs-13"
                  >
                    <i :class="[alert.icon, alert.class, 'flex-shrink-0']"></i>
                    <span class="text-body">{{ alert.text }}</span>
                  </div>
                  <button
                    v-if="hiddenAlerts(row).length > 0 && !areHiddenAlertsExpanded(row.id)"
                    type="button"
                    class="btn btn-link btn-sm p-0 align-self-start text-muted text-decoration-none"
                    @click="toggleHiddenAlerts(row.id)"
                  >
                    +{{ hiddenAlerts(row).length }}
                    {{ hiddenAlerts(row).length === 1 ? "altro alert" : "altri alert" }}
                  </button>
                  <div v-if="areHiddenAlertsExpanded(row.id)" class="d-flex flex-column gap-1">
                    <div
                      v-for="alert in hiddenAlerts(row)"
                      :key="`${row.id}-${alert.text}`"
                      class="d-flex align-items-center gap-2 fs-13"
                    >
                      <i :class="[alert.icon, alert.class, 'flex-shrink-0']"></i>
                      <span class="text-body">{{ alert.text }}</span>
                    </div>
                    <button
                      v-if="hiddenAlerts(row).length > 0"
                      type="button"
                      class="btn btn-link btn-sm p-0 align-self-start text-muted text-decoration-none"
                      @click="toggleHiddenAlerts(row.id)"
                    >
                      Mostra meno
                    </button>
                  </div>
                </div>
              </template>

              <template #cell-actions="{ row }">
                <div class="d-inline-flex align-items-center gap-1 justify-content-end">
                  <Link
                    :id="actionTooltipId(row.id, 'show')"
                    :href="route('companies.show', row.id)"
                    class="btn btn-soft-primary btn-icon"
                    style="width: 2.25rem; height: 2.25rem;"
                    aria-label="Apri dashboard azienda"
                  >
                    <i class="ri-eye-line fs-16"></i>
                  </Link>
                  <BTooltip :target="actionTooltipId(row.id, 'show')" title="Apri dashboard azienda" />

                  <Link
                    v-if="$page.props.tenantContext?.permissions?.can_manage_data && !row.is_deleted"
                    :id="actionTooltipId(row.id, 'edit')"
                    :href="route('companies.edit', row.id)"
                    class="btn btn-soft-secondary btn-icon"
                    style="width: 2.25rem; height: 2.25rem;"
                    aria-label="Configura azienda"
                  >
                    <i class="ri-pencil-line fs-16"></i>
                  </Link>
                  <BTooltip
                    v-if="$page.props.tenantContext?.permissions?.can_manage_data && !row.is_deleted"
                    :target="actionTooltipId(row.id, 'edit')"
                    title="Configura azienda"
                  />

                  <button
                    v-if="$page.props.tenantContext?.permissions?.can_manage_data && !row.is_deleted"
                    :id="actionTooltipId(row.id, 'delete')"
                    type="button"
                    class="btn btn-soft-danger btn-icon"
                    style="width: 2.25rem; height: 2.25rem;"
                    aria-label="Elimina azienda"
                    @click="confirmDeleteCompany(row)"
                  >
                    <i class="ri-delete-bin-line fs-16"></i>
                  </button>
                  <BTooltip
                    v-if="$page.props.tenantContext?.permissions?.can_manage_data && !row.is_deleted"
                    :target="actionTooltipId(row.id, 'delete')"
                    title="Elimina azienda"
                  />

                  <button
                    v-if="$page.props.tenantContext?.permissions?.can_manage_data && row.is_deleted"
                    :id="actionTooltipId(row.id, 'restore')"
                    type="button"
                    class="btn btn-soft-success btn-icon"
                    style="width: 2.25rem; height: 2.25rem;"
                    aria-label="Ripristina azienda"
                    @click="confirmRestoreCompany(row)"
                  >
                    <i class="ri-refresh-line fs-16"></i>
                  </button>
                  <BTooltip
                    v-if="$page.props.tenantContext?.permissions?.can_manage_data && row.is_deleted"
                    :target="actionTooltipId(row.id, 'restore')"
                    title="Ripristina azienda"
                  />
                </div>
              </template>
            </SicurezzaDataTable>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>
  </Layout>
</template>
