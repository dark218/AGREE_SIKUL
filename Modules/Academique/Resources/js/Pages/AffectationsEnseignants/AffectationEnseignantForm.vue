<script setup>
import { defineProps, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import HierarchyContextBar from '@/Components/Common/HierarchyContextBar.vue';
import InheritedContextBar from '@/Components/Common/InheritedContextBar.vue';
import { useClasseCascade } from '@/Composables/useClasseCascade';

const { t } = useI18n();

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    enseignants: {
        type: Array,
        default: () => [],
    },
    anneesScolaires: {
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
    institutions: {
        type: Array,
        default: () => [],
    },
    campuses: {
        type: Array,
        default: () => [],
    },
    matieres: {
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
const classeSelected = computed(() => !!props.form.classe_id);

const autoLabel = (list, id) => {
    if (!id || !list?.length) return '—';
    const found = list.find(item => String(item.id) === String(id));
    return found?.libelle || found?.nom || found?.label || '—';
};
const ecoleLabel = computed(() => autoLabel(props.ecoles, props.form.ecole_id));
const campusLabel = computed(() => autoLabel(props.campuses, props.form.campus_id));

// Cascade auto via composable
useClasseCascade(props.form, () => props.classes);
const handleClasseChange = () => { /* composable gère tout */ };

const etatOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Section 1: Informations Générales -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-0">{{ t('common.basic_information') || 'Informations générales' }}</h5>
        </div>

        <!-- Année Scolaire -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.annee_scolaire') || 'Année scolaire' }} <span class="text-danger">*</span></label>
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

        <!-- Enseignant -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.enseignant') || 'Enseignant' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.enseignant_id"
                    :options="enseignants"
                    :disabled="isReadOnly"
                    option-value="id"
                    :option-label="(opt) => `${opt.nom} ${opt.prenoms}`"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.enseignant_id" class="text-danger">
                    <strong>{{ form.errors.enseignant_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Classe -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.classe') || 'Classe' }}</label>
                <SearchableSelect
                    v-model="form.classe_id"
                    @update:modelValue="handleClasseChange"
                    :options="classes"
                    :disabled="isReadOnly"
                    option-value="id"
                    option-label="nom"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.classe_id" class="text-danger">
                    <strong>{{ form.errors.classe_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Contexte hiérarchique (affiché quand classe sélectionnée) -->
        <InheritedContextBar
            :source="classes?.find(c => String(c.id) === String(form.classe_id)) || null"
            title="Hérité de la classe"
        />
        <HierarchyContextBar v-if="false"
            :form="form"
            :ecoles="ecoles"
            :campuses="campuses"
        />

        <!-- Ecole -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.ecole') || 'Ecole' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="ecoleLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Institution -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.institution') || 'Institution' }}</label>
                <SearchableSelect
                    v-model="form.institution_id"
                    :options="institutions"
                    :disabled="isReadOnly"
                    option-value="id"
                    option-label="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.institution_id" class="text-danger">
                    <strong>{{ form.errors.institution_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Campus -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.campus') || 'Campus' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="campusLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Section 2: Matières Affectées -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.assigned_subjects') || 'Matières affectées' }}</h5>
        </div>

        <!-- Matière 1-7 -->
        <div v-for="i in 7" :key="`matiere-${i}`" class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.matiere') || 'Matière' }} {{ i }}</label>
                <SearchableSelect
                    v-model="form[`matiere_${i}_id`]"
                    :options="matieres"
                    :disabled="isReadOnly"
                    option-value="id"
                    option-label="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.[`matiere_${i}_id`]" class="text-danger">
                    <strong>{{ form.errors[`matiere_${i}_id`] }}</strong>
                </span>
            </div>
        </div>

        <!-- Matière 8-14 -->
        <div v-for="i in [8,9,10,11,12,13,14]" :key="`matiere-${i}`" class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.matiere') || 'Matière' }} {{ i }}</label>
                <SearchableSelect
                    v-model="form[`matiere_${i}_id`]"
                    :options="matieres"
                    :disabled="isReadOnly"
                    option-value="id"
                    option-label="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.[`matiere_${i}_id`]" class="text-danger">
                    <strong>{{ form.errors[`matiere_${i}_id`] }}</strong>
                </span>
            </div>
        </div>

        <!-- Matière 15-21 -->
        <div v-for="i in [15,16,17,18,19,20,21]" :key="`matiere-${i}`" class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.matiere') || 'Matière' }} {{ i }}</label>
                <SearchableSelect
                    v-model="form[`matiere_${i}_id`]"
                    :options="matieres"
                    :disabled="isReadOnly"
                    option-value="id"
                    option-label="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.[`matiere_${i}_id`]" class="text-danger">
                    <strong>{{ form.errors[`matiere_${i}_id`] }}</strong>
                </span>
            </div>
        </div>

        <!-- Section 3: État -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.status') || 'État' }}</h5>
        </div>

        <!-- État d'activation -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.status') || 'État d\'activation' }} <span class="text-danger">*</span></label>
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

<style scoped>
.section-title {
    font-weight: 600;
    color: #333;
    font-size: 1.1rem;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 0.5rem;
}
</style>
