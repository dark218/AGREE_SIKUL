<script setup>
import { useI18n } from 'vue-i18n';
import { onMounted } from 'vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import HierarchyContextBar from '@/Components/Common/HierarchyContextBar.vue';
const { t } = useI18n();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
    matieres: Array,
    classes: Array,
    enseignants: Array,
});
const isReadOnly = props.mode === 'show';

// Handle classe selection to auto-fill dependent fields
const handleClasseChange = async (newClasseId) => {
    if (!newClasseId) return;

    try {
        console.log('[Auto-fill] Fetching classe data for ID:', newClasseId);
        const response = await fetch(`/api/classes/${newClasseId}`);
        if (!response.ok) {
            console.error('[Auto-fill] API error:', response.status);
            return;
        }
        const data = await response.json();
        console.log('[Auto-fill] Data received:', data);

        // Auto-fill dependent fields
        props.form.ecole_id = data.ecole_id || null;
        props.form.campus_id = data.campus_id || null;
        props.form.section_id = data.section_id || null;
        props.form.cycle_id = data.cycle_id || null;
        props.form.annee_scolaire_id = data.annee_scolaire_id || null;

        console.log('[Auto-fill] Form updated:', {
            ecole_id: props.form.ecole_id,
            campus_id: props.form.campus_id,
            section_id: props.form.section_id,
            cycle_id: props.form.cycle_id,
            annee_scolaire_id: props.form.annee_scolaire_id
        });
    } catch (error) {
        console.error('[Auto-fill] Error:', error);
    }
};

onMounted(() => {
    console.log('🔍 CoursForm.vue mounted');
    console.log('📚 Matieres reçues:', props.matieres?.length, props.matieres);
    console.log('🏫 Classes reçues:', props.classes?.length, props.classes);
    console.log('👨‍🏫 Enseignants reçus:', props.enseignants?.length, props.enseignants);
    console.log('✅ Form state:', props.form);
});
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Code -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code') || 'Code' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.code" class="form-control" :placeholder="t('fields.code') || 'Code'" :disabled="isReadOnly">
                <span v-if="form.errors?.code" class="text-danger">
                    <strong>{{ form.errors.code }}</strong>
                </span>
            </div>
        </div>
        <!-- Titre -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.titre') || 'Titre' }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.titre" class="form-control" :placeholder="t('fields.titre') || 'Titre'" :disabled="isReadOnly">
                <span v-if="form.errors?.titre" class="text-danger">
                    <strong>{{ form.errors.titre }}</strong>
                </span>
            </div>
        </div>
        <!-- Description -->
        <div class="col-sm-12">
            <div class="mb-3">
                <label>{{ t('fields.description') || 'Description' }}</label>
                <textarea v-model="form.description" class="form-control" :placeholder="t('fields.description') || 'Description'" :disabled="isReadOnly" rows="3"></textarea>
                <span v-if="form.errors?.description" class="text-danger">
                    <strong>{{ form.errors.description }}</strong>
                </span>
            </div>
        </div>
        <!-- Matière -->
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('common.matiere') || 'Matière' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.matiere_id"
                    :options="matieres"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.matiere_id" class="text-danger">
                    <strong>{{ form.errors.matiere_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Classe -->
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('common.classe') || 'Classe' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.classe_id"
                    @update:modelValue="handleClasseChange"
                    :options="classes"
                    optionValue="id"
                    optionLabel="nom"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.classe_id" class="text-danger">
                    <strong>{{ form.errors.classe_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Contexte hiérarchique (auto-rempli par la classe) -->
        <HierarchyContextBar :form="form" />

        <!-- Enseignant -->
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('common.enseignant') || 'Enseignant' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.enseignant_id"
                    :options="enseignants"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.enseignant_id" class="text-danger">
                    <strong>{{ form.errors.enseignant_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Date Début -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_debut') || 'Date Début' }} <span class="text-danger">*</span></label>
                <div v-if="isReadOnly" class="form-control" disabled>
                    {{ form.date_debut ? new Date(form.date_debut).toLocaleString('fr-FR') : 'N/A' }}
                </div>
                <input v-else type="datetime-local" v-model="form.date_debut" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_debut" class="text-danger">
                    <strong>{{ form.errors.date_debut }}</strong>
                </span>
            </div>
        </div>
        <!-- Date Fin -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_fin') || 'Date Fin' }} <span class="text-danger">*</span></label>
                <div v-if="isReadOnly" class="form-control" disabled>
                    {{ form.date_fin ? new Date(form.date_fin).toLocaleString('fr-FR') : 'N/A' }}
                </div>
                <input v-else type="datetime-local" v-model="form.date_fin" class="form-control" :disabled="isReadOnly">
                <span v-if="form.errors?.date_fin" class="text-danger">
                    <strong>{{ form.errors.date_fin }}</strong>
                </span>
            </div>
        </div>
        <!-- Statut -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.status') || 'Statut' }}</label>
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
