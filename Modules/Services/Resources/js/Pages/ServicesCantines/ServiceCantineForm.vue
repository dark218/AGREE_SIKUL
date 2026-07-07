<!--
  ServiceCantineForm.vue — Refonte Phase 4.4 (Steppers).
  Historique : 439 lignes / 5 sections empilées → 3 steps guidés.

  Steps :
    1. Identité & Scolarité (nom, code, année scolaire, niveau, cycle, école → campus auto)
    2. Tarification         (prix, tarifs mensuel/trimestriel/semestriel/annuel, description)
    3. Période & Statut     (date début/fin, statut)
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
    niveaux:         { type: Array,  default: () => [] },
    cycles:          { type: Array,  default: () => [] },
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
    { key: 'identite',     label: 'Identité & Scolarité', icon: 'fas fa-utensils',    requiredFields: ['nom', 'ecole_id'] },
    { key: 'tarification', label: 'Tarification',         icon: 'fas fa-money-bill' },
    { key: 'periode',      label: 'Période & Statut',     icon: 'fas fa-calendar',    requiredFields: ['statut'] },
];
</script>

<template>
    <FormStepper
        v-model="currentStep"
        :steps="steps"
        :form="form"
        persist-key="service-cantine-form"
        @submit="$emit('submit')"
    >
        <!-- STEP 1 : IDENTITÉ & SCOLARITÉ -->
        <template #identite>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Nom du service <span class="text-danger">*</span></label>
                    <input v-model="form.nom" :disabled="isReadOnly" type="text" class="form-control" placeholder="Nom du service" />
                    <span v-if="form.errors?.nom" class="text-danger small">{{ form.errors.nom }}</span>
                </div>
                <div class="col-md-6">
                    <label>Code</label>
                    <input v-model="form.code" :disabled="isReadOnly" type="text" class="form-control" placeholder="Code unique" />
                    <span v-if="form.errors?.code" class="text-danger small">{{ form.errors.code }}</span>
                </div>

                <hr class="mt-3" />
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
                    <label>Niveau</label>
                    <SearchableSelect
                        v-model="form.niveau_id"
                        :options="niveaux"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-md-6">
                    <label>Cycle</label>
                    <SearchableSelect
                        v-model="form.cycle_enseignement_id"
                        :options="cycles"
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
                        optionLabel="libelle"
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

        <!-- STEP 2 : TARIFICATION -->
        <template #tarification>
            <div class="row g-3">
                <div class="col-md-4">
                    <label>Prix unitaire (par repas) — en cts</label>
                    <input v-model.number="form.prix_cents" :disabled="isReadOnly" type="number" min="0" class="form-control" placeholder="0" />
                </div>
                <div class="col-md-8">
                    <label>Description</label>
                    <textarea v-model="form.description" :disabled="isReadOnly" class="form-control" rows="2" placeholder="Description"></textarea>
                </div>

                <hr class="mt-3" />
                <div class="col-12">
                    <h6 class="text-primary"><i class="fa fa-tags me-1"></i> Forfaits</h6>
                </div>
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

        <!-- STEP 3 : PÉRIODE & STATUT -->
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
                    <label>Statut <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.statut"
                        :options="statusOptions"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.statut" class="text-danger small">{{ form.errors.statut }}</span>
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
