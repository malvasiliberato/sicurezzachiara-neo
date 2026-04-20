<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

const quickStats = [
  {
    title: "Aziende monitorate",
    value: "48",
    helper: "portafoglio consulente attivo",
    icon: "ri-building-line",
    tone: "primary",
  },
  {
    title: "Rischi in presidio",
    value: "216",
    helper: "tra mansioni, macchinari e luoghi",
    icon: "ri-alarm-warning-line",
    tone: "warning",
  },
  {
    title: "Scadenze imminenti",
    value: "39",
    helper: "entro i prossimi 30 giorni",
    icon: "ri-calendar-event-line",
    tone: "danger",
  },
  {
    title: "Misure da verificare",
    value: "57",
    helper: "DPI, corsi, visite, misure tecniche",
    icon: "ri-shield-check-line",
    tone: "success",
  },
];

const workAreas = [
  {
    title: "Governance del rischio",
    text: "Il presidio operativo parte da mansioni, macchinari e luoghi di lavoro e si consolida nel tempo.",
    icon: "ri-radar-line",
  },
  {
    title: "Misure di mitigazione",
    text: "Visite, formazione, DPI e misure organizzative devono essere tracciate come attivita' vive, non statiche.",
    icon: "ri-checkbox-circle-line",
  },
  {
    title: "DVR come output vivo",
    text: "Il DVR deriva dai dati reali del sistema e si aggiorna con scadenze, misure e stato di presidio.",
    icon: "ri-file-text-line",
  },
];
</script>

<template>
  <Layout>
    <Head title="Dashboard" />

    <PageHeader title="Dashboard" pageTitle="SicurezzaChiara" />

    <BRow class="g-4 mb-4">
      <BCol lg="8">
        <BCard no-body class="overflow-hidden">
          <BCardBody class="p-4 p-lg-5">
            <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">Bootstrap prodotto</span>
            <h2 class="mb-3">Base iniziale del progetto SicurezzaChiara</h2>
            <p class="text-muted fs-15 mb-4">
              Questa dashboard mantiene il linguaggio Velzon ma riduce il rumore demo, lasciando una base iniziale piu'
              coerente con il prodotto e con la governance operativa del rischio.
            </p>
            <div class="hstack gap-2 flex-wrap">
              <Link :href="route('companies.index')" class="btn btn-primary">
              Apri aziende
              </Link>
              <Link href="/sicurezzachiara/ui-reference" class="btn btn-primary">
              Apri UI Reference
              </Link>
              <Link href="/user/profile" class="btn btn-soft-secondary">
              Profilo utente
              </Link>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol lg="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0">
            <h4 class="card-title mb-0">Principi permanenti</h4>
          </BCardHeader>
          <BCardBody>
            <div class="vstack gap-3">
              <div class="d-flex gap-3">
                <div class="flex-shrink-0 text-primary fs-4">
                  <i class="ri-layout-2-line"></i>
                </div>
                <div>
                  <h6 class="mb-1">Velzon-first</h6>
                  <p class="text-muted mb-0">La base visiva resta quella del template, senza linguaggi alieni.</p>
                </div>
              </div>
              <div class="d-flex gap-3">
                <div class="flex-shrink-0 text-primary fs-4">
                  <i class="ri-focus-3-line"></i>
                </div>
                <div>
                  <h6 class="mb-1">Chiarezza funzionale</h6>
                  <p class="text-muted mb-0">Riduciamo il rumore demo solo dove diventa fuorviante per il progetto.</p>
                </div>
              </div>
              <div class="d-flex gap-3">
                <div class="flex-shrink-0 text-primary fs-4">
                  <i class="ri-route-line"></i>
                </div>
                <div>
                  <h6 class="mb-1">Flusso reale</h6>
                  <p class="text-muted mb-0">Il focus resta sul presidio continuo del rischio aziendale.</p>
                </div>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4 mb-4">
      <BCol v-for="item in quickStats" :key="item.title" xl="3" md="6">
        <BCard no-body class="card-animate card-height-100">
          <BCardBody>
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <p class="text-uppercase fw-medium text-muted text-truncate mb-2">{{ item.title }}</p>
                <h4 class="fs-22 fw-semibold ff-secondary mb-1">{{ item.value }}</h4>
                <p class="text-muted mb-0">{{ item.helper }}</p>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span :class="`avatar-title rounded-2 bg-${item.tone}-subtle text-${item.tone} fs-3`">
                  <i :class="item.icon"></i>
                </span>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4">
      <BCol v-for="area in workAreas" :key="area.title" xl="4" md="6">
        <BCard no-body class="card-height-100">
          <BCardBody class="d-flex flex-column">
            <div class="avatar-sm mb-3">
              <span class="avatar-title rounded-circle bg-primary-subtle text-primary fs-4">
                <i :class="area.icon"></i>
              </span>
            </div>
            <h5 class="mb-2">{{ area.title }}</h5>
            <p class="text-muted mb-0 flex-grow-1">{{ area.text }}</p>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>
  </Layout>
</template>
