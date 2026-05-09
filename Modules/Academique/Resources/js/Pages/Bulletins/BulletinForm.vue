<script setup>
import { watch } from 'vue';
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
    classes: {
        type: Array,
        default: () => [],
    },
    anneesScolaires: {
        type: Array,
        default: () => [],
    },
    periodes: {
        type: Array,
        default: () => [],
    },
    decisionConseilOptions: {
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

// Handle apprenant selection to auto-fill classe and annee_scolaire
const handleApprenantChange = async (newApprenantId) => {
    if (!newApprenantId) return;

    try {
        console.log('[Bulletin Auto-fill] Fetching apprenant data for ID:', newApprenantId);
        const response = await fetch(`/academique/apprenants/${newApprenantId}/api-show`);
        if (!response.ok) {
            console.error('[Bulletin Auto-fill] API error:', response.status);
            return;
        }
        const json = await response.json();
        console.log('[Bulletin Auto-fill] Data received:', json);

        if (!json.success || !json.data) {
            console.error('[Bulletin Auto-fill] Invalid response format');
            return;
        }

        const data = json.data;
        // Auto-fill dependent fields from apprenant
        props.form.classe_id = data.classe_id || null;
        props.form.annee_scolaire_id = data.annee_scolaire_id || null;

        console.log('[Bulletin Auto-fill] Form updated:', {
            classe_id: props.form.classe_id,
            annee_scolaire_id: props.form.annee_scolaire_id
        });
    } catch (error) {
        console.error('[Bulletin Auto-fill] Error:', error);
    }
};

// Watch apprenant changes in create/edit mode
if (props.mode !== 'show') {
    watch(() => props.form.apprenant_id, (newVal) => {
        if (newVal) {
            handleApprenantChange(newVal);
        }
    });
}

</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Apprenant -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.apprenant') || 'Apprenant' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.apprenant_id"
                    :options="apprenants"
                    optionValue="id"
                    optionLabel="label"
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
        <!-- Contexte hiérarchique (affiché quand classe sélectionnée) -->
        <HierarchyContextBar
            :form="form"
        />
        <!-- Année Scolaire -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.annee_scolaire') || 'Année Scolaire' }} <span class="text-danger">*</span></label>
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
        <!-- Moyenne Générale (Calculée) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.moyenne_generale') || 'Moyenne Générale' }} <span class="text-muted">(calculée)</span></label>
                <input
                    type="text"
                    v-model="form.moyenne_generale"
                    class="form-control"
                    :placeholder="t('fields.moyenne_generale') || 'Moyenne Générale'"
                    disabled
                >
            </div>
        </div>
        <!-- Rang -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.rang') || 'Rang' }}</label>
                <input
                    type="number"
                    min="1"
                    v-model.number="form.rang"
                    class="form-control"
                    :placeholder="t('fields.rang') || 'Rang'"
                    :disabled="isReadOnly"
                >
                <span v-if="form.errors?.rang" class="text-danger">
                    <strong>{{ form.errors.rang }}</strong>
                </span>
            </div>
        </div>
        <!-- Période -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.periode') || 'Période' }}</label>
                <SearchableSelect
                    v-model="form.periode"
                    :options="periodes"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.periode" class="text-danger">
                    <strong>{{ form.errors.periode }}</strong>
                </span>
            </div>
        </div>
        <!-- Décision du Conseil -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.decision_conseil') || 'Décision du Conseil' }}</label>
                <SearchableSelect
                    v-model="form.decision_conseil"
                    :options="decisionConseilOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.decision_conseil" class="text-danger">
                    <strong>{{ form.errors.decision_conseil }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>
