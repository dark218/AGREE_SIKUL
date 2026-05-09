<script setup>
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    ecoles: {
        type: Array,
        default: () => [],
    },
    niveaux: {
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
    enseignants: {
        type: Array,
        default: () => [],
    },
    anneesScolaires: {
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

const isReadOnly = props.mode === 'show';
const ecoleSelected = computed(() => !!props.form.ecole_id);

const autoLabel = (list, id) => {
    if (!id || !list?.length) return '\u2014';
    const found = list.find(item => String(item.id) === String(id));
    return found?.libelle || found?.nom || found?.label || '\u2014';
};

const campusLabel = computed(() => autoLabel(props.campuses, props.form.campus_id));

const statusOptions = [
    { id: 'actif', libelle: t('common.active') || 'Actif' },
    { id: 'non_actif', libelle: t('common.inactive') || 'Inactif' },
    { id: 'suspendu', libelle: t('common.suspended') || 'Suspendu' },
];

// ── CASCADE: École → Campus (auto-fill) + Niveaux (filtrage) ──

// Quand l'école change → auto-fill campus depuis l'école sélectionnée
watch(() => props.form.ecole_id, (newEcoleId) => {
    if (!newEcoleId || isReadOnly) return;
    const ecole = props.ecoles.find(e => String(e.id) === String(newEcoleId));
    if (ecole?.campus_id) {
        props.form.campus_id = ecole.campus_id;
    }
});

// Filtrer les niveaux par école sélectionnée
const filteredNiveaux = computed(() => {
    if (!props.form.ecole_id) return props.niveaux;
    return props.niveaux.filter(n =>
        !n.ecole_id || String(n.ecole_id) === String(props.form.ecole_id)
    );
});

// Quand le niveau sélectionné n'est plus dans la liste filtrée → reset
watch(filteredNiveaux, (newList) => {
    if (props.form.niveau_id && !newList.find(n => String(n.id) === String(props.form.niveau_id))) {
        props.form.niveau_id = null;
    }
});
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- SECTION 1: BASIC INFORMATION -->
        <div class="col-12">
            <h5 class="section-title">{{ t('fields.basic_info') || 'Informations de base' }}</h5>
        </div>

        <!-- Nom -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.nom') || 'Nom' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.nom"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.nom') || 'Nom de la classe'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.nom" class="text-danger">
                    <strong>{{ form.errors.nom }}</strong>
                </span>
            </div>
        </div>

        <!-- Code Salle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code_salle') || 'Code salle' }}</label>
                <input
                    v-model="form.code_salle"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.code_salle') || 'Code salle'"
                    :disabled="isReadOnly"
                    maxlength="100"
                />
                <span v-if="form.errors?.code_salle" class="text-danger">
                    <strong>{{ form.errors.code_salle }}</strong>
                </span>
            </div>
        </div>

        <!-- Libellé à afficher -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.libelle_affichage') || 'Libellé à afficher' }}</label>
                <input
                    v-model="form.libelle_affichage"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.libelle_affichage') || 'Libellé à afficher'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.libelle_affichage" class="text-danger">
                    <strong>{{ form.errors.libelle_affichage }}</strong>
                </span>
            </div>
        </div>

        <!-- Salle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.room') || 'Salle' }}</label>
                <input
                    v-model="form.salle"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.room') || 'Numéro/Nom de salle'"
                    :disabled="isReadOnly"
                    maxlength="100"
                />
                <span v-if="form.errors?.salle" class="text-danger">
                    <strong>{{ form.errors.salle }}</strong>
                </span>
            </div>
        </div>

        <!-- SECTION 2: ACADEMIC STRUCTURE (CASCADE) -->
        <div class="col-12">
            <h5 class="section-title">{{ t('fields.academic_structure') || 'Structure académique' }}</h5>
            <p class="text-muted small mb-2">
                <i class="bx bx-info-circle"></i>
                Sélectionnez l'école en premier — le campus et les niveaux seront filtrés automatiquement.
            </p>
        </div>

        <!-- 1. École (point d'entrée) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.school') || 'École' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.ecole_id"
                    :options="ecoles"
                    optionValue="id"
                    optionLabel="nom"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.ecole_id" class="text-danger">
                    <strong>{{ form.errors.ecole_id }}</strong>
                </span>
            </div>
        </div>

        <!-- 2. Campus (auto-rempli depuis l'école, disabled) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.campus') || 'Campus' }}
                    <span v-if="ecoleSelected" class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span>
                </label>
                <input type="text" class="form-control" :value="campusLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- 3. Niveau (filtré par école) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.niveau') || 'Niveau' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.niveau_id"
                    :options="filteredNiveaux"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="ecoleSelected ? (t('actions.select') || '-- Sélectionner --') : 'Sélectionnez d\'abord une école'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.niveau_id" class="text-danger">
                    <strong>{{ form.errors.niveau_id }}</strong>
                </span>
            </div>
        </div>

        <!-- 4. Section -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.section') || 'Section' }}</label>
                <SearchableSelect
                    v-model="form.section_id"
                    :options="sections"
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

        <!-- 5. Cycle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.cycle') || 'Cycle' }}</label>
                <SearchableSelect
                    v-model="form.cycle_id"
                    :options="cycles"
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

        <!-- 6. Année Scolaire -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.annee_scolaire') || 'Année scolaire courante' }}</label>
                <SearchableSelect
                    v-model="form.annee_scolaire_id"
                    :options="anneesScolaires"
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

        <!-- SECTION 3: INSTRUCTOR & CAPACITY -->
        <div class="col-12">
            <h5 class="section-title">{{ t('fields.instructor_capacity') || 'Enseignant et capacité' }}</h5>
        </div>

        <!-- Enseignant Titulaire -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.enseignant_titulaire') || 'Enseignant titulaire' }}</label>
                <SearchableSelect
                    v-model="form.enseignant_titulaire_id"
                    :options="enseignants"
                    optionValue="id"
                    :optionLabel="(opt) => `${opt.nom} ${opt.prenoms || ''}`"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.enseignant_titulaire_id" class="text-danger">
                    <strong>{{ form.errors.enseignant_titulaire_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Capacité -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.capacity') || 'Capacité maximale' }}</label>
                <input
                    v-model.number="form.capacite_max"
                    type="number"
                    class="form-control"
                    :placeholder="t('fields.capacity') || 'Nombre d\'apprenants max'"
                    :disabled="isReadOnly"
                    min="1"
                />
                <span v-if="form.errors?.capacite_max" class="text-danger">
                    <strong>{{ form.errors.capacite_max }}</strong>
                </span>
            </div>
        </div>

        <!-- SECTION 4: STATUS -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.statut') || 'Statut' }}</label>
                <SearchableSelect
                    v-model="form.statut"
                    :options="statusOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.statut" class="text-danger">
                    <strong>{{ form.errors.statut }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.section-title {
    font-weight: 600;
    margin-top: 1rem;
    margin-bottom: 1rem;
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 0.5rem;
}
</style>
