<script setup>
import { computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

const props = defineProps({
  tenant: Object,
  risk: Object,
  formOptions: Object,
});

const mappingForm = useForm({
  source_family: "job_role",
  sourceable_id: "",
  relevance: "primary",
  notes: "",
});

const submitMapping = () => {
  mappingForm.post(route("risk-catalog.source-links.store", props.risk.id), {
    preserveScroll: true,
    onSuccess: () => {
      mappingForm.reset("sourceable_id", "notes");
      mappingForm.source_family = "job_role";
      mappingForm.relevance = "primary";
    },
  });
};

const removeMapping = (link) => {
  useForm({}).delete(route("risk-catalog.source-links.destroy", [props.risk.id, link.id]), {
    preserveScroll: true,
  });
};

const sourceOptions = computed(() => {
  if (mappingForm.source_family === "equipment_type") {
    return props.formOptions.equipmentTypes;
  }

  if (mappingForm.source_family === "workplace_type") {
    return props.formOptions.workplaceTypes;
  }

  return props.formOptions.jobRoles;
});

const jobRoleLinks = computed(() => props.risk.source_links.filter((link) => link.sourceable_type.includes("JobRole")));
const equipmentTypeLinks = computed(() => props.risk.source_links.filter((link) => link.sourceable_type.includes("EquipmentType")));
const workplaceTypeLinks = computed(() => props.risk.source_links.filter((link) => link.sourceable_type.includes("WorkplaceType")));
</script>

<template>
  <Layout>
    <Head :title="risk.name" />
    <PageHeader title="Dettaglio rischio" pageTitle="SicurezzaChiara" />

    <BAlert v-if="$page.props.flash.success" show variant="success" class="mb-4">
      <i class="ri-checkbox-circle-line me-2 align-middle"></i>
      {{ $page.props.flash.success }}
    </BAlert>

    <BRow class="g-4 mb-4">
      <BCol xl="8">
        <BCard no-body class="overflow-hidden">
          <BCardBody class="p-4">
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
              <div>
                <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">Catalogo rischi</span>
                <h2 class="mb-1">{{ risk.name }}</h2>
                <p class="text-muted mb-3">{{ risk.description || "Nessuna descrizione operativa presente." }}</p>
                <div class="hstack gap-2 flex-wrap">
                  <span class="badge bg-light text-body">{{ tenant.name }}</span>
                  <span class="badge bg-soft-secondary text-secondary">{{ risk.category?.name || "Categoria non disponibile" }}</span>
                  <span v-if="risk.source === 'tenant'" class="badge bg-primary-subtle text-primary">Tenant</span>
                  <span v-else class="badge bg-light text-body">Core</span>
                  <span
                    class="badge"
                    :class="{
                      'bg-danger-subtle text-danger': risk.default_priority === 'high',
                      'bg-warning-subtle text-warning': risk.default_priority === 'medium',
                      'bg-success-subtle text-success': risk.default_priority === 'low',
                    }"
                  >
                    Priorita' {{ risk.default_priority }}
                  </span>
                </div>
              </div>
              <div class="hstack gap-2 flex-wrap">
                <Link v-if="$page.props.tenantContext?.permissions?.can_manage_data && risk.source === 'tenant'" :href="route('risk-catalog.edit', risk.id)" class="btn btn-primary">Modifica rischio</Link>
                <Link :href="route('risk-catalog.index')" class="btn btn-soft-secondary">Torna elenco</Link>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xl="4">
        <BCard no-body class="h-100">
          <BCardHeader class="border-0">
            <h4 class="card-title mb-0">Riepilogo</h4>
          </BCardHeader>
          <BCardBody>
            <div class="vstack gap-3">
              <div>
                <span class="text-muted d-block fs-13">Codice</span>
                <span class="fw-medium">{{ risk.code || "Non indicato" }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Origine</span>
                <span class="fw-medium">{{ risk.source === "tenant" ? "Catalogo tenant" : "Catalogo core" }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Tenant proprietario</span>
                <span class="fw-medium">{{ risk.tenant?.name || "Catalogo core condiviso" }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Collegamenti attivi</span>
                <span class="fw-medium">{{ risk.source_links.length }}</span>
              </div>
              <div>
                <span class="text-muted d-block fs-13">Presidi attesi</span>
                <span class="fw-medium">{{ risk.expected_measures?.length || 0 }}</span>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BCard no-body class="mb-4">
      <BCardHeader class="border-0">
        <div>
          <h4 class="card-title mb-1">Presidi attesi dal motore</h4>
          <p class="text-muted mb-0">Questa configurazione aiuta il sistema a distinguere tra misure semplicemente presenti e copertura effettivamente attesa per il rischio.</p>
        </div>
      </BCardHeader>
      <BCardBody class="pt-0">
        <div v-if="!(risk.expected_measures?.length)" class="text-muted">
          Nessun presidio atteso configurato su questo rischio.
        </div>
        <div v-else class="table-responsive">
          <table class="table align-middle table-nowrap mb-0">
            <thead class="table-light">
              <tr>
                <th>Codice</th>
                <th>Famiglia</th>
                <th>Presidio atteso</th>
                <th>Ruolo</th>
                <th>Equivalenza</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="expectedMeasure in risk.expected_measures" :key="expectedMeasure.code">
                <td><span class="badge bg-light text-body">{{ expectedMeasure.code }}</span></td>
                <td>{{ expectedMeasure.family }}</td>
                <td>
                  <div class="fw-semibold">{{ expectedMeasure.title }}</div>
                  <div class="text-muted fs-13">{{ expectedMeasure.description || "Nessuna descrizione aggiuntiva." }}</div>
                </td>
                <td>
                  <span class="badge" :class="expectedMeasure.is_required ? 'bg-warning-subtle text-warning' : 'bg-light text-body'">
                    {{ expectedMeasure.is_required ? "Richiesto" : "Opzionale" }}
                  </span>
                </td>
                <td>
                  <span class="badge" :class="expectedMeasure.allows_family_substitution ? 'bg-info-subtle text-info' : 'bg-light text-body'">
                    {{ expectedMeasure.allows_family_substitution ? "Stessa famiglia ammessa" : "Solo match diretto" }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </BCardBody>
    </BCard>

    <BRow class="g-4">
      <BCol xl="7">
        <BCard no-body>
          <BCardHeader class="border-0">
            <div>
              <h4 class="card-title mb-1">Mapping sorgenti</h4>
              <p class="text-muted mb-0">Il rischio resta indipendente dalle sorgenti, ma qui definiamo i primi collegamenti riusabili per il futuro motore.</p>
            </div>
          </BCardHeader>
          <BCardBody class="pt-0">
            <div class="table-responsive mb-4">
              <table class="table align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Mansioni collegate</th>
                    <th>Rilevanza</th>
                    <th class="text-end">Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="jobRoleLinks.length === 0">
                    <td colspan="3" class="text-center text-muted">Nessuna mansione collegata.</td>
                  </tr>
                  <tr v-for="link in jobRoleLinks" :key="`job-role-${link.id}`">
                    <td>
                      <div class="fw-semibold">{{ link.sourceable?.name }}</div>
                      <div class="text-muted fs-13">{{ link.notes || "Nessuna nota" }}</div>
                    </td>
                    <td>{{ link.relevance }}</td>
                    <td class="text-end">
                      <button v-if="$page.props.tenantContext?.permissions?.can_manage_data" type="button" class="btn btn-soft-danger btn-sm" @click="removeMapping(link)">Rimuovi</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="table-responsive mb-4">
              <table class="table align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Tipologie macchinario collegate</th>
                    <th>Rilevanza</th>
                    <th class="text-end">Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="equipmentTypeLinks.length === 0">
                    <td colspan="3" class="text-center text-muted">Nessuna tipologia macchinario collegata.</td>
                  </tr>
                  <tr v-for="link in equipmentTypeLinks" :key="`equipment-type-${link.id}`">
                    <td>
                      <div class="fw-semibold">{{ link.sourceable?.name }}</div>
                      <div class="text-muted fs-13">{{ link.notes || "Nessuna nota" }}</div>
                    </td>
                    <td>{{ link.relevance }}</td>
                    <td class="text-end">
                      <button v-if="$page.props.tenantContext?.permissions?.can_manage_data" type="button" class="btn btn-soft-danger btn-sm" @click="removeMapping(link)">Rimuovi</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="table-responsive">
              <table class="table align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Tipologie luogo collegate</th>
                    <th>Rilevanza</th>
                    <th class="text-end">Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="workplaceTypeLinks.length === 0">
                    <td colspan="3" class="text-center text-muted">Nessuna tipologia luogo collegata.</td>
                  </tr>
                  <tr v-for="link in workplaceTypeLinks" :key="`workplace-type-${link.id}`">
                    <td>
                      <div class="fw-semibold">{{ link.sourceable?.name }}</div>
                      <div class="text-muted fs-13">{{ link.notes || "Nessuna nota" }}</div>
                    </td>
                    <td>{{ link.relevance }}</td>
                    <td class="text-end">
                      <button v-if="$page.props.tenantContext?.permissions?.can_manage_data" type="button" class="btn btn-soft-danger btn-sm" @click="removeMapping(link)">Rimuovi</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </BCardBody>
        </BCard>
      </BCol>

      <BCol v-if="$page.props.tenantContext?.permissions?.can_manage_data" xl="5">
        <BCard no-body class="border">
          <BCardHeader class="bg-light-subtle border-0">
            <h5 class="mb-0">Aggiungi collegamento</h5>
          </BCardHeader>
          <BCardBody>
            <form @submit.prevent="submitMapping">
              <div class="mb-3">
                <label for="source_family" class="form-label">Famiglia sorgente *</label>
                <select id="source_family" v-model="mappingForm.source_family" class="form-select" :class="{ 'is-invalid': mappingForm.errors.source_family }">
                  <option value="job_role">Mansione</option>
                  <option value="equipment_type">Tipologia macchinario</option>
                  <option value="workplace_type">Tipologia luogo</option>
                </select>
                <div v-if="mappingForm.errors.source_family" class="invalid-feedback d-block">{{ mappingForm.errors.source_family }}</div>
              </div>
              <div class="mb-3">
                <label for="sourceable_id" class="form-label">Elemento sorgente *</label>
                <select id="sourceable_id" v-model="mappingForm.sourceable_id" class="form-select" :class="{ 'is-invalid': mappingForm.errors.sourceable_id }">
                  <option value="">Seleziona elemento</option>
                  <option v-for="option in sourceOptions" :key="`${mappingForm.source_family}-${option.id}`" :value="option.id">
                    {{ option.name }}{{ option.source === "core" ? " - core" : "" }}
                  </option>
                </select>
                <div v-if="mappingForm.errors.sourceable_id" class="invalid-feedback d-block">{{ mappingForm.errors.sourceable_id }}</div>
              </div>
              <div class="mb-3">
                <label for="relevance" class="form-label">Rilevanza *</label>
                <select id="relevance" v-model="mappingForm.relevance" class="form-select" :class="{ 'is-invalid': mappingForm.errors.relevance }">
                  <option value="primary">Primaria</option>
                  <option value="secondary">Secondaria</option>
                </select>
                <div v-if="mappingForm.errors.relevance" class="invalid-feedback d-block">{{ mappingForm.errors.relevance }}</div>
              </div>
              <div class="mb-3">
                <label for="mapping_notes" class="form-label">Note di collegamento</label>
                <textarea id="mapping_notes" v-model="mappingForm.notes" rows="3" class="form-control" :class="{ 'is-invalid': mappingForm.errors.notes }"></textarea>
                <div v-if="mappingForm.errors.notes" class="invalid-feedback d-block">{{ mappingForm.errors.notes }}</div>
              </div>
              <BButton variant="primary" type="submit" :disabled="mappingForm.processing">Aggiungi collegamento</BButton>
            </form>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>
  </Layout>
</template>
