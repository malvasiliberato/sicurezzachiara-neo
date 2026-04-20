<script setup>
import { Head } from "@inertiajs/vue3";
import { ref } from "vue";
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import Multiselect from "@vueform/multiselect";
import "@vueform/multiselect/themes/default.css";

import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

const filterDate = ref(null);
const filterCompany = ref(["metalnova"]);
const filterStatus = ref(["in_scadenza", "da_verificare"]);
const filterCategory = ref(["formazione"]);
const riskOffcanvas = ref(false);
const deadlineOffcanvas = ref(false);
const noteModal = ref(false);

const companyOptions = [
  { value: "metalnova", label: "Metalnova S.r.l." },
  { value: "logiserv", label: "LogiServ Cooperativa" },
  { value: "cantieri", label: "Cantieri Riuniti S.p.A." },
];

const statusOptions = [
  { value: "conforme", label: "Conforme" },
  { value: "da_verificare", label: "Da verificare" },
  { value: "in_scadenza", label: "In scadenza" },
  { value: "scaduto", label: "Scaduto" },
  { value: "non_coperto", label: "Non coperto" },
];

const categoryOptions = [
  { value: "formazione", label: "Formazione" },
  { value: "dpi", label: "DPI" },
  { value: "visite", label: "Visite mediche" },
  { value: "organizzative", label: "Misure organizzative" },
];

const dateConfig = {
  mode: "range",
  altInput: true,
  altFormat: "d M, Y",
  dateFormat: "Y-m-d",
};

const kpis = [
  { title: "Aziende monitorate", value: "48", delta: "+4 questo mese", icon: "ri-building-line", tone: "primary" },
  { title: "Lavoratori monitorati", value: "1.284", delta: "97 con presidio da confermare", icon: "ri-team-line", tone: "info" },
  { title: "Rischi attivi", value: "216", delta: "34 ad alta priorita'", icon: "ri-alarm-warning-line", tone: "warning" },
  { title: "Scadenze imminenti", value: "39", delta: "entro 30 giorni", icon: "ri-calendar-event-line", tone: "danger" },
  { title: "Misure da verificare", value: "57", delta: "tra DPI, corsi e visite", icon: "ri-shield-check-line", tone: "success" },
  { title: "Criticita' aperte", value: "12", delta: "5 senza copertura completa", icon: "ri-error-warning-line", tone: "danger" },
];

const summaryCards = [
  { title: "Stato azienda", value: "Presidio parziale", helper: "Metalnova S.r.l.", progress: 68, tone: "warning" },
  { title: "Copertura rischi", value: "74%", helper: "mansioni, macchinari e luoghi censiti", progress: 74, tone: "primary" },
  { title: "Stato formazione", value: "19 corsi validi", helper: "6 rinnovi da pianificare", progress: 76, tone: "success" },
  { title: "Stato visite", value: "8 visite in scadenza", helper: "2 non ancora calendarizzate", progress: 61, tone: "warning" },
  { title: "Stato DPI", value: "43 consegne tracciate", helper: "3 dotazioni da verificare", progress: 83, tone: "info" },
  { title: "Alert di presidio", value: "2 aree critiche", helper: "magazzino chimici e reparto taglio", progress: 42, tone: "danger" },
];

const companies = [
  { name: "Metalnova S.r.l.", sector: "Metalmeccanica", risks: 18, deadlines: 6, status: "In presidio" },
  { name: "LogiServ Cooperativa", sector: "Logistica", risks: 11, deadlines: 4, status: "Da verificare" },
  { name: "Cantieri Riuniti S.p.A.", sector: "Edilizia", risks: 23, deadlines: 8, status: "Criticita' aperte" },
  { name: "FoodLab Nord", sector: "Alimentare", risks: 9, deadlines: 2, status: "Conforme" },
];

const workers = [
  { name: "Marco Rinaldi", role: "Carrellista", source: "Macchinari", training: "In scadenza", visit: "Programmato" },
  { name: "Elena Valli", role: "Impiegata logistica", source: "Luoghi", training: "Conforme", visit: "Conforme" },
  { name: "Giorgio Conti", role: "Saldatore", source: "Mansioni", training: "Da verificare", visit: "Scaduto" },
  { name: "Chiara Negri", role: "Capo turno", source: "Mansioni", training: "Conforme", visit: "Conforme" },
];

const deadlines = [
  { item: "Visita medica reparto taglio", company: "Metalnova S.r.l.", due: "28 Apr 2026", owner: "Medico competente", status: "In scadenza" },
  { item: "Aggiornamento antincendio", company: "LogiServ Cooperativa", due: "02 Mag 2026", owner: "Consulente HSE", status: "Da pianificare" },
  { item: "Sostituzione DPI udito", company: "Cantieri Riuniti S.p.A.", due: "18 Apr 2026", owner: "Preposto", status: "Scaduto" },
  { item: "Verifica aspirazione fumi", company: "Metalnova S.r.l.", due: "12 Mag 2026", owner: "RSPP", status: "Aperto" },
];

const measures = [
  { measure: "Formazione carrellisti", type: "Formazione", coverage: "12/15 completati", state: "Da completare" },
  { measure: "Sorveglianza sanitaria reparto saldatura", type: "Visite", coverage: "7/8 idoneita' registrate", state: "In presidio" },
  { measure: "Kit DPI area verniciatura", type: "DPI", coverage: "100% assegnati", state: "Attuato" },
  { measure: "Segnaletica area carico", type: "Organizzativa", coverage: "Installazione in corso", state: "Non attuato" },
];

const badgeSamples = [
  { label: "Conforme", classes: "bg-success-subtle text-success" },
  { label: "Da verificare", classes: "bg-warning-subtle text-warning" },
  { label: "In scadenza", classes: "bg-danger-subtle text-danger" },
  { label: "Scaduto", classes: "bg-danger text-white" },
  { label: "Non coperto", classes: "bg-dark-subtle text-dark" },
  { label: "Attuato", classes: "bg-info-subtle text-info" },
  { label: "Non attuato", classes: "bg-secondary-subtle text-secondary" },
];

const deadlinesChart = {
  series: [{ name: "Scadenze", data: [12, 18, 16, 23, 19, 27] }],
  options: {
    chart: { type: "line", height: 290, toolbar: { show: false } },
    stroke: { curve: "smooth", width: 3 },
    dataLabels: { enabled: false },
    colors: ["#405189"],
    xaxis: { categories: ["Apr", "Mag", "Giu", "Lug", "Ago", "Set"] },
    yaxis: { labels: { formatter: (value) => `${value}` } },
    grid: { borderColor: "#f1f1f1" },
    legend: { show: false },
  },
};

const criticalityChart = {
  series: [7, 11, 19],
  options: {
    chart: { type: "donut", height: 290 },
    labels: ["Alta", "Media", "Bassa"],
    colors: ["#f06548", "#f7b84b", "#0ab39c"],
    legend: { position: "bottom" },
    dataLabels: { enabled: false },
    plotOptions: { pie: { donut: { size: "68%" } } },
  },
};

const coverageChart = {
  series: [78],
  options: {
    chart: { type: "radialBar", height: 290 },
    plotOptions: {
      radialBar: {
        hollow: { size: "62%" },
        dataLabels: {
          name: { show: true, offsetY: -12, color: "#878a99" },
          value: { show: true, fontSize: "24px", fontWeight: 600, formatter: (value) => `${value}%` },
        },
      },
    },
    labels: ["Copertura misure"],
    colors: ["#0ab39c"],
  },
};

const originChart = {
  series: [{ name: "Rischi", data: [96, 74, 58] }],
  options: {
    chart: { type: "bar", height: 290, toolbar: { show: false } },
    plotOptions: { bar: { horizontal: true, borderRadius: 4, columnWidth: "45%" } },
    colors: ["#299cdb"],
    dataLabels: { enabled: false },
    xaxis: { categories: ["Mansioni", "Macchinari", "Luoghi di lavoro"] },
    legend: { show: false },
    grid: { borderColor: "#f1f1f1" },
  },
};

const riskTabs = [
  { title: "Profilo rischio", text: "Matrice sintetica di esposizione per mansioni, luoghi e macchinari, con alert prioritari e coperture mancanti." },
  { title: "DPI", text: "Dotazioni assegnate, taglie, consegne, sostituzioni e verifiche di utilizzo per reparto o mansione." },
  { title: "Formazione", text: "Storico corsi, validita', aggiornamenti programmati e gap tra rischio rilevato e formazione effettiva." },
  { title: "Visite mediche", text: "Idoneita', scadenze, convocazioni e note del medico competente collegate ai profili di rischio." },
  { title: "Documenti / DVR", text: "Output documentale coerente con i dati di presidio, aggiornato nel tempo e verificabile." },
];

function statusBadgeClasses(status) {
  const map = {
    "In presidio": "bg-primary-subtle text-primary",
    "Da verificare": "bg-warning-subtle text-warning",
    "Criticita' aperte": "bg-danger-subtle text-danger",
    Conforme: "bg-success-subtle text-success",
    "In scadenza": "bg-danger-subtle text-danger",
    "Da pianificare": "bg-warning-subtle text-warning",
    Scaduto: "bg-danger text-white",
    Aperto: "bg-info-subtle text-info",
    "Da completare": "bg-warning-subtle text-warning",
    Attuato: "bg-success-subtle text-success",
    "Non attuato": "bg-secondary-subtle text-secondary",
    Programmato: "bg-info-subtle text-info",
  };

  return map[status] || "bg-light text-muted";
}
</script>

<template>
  <Layout>
    <Head title="UI Reference" />

    <PageHeader title="UI Reference" pageTitle="SicurezzaChiara" />

    <BRow class="mb-4">
      <BCol lg="12">
        <BCard no-body class="overflow-hidden">
          <BCardBody class="p-4 p-lg-5">
            <div class="d-flex flex-column flex-lg-row gap-4 align-items-lg-center justify-content-between">
              <div>
                <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">Gold standard interno</span>
                <h2 class="mb-2">Reference page UI/UX per SicurezzaChiara</h2>
                <p class="text-muted fs-15 mb-0">
                  Questa pagina raccoglie i pattern Velzon che vogliamo riusare nelle implementazioni future di
                  dashboard, registri, profilo di rischio, scadenze e workspace operativi.
                </p>
              </div>
              <div class="hstack gap-2 flex-wrap">
                <span class="badge bg-success-subtle text-success fs-12">Velzon-first</span>
                <span class="badge bg-info-subtle text-info fs-12">Minimum visual change</span>
                <span class="badge bg-warning-subtle text-warning fs-12">B2B consulente HSE</span>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4 mb-4">
      <BCol v-for="item in kpis" :key="item.title" xxl="2" md="4" sm="6">
        <BCard no-body class="card-animate card-height-100">
          <BCardBody>
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <p class="text-uppercase fw-medium text-muted text-truncate mb-2">{{ item.title }}</p>
                <h4 class="fs-22 fw-semibold ff-secondary mb-1">{{ item.value }}</h4>
                <p class="text-muted mb-0">{{ item.delta }}</p>
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

    <BRow class="g-4 mb-4">
      <BCol lg="12">
        <BCard no-body>
          <BCardHeader class="border-0 pb-1">
            <div>
              <h4 class="card-title mb-1">1. Summary cards / operative cards</h4>
              <p class="text-muted mb-0">Pattern per stato azienda, coperture e alert di presidio.</p>
            </div>
          </BCardHeader>
          <BCardBody>
            <BRow class="g-4">
              <BCol v-for="card in summaryCards" :key="card.title" xl="4" md="6">
                <BCard no-body class="border card-height-100 shadow-none mb-0">
                  <BCardBody>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                      <h5 class="fs-15 mb-0">{{ card.title }}</h5>
                      <span :class="`badge bg-${card.tone}-subtle text-${card.tone}`">{{ card.value }}</span>
                    </div>
                    <p class="text-muted mb-3">{{ card.helper }}</p>
                    <BProgress :value="card.progress" :variant="card.tone" class="progress-sm" />
                    <div class="d-flex justify-content-between mt-2">
                      <span class="text-muted fs-13">Copertura</span>
                      <span class="fw-medium fs-13">{{ card.progress }}%</span>
                    </div>
                  </BCardBody>
                </BCard>
              </BCol>
            </BRow>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4 mb-4">
      <BCol lg="12">
        <BCard no-body>
          <BCardHeader class="border-0 pb-1">
            <div>
              <h4 class="card-title mb-1">3. Tabelle standard</h4>
              <p class="text-muted mb-0">Varianti di tabelle coerenti con il template, con badge stato e azioni compatte.</p>
            </div>
          </BCardHeader>
          <BCardBody>
            <BRow class="g-4">
              <BCol xl="6">
                <div class="table-responsive">
                  <table class="table align-middle table-nowrap mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Azienda</th>
                        <th>Settore</th>
                        <th>Rischi</th>
                        <th>Scadenze</th>
                        <th>Stato</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="row in companies" :key="row.name">
                        <td class="fw-medium">{{ row.name }}</td>
                        <td>{{ row.sector }}</td>
                        <td>{{ row.risks }}</td>
                        <td>{{ row.deadlines }}</td>
                        <td><span :class="`badge ${statusBadgeClasses(row.status)}`">{{ row.status }}</span></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </BCol>
              <BCol xl="6">
                <div class="table-responsive">
                  <table class="table table-striped table-nowrap align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Lavoratore</th>
                        <th>Mansione</th>
                        <th>Origine rischio</th>
                        <th>Formazione</th>
                        <th>Visite</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="row in workers" :key="row.name">
                        <td class="fw-medium">{{ row.name }}</td>
                        <td>{{ row.role }}</td>
                        <td>{{ row.source }}</td>
                        <td><span :class="`badge ${statusBadgeClasses(row.training)}`">{{ row.training }}</span></td>
                        <td><span :class="`badge ${statusBadgeClasses(row.visit)}`">{{ row.visit }}</span></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </BCol>
              <BCol xl="6">
                <div class="table-responsive">
                  <table class="table table-hover align-middle table-nowrap mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Scadenza</th>
                        <th>Azienda</th>
                        <th>Data</th>
                        <th>Owner</th>
                        <th>Azioni</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="row in deadlines" :key="row.item">
                        <td>
                          <div class="fw-medium">{{ row.item }}</div>
                          <span :class="`badge ${statusBadgeClasses(row.status)}`">{{ row.status }}</span>
                        </td>
                        <td>{{ row.company }}</td>
                        <td>{{ row.due }}</td>
                        <td>{{ row.owner }}</td>
                        <td>
                          <div class="hstack gap-2">
                            <BButton size="sm" variant="soft-primary" @click="deadlineOffcanvas = true">Dettaglio</BButton>
                            <BButton size="sm" variant="soft-secondary">Pianifica</BButton>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </BCol>
              <BCol xl="6">
                <div class="table-responsive">
                  <table class="table align-middle table-nowrap mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Misura</th>
                        <th>Tipo</th>
                        <th>Copertura</th>
                        <th>Stato</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="row in measures" :key="row.measure">
                        <td class="fw-medium">{{ row.measure }}</td>
                        <td>{{ row.type }}</td>
                        <td>{{ row.coverage }}</td>
                        <td><span :class="`badge ${statusBadgeClasses(row.state)}`">{{ row.state }}</span></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </BCol>
            </BRow>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4 mb-4">
      <BCol lg="12">
        <BCard no-body>
          <BCardHeader class="border-0 pb-1">
            <div>
              <h4 class="card-title mb-1">4. Card per grafici</h4>
              <p class="text-muted mb-0">Blocchi chart in stile Velzon, con mock credibili per un SaaS operativo B2B.</p>
            </div>
          </BCardHeader>
          <BCardBody>
            <BRow class="g-4">
              <BCol xl="6">
                <BCard no-body class="border shadow-none mb-0">
                  <BCardHeader class="bg-light-subtle border-0">
                    <h5 class="card-title mb-0">Andamento scadenze</h5>
                  </BCardHeader>
                  <BCardBody>
                    <apexchart type="line" height="290" :options="deadlinesChart.options" :series="deadlinesChart.series" />
                  </BCardBody>
                </BCard>
              </BCol>
              <BCol xl="3" md="6">
                <BCard no-body class="border shadow-none mb-0">
                  <BCardHeader class="bg-light-subtle border-0">
                    <h5 class="card-title mb-0">Distribuzione criticita'</h5>
                  </BCardHeader>
                  <BCardBody>
                    <apexchart type="donut" height="290" :options="criticalityChart.options" :series="criticalityChart.series" />
                  </BCardBody>
                </BCard>
              </BCol>
              <BCol xl="3" md="6">
                <BCard no-body class="border shadow-none mb-0">
                  <BCardHeader class="bg-light-subtle border-0">
                    <h5 class="card-title mb-0">Copertura misure</h5>
                  </BCardHeader>
                  <BCardBody>
                    <apexchart type="radialBar" height="290" :options="coverageChart.options" :series="coverageChart.series" />
                  </BCardBody>
                </BCard>
              </BCol>
              <BCol xl="12">
                <BCard no-body class="border shadow-none mb-0">
                  <BCardHeader class="bg-light-subtle border-0">
                    <h5 class="card-title mb-0">Rischi per origine</h5>
                  </BCardHeader>
                  <BCardBody>
                    <apexchart type="bar" height="290" :options="originChart.options" :series="originChart.series" />
                  </BCardBody>
                </BCard>
              </BCol>
            </BRow>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4 mb-4">
      <BCol xl="5">
        <BCard no-body class="card-height-100">
          <BCardHeader class="border-0 pb-1">
            <div>
              <h4 class="card-title mb-1">5. Badge e stati</h4>
              <p class="text-muted mb-0">Vocabolario minimo degli stati operativi ricorrenti.</p>
            </div>
          </BCardHeader>
          <BCardBody>
            <div class="hstack gap-2 flex-wrap">
              <span v-for="badge in badgeSamples" :key="badge.label" :class="`badge ${badge.classes} fs-12`">{{ badge.label }}</span>
            </div>
            <div class="mt-4">
              <h6 class="text-uppercase fw-semibold fs-12 text-muted mb-3">Uso consigliato</h6>
              <div class="d-flex flex-column gap-2">
                <div class="d-flex justify-content-between align-items-center">
                  <span class="text-muted">Presidio pieno</span>
                  <span class="badge bg-success-subtle text-success">Conforme</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                  <span class="text-muted">Controllo richiesto</span>
                  <span class="badge bg-warning-subtle text-warning">Da verificare</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                  <span class="text-muted">Scadenza critica</span>
                  <span class="badge bg-danger text-white">Scaduto</span>
                </div>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xl="7">
        <BCard no-body class="card-height-100">
          <BCardHeader class="border-0 pb-1">
            <div>
              <h4 class="card-title mb-1">6. Alert / callout</h4>
              <p class="text-muted mb-0">Messaggi operativi coerenti con il dominio e con il tono del template.</p>
            </div>
          </BCardHeader>
          <BCardBody>
            <BAlert show variant="warning" class="mb-3">
              <i class="ri-alert-line me-2 align-middle"></i>
              Sono presenti 8 scadenze ravvicinate nei prossimi 15 giorni tra visite mediche e aggiornamenti formativi.
            </BAlert>
            <BAlert show variant="danger" class="mb-3">
              <i class="ri-error-warning-line me-2 align-middle"></i>
              Il rischio esposizione fumi nel reparto saldatura risulta non completamente coperto da misure attive.
            </BAlert>
            <BAlert show variant="info" class="mb-3">
              <i class="ri-information-line me-2 align-middle"></i>
              Le note del consulente devono restare vicine al presidio operativo, non separate dal contesto di rischio.
            </BAlert>
            <BAlert show variant="success" class="mb-0">
              <i class="ri-checkbox-circle-line me-2 align-middle"></i>
              Il layout alert in card si presta bene ai workspace con priorita' giornaliere e verifiche rapide.
            </BAlert>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4 mb-4">
      <BCol lg="12">
        <BCard no-body>
          <BCardHeader class="border-0 pb-1">
            <div>
              <h4 class="card-title mb-1">7. Form sections</h4>
              <p class="text-muted mb-0">Blocchi form organizzati per card/section, coerenti con workspace anagrafici e di presidio.</p>
            </div>
          </BCardHeader>
          <BCardBody>
            <BRow class="g-4">
              <BCol xl="6">
                <BCard no-body class="border shadow-none mb-0">
                  <BCardHeader class="bg-light-subtle border-0">
                    <h5 class="card-title mb-0">Anagrafica azienda</h5>
                  </BCardHeader>
                  <BCardBody>
                    <BRow class="g-3">
                      <BCol md="6">
                        <label class="form-label">Ragione sociale</label>
                        <input type="text" class="form-control" value="Metalnova S.r.l." />
                      </BCol>
                      <BCol md="6">
                        <label class="form-label">Settore ATECO</label>
                        <input type="text" class="form-control" value="25.62 - Lavorazioni meccaniche" />
                      </BCol>
                      <BCol md="6">
                        <label class="form-label">Sede operativa</label>
                        <input type="text" class="form-control" value="Modena" />
                      </BCol>
                      <BCol md="6">
                        <label class="form-label">Referente aziendale</label>
                        <input type="text" class="form-control" value="Laura M." />
                      </BCol>
                      <BCol md="12">
                        <label class="form-label">Note operative</label>
                        <textarea class="form-control" rows="3">Stabilimento con aree produttive ad accesso controllato e turnazione su due fasce.</textarea>
                      </BCol>
                    </BRow>
                  </BCardBody>
                </BCard>
              </BCol>
              <BCol xl="6">
                <BCard no-body class="border shadow-none mb-0">
                  <BCardHeader class="bg-light-subtle border-0">
                    <h5 class="card-title mb-0">Configurazione mansione e misure</h5>
                  </BCardHeader>
                  <BCardBody>
                    <BRow class="g-3">
                      <BCol md="6">
                        <label class="form-label">Mansione</label>
                        <input type="text" class="form-control" value="Saldatore" />
                      </BCol>
                      <BCol md="6">
                        <label class="form-label">Origine prevalente</label>
                        <Multiselect :model-value="['mansioni']" :options="[
                          { value: 'mansioni', label: 'Mansioni' },
                          { value: 'macchinari', label: 'Macchinari' },
                          { value: 'luoghi', label: 'Luoghi di lavoro' },
                        ]" />
                      </BCol>
                      <BCol md="6">
                        <label class="form-label">Luogo di lavoro</label>
                        <input type="text" class="form-control" value="Reparto saldatura" />
                      </BCol>
                      <BCol md="6">
                        <label class="form-label">Macchinario</label>
                        <input type="text" class="form-control" value="Saldatrice MIG 4000" />
                      </BCol>
                      <BCol md="12">
                        <label class="form-label">Misure assegnate</label>
                        <Multiselect
                          :model-value="['dpi', 'visite', 'formazione']"
                          mode="tags"
                          :close-on-select="false"
                          :options="[
                            { value: 'dpi', label: 'DPI' },
                            { value: 'visite', label: 'Visite mediche' },
                            { value: 'formazione', label: 'Formazione' },
                            { value: 'organizzative', label: 'Misure organizzative' },
                          ]"
                        />
                      </BCol>
                    </BRow>
                  </BCardBody>
                </BCard>
              </BCol>
            </BRow>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4 mb-4">
      <BCol lg="12">
        <BCard no-body>
          <BCardHeader class="border-0 pb-1">
            <div>
              <h4 class="card-title mb-1">8. Tab / workspace pattern</h4>
              <p class="text-muted mb-0">Schema utile per gestire aree tematiche dello stesso profilo azienda o mansione.</p>
            </div>
          </BCardHeader>
          <BCardBody>
            <BTabs nav-class="nav-success mb-3" pills>
              <BTab v-for="(tab, index) in riskTabs" :key="tab.title" :title="tab.title" :active="index === 0">
                <div class="text-muted">
                  <div class="d-flex">
                    <div class="flex-shrink-0">
                      <i class="ri-checkbox-circle-fill text-success"></i>
                    </div>
                    <div class="flex-grow-1 ms-2">
                      {{ tab.text }}
                    </div>
                  </div>
                </div>
              </BTab>
            </BTabs>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4 mb-4">
      <BCol lg="12">
        <BCard no-body>
          <BCardHeader class="border-0 pb-1">
            <div>
              <h4 class="card-title mb-1">9. Offcanvas / modal pattern</h4>
              <p class="text-muted mb-0">Dettagli rapidi e azioni operative senza uscire dal workspace principale.</p>
            </div>
          </BCardHeader>
          <BCardBody>
            <div class="hstack gap-2 flex-wrap">
              <BButton variant="primary" @click="riskOffcanvas = true">Dettaglio rischio</BButton>
              <BButton variant="soft-warning" @click="deadlineOffcanvas = true">Dettaglio scadenza</BButton>
              <BButton variant="soft-info" @click="noteModal = true">Note consulente</BButton>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow class="g-4">
      <BCol lg="12">
        <BCard no-body>
          <BCardHeader class="border-0 pb-1">
            <div>
              <h4 class="card-title mb-1">10. Dashboard composition example</h4>
              <p class="text-muted mb-0">Esempio di composizione pagina che puo' guidare future dashboard e workspace consulente.</p>
            </div>
          </BCardHeader>
          <BCardBody>
            <BRow class="g-4">
              <BCol xl="12">
                <BRow class="g-3">
                  <BCol xl="3" md="6">
                    <BCard no-body class="border shadow-none mb-0">
                      <BCardBody>
                        <p class="text-muted text-uppercase fw-medium mb-2">Aziende ad alta priorita'</p>
                        <h4 class="mb-1">7</h4>
                        <span class="text-danger fs-13">2 senza misure complete</span>
                      </BCardBody>
                    </BCard>
                  </BCol>
                  <BCol xl="3" md="6">
                    <BCard no-body class="border shadow-none mb-0">
                      <BCardBody>
                        <p class="text-muted text-uppercase fw-medium mb-2">Visite da pianificare</p>
                        <h4 class="mb-1">14</h4>
                        <span class="text-warning fs-13">entro 21 giorni</span>
                      </BCardBody>
                    </BCard>
                  </BCol>
                  <BCol xl="3" md="6">
                    <BCard no-body class="border shadow-none mb-0">
                      <BCardBody>
                        <p class="text-muted text-uppercase fw-medium mb-2">Formazioni scadute</p>
                        <h4 class="mb-1">9</h4>
                        <span class="text-danger fs-13">richiesto intervento</span>
                      </BCardBody>
                    </BCard>
                  </BCol>
                  <BCol xl="3" md="6">
                    <BCard no-body class="border shadow-none mb-0">
                      <BCardBody>
                        <p class="text-muted text-uppercase fw-medium mb-2">DVR allineati</p>
                        <h4 class="mb-1">32</h4>
                        <span class="text-success fs-13">aggiornati ai dati di presidio</span>
                      </BCardBody>
                    </BCard>
                  </BCol>
                </BRow>
              </BCol>
              <BCol xl="8">
                <BAlert show variant="warning" class="mb-4">
                  Priorita' del consulente: chiudere le scadenze piu' vicine nelle aziende con copertura rischio incompleta.
                </BAlert>
                <BCard no-body class="border shadow-none mb-0">
                  <BCardHeader class="bg-light-subtle border-0">
                    <h5 class="card-title mb-0">Trend scadenze del portafoglio clienti</h5>
                  </BCardHeader>
                  <BCardBody>
                    <apexchart type="line" height="290" :options="deadlinesChart.options" :series="deadlinesChart.series" />
                  </BCardBody>
                </BCard>
              </BCol>
              <BCol xl="4">
                <BCard no-body class="border shadow-none mb-0 card-height-100">
                  <BCardHeader class="bg-light-subtle border-0">
                    <h5 class="card-title mb-0">Priorita' aperte</h5>
                  </BCardHeader>
                  <BCardBody>
                    <div class="d-flex gap-3 mb-4">
                      <div class="avatar-sm">
                        <span class="avatar-title rounded-circle bg-danger-subtle text-danger">
                          <i class="ri-error-warning-line"></i>
                        </span>
                      </div>
                      <div>
                        <h6 class="mb-1">Rischi non coperti</h6>
                        <p class="text-muted mb-0">5 esposizioni con misure ancora parziali.</p>
                      </div>
                    </div>
                    <div class="d-flex gap-3 mb-4">
                      <div class="avatar-sm">
                        <span class="avatar-title rounded-circle bg-warning-subtle text-warning">
                          <i class="ri-calendar-close-line"></i>
                        </span>
                      </div>
                      <div>
                        <h6 class="mb-1">Scadenze ravvicinate</h6>
                        <p class="text-muted mb-0">14 scadenze entro tre settimane.</p>
                      </div>
                    </div>
                    <div class="d-flex gap-3">
                      <div class="avatar-sm">
                        <span class="avatar-title rounded-circle bg-info-subtle text-info">
                          <i class="ri-file-list-3-line"></i>
                        </span>
                      </div>
                      <div>
                        <h6 class="mb-1">DVR da riallineare</h6>
                        <p class="text-muted mb-0">3 documenti con ultimo aggiornamento precedente all'ultimo audit.</p>
                      </div>
                    </div>
                  </BCardBody>
                </BCard>
              </BCol>
              <BCol xl="12">
                <BCard no-body class="border shadow-none mb-0">
                  <BCardHeader class="bg-light-subtle border-0">
                    <h5 class="card-title mb-0">Lista operativa del giorno</h5>
                  </BCardHeader>
                  <BCardBody class="p-0">
                    <div class="table-responsive">
                      <table class="table align-middle table-nowrap mb-0">
                        <thead class="table-light">
                          <tr>
                            <th>Attivita'</th>
                            <th>Azienda</th>
                            <th>Stato</th>
                            <th>Owner</th>
                            <th>Scadenza</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="row in deadlines" :key="`${row.item}-composition`">
                            <td class="fw-medium">{{ row.item }}</td>
                            <td>{{ row.company }}</td>
                            <td><span :class="`badge ${statusBadgeClasses(row.status)}`">{{ row.status }}</span></td>
                            <td>{{ row.owner }}</td>
                            <td>{{ row.due }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </BCardBody>
                </BCard>
              </BCol>
            </BRow>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BOffcanvas v-model="riskOffcanvas" title="Dettaglio rischio" placement="end" header-class="border-bottom">
      <div class="mb-3">
        <span class="badge bg-danger-subtle text-danger mb-2">Rischio alto</span>
        <h5>Esposizione a fumi di saldatura</h5>
        <p class="text-muted mb-0">Origine: mansione + macchinario. Reparto saldatura, turno mattina/pomeriggio.</p>
      </div>
      <div class="border rounded p-3 mb-3">
        <h6 class="mb-2">Misure collegate</h6>
        <ul class="text-muted ps-3 mb-0">
          <li>Aspirazione localizzata</li>
          <li>Visite mediche periodiche</li>
          <li>Formazione specifica</li>
        </ul>
      </div>
      <div class="border rounded p-3">
        <h6 class="mb-2">Stato presidio</h6>
        <p class="text-muted mb-0">Copertura parziale: aspirazione verificata, aggiornamento formativo ancora da chiudere.</p>
      </div>
    </BOffcanvas>

    <BOffcanvas v-model="deadlineOffcanvas" title="Dettaglio scadenza" placement="end" header-class="border-bottom">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <span class="badge bg-warning-subtle text-warning mb-2">In scadenza</span>
          <h5 class="mb-1">Visita medica reparto taglio</h5>
          <p class="text-muted mb-0">Metalnova S.r.l. - 28 Apr 2026</p>
        </div>
      </div>
      <div class="vstack gap-3">
        <div class="border rounded p-3">
          <h6 class="mb-1">Owner</h6>
          <p class="text-muted mb-0">Medico competente</p>
        </div>
        <div class="border rounded p-3">
          <h6 class="mb-1">Stato operativo</h6>
          <p class="text-muted mb-0">Convocazioni da inviare a 3 lavoratori, slot gia' opzionati.</p>
        </div>
        <div class="border rounded p-3">
          <h6 class="mb-1">Ultima nota consulente</h6>
          <p class="text-muted mb-0">Verificare eventuali nuove mansioni assegnate prima dell'invio definitivo.</p>
        </div>
      </div>
    </BOffcanvas>

    <BModal v-model="noteModal" title="Note consulente" hide-footer>
      <p class="text-muted">
        Pattern consigliato per annotazioni rapide su rischio, misura o scadenza senza uscire dal contesto operativo.
      </p>
      <textarea class="form-control" rows="5">Verificare con il preposto la sostituzione dei DPI udito e riallineare la scheda mansione prima del prossimo sopralluogo.</textarea>
      <div class="hstack justify-content-end gap-2 mt-3">
        <BButton variant="soft-secondary" @click="noteModal = false">Chiudi</BButton>
        <BButton variant="primary">Salva nota</BButton>
      </div>
    </BModal>
  </Layout>
</template>
