<script setup>
import { useI18n } from 'vue-i18n';
import { ref, computed, nextTick, watch } from 'vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import HierarchyContextBar from '@/Components/Common/HierarchyContextBar.vue';
import axios from 'axios';
const { t } = useI18n();
const URL = window.URL;
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    enseignants: {
        type: Array,
        required: true,
    },
    matieres: {
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
    sections: {
        type: Array,
        default: () => [],
    },
    cycles: {
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

const statutOptions = [
    { id: 'en_attente', libelle: 'En attente' },
    { id: 'validee', libelle: 'Validée' },
    { id: 'rejetee', libelle: 'Rejetée' },
];
const etatOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

// Schedule slots management
const scheduleSlots = ref([]);
const loadingSlots = ref(false);
const selectedSlot = ref(null);

// Group schedule slots by day
const slotsByDay = computed(() => {
    const grouped = {};
    const dayOrder = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    scheduleSlots.value.forEach(slot => {
        if (!grouped[slot.jour]) {
            grouped[slot.jour] = [];
        }
        grouped[slot.jour].push(slot);
    });
    return Object.keys(grouped)
        .sort((a, b) => dayOrder.indexOf(a) - dayOrder.indexOf(b))
        .reduce((result, day) => {
            result[day] = grouped[day].sort((a, b) => {
                const timeA = a.date_debut ? new Date(a.date_debut).getTime() : 0;
                const timeB = b.date_debut ? new Date(b.date_debut).getTime() : 0;
                return timeA - timeB;
            });
            return result;
        }, {});
});

// File preview management
const uploadedFiles = ref([]);
const showFileModal = ref(false);
const currentPreviewFile = ref(null);

// Get file type
const getFileType = (fileName) => {
    if (/\.(jpg|jpeg|png|gif)$/i.test(fileName)) return 'image';
    if (/\.pdf$/i.test(fileName)) return 'pdf';
    if (/\.(doc|docx)$/i.test(fileName)) return 'document';
    return 'file';
};

// Handle file input change - Add files to array
const handleFileChange = (event) => {
    const files = event.target.files;
    if (!files || files.length === 0) return;

    const newFiles = Array.from(files);
    uploadedFiles.value = [...(uploadedFiles.value || []), ...newFiles];
    props.form.justificatif_path = uploadedFiles.value;
};

// Clear all files
const clearAllFiles = () => {
    uploadedFiles.value = [];
    props.form.justificatif_path = null;
};

// Open file preview modal
const openPreview = (file) => {
    currentPreviewFile.value = file;
    showFileModal.value = true;
    nextTick(() => {
        showFileModal.value = true;
    });
};

// Close file preview modal
const closePreview = () => {
    showFileModal.value = false;
    currentPreviewFile.value = null;
};

// Auto-calculate nombre_heures from date_debut and date_fin
const calculateHeures = () => {
    if (props.form.date_debut && props.form.date_fin) {
        const debut = new Date(props.form.date_debut);
        const fin = new Date(props.form.date_fin);
        const diffMs = fin - debut;
        const diffHeures = diffMs / (1000 * 60 * 60);
        props.form.nombre_heures = Math.max(0, parseFloat(diffHeures.toFixed(2)));
    }
};

// Watch for changes in date_debut and date_fin
watch(() => [props.form.date_debut, props.form.date_fin], () => {
    calculateHeures();
});

// Load schedule slots when teacher is selected
const fetchScheduleSlots = async () => {
    if (!props.form.enseignant_id) {
        scheduleSlots.value = [];
        selectedSlot.value = null;
        return;
    }

    loadingSlots.value = true;
    try {
        const response = await axios.get(route('academique.absences_enseignants.schedule_slots'), {
            params: { enseignant_id: props.form.enseignant_id }
        });
        scheduleSlots.value = (response.data.schedules || []).map(slot => ({
            ...slot,
            // Ensure dates are formatted as 'Y-m-d\TH:i' for datetime-local inputs
            date_debut: slot.date_debut ? slot.date_debut : null,
            date_fin: slot.date_fin ? slot.date_fin : null,
        }));
    } catch (error) {
        console.error('Error fetching schedule slots:', error);
        scheduleSlots.value = [];
    } finally {
        loadingSlots.value = false;
    }
};

// Populate form from selected schedule slot
const selectScheduleSlot = (slot) => {
    props.form.matiere_id = slot.matiere_id;
    props.form.classe_id = slot.classe_id;
    props.form.nombre_heures = slot.duree; // duree is already in hours (1.5, not 90)

    // Set date_debut and date_fin from slot data
    if (slot.date_debut) {
        props.form.date_debut = slot.date_debut;
    }
    if (slot.date_fin) {
        props.form.date_fin = slot.date_fin;
    }

    calculateHeures();
};

// Watch enseignant_id changes
watch(() => props.form.enseignant_id, () => {
    if (props.mode !== 'show') {
        fetchScheduleSlots();
        selectedSlot.value = null;
    }
});
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Enseignant (ANCRE : l'absence concerne un enseignant → charge ses créneaux) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.enseignant') || 'Enseignant' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.enseignant_id"
                    :options="enseignants"
                    optionValue="id"
                    :optionLabel="(opt) => `${opt.nom} ${opt.prenoms}`"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <small class="text-muted">Sélectionnez l'enseignant : ses créneaux d'emploi du temps s'affichent ci-dessous.</small>
                <span v-if="form.errors?.enseignant_id" class="text-danger">
                    <strong>{{ form.errors.enseignant_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Schedule Slots grouped by day (shown after teacher is selected) -->
        <div v-if="Object.keys(slotsByDay).length > 0" class="col-sm-12">
            <div class="mb-3">
                <label class="d-block mb-2">
                    <strong>📅 {{ t('common.schedule_slots') || 'Créneaux d\'emploi du temps disponibles' }}</strong>
                    <small class="text-muted d-block">Cliquez sur un cours pour pré-remplir les détails</small>
                </label>
                <template v-for="(slots, day) in slotsByDay" :key="day">
                    <div class="mb-3">
                        <div class="bg-primary text-white p-2 rounded mb-2" style="font-weight: bold;">
                            📅 {{ day }}
                        </div>
                        <div class="row g-2">
                            <div v-for="slot in slots" :key="slot.id" class="col-sm-6 col-lg-4">
                                <div
                                    class="card schedule-slot"
                                    :class="{ 'border-primary bg-light': selectedSlot === slot.id }"
                                    @click="selectScheduleSlot(slot)"
                                    style="cursor: pointer; transition: all 0.3s;"
                                >
                                    <div class="card-body p-3">
                                        <div class="font-weight-bold text-primary">{{ slot.matiere_libelle }}</div>
                                        <div class="small text-muted">
                                            <i class="fa fa-clock"></i>
                                            {{ slot.date_debut ? new Date(slot.date_debut).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '' }}
                                            -
                                            {{ slot.date_fin ? new Date(slot.date_fin).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '' }}
                                        </div>
                                        <div class="small text-muted">
                                            <i class="fa fa-graduation-cap"></i> {{ slot.classe_nom }}
                                        </div>
                                        <div class="small text-muted">
                                            <i class="fa fa-hourglass-end"></i> {{ slot.duree }}h
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Classe (pré-remplie par le créneau, modifiable) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.classe') || 'Classe' }}</label>
                <SearchableSelect
                    v-model="form.classe_id"
                    @update:modelValue="handleClasseChange"
                    :options="classes"
                    optionValue="id"
                    optionLabel="nom"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.classe_id" class="text-danger">
                    <strong>{{ form.errors.classe_id }}</strong>
                </span>
            </div>
        </div>

        <HierarchyContextBar v-if="classeSelected" :form="form" :ecoles="ecoles" :campuses="campuses" :sections="sections" :cycles="cycles" />

        <!-- Matière (pré-remplie par le créneau, modifiable) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.matiere') || 'Matière' }}</label>
                <SearchableSelect
                    v-model="form.matiere_id"
                    :options="matieres"
                    optionValue="id"
                    optionLabel="nom"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.matiere_id" class="text-danger">
                    <strong>{{ form.errors.matiere_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Date Début -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_debut') || 'Date et heure de début' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.date_debut"
                    type="datetime-local"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.date_debut }"
                    :disabled="isReadOnly"
                    @change="calculateHeures"
                />
                <span v-if="form.errors?.date_debut" class="text-danger">
                    <strong>{{ form.errors.date_debut }}</strong>
                </span>
            </div>
        </div>
        <!-- Date Fin -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_fin') || 'Date et heure de fin' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.date_fin"
                    type="datetime-local"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.date_fin }"
                    :disabled="isReadOnly"
                    @change="calculateHeures"
                />
                <span v-if="form.errors?.date_fin" class="text-danger">
                    <strong>{{ form.errors.date_fin }}</strong>
                </span>
            </div>
        </div>
        <!-- Durée (en heures) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.nombre_heures') || 'Durée (en heures)' }}</label>
                <input
                    v-model.number="form.nombre_heures"
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.nombre_heures }"
                    disabled
                    placeholder="Calculé automatiquement"
                />
                <small class="text-muted">{{ t('common.auto_calculated') || 'Calculé automatiquement' }}</small>
                <span v-if="form.errors?.nombre_heures" class="text-danger">
                    <strong>{{ form.errors.nombre_heures }}</strong>
                </span>
            </div>
        </div>
        <!-- Motif -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.motif') || 'Motif' }}</label>
                <textarea
                    v-model="form.motif"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors?.motif }"
                    rows="2"
                    :disabled="isReadOnly"
                ></textarea>
                <span v-if="form.errors?.motif" class="text-danger">
                    <strong>{{ form.errors.motif }}</strong>
                </span>
            </div>
        </div>
        <!-- Statut -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.statut') || 'Statut' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.statut"
                    :options="statutOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.statut" class="text-danger">
                    <strong>{{ form.errors.statut }}</strong>
                </span>
            </div>
        </div>
        <!-- Justificatif (document/fichier/image) - Multiple Files Support -->
        <div class="col-sm-12">
            <div class="mb-3">
                <label>{{ t('fields.justificatif_path') || 'Justificatif (Documents/Images)' }}</label>
                <div class="input-group">
                    <input
                        type="file"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors?.justificatif_path }"
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif"
                        multiple
                        :disabled="isReadOnly"
                        @change="handleFileChange"
                    />
                </div>
                <!-- Remove files button if any uploaded -->
                <div v-if="uploadedFiles.length > 0" class="mt-2">
                    <button type="button" @click="clearAllFiles" class="btn btn-sm btn-danger">
                        <i class="fa fa-trash"></i> Effacer tous les fichiers
                    </button>
                </div>
                <small class="text-muted d-block mt-2">{{ t('common.supported_formats') || 'Formats: PDF, DOC, DOCX, JPG, PNG, GIF (max 5 MB chacun)' }}</small>
                <span v-if="form.errors?.justificatif_path" class="text-danger d-block mt-2">
                    <strong>{{ form.errors.justificatif_path }}</strong>
                </span>

                <!-- Multiple Files Preview Section -->
                <div v-show="(uploadedFiles && uploadedFiles.length > 0) || (Array.isArray(form.justificatif_path) && form.justificatif_path.length > 0)" class="mt-4">
                    <div v-if="uploadedFiles && uploadedFiles.length > 0" style="font-weight: 600; color: #495057; margin-bottom: 12px; padding: 8px; background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                        📎 Fichiers téléchargés ({{ uploadedFiles ? uploadedFiles.length : 0 }})
                    </div>
                    <div v-else-if="Array.isArray(form.justificatif_path) && form.justificatif_path.length > 0" style="font-weight: 600; color: #495057; margin-bottom: 12px; padding: 8px; background-color: #e8f5e9; border-left: 4px solid #81c784; border-radius: 4px;">
                        📎 Fichiers enregistrés ({{ form.justificatif_path.length }})
                    </div>

                    <!-- Loop through uploaded files -->
                    <div v-for="(file, index) in uploadedFiles" :key="index" class="mb-3 p-3" style="background-color: #f8f9fa; border-radius: 8px; border: 2px solid #0d6efd; display: flex; align-items: flex-start; justify-content: space-between; gap: 15px;">
                        <!-- Image Thumbnail -->
                        <div v-if="/\.(jpg|jpeg|png|gif)$/i.test(file.name)" style="flex: 1;">
                            <div style="display: flex; gap: 10px; align-items: flex-start;">
                                <img :src="URL.createObjectURL(file)" style="width: 100px; height: 100px; object-fit: cover; border-radius: 6px; border: 2px solid #0d6efd;" />
                                <div>
                                    <div style="font-weight: 600; color: #0d6efd;">{{ file.name }}</div>
                                    <div style="font-size: 13px; color: #6c757d;">🖼️ Image - {{ (file.size / 1024 / 1024).toFixed(2) }} MB</div>
                                </div>
                            </div>
                        </div>

                        <!-- PDF Files -->
                        <div v-else-if="/\.pdf$/i.test(file.name)" style="flex: 1; display: flex; align-items: center; gap: 10px;">
                            <i class="fa fa-file-pdf" style="font-size: 48px; color: #dc3545; flex-shrink: 0;"></i>
                            <div>
                                <div style="font-weight: 600; color: #dc3545;">{{ file.name }}</div>
                                <div style="font-size: 13px; color: #6c757d;">📄 PDF - {{ (file.size / 1024).toFixed(1) }} KB</div>
                            </div>
                        </div>

                        <!-- Document Files -->
                        <div v-else-if="/\.(doc|docx)$/i.test(file.name)" style="flex: 1; display: flex; align-items: center; gap: 10px;">
                            <i class="fa fa-file-word" style="font-size: 48px; color: #0078d4; flex-shrink: 0;"></i>
                            <div>
                                <div style="font-weight: 600; color: #0078d4;">{{ file.name }}</div>
                                <div style="font-size: 13px; color: #6c757d;">📝 {{ file.name.split('.').pop().toUpperCase() }} - {{ (file.size / 1024).toFixed(1) }} KB</div>
                            </div>
                        </div>

                        <!-- Other Files -->
                        <div v-else style="flex: 1; display: flex; align-items: center; gap: 10px;">
                            <i class="fa fa-file" style="font-size: 48px; color: #6c757d; flex-shrink: 0;"></i>
                            <div>
                                <div style="font-weight: 600; color: #6c757d;">{{ file.name }}</div>
                                <div style="font-size: 13px; color: #6c757d;">📦 {{ file.name.split('.').pop().toUpperCase() }} - {{ (file.size / 1024).toFixed(1) }} KB</div>
                            </div>
                        </div>
                        <!-- Preview Button -->
                        <button type="button" @click="openPreview(file)" class="btn btn-sm btn-primary" style="margin-left: 10px; flex-shrink: 0;">
                            <i class="fa fa-eye"></i> Aperçu
                        </button>
                    </div>

                    <!-- Display existing files from database -->
                    <div v-if="!uploadedFiles.length && Array.isArray(form.justificatif_path) && form.justificatif_path.length > 0">
                        <div v-for="(filePath, index) in form.justificatif_path.filter(f => typeof f === 'string')" :key="'existing-' + index" class="mb-3 p-3" style="background-color: #e8f5e9; border-radius: 8px; border: 1px solid #81c784; display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 10px; flex: 1;">
                                <i v-if="/\.pdf$/i.test(filePath)" class="fa fa-file-pdf" style="font-size: 32px; color: #dc3545; flex-shrink: 0;"></i>
                                <i v-else-if="/\.(doc|docx)$/i.test(filePath)" class="fa fa-file-word" style="font-size: 32px; color: #0078d4; flex-shrink: 0;"></i>
                                <i v-else-if="/\.(jpg|jpeg|png|gif)$/i.test(filePath)" class="fa fa-image" style="font-size: 32px; color: #1976d2; flex-shrink: 0;"></i>
                                <i v-else class="fa fa-file" style="font-size: 32px; color: #6c757d; flex-shrink: 0;"></i>
                                <div>
                                    <div style="font-weight: 600; color: #2e7d32;">{{ typeof filePath === 'string' ? filePath.split('/').pop() : 'Fichier' }}</div>
                                    <div style="font-size: 13px; color: #558b2f;">Enregistré ✓</div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px; flex-shrink: 0;">
                                <button v-if="typeof filePath === 'string'" type="button" @click="openPreview(filePath)" class="btn btn-sm btn-outline-info" style="padding: 4px 12px; font-size: 12px;">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <a v-if="typeof filePath === 'string'" :href="`/storage/${filePath}`" target="_blank" class="btn btn-sm btn-outline-success" style="padding: 4px 12px; font-size: 12px;">
                                    <i class="fa fa-download"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Etat (Activité) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.etat') || 'État' }}</label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="etatOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.etat" class="text-danger">
                    <strong>{{ form.errors.etat }}</strong>
                </span>
            </div>
        </div>
    </div>

    <!-- Modale de visualisation de fichier -->
    <div v-if="showFileModal && currentPreviewFile" class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-file"></i>
                        Aperçu: {{ typeof currentPreviewFile === 'string' ? currentPreviewFile.split('/').pop() : currentPreviewFile.name }}
                    </h5>
                    <button type="button" class="btn-close" @click="closePreview"></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <!-- Image Preview -->
                    <div v-if="currentPreviewFile && getFileType(typeof currentPreviewFile === 'string' ? currentPreviewFile : currentPreviewFile.name) === 'image'" class="text-center">
                        <p class="text-muted mb-3"><i class="fa fa-image"></i> Image</p>
                        <img
                            :src="typeof currentPreviewFile === 'string' ? `/storage/${currentPreviewFile}` : URL.createObjectURL(currentPreviewFile)"
                            alt="Image"
                            style="max-width: 100%; max-height: 55vh; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);"
                        />
                    </div>

                    <!-- PDF Preview -->
                    <div v-else-if="currentPreviewFile && getFileType(typeof currentPreviewFile === 'string' ? currentPreviewFile : currentPreviewFile.name) === 'pdf'" class="text-center">
                        <p class="text-muted mb-3"><i class="fa fa-file-pdf"></i> Document PDF</p>
                        <iframe
                            :src="typeof currentPreviewFile === 'string' ? `/storage/${currentPreviewFile}` : URL.createObjectURL(currentPreviewFile)"
                            style="width: 100%; height: 55vh; border: 1px solid #ddd; border-radius: 8px;"
                        ></iframe>
                    </div>

                    <!-- Document Preview -->
                    <div v-else-if="currentPreviewFile && getFileType(typeof currentPreviewFile === 'string' ? currentPreviewFile : currentPreviewFile.name) === 'document'" class="alert alert-info text-center">
                        <i class="fa fa-file-word" style="font-size: 48px; color: #0078d4; margin-bottom: 10px;"></i>
                        <p class="mt-3">Document Word</p>
                        <p class="text-muted">{{ typeof currentPreviewFile === 'string' ? currentPreviewFile.split('/').pop() : currentPreviewFile.name }}</p>
                        <p class="small text-muted mt-3">La prévisualisation de documents Word n'est pas disponible. Téléchargez le fichier pour le consulter.</p>
                    </div>

                    <!-- Other Files -->
                    <div v-else class="alert alert-info text-center">
                        <i class="fa fa-file" style="font-size: 48px; color: #6c757d; margin-bottom: 10px;"></i>
                        <p class="mt-3">Fichier</p>
                        <p v-if="currentPreviewFile" class="text-muted">{{ typeof currentPreviewFile === 'string' ? currentPreviewFile.split('/').pop() : currentPreviewFile.name }}</p>
                        <p class="small text-muted mt-3">Ce type de fichier ne peut pas être prévisualisé. Utilisez le bouton "Télécharger" pour accéder au fichier.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <a
                        v-if="typeof currentPreviewFile === 'string'"
                        :href="`/storage/${currentPreviewFile}`"
                        class="btn btn-primary"
                        download
                    >
                        <i class="fa fa-download"></i> Télécharger
                    </a>
                    <button type="button" class="btn btn-secondary" @click="closePreview">
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Backdrop -->
    <div v-if="showFileModal" class="modal-backdrop fade show" style="background: rgba(0,0,0,0.5);"></div>
</template>
