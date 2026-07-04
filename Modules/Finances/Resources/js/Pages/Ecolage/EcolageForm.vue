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
    sections: {
        type: Array,
        default: () => [],
    },
    cycles: {
        type: Array,
        default: () => [],
    },
    postesRecettes: {
        type: Array,
        default: () => [],
    },
    comptes: {
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
const sectionsOptions = computed(() =>
    props.sections.map(s => ({ id: s.id, libelle: s.libelle }))
);
const cyclesOptions = computed(() =>
    props.cycles.map(c => ({ id: c.id, libelle: c.libelle }))
);
const postesOptions = computed(() =>
    props.postesRecettes.map(p => ({ id: p.id, libelle: p.code ? `${p.code} — ${p.libelle}` : p.libelle }))
);
const comptesOptions = computed(() =>
    props.comptes.map(c => ({ id: c.id, libelle: c.numero_compte ? `${c.numero_compte} — ${c.libelle_compte}` : c.libelle_compte }))
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

// ===== Lignes de frais (Poste / Compte comptable / Montant / Date limite) =====
const emptyLigne = () => ({
    poste_recette_id: null,
    plan_compte_id: null,
    libelle: '',
    montant: null,
    date_limite: '',
});

const addFrais = () => {
    if (!Array.isArray(props.form.frais)) props.form.frais = [];
    props.form.frais.push(emptyLigne());
};

const removeFrais = (index) => {
    props.form.frais.splice(index, 1);
};

const totalFrais = computed(() =>
    (props.form.frais || []).reduce((sum, l) => sum + (Number(l.montant) || 0), 0)
);

const formatMontant = (value) =>
    new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0 }).format(value || 0);

const fraisError = (index, field) => props.form.errors?.[`frais.${index}.${field}`];
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- ===== Bloc Informations de base ===== -->
        <div class="col-12">
            <h6 class="section-title">{{ t('common.basic_information') || 'Informations de base' }}</h6>
        </div>

        <!-- Année Scolaire -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.academic_year') || 'Année scolaire' }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
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
                <label>{{ t('fields.level') || 'Niveau' }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
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

        <!-- Ecole (auto) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.school') || 'École' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span> <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input type="text" class="form-control" :value="ecoleLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Campus (auto) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.campus') || 'Campus' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="campusLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Section -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.section') || 'Section' }}</label>
                <SearchableSelect
                    v-model="form.section_id"
                    :options="sectionsOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.section_id" class="text-danger">
                    <strong>{{ form.errors.section_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Cycle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.cycle') || 'Cycle' }}</label>
                <SearchableSelect
                    v-model="form.cycle_id"
                    :options="cyclesOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.cycle_id" class="text-danger">
                    <strong>{{ form.errors.cycle_id }}</strong>
                </span>
            </div>
        </div>

        <!-- ===== Bloc Frais et montants (lignes) ===== -->
        <div class="col-12 d-flex justify-content-between align-items-center mt-2">
            <h6 class="section-title mb-0">{{ t('fields.fees') || 'Frais et montants' }}</h6>
            <button v-if="!isReadOnly" type="button" class="btn btn-sm btn-primary" @click="addFrais">
                <i class="fa fa-plus"></i> {{ t('actions.add_line') || 'Ajouter une ligne' }}
            </button>
        </div>

        <div class="col-12">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 200px;">{{ t('fields.poste') || 'Poste' }}</th>
                            <th style="min-width: 200px;">{{ t('fields.accounting_account') || 'Compte comptable' }}</th>
                            <th style="min-width: 140px;">{{ t('fields.amount') || 'Montant' }}</th>
                            <th style="min-width: 160px;">{{ t('fields.payment_deadline') || 'Date limite' }}</th>
                            <th v-if="!isReadOnly" style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(ligne, index) in (form.frais || [])" :key="index">
                            <td>
                                <SearchableSelect
                                    v-model="ligne.poste_recette_id"
                                    :options="postesOptions"
                                    optionValue="id"
                                    optionLabel="libelle"
                                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                                    :disabled="isReadOnly"
                                />
                                <span v-if="fraisError(index, 'poste_recette_id')" class="text-danger small">
                                    {{ fraisError(index, 'poste_recette_id') }}
                                </span>
                            </td>
                            <td>
                                <SearchableSelect
                                    v-model="ligne.plan_compte_id"
                                    :options="comptesOptions"
                                    optionValue="id"
                                    optionLabel="libelle"
                                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                                    :disabled="isReadOnly"
                                />
                                <span v-if="fraisError(index, 'plan_compte_id')" class="text-danger small">
                                    {{ fraisError(index, 'plan_compte_id') }}
                                </span>
                            </td>
                            <td>
                                <input
                                    v-model.number="ligne.montant"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    :disabled="isReadOnly"
                                    placeholder="0"
                                />
                                <span v-if="fraisError(index, 'montant')" class="text-danger small">
                                    {{ fraisError(index, 'montant') }}
                                </span>
                            </td>
                            <td>
                                <input
                                    v-model="ligne.date_limite"
                                    type="date"
                                    class="form-control"
                                    :disabled="isReadOnly"
                                />
                                <span v-if="fraisError(index, 'date_limite')" class="text-danger small">
                                    {{ fraisError(index, 'date_limite') }}
                                </span>
                            </td>
                            <td v-if="!isReadOnly" class="text-center">
                                <button type="button" class="btn btn-sm btn-danger" @click="removeFrais(index)" title="Supprimer">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!(form.frais && form.frais.length)">
                            <td :colspan="isReadOnly ? 4 : 5" class="text-center text-muted py-3">
                                {{ t('common.no_data') || 'Aucune ligne de frais' }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot v-if="form.frais && form.frais.length">
                        <tr>
                            <th colspan="2" class="text-end">{{ t('fields.total') || 'Total' }}</th>
                            <th colspan="2">{{ formatMontant(totalFrais) }}</th>
                            <th v-if="!isReadOnly"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- ===== État ===== -->
        <div class="col-12">
            <h6 class="section-title">{{ t('common.settings') || 'Paramètres' }}</h6>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.status') || 'Statut' }}</label>
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
