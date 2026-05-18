<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
  form: Object,
  formOptions: Object,
  submitLabel: String,
});

const measureFamilies = computed(() => props.formOptions.measureFamilies ?? []);

const addExpectedMeasure = () => {
  props.form.expected_measures.push({
    code: `measure_${props.form.expected_measures.length + 1}`,
    family: "organizational",
    title: "",
    description: "",
    is_required: true,
    allows_family_substitution: false,
  });
};

const removeExpectedMeasure = (index) => {
  props.form.expected_measures.splice(index, 1);
};
</script>

<template>
  <BCard no-body>
    <BCardHeader class="border-0">
      <div>
        <h4 class="card-title mb-1">Rischio normalizzato</h4>
        <p class="text-muted mb-0">Elemento di catalogo riusabile e agganciabile alle sorgenti operative del dominio.</p>
      </div>
    </BCardHeader>
    <BCardBody>
      <BRow class="g-3">
        <BCol md="4">
          <InputLabel for="code" value="Codice" />
          <TextInput id="code" v-model="form.code" type="text" :class="{ 'is-invalid': form.errors.code }" />
          <InputError :message="form.errors.code" />
        </BCol>
        <BCol md="8">
          <InputLabel for="name" value="Nome rischio *" />
          <TextInput id="name" v-model="form.name" type="text" :class="{ 'is-invalid': form.errors.name }" />
          <InputError :message="form.errors.name" />
        </BCol>
        <BCol md="6">
          <InputLabel for="risk_category_id" value="Categoria *" />
          <select id="risk_category_id" v-model="form.risk_category_id" class="form-select" :class="{ 'is-invalid': form.errors.risk_category_id }">
            <option value="">Seleziona categoria</option>
            <option v-for="category in formOptions.categories" :key="category.id" :value="category.id">{{ category.name }}</option>
          </select>
          <InputError :message="form.errors.risk_category_id" />
        </BCol>
        <BCol md="6">
          <InputLabel for="default_priority" value="Priorita' base *" />
          <select id="default_priority" v-model="form.default_priority" class="form-select" :class="{ 'is-invalid': form.errors.default_priority }">
            <option v-for="priority in formOptions.priorities" :key="priority.value" :value="priority.value">{{ priority.label }}</option>
          </select>
          <InputError :message="form.errors.default_priority" />
        </BCol>
        <BCol md="12">
          <InputLabel for="description" value="Descrizione operativa" />
          <textarea id="description" v-model="form.description" rows="4" class="form-control" :class="{ 'is-invalid': form.errors.description }"></textarea>
          <InputError :message="form.errors.description" />
        </BCol>
        <BCol md="12">
          <div class="border rounded p-3">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
              <div>
                <h5 class="mb-1">Presidi attesi dal rischio</h5>
                <p class="text-muted mb-0">Definisci in modo leggero cosa il motore dovrebbe aspettarsi come copertura minima per questo rischio.</p>
              </div>
              <button type="button" class="btn btn-soft-primary btn-sm" @click="addExpectedMeasure">
                Aggiungi presidio atteso
              </button>
            </div>

            <div v-if="form.expected_measures.length === 0" class="text-muted fs-13">
              Nessun presidio atteso configurato. In assenza di aspettative esplicite, la copertura continuera' a leggere solo le misure effettivamente registrate.
            </div>

            <div v-else class="vstack gap-3">
              <div v-for="(expectedMeasure, index) in form.expected_measures" :key="`expected-measure-${index}`" class="border rounded p-3">
                <BRow class="g-3">
                  <BCol md="3">
                    <InputLabel :for="`expected_measures_${index}_code`" value="Codice" />
                    <TextInput :id="`expected_measures_${index}_code`" v-model="expectedMeasure.code" type="text" :class="{ 'is-invalid': form.errors[`expected_measures.${index}.code`] }" />
                    <InputError :message="form.errors[`expected_measures.${index}.code`]" />
                  </BCol>
                  <BCol md="3">
                    <InputLabel :for="`expected_measures_${index}_family`" value="Famiglia *" />
                    <select :id="`expected_measures_${index}_family`" v-model="expectedMeasure.family" class="form-select" :class="{ 'is-invalid': form.errors[`expected_measures.${index}.family`] }">
                      <option v-for="family in measureFamilies" :key="family.value" :value="family.value">{{ family.label }}</option>
                    </select>
                    <InputError :message="form.errors[`expected_measures.${index}.family`]" />
                  </BCol>
                  <BCol md="4">
                    <InputLabel :for="`expected_measures_${index}_title`" value="Titolo presidio *" />
                    <TextInput :id="`expected_measures_${index}_title`" v-model="expectedMeasure.title" type="text" :class="{ 'is-invalid': form.errors[`expected_measures.${index}.title`] }" />
                    <InputError :message="form.errors[`expected_measures.${index}.title`]" />
                  </BCol>
                  <BCol md="2">
                    <div class="form-check form-switch form-switch-md mt-4">
                      <input :id="`expected_measures_${index}_required`" v-model="expectedMeasure.is_required" class="form-check-input" type="checkbox" />
                      <label class="form-check-label" :for="`expected_measures_${index}_required`">Richiesto</label>
                    </div>
                  </BCol>
                  <BCol md="11">
                    <InputLabel :for="`expected_measures_${index}_description`" value="Descrizione presidio" />
                    <textarea :id="`expected_measures_${index}_description`" v-model="expectedMeasure.description" rows="2" class="form-control" :class="{ 'is-invalid': form.errors[`expected_measures.${index}.description`] }"></textarea>
                    <InputError :message="form.errors[`expected_measures.${index}.description`]" />
                  </BCol>
                  <BCol md="11">
                    <div class="form-check form-switch form-switch-md">
                      <input :id="`expected_measures_${index}_allows_family_substitution`" v-model="expectedMeasure.allows_family_substitution" class="form-check-input" type="checkbox" />
                      <label class="form-check-label" :for="`expected_measures_${index}_allows_family_substitution`">
                        Consenti copertura equivalente della stessa famiglia
                      </label>
                    </div>
                    <div class="text-muted fs-13 mt-1">
                      Se attivo, una misura libera della stessa famiglia puo' coprire questo presidio atteso anche senza aggancio rigido al codice.
                    </div>
                    <InputError :message="form.errors[`expected_measures.${index}.allows_family_substitution`]" />
                  </BCol>
                  <BCol md="1" class="d-flex align-items-end justify-content-end">
                    <button type="button" class="btn btn-soft-danger btn-sm" @click="removeExpectedMeasure(index)">
                      Rimuovi
                    </button>
                  </BCol>
                </BRow>
              </div>
            </div>
          </div>
        </BCol>
        <BCol md="4">
          <div class="form-check form-switch form-switch-md mt-2">
            <input id="is_active" v-model="form.is_active" class="form-check-input" type="checkbox" />
            <label class="form-check-label" for="is_active">Rischio attivo</label>
          </div>
          <InputError :message="form.errors.is_active" />
        </BCol>
      </BRow>
    </BCardBody>
    <BCardFooter class="bg-white border-top">
      <div class="hstack justify-content-end gap-2">
        <Link :href="route('risk-catalog.index')" class="btn btn-soft-secondary">Annulla</Link>
        <BButton variant="primary" type="submit" :disabled="form.processing">{{ submitLabel }}</BButton>
      </div>
    </BCardFooter>
  </BCard>
</template>
