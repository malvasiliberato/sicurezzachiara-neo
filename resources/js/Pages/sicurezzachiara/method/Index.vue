<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

const props = defineProps({
  tenant: {
    type: Object,
    required: true,
  },
  methodSummary: {
    type: Object,
    required: true,
  },
  linksSummary: {
    type: Array,
    required: true,
  },
  methodJourney: {
    type: Object,
    required: true,
  },
  operationalBoundary: {
    type: Object,
    required: true,
  },
});

const catalogs = computed(() => Object.values(props.methodSummary));

const statusBadge = (status) => {
  if (status === "Operativo") {
    return "bg-success-subtle text-success";
  }

  if (status === "Nel rischio") {
    return "bg-info-subtle text-info";
  }

  return "bg-light text-body";
};
</script>

<template>
  <Layout>
    <Head title="Metodo" />
    <PageHeader title="Metodo" pageTitle="SicurezzaChiara" />

    <BRow class="g-4 mb-4">
      <BCol xl="8">
        <BCard no-body class="overflow-hidden h-100">
          <BCardBody class="p-4">
            <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">Metodo</span>
            <h2 class="mb-2">Come SicurezzaChiara genera rischi e misure</h2>
            <p class="text-muted mb-4">
              Qui governi i cataloghi e i collegamenti che spiegano come il prodotto passa da mansioni,
              macchinari e luoghi al primo profilo rischio aziendale. L'uso quotidiano resta poi in
              <strong>Aziende</strong>.
            </p>
            <div class="hstack gap-2 flex-wrap mb-4">
              <span class="badge bg-light text-body">{{ tenant.name }}</span>
              <span class="badge bg-soft-primary text-primary">{{ catalogs.length }} cataloghi visibili</span>
              <span class="badge bg-soft-info text-info">{{ linksSummary.length }} collegamenti chiave</span>
            </div>
            <div class="border rounded-3 p-3 bg-light-subtle">
              <div class="fw-semibold mb-2">{{ methodJourney.title }}</div>
              <ol class="ps-3 mb-0 text-muted">
                <li v-for="step in methodJourney.steps" :key="step" class="mb-2">
                  {{ step }}
                </li>
              </ol>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0">
            <div>
              <h4 class="card-title mb-1">Confine operativo</h4>
              <p class="text-muted mb-0">Metodo configura il ragionamento; la presa in carico quotidiana resta sul caso reale.</p>
            </div>
          </BCardHeader>
          <BCardBody class="d-flex flex-column gap-3">
            <div class="border rounded-3 p-3">
              <div class="fw-semibold mb-2">{{ operationalBoundary.title }}</div>
              <ul class="text-muted ps-3 mb-0">
                <li v-for="item in operationalBoundary.items" :key="item" class="mb-2">
                  {{ item }}
                </li>
              </ul>
            </div>
            <Link :href="route('companies.index')" class="btn btn-soft-primary">Torna alle aziende</Link>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4 mb-4">
      <BCol xl="8">
        <BCard no-body>
          <BCardHeader class="border-0">
            <div>
              <h4 class="card-title mb-1">Cataloghi del metodo</h4>
              <p class="text-muted mb-0">Espongono solo superfici reali gia' presenti nel prodotto, senza aprire nuovo dominio.</p>
            </div>
          </BCardHeader>
          <BCardBody class="pt-0">
            <BRow class="g-3">
              <BCol v-for="catalog in catalogs" :key="catalog.label" md="6">
                <div class="border rounded-3 p-3 h-100">
                  <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                    <div>
                      <div class="fw-semibold">{{ catalog.label }}</div>
                      <div class="text-muted fs-13">{{ catalog.description }}</div>
                    </div>
                    <span class="badge bg-soft-primary text-primary">{{ catalog.count }}</span>
                  </div>
                  <div class="hstack gap-2 flex-wrap mb-3">
                    <span class="badge bg-light text-body">{{ catalog.tenantCount }} personalizzati</span>
                  </div>
                  <div class="hstack gap-2 flex-wrap">
                    <Link :href="catalog.route" class="btn btn-primary btn-sm">Apri</Link>
                    <Link
                      v-if="$page.props.tenantContext?.permissions?.can_manage_data"
                      :href="catalog.createRoute"
                      class="btn btn-soft-secondary btn-sm"
                    >
                      Aggiungi
                    </Link>
                  </div>
                </div>
              </BCol>
            </BRow>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0">
            <div>
              <h4 class="card-title mb-1">Collegamenti attivi</h4>
              <p class="text-muted mb-0">I mapping restano leggibili e si governano oggi a partire dal rischio.</p>
            </div>
          </BCardHeader>
          <BCardBody class="pt-0 d-flex flex-column gap-3">
            <div v-for="linkItem in linksSummary" :key="linkItem.label" class="border rounded-3 p-3">
              <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                <div class="fw-semibold">{{ linkItem.label }}</div>
                <span class="badge" :class="statusBadge(linkItem.status)">{{ linkItem.status }}</span>
              </div>
              <div class="text-muted fs-13 mb-2">{{ linkItem.detail }}</div>
              <span class="badge bg-light text-body">{{ linkItem.count }} collegamenti</span>
            </div>
            <Link :href="route('risk-catalog.index')" class="btn btn-soft-primary">Apri rischi e mapping</Link>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>
  </Layout>
</template>
