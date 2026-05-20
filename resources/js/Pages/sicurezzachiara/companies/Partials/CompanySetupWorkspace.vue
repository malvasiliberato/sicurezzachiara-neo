<script setup>
import { computed, ref, watch } from "vue";
import { Link, useForm, usePage } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import CompanyForm from "./CompanyForm.vue";
import SiteForm from "./SiteForm.vue";
import WorkplaceForm from "@/Pages/sicurezzachiara/workplaces/Partials/WorkplaceForm.vue";
import EquipmentAssetForm from "@/Pages/sicurezzachiara/equipment-assets/Partials/EquipmentAssetForm.vue";
import WorkerForm from "@/Pages/sicurezzachiara/workers/Partials/WorkerForm.vue";

const props = defineProps({
  tenant: { type: Object, required: true },
  form: { type: Object, required: true },
  company: { type: Object, default: null },
  configureForms: { type: Object, default: null },
  atecoConfig: { type: Object, default: null },
  comuniConfig: { type: Object, default: null },
  submitLabel: { type: String, default: "Salva azienda" },
  mode: { type: String, default: "create" },
});

const emit = defineEmits(["submit-company"]);
const page = usePage();

const openSiteOffcanvas = ref(false);
const openWorkplaceOffcanvas = ref(false);
const openEquipmentOffcanvas = ref(false);
const openWorkerOffcanvas = ref(false);

const companyId = computed(() => props.company?.id ?? null);
const sites = computed(() => props.company?.sites ?? []);
const workplaces = computed(() => sites.value.flatMap((site) => site.workplaces ?? []));
const equipmentAssets = computed(() => props.company?.equipment_assets ?? []);
const workers = computed(() => props.company?.workers ?? []);
const workerAssignmentsCount = computed(() => workers.value.reduce((count, worker) => count + (worker.job_role_assignments?.length ?? 0), 0));
const editingSiteId = ref(null);
const editingWorkplaceId = ref(null);
const editingEquipmentId = ref(null);
const editingWorkerId = ref(null);
const siteComuneOption = ref(null);

const siteForm = useForm({
  name: "",
  site_code: "",
  is_headquarters: sites.value.length === 0,
  address_line: "",
  street_number: "",
  postal_code: "",
  city: props.form.city ?? "",
  province: props.form.province ?? "",
  notes: "",
  redirect_to_company_edit: true,
});
const deleteSiteForm = useForm({
  redirect_to_company_edit: true,
});

const escapeHtml = (value) =>
  String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");

const formatDependencyReference = (reference) => {
  const items = reference.items ?? [];
  const names = items.length > 0 ? `: ${items.map((item) => escapeHtml(item)).join(", ")}` : "";
  const more = reference.has_more ? " e altri collegamenti" : "";

  return `<li><strong>${escapeHtml(reference.label)}</strong> (${reference.count})${names}${more}</li>`;
};

const buildDependencyHtml = (references = []) =>
  references.length > 0
    ? `<ul class="text-start ps-3 mb-0">${references.map(formatDependencyReference).join("")}</ul>`
    : "";

const workplaceForm = useForm({
  company_id: props.company?.id ?? "",
  company_site_id: props.configureForms?.workplace?.defaults?.company_site_id ?? "",
  workplace_type_id: "",
  custom_workplace_type_name: "",
  code: "",
  name: "",
  status: "active",
  notes: "",
  redirect_to_company_edit: true,
});
const deleteWorkplaceForm = useForm({
  redirect_to_company_edit: true,
});

const equipmentForm = useForm({
  company_id: props.company?.id ?? "",
  company_site_id: props.configureForms?.equipment?.defaults?.company_site_id ?? "",
  equipment_type_id: "",
  custom_equipment_type_name: "",
  asset_code: "",
  name: "",
  manufacturer: "",
  model: "",
  status: "active",
  notes: "",
  redirect_to_company_edit: true,
});
const deleteEquipmentForm = useForm({
  redirect_to_company_edit: true,
});

const workerForm = useForm({
  company_id: props.company?.id ?? "",
  primary_site_id: props.configureForms?.worker?.defaults?.primary_site_id ?? "",
  job_role_id: "",
  first_name: "",
  last_name: "",
  tax_code: "",
  email: "",
  phone: "",
  birth_date: "",
  hire_date: "",
  status: "active",
  notes: "",
  redirect_to_company_edit: true,
});
const deleteWorkerForm = useForm({
  redirect_to_company_edit: true,
});

const submitSite = () => {
  const request = editingSiteId.value
    ? siteForm.put(route("companies.sites.update", [companyId.value, editingSiteId.value]), {
        preserveScroll: true,
        onSuccess: () => {
          resetSiteEditor();
          openSiteOffcanvas.value = false;
        },
      })
    : siteForm.post(route("companies.sites.store", companyId.value), {
        preserveScroll: true,
        onSuccess: () => {
          resetSiteEditor();
          openSiteOffcanvas.value = false;
        },
      });

  return request;
};

const submitWorkplace = () => {
  if (editingWorkplaceId.value) {
    workplaceForm.put(route("workplaces.update", editingWorkplaceId.value), {
      preserveScroll: true,
      onSuccess: () => {
        resetWorkplaceEditor();
        openWorkplaceOffcanvas.value = false;
      },
    });

    return;
  }

  workplaceForm.post(route("workplaces.store"), {
    preserveScroll: true,
    onSuccess: () => {
      resetWorkplaceEditor();
      openWorkplaceOffcanvas.value = false;
    },
  });
};

const submitEquipment = () => {
  if (editingEquipmentId.value) {
    equipmentForm.put(route("equipment-assets.update", editingEquipmentId.value), {
      preserveScroll: true,
      onSuccess: () => {
        resetEquipmentEditor();
        openEquipmentOffcanvas.value = false;
      },
    });

    return;
  }

  equipmentForm.post(route("equipment-assets.store"), {
    preserveScroll: true,
    onSuccess: () => {
      resetEquipmentEditor();
      openEquipmentOffcanvas.value = false;
    },
  });
};

const submitWorker = () => {
  if (editingWorkerId.value) {
    workerForm.put(route("workers.update", editingWorkerId.value), {
      preserveScroll: true,
      onSuccess: () => {
        resetWorkerEditor();
        openWorkerOffcanvas.value = false;
      },
    });

    return;
  }

  workerForm.post(route("workers.store"), {
    preserveScroll: true,
    onSuccess: () => {
      resetWorkerEditor();
      openWorkerOffcanvas.value = false;
    },
  });
};

const siteOffcanvasTitle = computed(() => (editingSiteId.value ? "Modifica sede" : "Nuova sede"));
const siteSubmitLabel = computed(() => (editingSiteId.value ? "Aggiorna sede" : "Salva sede"));
const workplaceOffcanvasTitle = computed(() => (editingWorkplaceId.value ? "Modifica luogo" : "Nuovo luogo"));
const workplaceSubmitLabel = computed(() => (editingWorkplaceId.value ? "Aggiorna luogo" : "Salva luogo"));
const equipmentOffcanvasTitle = computed(() => (editingEquipmentId.value ? "Modifica macchinario" : "Nuovo macchinario"));
const equipmentSubmitLabel = computed(() => (editingEquipmentId.value ? "Aggiorna macchinario" : "Salva macchinario"));
const workerOffcanvasTitle = computed(() => (editingWorkerId.value ? "Modifica lavoratore" : "Nuovo lavoratore"));
const workerSubmitLabel = computed(() => (editingWorkerId.value ? "Aggiorna lavoratore" : "Salva lavoratore"));
const companyIntro = computed(
  () => props.form?.industry?.trim() || "Configura i dati minimi dell'azienda e completa il contesto operativo quando serve.",
);

const resetSiteEditor = () => {
  editingSiteId.value = null;
  siteComuneOption.value = null;
  siteForm.name = "";
  siteForm.site_code = "";
  siteForm.is_headquarters = sites.value.length === 0;
  siteForm.address_line = "";
  siteForm.street_number = "";
  siteForm.postal_code = "";
  siteForm.city = props.form.city ?? "";
  siteForm.province = props.form.province ?? "";
  siteForm.notes = "";
  siteForm.redirect_to_company_edit = true;
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
  siteForm.redirect_to_company_edit = true;
  siteForm.clearErrors();
  openSiteOffcanvas.value = true;
};

const resetWorkplaceEditor = () => {
  editingWorkplaceId.value = null;
  workplaceForm.company_id = props.company?.id ?? "";
  workplaceForm.company_site_id = props.configureForms?.workplace?.defaults?.company_site_id ?? "";
  workplaceForm.workplace_type_id = "";
  workplaceForm.custom_workplace_type_name = "";
  workplaceForm.code = "";
  workplaceForm.name = "";
  workplaceForm.status = "active";
  workplaceForm.notes = "";
  workplaceForm.redirect_to_company_edit = true;
  workplaceForm.clearErrors();
};

const openCreateWorkplaceOffcanvas = () => {
  resetWorkplaceEditor();
  openWorkplaceOffcanvas.value = true;
};

const openEditWorkplaceOffcanvas = (workplace, site) => {
  editingWorkplaceId.value = workplace.id;
  workplaceForm.company_id = props.company?.id ?? "";
  workplaceForm.company_site_id = workplace.company_site_id ?? site?.id ?? "";
  workplaceForm.workplace_type_id = workplace.workplace_type_id ?? "";
  workplaceForm.custom_workplace_type_name = "";
  workplaceForm.code = workplace.code ?? "";
  workplaceForm.name = workplace.name ?? "";
  workplaceForm.status = workplace.status ?? "active";
  workplaceForm.notes = workplace.notes ?? workplace.description ?? "";
  workplaceForm.redirect_to_company_edit = true;
  workplaceForm.clearErrors();
  openWorkplaceOffcanvas.value = true;
};

const resetEquipmentEditor = () => {
  editingEquipmentId.value = null;
  equipmentForm.company_id = props.company?.id ?? "";
  equipmentForm.company_site_id = props.configureForms?.equipment?.defaults?.company_site_id ?? "";
  equipmentForm.equipment_type_id = "";
  equipmentForm.custom_equipment_type_name = "";
  equipmentForm.asset_code = "";
  equipmentForm.name = "";
  equipmentForm.manufacturer = "";
  equipmentForm.model = "";
  equipmentForm.status = "active";
  equipmentForm.notes = "";
  equipmentForm.redirect_to_company_edit = true;
  equipmentForm.clearErrors();
};

const openCreateEquipmentOffcanvas = () => {
  resetEquipmentEditor();
  openEquipmentOffcanvas.value = true;
};

const openEditEquipmentOffcanvas = (asset) => {
  editingEquipmentId.value = asset.id;
  equipmentForm.company_id = props.company?.id ?? "";
  equipmentForm.company_site_id = asset.company_site_id ?? "";
  equipmentForm.equipment_type_id = asset.equipment_type_id ?? "";
  equipmentForm.custom_equipment_type_name = "";
  equipmentForm.asset_code = asset.asset_code ?? "";
  equipmentForm.name = asset.name ?? "";
  equipmentForm.manufacturer = asset.manufacturer ?? "";
  equipmentForm.model = asset.model ?? "";
  equipmentForm.status = asset.status ?? "active";
  equipmentForm.notes = asset.notes ?? "";
  equipmentForm.redirect_to_company_edit = true;
  equipmentForm.clearErrors();
  openEquipmentOffcanvas.value = true;
};

const resetWorkerEditor = () => {
  editingWorkerId.value = null;
  workerForm.company_id = props.company?.id ?? "";
  workerForm.primary_site_id = props.configureForms?.worker?.defaults?.primary_site_id ?? "";
  workerForm.job_role_id = "";
  workerForm.first_name = "";
  workerForm.last_name = "";
  workerForm.tax_code = "";
  workerForm.email = "";
  workerForm.phone = "";
  workerForm.birth_date = "";
  workerForm.hire_date = "";
  workerForm.status = "active";
  workerForm.notes = "";
  workerForm.redirect_to_company_edit = true;
  workerForm.clearErrors();
};

const openCreateWorkerOffcanvas = () => {
  resetWorkerEditor();
  openWorkerOffcanvas.value = true;
};

const openEditWorkerOffcanvas = (worker) => {
  editingWorkerId.value = worker.id;
  workerForm.company_id = props.company?.id ?? "";
  workerForm.primary_site_id = worker.primary_site_id ?? "";
  workerForm.job_role_id = worker.job_role_assignments?.[0]?.job_role_id ?? "";
  workerForm.first_name = worker.first_name ?? "";
  workerForm.last_name = worker.last_name ?? "";
  workerForm.tax_code = worker.tax_code ?? "";
  workerForm.email = worker.email ?? "";
  workerForm.phone = worker.phone ?? "";
  workerForm.birth_date = worker.birth_date ?? "";
  workerForm.hire_date = worker.hire_date ?? "";
  workerForm.status = worker.status ?? "active";
  workerForm.notes = worker.notes ?? "";
  workerForm.redirect_to_company_edit = true;
  workerForm.clearErrors();
  openWorkerOffcanvas.value = true;
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

    deleteSiteForm.delete(route("companies.sites.destroy", [companyId.value, site.id]), {
      preserveScroll: true,
    });
  });
};

const deleteWorkplace = (workplace) => {
  Swal.fire({
    title: "Cancellare il luogo?",
    text: `Il luogo "${workplace.name}" verra' rimosso dalla sede operativa.`,
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

    deleteWorkplaceForm.delete(route("workplaces.destroy", workplace.id), {
      preserveScroll: true,
    });
  });
};

const deleteWorker = (worker) => {
  Swal.fire({
    title: "Cancellare il lavoratore?",
    text: `Il lavoratore "${worker.full_name}" verra' rimosso dall'azienda.`,
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

    deleteWorkerForm.delete(route("workers.destroy", worker.id), {
      preserveScroll: true,
    });
  });
};

const deleteEquipment = (asset) => {
  Swal.fire({
    title: "Cancellare il macchinario?",
    text: `Il macchinario "${asset.name}" verra' rimosso dall'azienda.`,
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

    deleteEquipmentForm.delete(route("equipment-assets.destroy", asset.id), {
      preserveScroll: true,
    });
  });
};

watch(openSiteOffcanvas, (isOpen) => {
  if (!isOpen) {
    resetSiteEditor();
  }
});

watch(openWorkplaceOffcanvas, (isOpen) => {
  if (!isOpen) {
    resetWorkplaceEditor();
  }
});

watch(openEquipmentOffcanvas, (isOpen) => {
  if (!isOpen) {
    resetEquipmentEditor();
  }
});

watch(openWorkerOffcanvas, (isOpen) => {
  if (!isOpen) {
    resetWorkerEditor();
  }
});

watch(
  () => page.props.flash?.success,
  (message) => {
    if (!message) {
      return;
    }

    Swal.fire({
      title: "Operazione completata",
      text: message,
      icon: "success",
      confirmButtonText: "Ok",
      customClass: {
        confirmButton: "btn btn-primary w-xs mt-2",
      },
      buttonsStyling: false,
    });
  }
);

watch(
  () => page.props.flash?.error,
  (payload) => {
    if (!payload) {
      return;
    }

    const isStructured = typeof payload === "object" && payload !== null;

    Swal.fire({
      title: isStructured ? payload.title || "Operazione non disponibile" : "Operazione non disponibile",
      text: isStructured ? undefined : payload,
      html: isStructured
        ? `<p class="mb-3">${escapeHtml(payload.message || "Operazione non disponibile.")}</p>${buildDependencyHtml(payload.references || [])}`
        : undefined,
      icon: "info",
      confirmButtonText: "Ho capito",
      customClass: {
        confirmButton: "btn btn-primary w-xs mt-2",
      },
      buttonsStyling: false,
    });
  }
);
</script>

<template>
  <BCard no-body>
    <BCardBody class="p-4">
      <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
        <div>
          <span class="badge bg-primary-subtle text-primary text-uppercase mb-3">Configura azienda</span>
          <h3 class="mb-2">{{ company ? company.name : "Nuova azienda" }}</h3>
          <p class="text-muted mb-0">{{ companyIntro }}</p>
        </div>
        <Link :href="route('companies.index')" class="btn btn-soft-secondary">Torna ad aziende</Link>
      </div>

      <BTabs nav-class="nav-success mb-4" pills>
        <BTab title="Anagrafica" active>
          <div class="border rounded-3 p-4">
            <div class="mb-3">
              <h5 class="mb-1">Anagrafica minima</h5>
              <p class="text-muted mb-0">Per iniziare basta il nome dell'azienda. Gli altri dati servono a completare il contesto.</p>
            </div>

            <form @submit.prevent="emit('submit-company')">
              <CompanyForm
                :form="form"
                :ateco-config="atecoConfig"
                :comuni-config="comuniConfig"
                :submit-label="submitLabel"
                :light-create="mode === 'create'"
                embedded
              />
            </form>
          </div>
        </BTab>

        <BTab title="Sedi">
          <div class="border rounded-3 p-4">
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
              <div>
                <h5 class="mb-1">Sedi operative</h5>
                <p class="text-muted mb-0">Le sedi indicano dove si svolge il lavoro. Da qui collegherai luoghi, macchinari e lavoratori.</p>
              </div>
              <BButton v-if="company" variant="soft-secondary" @click="openCreateSiteOffcanvas">
                {{ sites.length > 0 ? "Aggiungi sede" : "Nuova sede" }}
              </BButton>
            </div>

            <div v-if="!company" class="border rounded-3 p-3 bg-light-subtle text-center">
              <div class="fw-semibold mb-1">Salva prima l'azienda</div>
              <div class="text-muted fs-13">Dopo il primo salvataggio potrai aggiungere le sedi operative.</div>
            </div>
            <div v-else-if="sites.length === 0" class="border rounded-3 p-3 bg-light-subtle text-center">
              <div class="fw-semibold mb-1">Nessuna sede ancora presente</div>
              <div class="text-muted fs-13">Aggiungi la prima sede per completare la struttura aziendale.</div>
            </div>
            <div v-else class="table-responsive">
              <table class="table align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Sede</th>
                    <th>Localita'</th>
                    <th class="text-end">Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="site in sites" :key="site.id">
                    <td>
                      <div class="fw-semibold">{{ site.name }}</div>
                      <div class="text-muted fs-13">{{ site.site_code || "Codice non indicato" }}</div>
                      <BAlert
                        v-if="site.dependency_alert"
                        show
                        variant="warning"
                        class="mt-2 mb-0 py-2 fs-13"
                      >
                        <div class="fw-semibold mb-1">{{ site.dependency_alert.title }}</div>
                        <ul class="mb-0 ps-3">
                          <li v-for="reference in site.dependency_alert.references" :key="`${site.id}-${reference.key}`">
                            {{ reference.label }} ({{ reference.count }})
                            <template v-if="reference.items?.length">
                              : {{ reference.items.join(", ") }}<span v-if="reference.has_more"> e altri collegamenti</span>
                            </template>
                          </li>
                        </ul>
                      </BAlert>
                    </td>
                    <td>{{ site.city || "Non indicata" }}<template v-if="site.province"> ({{ site.province }})</template></td>
                    <td class="text-end">
                      <div class="hstack justify-content-end gap-2">
                        <BButton variant="soft-secondary" size="sm" @click="openEditSiteOffcanvas(site)">Modifica</BButton>
                        <BButton variant="soft-danger" size="sm" @click="deleteSite(site)">Cancella</BButton>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </BTab>

        <BTab title="Luoghi">
          <div class="border rounded-3 p-4">
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
              <div>
                <h5 class="mb-1">Luoghi di lavoro</h5>
                <p class="text-muted mb-0">I luoghi rappresentano ambienti che possono generare rischi.</p>
              </div>
              <BButton v-if="company" variant="soft-secondary" :disabled="sites.length === 0" @click="openCreateWorkplaceOffcanvas">Nuovo luogo</BButton>
            </div>

            <div v-if="!company" class="border rounded-3 p-3 bg-light-subtle text-center">
              <div class="fw-semibold mb-1">Salva prima l'azienda</div>
              <div class="text-muted fs-13">Dopo il primo salvataggio potrai censire i luoghi di lavoro.</div>
            </div>
            <div v-else-if="sites.length === 0" class="border rounded-3 p-3 bg-light-subtle text-center">
              <div class="fw-semibold mb-1">Serve una sede a cui collegare il luogo</div>
              <div class="text-muted fs-13">Aggiungi prima almeno una sede per associare correttamente i luoghi.</div>
            </div>
            <div v-else-if="workplaces.length === 0" class="border rounded-3 p-3 bg-light-subtle text-center">
              <div class="fw-semibold mb-1">Nessun luogo ancora registrato</div>
              <div class="text-muted fs-13">Aggiungi i luoghi di lavoro presenti nelle sedi aziendali.</div>
            </div>
            <div v-else class="table-responsive">
              <table class="table align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Luogo</th>
                    <th>Sede</th>
                    <th>Tipologia</th>
                    <th class="text-end">Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-for="site in sites" :key="site.id">
                    <tr v-for="workplace in site.workplaces" :key="workplace.id">
                      <td>
                        <div class="fw-semibold">{{ workplace.name }}</div>
                        <div class="text-muted fs-13">{{ workplace.code || "Codice non indicato" }}</div>
                        <BAlert
                          v-if="workplace.dependency_alert"
                          show
                          variant="warning"
                          class="mt-2 mb-0 py-2 fs-13"
                        >
                          <div class="fw-semibold mb-1">{{ workplace.dependency_alert.title }}</div>
                          <ul class="mb-0 ps-3">
                            <li v-for="reference in workplace.dependency_alert.references" :key="`${workplace.id}-${reference.key}`">
                              {{ reference.label }} ({{ reference.count }})
                              <template v-if="reference.items?.length">
                                : {{ reference.items.join(", ") }}<span v-if="reference.has_more"> e altri collegamenti</span>
                              </template>
                            </li>
                          </ul>
                        </BAlert>
                      </td>
                      <td>{{ site.name }}</td>
                      <td>{{ workplace.workplace_type?.name || "Non disponibile" }}</td>
                      <td class="text-end">
                        <div class="hstack justify-content-end gap-2">
                          <BButton variant="soft-secondary" size="sm" @click="openEditWorkplaceOffcanvas(workplace, site)">Modifica</BButton>
                          <BButton variant="soft-danger" size="sm" @click="deleteWorkplace(workplace)">Cancella</BButton>
                        </div>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
        </BTab>

        <BTab title="Macchinari">
          <div class="border rounded-3 p-4">
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
              <div>
                <h5 class="mb-1">Macchinari e attrezzature</h5>
                <p class="text-muted mb-0">I macchinari e le attrezzature completano il contesto da cui nasce il profilo rischio.</p>
              </div>
              <BButton v-if="company" variant="soft-secondary" @click="openCreateEquipmentOffcanvas">Nuovo macchinario</BButton>
            </div>

            <div v-if="!company" class="border rounded-3 p-3 bg-light-subtle text-center">
              <div class="fw-semibold mb-1">Salva prima l'azienda</div>
              <div class="text-muted fs-13">Dopo il primo salvataggio potrai aggiungere macchinari e attrezzature.</div>
            </div>
            <div v-else-if="equipmentAssets.length === 0" class="border rounded-3 p-3 bg-light-subtle text-center">
              <div class="fw-semibold mb-1">Nessun macchinario ancora registrato</div>
              <div class="text-muted fs-13">Aggiungi le attrezzature rilevanti per il contesto aziendale.</div>
            </div>
            <div v-else class="table-responsive">
              <table class="table align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Macchinario</th>
                    <th>Sede</th>
                    <th>Tipologia</th>
                    <th class="text-end">Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="asset in equipmentAssets" :key="asset.id">
                    <td>
                      <div class="fw-semibold">{{ asset.name }}</div>
                      <div class="text-muted fs-13">{{ asset.asset_code || "Codice non indicato" }}</div>
                      <BAlert
                        v-if="asset.dependency_alert"
                        show
                        variant="warning"
                        class="mt-2 mb-0 py-2 fs-13"
                      >
                        <div class="fw-semibold mb-1">{{ asset.dependency_alert.title }}</div>
                        <ul class="mb-0 ps-3">
                          <li v-for="reference in asset.dependency_alert.references" :key="`${asset.id}-${reference.key}`">
                            {{ reference.label }} ({{ reference.count }})
                            <template v-if="reference.items?.length">
                              : {{ reference.items.join(", ") }}<span v-if="reference.has_more"> e altri collegamenti</span>
                            </template>
                          </li>
                        </ul>
                      </BAlert>
                    </td>
                    <td>{{ asset.site?.name || "Non assegnata" }}</td>
                    <td>{{ asset.equipment_type?.name || "Non disponibile" }}</td>
                    <td class="text-end">
                      <div class="hstack justify-content-end gap-2">
                        <BButton variant="soft-secondary" size="sm" @click="openEditEquipmentOffcanvas(asset)">Modifica</BButton>
                        <BButton variant="soft-danger" size="sm" @click="deleteEquipment(asset)">Cancella</BButton>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </BTab>

        <BTab title="Lavoratori">
          <div class="border rounded-3 p-4">
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
              <div>
                <h5 class="mb-1">Lavoratori e mansioni</h5>
                <p class="text-muted mb-0">Assegna le mansioni ai lavoratori: da qui SicurezzaChiara propone rischi, DPI, corsi e visite.</p>
              </div>
              <BButton v-if="company" variant="soft-secondary" @click="openCreateWorkerOffcanvas">Nuovo lavoratore</BButton>
            </div>

            <div v-if="!company" class="border rounded-3 p-3 bg-light-subtle text-center">
              <div class="fw-semibold mb-1">Salva prima l'azienda</div>
              <div class="text-muted fs-13">Dopo il primo salvataggio potrai inserire i lavoratori dell'azienda.</div>
            </div>
            <div v-else-if="workers.length === 0" class="border rounded-3 p-3 bg-light-subtle text-center">
              <div class="fw-semibold mb-1">Nessun lavoratore ancora registrato</div>
              <div class="text-muted fs-13">Aggiungi i lavoratori per completare il contesto operativo dell'azienda.</div>
            </div>
            <div v-else>
              <div v-if="workerAssignmentsCount === 0" class="border rounded-3 p-3 bg-warning-subtle text-warning-emphasis mb-3">
                Completa le mansioni per collegare i lavoratori al contesto operativo.
              </div>
              <div class="table-responsive">
                <table class="table align-middle table-nowrap mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Lavoratore</th>
                      <th>Sede prevalente</th>
                      <th>Mansione prevalente</th>
                      <th class="text-end">Azioni</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="worker in workers" :key="worker.id">
                      <td>
                        <div class="fw-semibold">{{ worker.full_name }}</div>
                        <div class="text-muted fs-13">{{ worker.tax_code || "Codice fiscale non indicato" }}</div>
                        <BAlert
                          v-if="worker.dependency_alert"
                          show
                          variant="warning"
                          class="mt-2 mb-0 py-2 fs-13"
                        >
                          <div class="fw-semibold mb-1">{{ worker.dependency_alert.title }}</div>
                          <ul class="mb-0 ps-3">
                            <li v-for="reference in worker.dependency_alert.references" :key="`${worker.id}-${reference.key}`">
                              {{ reference.label }} ({{ reference.count }})
                              <template v-if="reference.items?.length">
                                : {{ reference.items.join(", ") }}<span v-if="reference.has_more"> e altri collegamenti</span>
                              </template>
                            </li>
                          </ul>
                        </BAlert>
                      </td>
                      <td>{{ worker.primary_site?.name || "Non assegnata" }}</td>
                      <td>{{ worker.job_role_assignments?.[0]?.job_role?.name || "Da completare" }}</td>
                      <td class="text-end">
                        <div class="hstack justify-content-end gap-2">
                          <BButton variant="soft-secondary" size="sm" @click="openEditWorkerOffcanvas(worker)">Modifica</BButton>
                          <Link :href="route('workers.show', worker.id)" class="btn btn-soft-primary btn-sm">Apri</Link>
                          <BButton variant="soft-danger" size="sm" @click="deleteWorker(worker)">Cancella</BButton>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </BTab>
      </BTabs>
    </BCardBody>
  </BCard>

  <BOffcanvas
    v-if="company"
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
        :comuni-config="{ searchRoute: comuniConfig?.searchRoute, initialOption: siteComuneOption }"
        embedded
        @cancel="resetSiteEditor(); openSiteOffcanvas = false"
      />
    </form>
  </BOffcanvas>

  <BOffcanvas
    v-if="company"
    v-model="openWorkplaceOffcanvas"
    placement="end"
    body-class="p-4"
    style="--bs-offcanvas-width: min(1320px, 98vw);"
  >
    <template #header><div><h5 class="mb-0">{{ workplaceOffcanvasTitle }}</h5></div></template>
    <form @submit.prevent="submitWorkplace"><WorkplaceForm :form="workplaceForm" :form-options="configureForms.workplace.formOptions" :submit-label="workplaceSubmitLabel" embedded @cancel="resetWorkplaceEditor(); openWorkplaceOffcanvas = false" /></form>
  </BOffcanvas>

  <BOffcanvas
    v-if="company"
    v-model="openEquipmentOffcanvas"
    placement="end"
    body-class="p-4"
    style="--bs-offcanvas-width: min(1320px, 98vw);"
  >
    <template #header><div><h5 class="mb-0">{{ equipmentOffcanvasTitle }}</h5></div></template>
    <form @submit.prevent="submitEquipment"><EquipmentAssetForm :form="equipmentForm" :form-options="configureForms.equipment.formOptions" :submit-label="equipmentSubmitLabel" embedded @cancel="resetEquipmentEditor(); openEquipmentOffcanvas = false" /></form>
  </BOffcanvas>

  <BOffcanvas
    v-if="company"
    v-model="openWorkerOffcanvas"
    placement="end"
    body-class="p-4"
    style="--bs-offcanvas-width: min(1320px, 98vw);"
  >
    <template #header><div><h5 class="mb-0">{{ workerOffcanvasTitle }}</h5></div></template>
    <form @submit.prevent="submitWorker"><WorkerForm :form="workerForm" :form-options="configureForms.worker.formOptions" :submit-label="workerSubmitLabel" embedded @cancel="resetWorkerEditor(); openWorkerOffcanvas = false" /></form>
  </BOffcanvas>
</template>
