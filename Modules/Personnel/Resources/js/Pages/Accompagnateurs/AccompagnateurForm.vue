<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import ApprenantsPicker from '@/Components/Common/ApprenantsPicker.vue';

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
    institutions: {
        type: Array,
        default: () => [],
    },
    campuses: {
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

const autoLabel = (list, id) => {
    if (!id || !list?.length) return '\u2014';
    const found = list.find(item => String(item.id) === String(id));
    return found?.libelle || found?.nom || found?.label || '\u2014';
};

const institutionLabel = computed(() => autoLabel(props.institutions, props.form.institution_id));
const campusLabel = computed(() => autoLabel(props.campuses, props.form.campus_id));

// Photo preview states - initialiser avec les URLs existantes
const photoPreview1 = ref(props.form?.accompagnant1_photo_url || null);
const photoPreview2 = ref(props.form?.accompagnant2_photo_url || null);
const photoPreview3 = ref(props.form?.accompagnant3_photo_url || null);

// Handle photo file selection
const handlePhotoChange = (e, field) => {
    const file = e.target.files?.[0];
    if (!file) return;

    // Validate file type
    if (!file.type.startsWith('image/')) {
        alert('Veuillez sélectionner une image valide');
        e.target.value = '';
        return;
    }

    // Create preview
    const reader = new FileReader();
    reader.onload = (event) => {
        if (field === 'accompagnant1_photo') {
            photoPreview1.value = event.target.result;
        } else if (field === 'accompagnant2_photo') {
            photoPreview2.value = event.target.result;
        } else if (field === 'accompagnant3_photo') {
            photoPreview3.value = event.target.result;
        }
    };
    reader.readAsDataURL(file);

    // Set the file in form
    props.form[field] = file;
};

const civiliteOptions = [
    { id: 'mr', libelle: 'M.' },
    { id: 'mme', libelle: 'Mme' },
    { id: 'mlle', libelle: 'Mlle' },
];

const etatOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
</script>

<template>
    <div class="form-wrapper">
        <!-- Section 1: Informations de l'école -->
        <div class="form-section">
            <h5 class="section-title">{{ t('common.school_information') || 'Informations de l\'école' }}</h5>
            <div class="row">
                <div class="col-sm-4 mb-3">
                    <label class="form-label">{{ t('fields.ecole') || 'École' }}</label>
                    <SearchableSelect
                        v-model="form.ecole_id"
                        :options="ecoles"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('fields.ecole') || 'Sélectionner une école'"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-sm-4 mb-3">
                    <label class="form-label">{{ t('fields.institution') || 'Institution' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                    <input type="text" class="form-control" :value="institutionLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
                </div>
                <div class="col-sm-4 mb-3">
                    <label class="form-label">{{ t('fields.campus') || 'Campus' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                    <input type="text" class="form-control" :value="campusLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
                </div>
            </div>
        </div>

        <!-- Section : Apprenants rattachés (multi) -->
        <div class="form-section">
            <h5 class="section-title">
                <i class="fa fa-users me-2"></i>Apprenants rattachés
                <small class="text-muted fs-6">— fratrie dans la même école</small>
            </h5>
            <ApprenantsPicker
                v-model="form.apprenant_ids"
                :apprenants="apprenants"
                :disabled="isReadOnly"
            />
            <div v-if="form.errors?.apprenant_ids" class="text-danger small mt-1">
                {{ Array.isArray(form.errors.apprenant_ids) ? form.errors.apprenant_ids[0] : form.errors.apprenant_ids }}
            </div>
        </div>

        <!-- Section 2: Accompagnant 1 -->
        <div class="form-section">
            <h5 class="section-title">{{ t('fields.accompagnant1') || 'Accompagnant 1' }}</h5>
            <div class="row">
                <div class="col-sm-2 mb-3">
                    <label class="form-label">{{ t('fields.civilite') || 'Civilité' }}</label>
                    <select
                        v-model="form.accompagnant1_civilite"
                        class="form-control"
                        :disabled="isReadOnly"
                    >
                        <option value="">-- Sélectionner --</option>
                        <option v-for="option in civiliteOptions" :key="option.id" :value="option.id">
                            {{ option.libelle }}
                        </option>
                    </select>
                </div>
                <div class="col-sm-3 mb-3">
                    <label class="form-label">{{ t('fields.nom') || 'Nom' }}</label>
                    <input
                        v-model="form.accompagnant1_nom"
                        type="text"
                        class="form-control"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-sm-3 mb-3">
                    <label class="form-label">{{ t('fields.prenoms') || 'Prénoms' }}</label>
                    <input
                        v-model="form.accompagnant1_prenoms"
                        type="text"
                        class="form-control"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-sm-4 mb-3">
                    <label class="form-label">{{ t('fields.nom_complet') || 'Nom complet' }}</label>
                    <input
                        v-model="form.accompagnant1_nom_complet"
                        type="text"
                        class="form-control"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-sm-4 mb-3">
                    <label class="form-label">{{ t('fields.lien') || 'Lien de parenté' }}</label>
                    <input
                        v-model="form.accompagnant1_lien"
                        type="text"
                        class="form-control"
                        placeholder="ex: Père, Mère, Tuteur"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-sm-8 mb-3">
                    <label class="form-label">{{ t('fields.photo') || 'Photo' }}</label>
                    <div class="photo-upload-wrapper">
                        <div class="photo-preview-container">
                            <div v-if="photoPreview1" class="photo-preview">
                                <img :src="photoPreview1" alt="Aperçu photo" class="preview-img" />
                                <button
                                    v-if="!isReadOnly"
                                    @click="photoPreview1 = null"
                                    type="button"
                                    class="btn-remove-photo"
                                    title="Supprimer l'image"
                                >
                                    <span class="fa fa-times"></span>
                                </button>
                            </div>
                            <div v-else class="photo-placeholder">
                                <span class="fa fa-image"></span>
                                <p>Aucune image</p>
                            </div>
                        </div>
                        <input
                            v-if="!isReadOnly"
                            type="file"
                            accept="image/*"
                            @change="(e) => handlePhotoChange(e, 'accompagnant1_photo')"
                            class="form-control photo-input"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Accompagnant 2 -->
        <div class="form-section">
            <h5 class="section-title">{{ t('fields.accompagnant2') || 'Accompagnant 2' }}</h5>
            <div class="row">
                <div class="col-sm-2 mb-3">
                    <label class="form-label">{{ t('fields.civilite') || 'Civilité' }}</label>
                    <select
                        v-model="form.accompagnant2_civilite"
                        class="form-control"
                        :disabled="isReadOnly"
                    >
                        <option value="">-- Sélectionner --</option>
                        <option v-for="option in civiliteOptions" :key="option.id" :value="option.id">
                            {{ option.libelle }}
                        </option>
                    </select>
                </div>
                <div class="col-sm-3 mb-3">
                    <label class="form-label">{{ t('fields.nom') || 'Nom' }}</label>
                    <input
                        v-model="form.accompagnant2_nom"
                        type="text"
                        class="form-control"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-sm-3 mb-3">
                    <label class="form-label">{{ t('fields.prenoms') || 'Prénoms' }}</label>
                    <input
                        v-model="form.accompagnant2_prenoms"
                        type="text"
                        class="form-control"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-sm-4 mb-3">
                    <label class="form-label">{{ t('fields.nom_complet') || 'Nom complet' }}</label>
                    <input
                        v-model="form.accompagnant2_nom_complet"
                        type="text"
                        class="form-control"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-sm-4 mb-3">
                    <label class="form-label">{{ t('fields.lien') || 'Lien de parenté' }}</label>
                    <input
                        v-model="form.accompagnant2_lien"
                        type="text"
                        class="form-control"
                        placeholder="ex: Père, Mère, Tuteur"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-sm-8 mb-3">
                    <label class="form-label">{{ t('fields.photo') || 'Photo' }}</label>
                    <div class="photo-upload-wrapper">
                        <div class="photo-preview-container">
                            <div v-if="photoPreview2" class="photo-preview">
                                <img :src="photoPreview2" alt="Aperçu photo" class="preview-img" />
                                <button
                                    v-if="!isReadOnly"
                                    @click="photoPreview2 = null"
                                    type="button"
                                    class="btn-remove-photo"
                                    title="Supprimer l'image"
                                >
                                    <span class="fa fa-times"></span>
                                </button>
                            </div>
                            <div v-else class="photo-placeholder">
                                <span class="fa fa-image"></span>
                                <p>Aucune image</p>
                            </div>
                        </div>
                        <input
                            v-if="!isReadOnly"
                            type="file"
                            accept="image/*"
                            @change="(e) => handlePhotoChange(e, 'accompagnant2_photo')"
                            class="form-control photo-input"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Accompagnant 3 -->
        <div class="form-section">
            <h5 class="section-title">{{ t('fields.accompagnant3') || 'Accompagnant 3' }}</h5>
            <div class="row">
                <div class="col-sm-2 mb-3">
                    <label class="form-label">{{ t('fields.civilite') || 'Civilité' }}</label>
                    <select
                        v-model="form.accompagnant3_civilite"
                        class="form-control"
                        :disabled="isReadOnly"
                    >
                        <option value="">-- Sélectionner --</option>
                        <option v-for="option in civiliteOptions" :key="option.id" :value="option.id">
                            {{ option.libelle }}
                        </option>
                    </select>
                </div>
                <div class="col-sm-3 mb-3">
                    <label class="form-label">{{ t('fields.nom') || 'Nom' }}</label>
                    <input
                        v-model="form.accompagnant3_nom"
                        type="text"
                        class="form-control"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-sm-3 mb-3">
                    <label class="form-label">{{ t('fields.prenoms') || 'Prénoms' }}</label>
                    <input
                        v-model="form.accompagnant3_prenoms"
                        type="text"
                        class="form-control"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-sm-4 mb-3">
                    <label class="form-label">{{ t('fields.nom_complet') || 'Nom complet' }}</label>
                    <input
                        v-model="form.accompagnant3_nom_complet"
                        type="text"
                        class="form-control"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-sm-4 mb-3">
                    <label class="form-label">{{ t('fields.lien') || 'Lien de parenté' }}</label>
                    <input
                        v-model="form.accompagnant3_lien"
                        type="text"
                        class="form-control"
                        placeholder="ex: Père, Mère, Tuteur"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-sm-8 mb-3">
                    <label class="form-label">{{ t('fields.photo') || 'Photo' }}</label>
                    <div class="photo-upload-wrapper">
                        <div class="photo-preview-container">
                            <div v-if="photoPreview3" class="photo-preview">
                                <img :src="photoPreview3" alt="Aperçu photo" class="preview-img" />
                                <button
                                    v-if="!isReadOnly"
                                    @click="photoPreview3 = null"
                                    type="button"
                                    class="btn-remove-photo"
                                    title="Supprimer l'image"
                                >
                                    <span class="fa fa-times"></span>
                                </button>
                            </div>
                            <div v-else class="photo-placeholder">
                                <span class="fa fa-image"></span>
                                <p>Aucune image</p>
                            </div>
                        </div>
                        <input
                            v-if="!isReadOnly"
                            type="file"
                            accept="image/*"
                            @change="(e) => handlePhotoChange(e, 'accompagnant3_photo')"
                            class="form-control photo-input"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 5: Statut -->
        <div class="form-section">
            <h5 class="section-title">{{ t('common.status') || 'État' }}</h5>
            <div class="row">
                <div class="col-sm-4 mb-3">
                    <label class="form-label">{{ t('fields.status') || 'État' }}</label>
                    <select
                        v-model="form.etat"
                        class="form-control"
                        :disabled="isReadOnly"
                    >
                        <option value="">-- Sélectionner --</option>
                        <option v-for="option in etatOptions" :key="option.id" :value="option.id">
                            {{ option.libelle }}
                        </option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.form-wrapper {
    padding: 0;
}

.form-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e3e6f0;
}

.form-section:last-child {
    border-bottom: none;
}

.section-title {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 15px;
    font-size: 16px;
}

.form-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 5px;
}

.form-control:disabled {
    background-color: #e9ecef;
    cursor: not-allowed;
}

input[type="text"],
input[type="email"],
input[type="tel"],
input[type="date"],
select,
textarea {
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
}

input[type="text"]:focus,
input[type="email"]:focus,
input[type="tel"]:focus,
input[type="date"]:focus,
select:focus,
textarea:focus {
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Photo Upload Styles */
.photo-upload-wrapper {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.photo-preview-container {
    width: 150px;
    height: 180px;
    border: 2px dashed #ced4da;
    border-radius: 8px;
    overflow: hidden;
    background-color: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.photo-preview-container:hover {
    border-color: #0d6efd;
    background-color: #f0f6ff;
}

.photo-preview {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.preview-img {
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
}

.photo-placeholder {
    text-align: center;
    color: #6c757d;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.photo-placeholder .fa {
    font-size: 32px;
    color: #adb5bd;
}

.photo-placeholder p {
    margin: 0;
    font-size: 14px;
}

.photo-input {
    padding: 10px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
}

.photo-input:hover {
    background-color: #f8f9fa;
}

.btn-remove-photo {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: rgba(220, 53, 69, 0.9);
    color: white;
    border: none;
    cursor: pointer;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.btn-remove-photo:hover {
    background-color: rgba(220, 53, 69, 1);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}
</style>
