<script setup>
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import HierarchyContextBar from '@/Components/Common/HierarchyContextBar.vue';

const { t } = useI18n();

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    anneesScolaires: {
        type: Array,
        default: () => [],
    },
    niveaux: {
        type: Array,
        default: () => [],
    },
    ecoles: {
        type: Array,
        default: () => [],
    },
    campuses: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});

const isReadOnly = computed(() => props.mode === 'show');
const niveauSelected = computed(() => !!props.form.niveau_id);

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const anneesOptions = computed(() =>
    props.anneesScolaires.map(a => ({ id: a.id, libelle: a.libelle }))
);

const niveauxOptions = computed(() =>
    props.niveaux.map(n => ({ id: n.id, libelle: n.nom || n.libelle }))
);

const ecolesOptions = computed(() =>
    props.ecoles.map(e => ({ id: e.id, libelle: e.nom }))
);

const campusesOptions = computed(() =>
    props.campuses.map(c => ({ id: c.id, libelle: c.nom }))
);

// Auto-fill ecole_id from selected niveau
watch(() => props.form.niveau_id, (niveauId) => {
    if (!niveauId) return;
    const niv = props.niveaux?.find(n => n.id == niveauId);
    if (niv?.ecole_id) props.form.ecole_id = niv.ecole_id;
});

const autoLabel = (list, id) => {
    if (!id || !list?.length) return '—';
    const found = list.find(item => String(item.id) === String(id));
    return found?.libelle || found?.nom || found?.label || '—';
};

const ecoleLabel = computed(() => autoLabel(ecolesOptions.value, props.form.ecole_id));
const campusLabel = computed(() => autoLabel(campusesOptions.value, props.form.campus_id));
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Section: Informations Générales -->
        <div class="col-12">
            <h6 class="section-title">{{ t('common.basic_information') }}</h6>
        </div>

        <!-- Année Scolaire -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.academic_year') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.annee_scolaire_id"
                    :options="anneesOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.annee_scolaire_id" class="text-danger">
                    <strong>{{ form.errors.annee_scolaire_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Niveau -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.level') }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.niveau_id"
                    :options="niveauxOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.niveau_id" class="text-danger">
                    <strong>{{ form.errors.niveau_id }}</strong>
                </span>
            </div>
        </div>

        <!-- HierarchyContextBar: shown when niveau is selected -->
        <HierarchyContextBar
            v-if="niveauSelected"
            :form="form"
            :ecoles="ecoles"
            :campuses="campuses"
            :niveaux="niveaux"
        />

        <!-- Ecole (hidden when niveau auto-fills it) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.school') }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span> <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" class="form-control" :value="ecoleLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Campus (hidden when niveau auto-fills it) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.campus') }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="campusLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Section: Frais -->
        <div class="col-12">
            <h6 class="section-title">{{ t('fields.fees') }}</h6>
        </div>

        <!-- Frais de Dossier -->
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.file_fees') }}</label>
                <input
                    type="number"
                    v-model="form.frais_dossier"
                    step="0.01"
                    min="0"
                    class="form-control"
                    :placeholder="t('fields.file_fees')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.frais_dossier" class="text-danger">
                    <strong>{{ form.errors.frais_dossier }}</strong>
                </span>
            </div>
        </div>

        <!-- Frais d'Inscription -->
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.registration_fees') }}</label>
                <input
                    type="number"
                    v-model="form.frais_inscription"
                    step="0.01"
                    min="0"
                    class="form-control"
                    :placeholder="t('fields.registration_fees')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.frais_inscription" class="text-danger">
                    <strong>{{ form.errors.frais_inscription }}</strong>
                </span>
            </div>
        </div>

        <!-- Frais de Scolarité -->
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.tuition_fees') }}</label>
                <input
                    type="number"
                    v-model="form.frais_scolarite"
                    step="0.01"
                    min="0"
                    class="form-control"
                    :placeholder="t('fields.tuition_fees')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.frais_scolarite" class="text-danger">
                    <strong>{{ form.errors.frais_scolarite }}</strong>
                </span>
            </div>
        </div>

        <!-- Section: État -->
        <div class="col-12">
            <h6 class="section-title">{{ t('common.settings') }}</h6>
        </div>

        <!-- Status -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.status') }}</label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="statusOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.etat" class="text-danger">
                    <strong>{{ form.errors.etat }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.section-title {
    font-weight: 600;
    color: #333;
    margin-top: 15px;
    margin-bottom: 10px;
    padding-bottom: 5px;
    border-bottom: 2px solid #f0f0f0;
}
</style>
