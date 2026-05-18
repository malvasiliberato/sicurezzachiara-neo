<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

defineProps({
  tenant: Object,
  equipmentTypes: Array,
  summary: Object,
});
</script>

<template>
  <Layout>
    <Head title="Catalogo macchinari" />
    <PageHeader title="Catalogo macchinari" pageTitle="SicurezzaChiara" />

    <BAlert v-if="$page.props.flash.success" show variant="success" class="mb-4">
      <i class="ri-checkbox-circle-line me-2 align-middle"></i>
      {{ $page.props.flash.success }}
    </BAlert>

    <BRow class="g-4 mb-4">
      <BCol lg="8">
        <BCard no-body class="overflow-hidden">
          <BCardBody class="p-4">
            <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">SC-DOM-003B</span>
            <h2 class="mb-2">Tipologie macchinario riusabili</h2>
            <p class="text-muted mb-4">
              Il catalogo distingue gia' una base `core` da una base `tenant`, ma il modulo resta fondativo:
              prepara le istanze operative reali senza anticipare ancora il motore di rischio.
            </p>
            <div class="hstack gap-2 flex-wrap">
              <span class="badge bg-light text-body">{{ tenant.name }}</span>
              <span class="badge bg-soft-primary text-primary">{{ summary.totalCount }} tipologie visibili</span>
              <span class="badge bg-soft-success text-success">{{ summary.tenantCount }} tenant-level</span>
              <span class="badge bg-soft-info text-info">{{ summary.coreCount }} core</span>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol lg="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0">
            <div>
              <h4 class="card-title mb-1">Azioni rapide</h4>
              <p class="text-muted mb-0">Aggiungi tipologie personalizzate o apri le istanze gia' censite.</p>
            </div>
          </BCardHeader>
          <BCardBody class="d-flex flex-column gap-3">
            <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data" :href="route('equipment-types.create')" class="btn btn-primary">Nuova tipologia</Link>
            <Link :href="route('equipment-assets.index')" class="btn btn-soft-secondary">Apri macchinari</Link>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BCard no-body>
      <BCardHeader class="border-0">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <h4 class="card-title mb-1">Repertorio tipologie</h4>
            <p class="text-muted mb-0">Le tipologie `core` sono condivise, quelle `tenant` restano governabili dal consulente.</p>
          </div>
          <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data" :href="route('equipment-types.create')" class="btn btn-primary btn-sm">Aggiungi tipologia</Link>
        </div>
      </BCardHeader>
      <BCardBody class="pt-0">
        <div class="table-responsive">
          <table class="table align-middle table-nowrap mb-0">
            <thead class="table-light">
              <tr>
                <th>Tipologia</th>
                <th>Origine</th>
                <th>Stato</th>
                <th>Istanze</th>
                <th class="text-end">Azioni</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="equipmentType in equipmentTypes" :key="equipmentType.id">
                <td>
                  <div class="fw-semibold">{{ equipmentType.name }}</div>
                  <div class="text-muted fs-13">{{ equipmentType.code || "Codice non indicato" }}</div>
                </td>
                <td>
                  <span v-if="equipmentType.source === 'tenant'" class="badge bg-primary-subtle text-primary">Tenant</span>
                  <span v-else class="badge bg-light text-body">Core</span>
                </td>
                <td>
                  <span v-if="equipmentType.is_active" class="badge bg-success-subtle text-success">Attiva</span>
                  <span v-else class="badge bg-light text-body">Non attiva</span>
                </td>
                <td>
                  <span class="badge bg-soft-info text-info">{{ equipmentType.assets_count }} macchinari</span>
                </td>
                <td class="text-end">
                  <div class="hstack gap-2 justify-content-end">
                    <Link :href="route('equipment-types.show', equipmentType.id)" class="btn btn-soft-primary btn-sm">Apri</Link>
                    <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data && equipmentType.source === 'tenant'" :href="route('equipment-types.edit', equipmentType.id)" class="btn btn-soft-secondary btn-sm">
                      Modifica
                    </Link>
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
