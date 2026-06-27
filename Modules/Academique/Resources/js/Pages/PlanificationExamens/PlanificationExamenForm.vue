<script setup>
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import HierarchyContextBar from '@/Components/Common/HierarchyContextBar.vue';
import InheritedContextBar from '@/Components/Common/InheritedContextBar.vue';
import { useClasseCascade } from '@/Composables/useClasseCascade';

const { t } = useI18n();

const props = defineProps({
    form: Object,
    natures: Array,
    types: Array,
    classes: Array,
    matieres: Array,
    enseignants: Array,
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});

const isReadOnly = props.mode === 'show';

// Cascade auto via composable (instantané)
useClasseCascade(props.form, () => props.classes);
const handleClasseChange = () => { /* composable gère tout */ };

// Calcul automatique de la durée
const calculateDuration = () => {
    if (props.form.heure_debut && props.form.heure_fin) {
        const [heureDebut, minuteDebut] = props.form.heure_debut.split(':').map(Number);
        const [heureFin, minuteFin] = props.form.heure_fin.split(':').map(Number);

        const startMinutes = heureDebut * 60 + minuteDebut;
        const endMinutes = heureFin * 60 + minuteFin;

        if (endMinutes > startMinutes) {
            const durationMinutes = endMinutes - startMinutes;
            props.form.duree = Math.round((durationMinutes / 60) * 10) / 10; // Arrondir à 0.1 près
        }
    }
};

// Watch both time inputs for changes
watch(
    () => props.form.heure_debut,
    () => calculateDuration(),
    { immediate: false }
);

watch(
    () => props.form.heure_fin,
    () => calculateDuration(),
    { immediate: false }
);
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Section 1: Informations de base -->
        <div class="form-section">
            <h6 class="section-title">{{ t('common.basic_info') || 'Informations de base' }}</h6>
            <div class="col-sm-6">
                <label class="form-label">{{ t('common.nature') || 'Nature' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.nature_examen_id"
                    :options="natures"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || 'Sélectionner'"
                    class="form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.nature_examen_id" class="text-danger small mt-1">{{ form.errors.nature_examen_id }}</div>
            </div>
            <div class="col-sm-6">
                <label class="form-label">{{ t('fields.type_examen') || 'Type d\'examen' }}</label>
                <SearchableSelect
                    v-model="form.type_examen_id"
                    :options="types"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || 'Sélectionner'"
                    class="form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.type_examen_id" class="text-danger small mt-1">{{ form.errors.type_examen_id }}</div>
            </div>
            <div class="col-sm-6">
                <label class="form-label">{{ t('common.classe') || 'Classe' }} <span class="text-danger">*</span></label>
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
            <!-- Contexte hiérarchique (affiché quand classe sélectionnée) -->
            <InheritedContextBar
                :source="classes?.find(c => String(c.id) === String(form.classe_id)) || null"
                title="Hérité de la classe"
            />
            <HierarchyContextBar v-if="false"
                :form="form"
            />
            <div class="col-sm-6">
                <label class="form-label">{{ t('common.matiere') || 'Matière' }} <span class="text-danger">*</span></label>
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
                <label class="form-label">{{ t('common.enseignant') || 'Enseignant' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.enseignant_id"
                    :options="enseignants"
                    optionValue="id"
                    :optionLabel="(option) => option.prenoms + ' ' + option.nom"
                    :placeholder="t('common.select') || 'Sélectionner'"
                    class="form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.enseignant_id" class="text-danger small mt-1">{{ form.errors.enseignant_id }}</div>
            </div>
        </div>

        <!-- Section 2: Dates et heures -->
        <div class="form-section mt-3">
            <h6 class="section-title">{{ t('fields.schedule') || 'Horaires' }}</h6>
            <div class="col-sm-6">
                <label class="form-label">{{ t('common.jour') || 'Jour' }}</label>
                <input
                    v-model="form.jour"
                    type="text"
                    class="form-control form-control-sm"
                    :placeholder="t('fields.jour') || 'Jour'"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.jour" class="text-danger small mt-1">{{ form.errors.jour }}</div>
            </div>
            <div class="col-sm-6">
                <label class="form-label">{{ t('common.date') || 'Date' }}</label>
                <input
                    v-model="form.date"
                    type="date"
                    class="form-control form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.date" class="text-danger small mt-1">{{ form.errors.date }}</div>
            </div>
            <div class="col-sm-6">
                <label class="form-label">{{ t('fields.heure_debut') || 'Heure de début' }}</label>
                <input
                    v-model="form.heure_debut"
                    type="time"
                    class="form-control form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.heure_debut" class="text-danger small mt-1">{{ form.errors.heure_debut }}</div>
            </div>
            <div class="col-sm-6">
                <label class="form-label">{{ t('fields.heure_fin') || 'Heure de fin' }}</label>
                <input
                    v-model="form.heure_fin"
                    type="time"
                    class="form-control form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.heure_fin" class="text-danger small mt-1">{{ form.errors.heure_fin }}</div>
            </div>
            <div class="col-sm-6">
                <label class="form-label">{{ t('fields.duree') || 'Durée (heures)' }}</label>
                <input
                    v-model="form.duree"
                    type="number"
                    step="0.5"
                    class="form-control form-control-sm"
                    :placeholder="t('fields.duree') || 'Durée'"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.duree" class="text-danger small mt-1">{{ form.errors.duree }}</div>
            </div>
        </div>

        <!-- Section 3: État -->
        <div class="form-section mt-3">
            <h6 class="section-title">{{ t('common.status') || 'Statut' }}</h6>
            <div class="col-sm-6">
                <label class="form-label">{{ t('common.statut') || 'Statut' }} <span class="text-danger">*</span></label>
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
