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
    absences: {
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

// Get selected absence with its justifications
const selectedAbsence = computed(() => {
    return props.absences.find(a => a.id === props.form.absence_id);
});

const justifications = computed(() => {
    return selectedAbsence.value?.justifications || [];
});

// Update absence_type when absence_id changes
watch(
    () => props.form.absence_id,
    (newId) => {
        const selected = props.absences.find(a => a.id === newId);
        if (selected && !isReadOnly) {
            props.form.absence_type = selected.type || 'apprenant';
        }
    }
);
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Absence -->
        <div class="col-sm-12">
            <div class="mb-3">
                <label>{{ t('common.absence') || 'Absence' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.absence_id"
                    :options="absences"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.absence_id" class="text-danger">
                    <strong>{{ form.errors.absence_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Justifications affichées automatiquement -->
        <div v-if="justifications.length > 0" class="col-sm-12">
            <div class="alert alert-info">
                <h6 class="mb-3"><i class="fa fa-file-alt"></i> {{ t('common.justificatifs') || 'Justificatifs de l\'absence' }}</h6>
                <div class="row">
                    <div v-for="justif in justifications" :key="justif.id" class="col-md-6 mb-2">
                        <div class="card">
                            <div class="card-body p-2">
                                <p class="mb-1"><strong>{{ t('fields.document') || 'Document' }}:</strong> {{ justif.document || 'N/A' }}</p>
                                <p class="mb-1"><strong>{{ t('fields.motif') || 'Motif' }}:</strong> {{ justif.motif || 'N/A' }}</p>
                                <p class="mb-1"><strong>{{ t('fields.date') || 'Date' }}:</strong> {{ justif.date_justification || 'N/A' }}</p>
                                <p class="mb-0"><strong>{{ t('fields.status') || 'Statut' }}:</strong> <span class="badge" :class="justif.statut === 'actif' ? 'bg-success' : 'bg-secondary'">{{ justif.statut }}</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Commentaire -->
        <div class="col-sm-12">
            <div class="mb-3">
                <label>{{ t('fields.commentaire') || 'Commentaire' }}</label>
                <textarea
                    v-model="form.commentaire"
                    class="form-control"
                    :placeholder="t('fields.commentaire') || 'Commentaire supplémentaire'"
                    :disabled="isReadOnly"
                    rows="3"
                ></textarea>
                <span v-if="form.errors?.commentaire" class="text-danger">
                    <strong>{{ form.errors.commentaire }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>
