<script setup>
import { computed, ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import { useApprenantAutoFill } from '../../composables/useApprenantAutoFill';

const { t } = useI18n();

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    devoirs: {
        type: Array,
        default: () => [],
    },
    apprenants: {
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
const isCreateMode = computed(() => props.mode === 'create');

// Format date for HTML date input (Y-m-d)
const formattedDate = computed({
    get() {
        if (!props.form.date_rendu) return '';
        // Handle both timestamp format (2026-03-27 00:00:00) and ISO format (2026-03-27)
        const dateStr = props.form.date_rendu.toString().substring(0, 10);
        return dateStr;
    },
    set(value) {
        props.form.date_rendu = value;
    }
});

// Auto-fill apprenant → classe
useApprenantAutoFill(props.form);

// File upload handling
const fileInput = ref(null);
const selectedFileName = ref('');
const filePreview = ref('');

const handleFileSelect = (event) => {
    const file = event.target.files?.[0];
    if (file) {
        props.form.fichier_rendu = file;
        selectedFileName.value = file.name;

        // Créer aperçu pour images
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                filePreview.value = e.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            filePreview.value = '';
        }
    }
};

const triggerFileInput = () => {
    fileInput.value?.click();
};

const getExistingFileUrl = (path) => {
    if (!path) return null;
    return `/storage/${path}`;
};
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Devoir -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.devoir') || 'Devoir' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-if="isCreateMode"
                    v-model="form.devoir_id"
                    :options="devoirs"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <div v-else class="form-control" style="background-color: #f5f5f5; border: 1px solid #ddd;">
                    {{ form.devoir?.titre || '' }}
                </div>
                <div v-if="form.errors?.devoir_id" class="invalid-feedback d-block">
                    {{ form.errors.devoir_id[0] }}
                </div>
            </div>
        </div>

        <!-- Apprenant -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.apprenant') || 'Apprenant' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-if="isCreateMode"
                    v-model="form.apprenant_id"
                    :options="apprenants"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <div v-else class="form-control" style="background-color: #f5f5f5; border: 1px solid #ddd;">
                    {{ form.apprenant?.user?.prenoms }} {{ form.apprenant?.user?.nom }}
                </div>
                <div v-if="form.errors?.apprenant_id" class="invalid-feedback d-block">
                    {{ form.errors.apprenant_id[0] }}
                </div>
            </div>
        </div>

        <!-- Date Rendu -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_rendu') || 'Date Rendu' }} <span class="text-danger">*</span></label>
                <input
                    v-model="formattedDate"
                    type="date"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.date_rendu }"
                    :disabled="isReadOnly"
                    required
                />
                <div v-if="form.errors?.date_rendu" class="invalid-feedback d-block">
                    {{ form.errors.date_rendu[0] }}
                </div>
            </div>
        </div>

        <!-- Note Finale -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.note_finale') || 'Note Finale' }}</label>
                <input
                    v-model.number="form.note_finale"
                    type="number"
                    min="0"
                    max="20"
                    step="0.01"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.note_finale }"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors?.note_finale" class="invalid-feedback d-block">
                    {{ form.errors.note_finale[0] }}
                </div>
            </div>
        </div>

        <!-- Fichier Rendu -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.fichier_rendu') || 'Fichier Rendu' }}</label>

                <!-- Hidden file input -->
                <input
                    ref="fileInput"
                    type="file"
                    class="d-none"
                    accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.zip"
                    @change="handleFileSelect"
                    :disabled="isReadOnly"
                />

                <!-- File upload button/display -->
                <div v-if="!isReadOnly" class="input-group mb-2">
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        @click="triggerFileInput"
                    >
                        <i class="bi bi-upload"></i> {{ t('actions.choose_file') || 'Choisir un fichier' }}
                    </button>
                    <span class="input-group-text" style="flex: 1; text-align: left;">
                        {{ selectedFileName || form.fichier_rendu?.name || 'Aucun fichier sélectionné' }}
                    </span>
                </div>

                <!-- Image Preview -->
                <div v-if="filePreview" class="mb-3">
                    <img :src="filePreview" alt="Aperçu" style="max-width: 100%; max-height: 300px; border: 1px solid #ddd; border-radius: 4px; padding: 5px;">
                    <small class="d-block mt-2 text-muted">✅ Aperçu du fichier image</small>
                </div>

                <div v-if="isReadOnly" class="form-control" style="background-color: #f5f5f5; border: 1px solid #ddd;">
                    <a v-if="form.fichier_rendu_path" :href="getExistingFileUrl(form.fichier_rendu_path)" target="_blank">
                        📄 {{ form.fichier_rendu_path }}
                    </a>
                    <span v-else>Aucun fichier</span>
                </div>

                <small class="text-muted d-block mt-2">
                    Formats acceptés: PDF, DOC, DOCX, TXT, JPG, PNG, ZIP
                </small>

                <div v-if="form.errors?.fichier_rendu" class="invalid-feedback d-block">
                    {{ form.errors.fichier_rendu[0] }}
                </div>
            </div>
        </div>

        <!-- Notes Enseignant -->
        <div class="col-sm-12">
            <div class="mb-3">
                <label>{{ t('fields.notes_enseignant') || 'Notes Enseignant' }}</label>
                <textarea
                    v-model="form.notes_enseignant"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.notes_enseignant }"
                    :disabled="isReadOnly"
                    rows="4"
                ></textarea>
                <div v-if="form.errors?.notes_enseignant" class="invalid-feedback d-block">
                    {{ form.errors.notes_enseignant[0] }}
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.rendu-devoir-form {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    font-weight: 500;
    margin-bottom: 8px;
    display: block;
}
.form-control:disabled {
    background-color: #e9ecef;
    cursor: not-allowed;
}
.form-actions {
    display: flex;
    gap: 10px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}
.form-actions button,
.form-actions a {
    padding: 10px 20px;
    font-weight: 500;
}
</style>
