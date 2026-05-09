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
    sections: {
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
    cycles: {
        type: Array,
        default: () => [],
    },
    niveaux: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});

const isReadOnly = props.mode === 'show';
const niveauSelected = computed(() => !!props.form.niveau_id);

const autoLabel = (list, id) => {
    if (!id || !list?.length) return '—';
    const found = list.find(item => String(item.id) === String(id));
    return found?.libelle || found?.nom || found?.label || '—';
};
const sectionLabel = computed(() => autoLabel(props.sections, props.form.section_id));
const cycleLabel = computed(() => autoLabel(props.cycles, props.form.cycle_id));
const ecoleLabel = computed(() => autoLabel(props.ecoles, props.form.ecole_id));
const campusLabel = computed(() => autoLabel(props.campuses, props.form.campus_id));

const etatOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

// Auto-fill ecole_id from selected niveau
watch(() => props.form.niveau_id, (niveauId) => {
    if (!niveauId) return;
    const niv = props.niveaux?.find(n => n.id == niveauId);
    if (niv?.ecole_id) props.form.ecole_id = niv.ecole_id;
});
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Section 1: Informations Générales -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-0">{{ t('common.basic_information') || 'Informations Générales' }}</h5>
        </div>

        <!-- Année Scolaire -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.annee_scolaire') || 'Année Scolaire' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.annee_scolaire_id"
                    :options="anneesScolaires"
                    :disabled="isReadOnly"
                    option-value="id"
                    option-label="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.annee_scolaire_id" class="text-danger">
                    <strong>{{ form.errors.annee_scolaire_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Section (masqué — déduit du niveau) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.section') || 'Section' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="sectionLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- École (hidden when niveau auto-fills it) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.ecole') || 'École' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" :value="ecoleLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Campus (hidden when niveau auto-fills it) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.campus') || 'Campus' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="campusLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Cycle (masqué — déduit du niveau) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.cycle') || 'Cycle' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="cycleLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Niveau -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.niveau') || 'Niveau' }}</label>
                <SearchableSelect
                    v-model="form.niveau_id"
                    :options="niveaux"
                    :disabled="isReadOnly"
                    option-value="id"
                    option-label="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
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
            :sections="sections"
            :cycles="cycles"
            :niveaux="niveaux"
        />

        <!-- Code -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code') || 'Code' }}</label>
                <input type="text" v-model="form.code" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.code" class="text-danger">
                    <strong>{{ form.errors.code }}</strong>
                </span>
            </div>
        </div>

        <!-- Libellé -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.libelle') || 'Libellé' }}</label>
                <input type="text" v-model="form.libelle" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.libelle" class="text-danger">
                    <strong>{{ form.errors.libelle }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 2: Facturation -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.facturation') || 'Facturation' }}</h5>
        </div>

        <!-- Ligne de Recette -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.ligne_recette') || 'Ligne de Recette' }}</label>
                <input type="text" v-model="form.ligne_recette" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.ligne_recette" class="text-danger">
                    <strong>{{ form.errors.ligne_recette }}</strong>
                </span>
            </div>
        </div>

        <!-- Unité de Facturation -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.unite_facturation') || 'Unité de Facturation' }}</label>
                <input type="text" v-model="form.unite_facturation" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.unite_facturation" class="text-danger">
                    <strong>{{ form.errors.unite_facturation }}</strong>
                </span>
            </div>
        </div>

        <!-- Quantité -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.quantite') || 'Quantité' }}</label>
                <input type="number" v-model.number="form.quantite" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.quantite" class="text-danger">
                    <strong>{{ form.errors.quantite }}</strong>
                </span>
            </div>
        </div>

        <!-- Montant -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.montant') || 'Montant' }}</label>
                <input type="number" v-model.number="form.montant" class="form-control" step="0.01" :disabled="isReadOnly">
                <span v-if="form.errors?.montant" class="text-danger">
                    <strong>{{ form.errors.montant }}</strong>
                </span>
            </div>
        </div>

        <!-- Date Début Exigibilité -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_debut_exigibilite') || 'Date Début Exigibilité' }}</label>
                <input type="date" v-model="form.date_debut_exigibilite" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_debut_exigibilite" class="text-danger">
                    <strong>{{ form.errors.date_debut_exigibilite }}</strong>
                </span>
            </div>
        </div>

        <!-- Date Fin Exigibilité -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_fin_exigibilite') || 'Date Fin Exigibilité' }}</label>
                <input type="date" v-model="form.date_fin_exigibilite" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_fin_exigibilite" class="text-danger">
                    <strong>{{ form.errors.date_fin_exigibilite }}</strong>
                </span>
            </div>
        </div>

        <!-- Compte Comptable -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.compte_comptable') || 'Compte Comptable' }}</label>
                <input type="text" v-model="form.compte_comptable" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.compte_comptable" class="text-danger">
                    <strong>{{ form.errors.compte_comptable }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 3: État -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.state') || 'État' }}</h5>
        </div>

        <!-- État -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.etat') || 'État' }}</label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="etatOptions"
                    :disabled="isReadOnly"
                    option-value="id"
                    option-label="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.etat" class="text-danger">
                    <strong>{{ form.errors.etat }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>
