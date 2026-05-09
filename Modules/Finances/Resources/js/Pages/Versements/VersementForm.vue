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
    apprenants: {
        type: Array,
        default: () => [],
    },
    anneesScolaires: {
        type: Array,
        default: () => [],
    },
    niveaux: {
        type: Array,
        default: () => [],
    },
    classes: {
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
const classeSelected = computed(() => !!props.form.classe_id);

// Auto-fill hierarchy from selected classe
watch(() => props.form.classe_id, (classeId) => {
    if (!classeId) return;
    const cls = props.classes?.find(c => c.id == classeId);
    if (cls) {
        if (cls.ecole_id) props.form.ecole_id = cls.ecole_id;
        if (cls.campus_id) props.form.campus_id = cls.campus_id;
        if (cls.niveau_id) props.form.niveau_id = cls.niveau_id;
        if (cls.annee_scolaire_id) props.form.annee_scolaire_id = cls.annee_scolaire_id;
    }
});

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const anneesOptions = computed(() =>
    props.anneesScolaires.map(a => ({ id: a.id, libelle: a.libelle }))
);

const apprenantsOptions = computed(() =>
    props.apprenants.map(a => ({ id: a.id, libelle: `${a.nom} ${a.prenoms}` }))
);

const niveauxOptions = computed(() =>
    props.niveaux.map(n => ({ id: n.id, libelle: n.nom || n.libelle }))
);

const classesOptions = computed(() =>
    props.classes.map(c => ({ id: c.id, libelle: c.nom }))
);

const ecolesOptions = computed(() =>
    props.ecoles.map(e => ({ id: e.id, libelle: e.nom }))
);

const campusesOptions = computed(() =>
    props.campuses.map(c => ({ id: c.id, libelle: c.nom }))
);

const selectedApprenant = computed(() => {
    if (!props.form.apprenant_id) return null;
    return props.apprenants.find(a => a.id === props.form.apprenant_id) ?? null;
});

const autoLabel = (list, id) => {
    if (!id || !list?.length) return '—';
    const found = list.find(item => String(item.id) === String(id));
    return found?.libelle || found?.nom || found?.label || '—';
};

const niveauLabel = computed(() => autoLabel(niveauxOptions.value, props.form.niveau_id));
const ecoleLabel = computed(() => autoLabel(ecolesOptions.value, props.form.ecole_id));
const campusLabel = computed(() => autoLabel(campusesOptions.value, props.form.campus_id));
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Section: Scolarité -->
        <div class="col-12">
            <h6 class="section-title">{{ t('common.scolarite') || 'Scolarité' }}</h6>
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

        <!-- Apprenant -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.apprenant') || 'Apprenant' }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.apprenant_id"
                    :options="apprenantsOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.apprenant_id" class="text-danger">
                    <strong>{{ form.errors.apprenant_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Classe -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.class') || 'Classe' }}</label>
                <SearchableSelect
                    v-model="form.classe_id"
                    :options="classesOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                    clearable
                />
                <span v-if="form.errors?.classe_id" class="text-danger">
                    <strong>{{ form.errors.classe_id }}</strong>
                </span>
            </div>
        </div>

        <!-- HierarchyContextBar: shown when classe is selected -->
        <HierarchyContextBar
            v-if="classeSelected"
            :form="form"
            :ecoles="ecoles"
            :campuses="campuses"
            :niveaux="niveaux"
        />

        <!-- Niveau (hidden when classe auto-fills it) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.level') }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="niveauLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- École (hidden when classe auto-fills it) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.school') }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="ecoleLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Campus (hidden when classe auto-fills it) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.campus') }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="campusLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Section: Informations Apprenant -->
        <div class="col-12">
            <h6 class="section-title">{{ t('common.apprenant_info') || 'Informations Apprenant' }}</h6>
        </div>

        <!-- Nom -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.name') || 'Nom' }}</label>
                <input
                    type="text"
                    :value="selectedApprenant?.nom || (isReadOnly && form.apprenant?.nom) || ''"
                    class="form-control"
                    disabled
                />
            </div>
        </div>

        <!-- Prénom(s) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.prenoms') || 'Prénom(s)' }}</label>
                <input
                    type="text"
                    :value="selectedApprenant?.prenoms || (isReadOnly && form.apprenant?.prenoms) || ''"
                    class="form-control"
                    disabled
                />
            </div>
        </div>

        <!-- Nom à restituer -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.nom_restituer') || 'Nom à restituer' }}</label>
                <input
                    type="text"
                    :value="selectedApprenant?.nom_restituer || (isReadOnly && form.apprenant?.nom_restituer) || ''"
                    class="form-control"
                    disabled
                />
            </div>
        </div>

        <!-- Sexe -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.sexe') || 'Sexe' }}</label>
                <input
                    type="text"
                    :value="selectedApprenant?.sexe || (isReadOnly && form.apprenant?.sexe) || ''"
                    class="form-control"
                    disabled
                />
            </div>
        </div>

        <!-- Date de naissance -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_naissance') || 'Date de naissance' }}</label>
                <input
                    type="date"
                    :value="selectedApprenant?.date_naissance || (isReadOnly && form.apprenant?.date_naissance) || ''"
                    class="form-control"
                    disabled
                />
            </div>
        </div>

        <!-- Section: Frais -->
        <div class="col-12">
            <h6 class="section-title">{{ t('fields.fees') || 'Frais' }}</h6>
        </div>

        <!-- Frais de Dossier -->
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.file_fees') || 'Frais de dossier' }}</label>
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
                <label>{{ t('fields.registration_fees') || 'Frais d\'inscription' }}</label>
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
                <label>{{ t('fields.tuition_fees') || 'Frais de scolarité' }}</label>
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

        <!-- Total Payé -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.total_paye') || 'Total payé' }}</label>
                <input
                    type="number"
                    v-model="form.total_paye"
                    step="0.01"
                    min="0"
                    class="form-control"
                    :placeholder="t('fields.total_paye')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.total_paye" class="text-danger">
                    <strong>{{ form.errors.total_paye }}</strong>
                </span>
            </div>
        </div>

        <!-- Restant à payer -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.restant_a_payer') || 'Restant à payer' }}</label>
                <input
                    type="number"
                    v-model="form.restant_a_payer"
                    step="0.01"
                    min="0"
                    class="form-control"
                    :placeholder="t('fields.restant_a_payer')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.restant_a_payer" class="text-danger">
                    <strong>{{ form.errors.restant_a_payer }}</strong>
                </span>
            </div>
        </div>

        <!-- Section: Versements -->
        <div class="col-12">
            <h6 class="section-title">{{ t('common.versements') || 'Versements' }}</h6>
        </div>

        <!-- Versement 1-12 -->
        <template v-for="i in 12" :key="i">
            <!-- Nature Versement -->
            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('fields.nature_versement') || 'Nature' }} {{ i }}</label>
                    <input
                        type="text"
                        :value="form[`nature_versement_${i}`]"
                        @input="form[`nature_versement_${i}`] = $event.target.value"
                        class="form-control"
                        :placeholder="`Nature versement ${i}`"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.[`nature_versement_${i}`]" class="text-danger">
                        <strong>{{ form.errors[`nature_versement_${i}`] }}</strong>
                    </span>
                </div>
            </div>

            <!-- Montant Versement -->
            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('fields.montant_versement') || 'Montant' }} {{ i }}</label>
                    <input
                        type="number"
                        :value="form[`montant_versement_${i}`]"
                        @input="form[`montant_versement_${i}`] = $event.target.value"
                        step="0.01"
                        min="0"
                        class="form-control"
                        :placeholder="`Montant versement ${i}`"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.[`montant_versement_${i}`]" class="text-danger">
                        <strong>{{ form.errors[`montant_versement_${i}`] }}</strong>
                    </span>
                </div>
            </div>
        </template>

        <!-- Section: État -->
        <div class="col-12">
            <h6 class="section-title">{{ t('common.settings') || 'Paramètres' }}</h6>
        </div>

        <!-- Status -->
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
