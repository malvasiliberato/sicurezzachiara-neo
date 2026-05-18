<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import SicurezzaDataTable from "@/Components/SicurezzaDataTable.vue";

const props = defineProps({
  tenant: {
    type: Object,
    required: true,
  },
  workers: {
    type: Array,
    required: true,
  },
  summary: {
    type: Object,
    required: true,
  },
  companyContext: {
    type: Object,
    default: null,
  },
});

const actionTooltipId = (workerId, action) => `worker-${workerId}-${action}-tooltip`;

const columns = [
  {
    id: "worker",
    accessorFn: (row) => row.full_name,
    header: "Lavoratore",
    enableSorting: true,
    meta: {
      width: "26%",
      slot: "worker",
    },
  },
  {
    id: "company",
    accessorFn: (row) => row.company?.name || "",
    header: "Azienda",
    enableSorting: true,
    meta: {
      width: "18%",
      slot: "company",
    },
  },
  {
    id: "context",
    accessorFn: (row) => row.primary_site?.name || row.primary_job_role?.name || "",
    header: "Contesto",
    enableSorting: true,
    meta: {
      width: "24%",
      slot: "context",
    },
  },
  {
    id: "contacts",
    accessorFn: (row) => [row.email, row.phone].filter(Boolean).join(" "),
    header: "Contatti",
    enableSorting: true,
    meta: {
      width: "18%",
      slot: "contacts",
    },
  },
  {
    id: "status",
    accessorFn: (row) => row.status || "",
    header: "Stato",
    enableSorting: true,
    meta: {
      width: "8%",
      slot: "status",
    },
  },
  {
    id: "actions",
    accessorFn: (row) => row.id,
    header: "Azioni",
    enableSorting: false,
    meta: {
      width: "6%",
      slot: "actions",
      thClass: "text-end",
      tdClass: "text-end",
      tdStyle: "white-space: nowrap;",
    },
  },
];
</script>

<template>
  <Layout>
    <Head title="Lavoratori" />

    <PageHeader title="Lavoratori" pageTitle="SicurezzaChiara" />

    <BAlert v-if="$page.props.flash.success" show variant="success" class="mb-4">
      <i class="ri-checkbox-circle-line me-2 align-middle"></i>
      {{ $page.props.flash.success }}
    </BAlert>

    <BCard no-body class="mb-4 shadow-sm border-0">
      <BCardBody class="p-4">
        <BRow class="g-4 align-items-start">
          <BCol xl="8" lg="8">
            <div class="rounded-3 bg-light-subtle p-3 h-100">
              <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
                <div>
                  <h2 class="mb-1">{{ companyContext ? "Lavoratori" : "Registro lavoratori" }}</h2>
                  <div class="text-muted">
                    {{ companyContext ? companyContext.name : tenant.name }}
                  </div>
                </div>
                <div class="hstack gap-2 flex-wrap">
                  <span class="badge bg-white text-body border">{{ summary.workersCount }} lavoratori</span>
                  <span class="badge bg-white text-body border">{{ summary.activeCount }} attivi</span>
                  <span class="badge bg-white text-body border">
                    {{ companyContext ? "Azienda corrente" : `${summary.companiesCount} aziende` }}
                  </span>
                </div>
              </div>

              <div v-if="companyContext" class="d-flex align-items-center gap-2 text-muted fs-13 flex-wrap">
                <i class="ri-building-line align-bottom"></i>
                <span>Sezione aziendale collegata alla dashboard.</span>
              </div>
              <div v-if="companyContext" class="d-flex align-items-center gap-2 flex-wrap mt-3">
                <Link :href="companyContext.riskProfileRoute" class="btn btn-soft-primary btn-sm">
                  Apri profilo rischio
                </Link>
                <Link :href="companyContext.registryRoute" class="btn btn-soft-info btn-sm">
                  Apri registri
                </Link>
              </div>
            </div>
          </BCol>

          <BCol xl="4" lg="4">
            <div class="rounded-3 bg-light-subtle p-3 h-100 d-flex justify-content-lg-end justify-content-start align-items-start">
              <Link :href="companyContext?.showRoute || route('companies.index')" class="btn btn-soft-secondary">
                <i class="ri-arrow-left-line me-1 align-bottom"></i>
                {{ companyContext ? "Torna alla dashboard azienda" : "Apri aziende" }}
              </Link>
            </div>
          </BCol>
        </BRow>
      </BCardBody>
    </BCard>

    <BCard no-body>
      <BCardHeader class="border-0">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
          <div>
            <h4 class="card-title mb-1">{{ companyContext ? "Lavoratori azienda" : "Lavoratori" }}</h4>
          </div>
          <Link
            v-if="$page.props.tenantContext?.permissions?.can_manage_data"
            :href="companyContext?.createRoute || route('workers.create')"
            class="btn btn-primary btn-sm"
          >
            Nuovo lavoratore
          </Link>
        </div>
      </BCardHeader>
      <BCardBody class="pt-0">
        <div v-if="workers.length === 0" class="text-center py-5">
          <div class="avatar-md mx-auto mb-3">
            <div class="avatar-title bg-light text-primary rounded-circle fs-2">
              <i class="ri-team-line"></i>
            </div>
          </div>
          <h5 class="mb-2">Nessun lavoratore ancora presente</h5>
          <p class="text-muted mb-4">
            {{ companyContext ? "Aggiungi il primo lavoratore dell'azienda." : "Aggiungi il primo lavoratore per iniziare." }}
          </p>
          <Link
            v-if="$page.props.tenantContext?.permissions?.can_manage_data"
            :href="companyContext?.createRoute || route('workers.create')"
            class="btn btn-primary"
          >
            Nuovo lavoratore
          </Link>
        </div>

        <SicurezzaDataTable
          v-else
          :columns="columns"
          :data="workers"
          search-placeholder="Cerca lavoratore, azienda, sede o mansione"
          empty-title="Nessun lavoratore trovato"
          empty-text="Nessun lavoratore corrisponde ai filtri correnti."
        >
          <template #cell-worker="{ row }">
            <div class="fw-semibold text-break">{{ row.full_name }}</div>
            <div class="text-muted fs-13 text-break">{{ row.tax_code || "Codice fiscale non indicato" }}</div>
          </template>

          <template #cell-company="{ row }">
            <div class="text-break">{{ row.company?.name || "Azienda non disponibile" }}</div>
          </template>

          <template #cell-context="{ row }">
            <div class="text-break">{{ row.primary_site?.name || "Sede non assegnata" }}</div>
            <div class="text-muted fs-13 text-break">
              {{ row.primary_job_role?.name || "Mansione prevalente non assegnata" }}
            </div>
          </template>

          <template #cell-contacts="{ row }">
            <div class="text-break">{{ row.email || "Email non indicata" }}</div>
            <div class="text-muted fs-13 text-break">{{ row.phone || "Telefono non indicato" }}</div>
          </template>

          <template #cell-status="{ row }">
            <span v-if="row.status === 'active'" class="badge bg-success-subtle text-success">Attivo</span>
            <span v-else class="badge bg-light text-body">Non attivo</span>
          </template>

          <template #cell-actions="{ row }">
            <div class="d-inline-flex align-items-center gap-1 justify-content-end">
              <Link
                :id="actionTooltipId(row.id, 'show')"
                :href="row.show_route || route('workers.show', row.id)"
                class="btn btn-soft-primary btn-icon"
                style="width: 2.25rem; height: 2.25rem;"
                aria-label="Apri lavoratore"
              >
                <i class="ri-eye-line fs-16"></i>
              </Link>
              <BTooltip :target="actionTooltipId(row.id, 'show')" title="Apri lavoratore" />

              <Link
                v-if="$page.props.tenantContext?.permissions?.can_manage_data"
                :id="actionTooltipId(row.id, 'edit')"
                :href="row.edit_route || route('workers.edit', row.id)"
                class="btn btn-soft-secondary btn-icon"
                style="width: 2.25rem; height: 2.25rem;"
                aria-label="Modifica lavoratore"
              >
                <i class="ri-pencil-line fs-16"></i>
              </Link>
              <BTooltip
                v-if="$page.props.tenantContext?.permissions?.can_manage_data"
                :target="actionTooltipId(row.id, 'edit')"
                title="Modifica lavoratore"
              />
            </div>
          </template>
        </SicurezzaDataTable>
      </BCardBody>
    </BCard>
  </Layout>
</template>
