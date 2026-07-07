<!--
  ServiceTransportForm.vue — Refonte Phase 4.4 (Steppers).
  Historique : 403 lignes / 5 sections empilées + 10 points hardcodés → 4 steps guidés.

  Steps :
    1. Contexte     (année scolaire, école → campus auto)
    2. Itinéraire   (zone, ligne, point de départ + 10 points d'arrêt dynamiques)
    3. Tarification (mensuel, trimestriel, semestriel, annuel)
    4. Période & Statut (date début/fin, état)
-->

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import HierarchyContextBar from '@/Components/Common/HierarchyContextBar.vue';
import FormStepper from '@/Components/Common/FormStepper.vue';

const { t } = useI18n();

const props = defineProps({
    form:            { type: Object, required: true },
    anneesScolaires: { type: Array,  default: () => [] },
    ecoles:          { type: Array,  default: () => [] },
    campuses:        { type: Array,  default: () => [] },
    mode: { type: String, default: 'create', validator: (v) => ['create', 'edit', 'show'].includes(v) },
});

const emit = defineEmits(['submit']);

const isReadOnly = props.mode === 'show';
const currentStep = ref(0);

const autoLabel = (list, id) => {
    if (!id || !list?.length) return '—';
    const f = list.find(x => String(x.id) === String(id));
    return f?.libelle || f?.nom || f?.label || '—';
};
const campusLabel = computed(() => autoLabel(props.campuses, props.form.campus_id));

const statusOptions = [
    { id: 'actif',   libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const steps = [
    { key: 'contexte',     label: 'Contexte',        icon: 'fas fa-bus',        requiredFields: ['ecole_id'] },
    { key: 'itineraire',   label: 'Itinéraire',      icon: 'fas fa-route' },
    { key: 'tarification', label: 'Tarification',    icon: 'fas fa-money-bill' },
    { key: 'periode',      label: 'Période & Statut', icon: 'fas fa-calendar',   requiredFields: ['etat'] },
];
</script>

<template>
    <FormStepper
        v-model="currentStep"
        :steps="steps"
        :form="form"
        persist-key="service-transport-form"
        @submit="$emit('submit')"
    >
        <!-- STEP 1 : CONTEXTE -->
        <template #contexte>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Année scolaire</label>
                    <SearchableSelect
                        v-model="form.annee_scolaire_id"
                        :options="anneesScolaires"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-md-6">
                    <label>École <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.ecole_id"
                        :options="ecoles"
                        optionValue="id"
                        optionLabel="nom"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.ecole_id" class="text-danger small">{{ form.errors.ecole_id }}</span>
                </div>
                <div class="col-12">
                    <HierarchyContextBar :form="form" :ecoles="ecoles" :campuses="campuses" />
                </div>
                <div class="col-md-6">
                    <label>Campus <span class="badge bg-secondary">auto</span></label>
                    <input :value="campusLabel" type="text" class="form-control" readonly disabled />
                </div>
            </div>
        </template>

        <!-- STEP 2 : ITINÉRAIRE -->
        <template #itineraire>
            <div class="row g-3">
                <div class="col-md-4">
                    <label>Zone</label>
                    <input v-model="form.zone" :disabled="isReadOnly" type="text" class="form-control" placeholder="Ex : Nord, Sud, Centre…" />
                </div>
                <div class="col-md-4">
                    <label>Ligne</label>
                    <input v-model="form.ligne" :disabled="isReadOnly" type="text" class="form-control" placeholder="Ligne de transport" />
                </div>
                <div class="col-md-4">
                    <label>Point de départ</label>
                    <input v-model="form.point_depart" :disabled="isReadOnly" type="text" class="form-control" placeholder="Point de départ" />
                </div>

                <hr class="mt-3" />
                <div class="col-12">
                    <h6 class="text-primary"><i class="fa fa-map-pin me-1"></i> Points d'arrêt (jusqu'à 10)</h6>
                </div>
                <div v-for="i in 10" :key="`point-${i}`" class="col-md-6">
                    <label>Point {{ i }}</label>
                    <input
                        v-model="form[`point_${i}`]"
                        :disabled="isReadOnly"
                        type="text"
                        class="form-control"
                        :placeholder="`Point d'arrêt ${i}`"
                    />
                </div>
            </div>
        </template>

        <!-- STEP 3 : TARIFICATION -->
        <template #tarification>
            <div class="row g-3">
                <div class="col-md-3">
                    <label>Tarif mensuel</label>
                    <input v-model.number="form.tarif_mensuel" :disabled="isReadOnly" type="number" min="0" class="form-control" placeholder="0" />
                </div>
                <div class="col-md-3">
                    <label>Tarif trimestriel</label>
                    <input v-model.number="form.tarif_trimestriel" :disabled="isReadOnly" type="number" min="0" class="form-control" placeholder="0" />
                </div>
                <div class="col-md-3">
                    <label>Tarif semestriel</label>
                    <input v-model.number="form.tarif_semestriel" :disabled="isReadOnly" type="number" min="0" class="form-control" placeholder="0" />
                </div>
                <div class="col-md-3">
                    <label>Tarif annuel</label>
                    <input v-model.number="form.tarif_annuel" :disabled="isReadOnly" type="number" min="0" class="form-control" placeholder="0" />
                </div>
            </div>
        </template>

        <!-- STEP 4 : PÉRIODE & STATUT -->
        <template #periode>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Date de début</label>
                    <input v-model="form.date_debut" :disabled="isReadOnly" type="date" class="form-control" />
                </div>
                <div class="col-md-6">
                    <label>Date de fin</label>
                    <input v-model="form.date_fin" :disabled="isReadOnly" type="date" class="form-control" />
                </div>
                <hr class="mt-3" />
                <div class="col-md-6">
                    <label>État <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.etat"
                        :options="statusOptions"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.etat" class="text-danger small">{{ form.errors.etat }}</span>
                </div>
            </div>
        </template>
    </FormStepper>
</template>

<style scoped>
.form-control {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 0.55rem 0.85rem;
    font-size: 0.95rem;
}
.form-control:focus {
    border-color: #0b5697;
    box-shadow: 0 0 0 0.2rem rgba(11, 86, 151, 0.15);
}
label {
    font-weight: 500;
    color: #374151;
    font-size: 0.9rem;
    margin-bottom: 0.4rem;
    display: block;
}
</style>
