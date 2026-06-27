<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import HierarchyContextBar from '@/Components/Common/HierarchyContextBar.vue';
import InheritedContextBar from '@/Components/Common/InheritedContextBar.vue';
import { useClasseCascade } from '@/Composables/useClasseCascade';
import { useApprenantCascade } from '@/Composables/useApprenantCascade';

const { t } = useI18n();

const props = defineProps({
    form: Object,
    matieres: Array,
    classes: Array,
    apprenants: Array,
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});

const isReadOnly = props.mode === 'show';

// Cascade auto via composables (instantané)
useClasseCascade(props.form, () => props.classes);
useApprenantCascade(props.form, () => props.apprenants);

const handleClasseChange = () => { /* composable gère tout */ };
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Section 1: Informations de base -->
        <div class="form-section">
            <h6 class="section-title">{{ t('common.basic_info') || 'Informations de base' }}</h6>
            <div class="col-sm-6">
                <label class="form-label">{{ t('common.matiere') || 'Matière' }}</label>
                <SearchableSelect
                    v-model="form.matiere_id"
                    :options="matieres"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || 'Sélectionner'"
                    class="form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.matiere_id" class="text-danger small mt-1">{{ form.errors.matiere_id }}</div>
            </div>
            <div class="col-sm-6">
                <label class="form-label">{{ t('common.classe') || 'Classe' }}</label>
                <SearchableSelect
                    v-model="form.classe_id"
                    @update:modelValue="handleClasseChange"
                    :options="classes"
                    optionValue="id"
                    optionLabel="nom"
                    :placeholder="t('common.select') || 'Sélectionner'"
                    class="form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.classe_id" class="text-danger small mt-1">{{ form.errors.classe_id }}</div>
            </div>
            <!-- Contexte hiérarchique hérité de la Classe -->
            <InheritedContextBar
                :source="classes?.find(c => String(c.id) === String(form.classe_id)) || null"
                title="Hérité de la classe"
            />
            <div class="col-sm-6">
                <label class="form-label">{{ t('common.apprenant') || 'Apprenant' }}</label>
                <SearchableSelect
                    v-model="form.apprenant_id"
                    :options="apprenants"
                    optionValue="id"
                    optionLabel="nom_restituer"
                    :placeholder="t('common.select') || 'Sélectionner'"
                    class="form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.apprenant_id" class="text-danger small mt-1">{{ form.errors.apprenant_id }}</div>
            </div>
        </div>

        <!-- Section 2: Dates -->
        <div class="form-section mt-3">
            <h6 class="section-title">{{ t('fields.dates') || 'Dates' }}</h6>
            <div class="col-sm-6">
                <label class="form-label">{{ t('fields.date_debut') || 'Date de début' }}</label>
                <input
                    v-model="form.date_debut"
                    type="date"
                    class="form-control form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.date_debut" class="text-danger small mt-1">{{ form.errors.date_debut }}</div>
            </div>
            <div class="col-sm-6">
                <label class="form-label">{{ t('fields.date_fin') || 'Date de fin' }}</label>
                <input
                    v-model="form.date_fin"
                    type="date"
                    class="form-control form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.date_fin" class="text-danger small mt-1">{{ form.errors.date_fin }}</div>
            </div>
        </div>

        <!-- Section 3: Paramètres d'examen -->
        <div class="form-section mt-3">
            <h6 class="section-title">{{ t('fields.exam_parameters') || 'Paramètres d\'examen' }}</h6>
            <div class="col-sm-4">
                <label class="form-label">{{ t('fields.note_maximale') || 'Note maximale' }}</label>
                <input
                    v-model="form.note_maximale"
                    type="number"
                    step="0.01"
                    class="form-control form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.note_maximale" class="text-danger small mt-1">{{ form.errors.note_maximale }}</div>
            </div>
            <div class="col-sm-4">
                <label class="form-label">{{ t('fields.nombre_questions') || 'Nombre de questions' }}</label>
                <input
                    v-model="form.nombre_questions"
                    type="number"
                    class="form-control form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.nombre_questions" class="text-danger small mt-1">{{ form.errors.nombre_questions }}</div>
            </div>
            <div class="col-sm-4">
                <label class="form-label">{{ t('fields.duree') || 'Durée (minutes)' }}</label>
                <input
                    v-model="form.duree"
                    type="number"
                    step="0.01"
                    class="form-control form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.duree" class="text-danger small mt-1">{{ form.errors.duree }}</div>
            </div>
        </div>

        <!-- Section 4: Résultats -->
        <div class="form-section mt-3">
            <h6 class="section-title">{{ t('fields.results') || 'Résultats' }}</h6>
            <div class="col-sm-4">
                <label class="form-label">{{ t('fields.points') || 'Points' }}</label>
                <input
                    v-model="form.points"
                    type="number"
                    step="0.01"
                    class="form-control form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.points" class="text-danger small mt-1">{{ form.errors.points }}</div>
            </div>
            <div class="col-sm-4">
                <label class="form-label">{{ t('fields.temps_effectue') || 'Temps effectué (minutes)' }}</label>
                <input
                    v-model="form.temps_effectue"
                    type="number"
                    step="0.01"
                    class="form-control form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.temps_effectue" class="text-danger small mt-1">{{ form.errors.temps_effectue }}</div>
            </div>
            <div class="col-sm-4">
                <label class="form-label">{{ t('fields.reponses_correctes') || 'Réponses correctes' }}</label>
                <input
                    v-model="form.reponses_correctes"
                    type="number"
                    class="form-control form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.reponses_correctes" class="text-danger small mt-1">{{ form.errors.reponses_correctes }}</div>
            </div>
            <div class="col-sm-4">
                <label class="form-label">{{ t('fields.reponses_fausses') || 'Réponses fausses' }}</label>
                <input
                    v-model="form.reponses_fausses"
                    type="number"
                    class="form-control form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.reponses_fausses" class="text-danger small mt-1">{{ form.errors.reponses_fausses }}</div>
            </div>
            <div class="col-sm-4">
                <label class="form-label">{{ t('fields.non_repondues') || 'Non répondues' }}</label>
                <input
                    v-model="form.non_repondues"
                    type="number"
                    class="form-control form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.non_repondues" class="text-danger small mt-1">{{ form.errors.non_repondues }}</div>
            </div>
            <div class="col-sm-4">
                <label class="form-label">{{ t('fields.reponses_douteuses') || 'Réponses douteuses' }}</label>
                <input
                    v-model="form.reponses_douteuses"
                    type="number"
                    class="form-control form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.reponses_douteuses" class="text-danger small mt-1">{{ form.errors.reponses_douteuses }}</div>
            </div>
        </div>

        <!-- Section 5: État -->
        <div class="form-section mt-3">
            <h6 class="section-title">{{ t('common.status') || 'Statut' }}</h6>
            <div class="col-sm-6">
                <label class="form-label">{{ t('common.etat') || 'État' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="[
                        { id: 'actif', libelle: 'Actif' },
                        { id: 'inactif', libelle: 'Inactif' }
                    ]"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || 'Sélectionner'"
                    class="form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.etat" class="text-danger small mt-1">{{ form.errors.etat }}</div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.form-section {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.form-section .col-sm-6,
.form-section .col-sm-4 {
    flex: 1;
    min-width: 200px;
}

.section-title {
    width: 100%;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #333;
}
</style>
