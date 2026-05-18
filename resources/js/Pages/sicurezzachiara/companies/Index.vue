<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import SicurezzaDataTable from "@/Components/SicurezzaDataTable.vue";

const actionTooltipId = (companyId, action) => `company-${companyId}-${action}-tooltip`;

const columns = [
  {
    id: "company",
    accessorFn: (row) => row.name,
    header: "Azienda",
    enableSorting: true,
    meta: {
      width: "25%",
      slot: "company",
    },
  },
  {
    id: "industry",
    accessorFn: (row) => row.industry || "",
    header: "Settore",
    enableSorting: true,
    meta: {
      width: "18%",
      slot: "industry",
    },
  },
  {
    id: "setup",
    accessorFn: (row) => row.area_one_journey?.completedSteps ?? 0,
    header: "Setup Area 1",
    enableSorting: true,
    meta: {
      width: "18%",
      slot: "setup",
    },
  },
  {
    id: "nextStep",
    accessorFn: (row) => row.area_one_journey?.nextStep?.label || "",
    header: "Prossimo passo",
    enableSorting: true,
    meta: {
      width: "27%",
      slot: "nextStep",
    },
  },
  {
    id: "actions",
    accessorFn: (row) => row.id,
    header: "Azioni",
    enableSorting: false,
    meta: {
      width: "12%",
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
          <BCardHeader class="border-0">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <h4 class="card-title mb-1">Portafoglio aziende</h4>
              </div>
              <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data" :href="route('companies.create')" class="btn btn-primary btn-sm">
                Nuova azienda
              </Link>
            </div>
          </BCardHeader>
          <BCardBody class="pt-0">
            <div v-if="companies.length === 0" class="text-center py-5">
              <div class="avatar-md mx-auto mb-3">
                <div class="avatar-title bg-light text-primary rounded-circle fs-2">
                  <i class="ri-building-line"></i>
                </div>
              </div>
              <h5 class="mb-2">Nessuna azienda ancora presente</h5>
              <p class="text-muted mb-4">
                Crea la prima azienda del tenant per iniziare a costruire il contesto operativo del progetto.
              </p>
              <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data" :href="route('companies.create')" class="btn btn-primary">Crea prima azienda</Link>
            </div>

            <SicurezzaDataTable
              v-else
              :columns="columns"
              :data="companies"
              search-placeholder="Cerca azienda, settore o prossimo passo"
              empty-title="Nessuna azienda trovata"
              empty-text="Nessuna azienda corrisponde ai filtri correnti."
            >
              <template #cell-company="{ row }">
                <div class="fw-semibold text-break">{{ row.name }}</div>
                <div class="text-muted fs-13 text-break">{{ row.legal_name || "Ragione sociale non specificata" }}</div>
              </template>

              <template #cell-industry="{ row }">
                <div class="text-break">{{ row.industry || "Non indicato" }}</div>
              </template>

              <template #cell-setup="{ row }">
                <div class="fw-semibold">{{ row.area_one_journey.completedSteps }} / {{ row.area_one_journey.totalSteps }} step</div>
                <div class="text-muted fs-13 d-flex flex-wrap gap-2">
                  <span class="badge bg-info-subtle text-info">{{ row.sites_count }} sedi</span>
                  <span class="badge bg-success-subtle text-success">{{ row.workers_count }} lavoratori</span>
                </div>
              </template>

              <template #cell-nextStep="{ row }">
                <div class="fw-semibold text-break">{{ row.area_one_journey.nextStep.label }}</div>
                <div class="text-muted fs-13 text-break">{{ row.area_one_journey.nextStep.helper }}</div>
              </template>

              <template #cell-actions="{ row }">
                <div class="d-inline-flex align-items-center gap-1 justify-content-end">
                  <Link
                    :id="actionTooltipId(row.id, 'show')"
                    :href="route('companies.show', row.id)"
                    class="btn btn-soft-primary btn-icon"
                    style="width: 2.25rem; height: 2.25rem;"
                    aria-label="Apri azienda"
                  >
                    <i class="ri-eye-line fs-16"></i>
                  </Link>
                  <BTooltip :target="actionTooltipId(row.id, 'show')" title="Apri azienda" />

                  <Link
                    v-if="$page.props.tenantContext?.permissions?.can_manage_data"
                    :id="actionTooltipId(row.id, 'edit')"
                    :href="route('companies.edit', row.id)"
                    class="btn btn-soft-secondary btn-icon"
                    style="width: 2.25rem; height: 2.25rem;"
                    aria-label="Modifica azienda"
                  >
                    <i class="ri-pencil-line fs-16"></i>
                  </Link>
                  <BTooltip
                    v-if="$page.props.tenantContext?.permissions?.can_manage_data"
                    :target="actionTooltipId(row.id, 'edit')"
                    title="Modifica azienda"
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
