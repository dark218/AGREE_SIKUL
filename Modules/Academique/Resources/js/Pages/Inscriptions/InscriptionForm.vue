<script setup>
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import HierarchyContextBar from '@/Components/Common/HierarchyContextBar.vue';
import { useClasseAutoFill } from '../../composables/useClasseAutoFill';
import { useApprenantAutoFill } from '../../composables/useApprenantAutoFill';

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
    ecoles: {
        type: Array,
        default: () => [],
    },
    campuses: {
        type: Array,
        default: () => [],
    },
    institutions: {
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

// Computed value for date_inscription with proper formatting
const formattedDateInscription = computed({
    get() {
        const formatted = formatDateForDateInput(props.form?.date_inscription);
        console.log('📝 formattedDateInscription GET:', formatted);
        return formatted;
    },
    set(value) {
        console.log('📝 formattedDateInscription SET:', value);
        props.form.date_inscription = value;
    }
});

console.log('🔵 InscriptionForm - Props received:', {
    apprenants: props.apprenants?.length || 0,
    classes: props.classes?.length || 0,
    mode: props.mode,
    date_inscription_raw: props.form?.date_inscription,
    date_inscription_type: typeof props.form?.date_inscription,
});

// DEBUG: Watch date_inscription
watch(() => props.form?.date_inscription, (newVal) => {
    console.log('👀 InscriptionForm - date_inscription changed:', newVal, 'Type:', typeof newVal);
}, { deep: true });

// Function to format ISO datetime to date format (YYYY-MM-DD)
const formatDateForDateInput = (dateStr) => {
    if (!dateStr) return '';

    console.log('🔧 formatDateForDateInput - Input:', dateStr);

    try {
        // Handle ISO format: '2008-07-10T00:00:00.000000Z'
        // Extract just the date part: '2008-07-10'
        if (typeof dateStr === 'string') {
            const datePart = dateStr.split('T')[0]; // Get YYYY-MM-DD
            console.log('✅ formatDateForDateInput - Output:', datePart);
            return datePart;
        }

        // Handle Date object
        if (dateStr instanceof Date) {
            const formatted = dateStr.toISOString().split('T')[0];
            console.log('✅ formatDateForDateInput - Date Output:', formatted);
            return formatted;
        }

        return '';
    } catch (error) {
        console.error('❌ formatDateForDateInput - Error:', error, dateStr);
        return '';
    }
};

if (!props.apprenants || props.apprenants.length === 0) {
    console.warn('⚠️ NO APPRENANTS LOADED!');
}

if (props.apprenants?.length > 0) {
    console.log('✅ Apprenants sample:', props.apprenants.slice(0, 3));
}

// Auto-fill only in create/edit mode (not in show/read-only mode)
if (!isReadOnly) {
    // Auto-fill classe → ecole, campus, section, cycle, annee_scolaire
    useClasseAutoFill(props.form);

    // Auto-fill apprenant → classe (and then classe auto-fills the rest)
    useApprenantAutoFill(props.form);

    // Auto-fill numero_inscription from selected apprenant
    watch(() => props.form.apprenant_id, (newApprenantId) => {
        if (newApprenantId) {
            const apprenant = props.apprenants.find(a => a.id === newApprenantId);
            if (apprenant && apprenant.numero_inscription) {
                props.form.numero_inscription = apprenant.numero_inscription;
                console.log('✅ Numero inscription auto-filled:', apprenant.numero_inscription);
            }
        }
    });
}

// Statut options with actual values
const statutOptions = [
    { id: 'en_attente', libelle: t('common.en_attente') || 'En attente' },
    { id: 'validee', libelle: t('common.validee') || 'Validée' },
    { id: 'rejetee', libelle: t('common.rejetee') || 'Rejetée' },
    { id: 'suspendue', libelle: t('common.suspendue') || 'Suspendue' },
];

// Type inscription options
const typeInscriptionOptions = [
    { id: 'nouveau', libelle: t('common.nouveau') || 'Nouveau' },
    { id: 'redoublement', libelle: t('common.redoublement') || 'Redoublement' },
    { id: 'transfert', libelle: t('common.transfert') || 'Transfert' },
    { id: 'reprise', libelle: t('common.reprise') || 'Reprise' },
];

// Computed fields for fee calculations
const fraisDossierRestant = computed(() => Math.max(0, (Number(props.form.frais_dossier) || 0) - (Number(props.form.frais_dossier_paye) || 0)));
const fraisInscriptionRestant = computed(() => Math.max(0, (Number(props.form.frais_inscription) || 0) - (Number(props.form.frais_inscription_paye) || 0)));
const fraisScolariteRestant = computed(() => Math.max(0, (Number(props.form.frais_scolarite) || 0) - (Number(props.form.frais_scolarite_paye) || 0)));
const totalPaye = computed(() => (Number(props.form.frais_dossier_paye) || 0) + (Number(props.form.frais_inscription_paye) || 0) + (Number(props.form.frais_scolarite_paye) || 0));
const totalRestant = computed(() => fraisDossierRestant.value + fraisInscriptionRestant.value + fraisScolariteRestant.value);

// Handle file upload - Inertia way
const handleFileUpload = (field, event) => {
    const file = event.target.files?.[0];
    if (file) {
        // Use Inertia's way to handle file uploads
        props.form[field] = file;
        console.log(`✅ File selected for ${field}:`, file.name, file.size);
    }
};

// Get existing file URL
const getFileUrl = (filePath) => {
    if (!filePath) return null;
    return `/storage/${filePath}`;
};

// File preview configuration
const fileFields = [
    { key: 'fiche_inscription', label: 'Fiche d\'inscription' },
    { key: 'carnet_vaccination', label: 'Carnet de vaccination' },
    { key: 'photos_4x4', label: 'Photos 4x4' },
    { key: 'copie_acte_naissance', label: 'Copie acte de naissance' },
    { key: 'piece1', label: 'Pièce 1' },
    { key: 'piece2', label: 'Pièce 2' },
    { key: 'piece3', label: 'Pièce 3' },
    { key: 'piece4', label: 'Pièce 4' },
];

// Get file preview data
const getFilePreviewData = (field) => {
    const file = props.form[field];
    if (!file) return null;

    // File object (newly selected)
    if (file instanceof File) {
        return {
            name: file.name,
            size: (file.size / 1024).toFixed(2) + ' KB',
            type: file.type,
            isImage: file.type.startsWith('image/'),
            isPdf: file.type === 'application/pdf',
            preview: URL.createObjectURL(file),
        };
    }

    // String path (existing file)
    if (typeof file === 'string') {
        return {
            name: file.split('/').pop(),
            size: 'Fichier existant',
            type: file.split('.').pop().toLowerCase(),
            isImage: ['jpg', 'jpeg', 'png', 'gif'].includes(file.split('.').pop().toLowerCase()),
            isPdf: file.endsWith('.pdf'),
            preview: `/storage/${file}`,
        };
    }

    return null;
};

// Computed list of selected files
const selectedFiles = computed(() => {
    return fileFields
        .map(field => ({
            ...field,
            data: getFilePreviewData(field.key),
        }))
        .filter(field => field.data !== null);
});
</script>

<template>
    <div class="custom-input">
        <!-- Section 1: Informations de base -->
        <h5 class="section-title mb-3 mt-4">{{ t('fields.base_info') || 'Informations de base' }}</h5>
        <div class="row g-3">
            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('common.apprenant') || 'Apprenant' }} <span class="text-danger">*</span>
                        <small v-if="mode === 'edit'" class="text-muted d-block">(Modifiable si changement nécessaire)</small>
                    </label>
                    <SearchableSelect
                        v-model="form.apprenant_id"
                        :options="apprenants"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.apprenant_id" class="text-danger"><strong>{{ form.errors.apprenant_id }}</strong></span>
                    <div v-if="mode === 'edit'" class="mt-2 p-2 bg-light rounded">
                        <small class="text-muted">
                            🔍 DEBUG edit mode: apprenant_id = {{ form.apprenant_id }}
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('fields.numero_inscription') || 'Numéro Inscription' }} <span class="text-muted">(Auto-rempli)</span></label>
                    <input type="text" v-model="form.numero_inscription" class="form-control" :disabled="true" />
                    <small class="text-muted">Rempli automatiquement depuis l'apprenant</small>
                    <span v-if="form.errors?.numero_inscription" class="text-danger"><strong>{{ form.errors.numero_inscription }}</strong></span>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('fields.date_inscription') || 'Date Inscription' }} <span class="text-danger">*</span></label>
                    <input type="date" v-model="formattedDateInscription" class="form-control" :disabled="isReadOnly" />
                    <span v-if="form.errors?.date_inscription" class="text-danger"><strong>{{ form.errors.date_inscription }}</strong></span>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('fields.type_inscription') || 'Type d\'inscription' }} <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.type_inscription"
                        :options="typeInscriptionOptions"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.type_inscription" class="text-danger"><strong>{{ form.errors.type_inscription }}</strong></span>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('fields.status') || 'Statut' }} <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.statut"
                        :options="statutOptions"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.statut" class="text-danger"><strong>{{ form.errors.statut }}</strong></span>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label class="form-check-label">
                        <input type="checkbox" v-model="form.premiere_inscription" class="form-check-input" :disabled="isReadOnly" />
                        {{ t('fields.premiere_inscription') || 'Première inscription' }}
                    </label>
                </div>
            </div>
        </div>

        <!-- Section 2: Affectation scolaire -->
        <h5 class="section-title mb-3 mt-4">{{ t('fields.academic_assignment') || 'Affectation scolaire' }}</h5>
        <div class="row g-3">
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
                    <span v-if="form.errors?.annee_scolaire_id" class="text-danger"><strong>{{ form.errors.annee_scolaire_id }}</strong></span>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('common.classe') || 'Classe' }} <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.classe_id"
                        :options="classes"
                        optionValue="id"
                        optionLabel="nom"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.classe_id" class="text-danger"><strong>{{ form.errors.classe_id }}</strong></span>
                </div>
            </div>
            <!-- Contexte hiérarchique (auto-rempli par la classe) -->
            <HierarchyContextBar :form="form" :ecoles="ecoles" :campuses="campuses" />

            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('fields.ecole') || 'École' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                    <input type="text" class="form-control" :value="ecoleLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('fields.campus') || 'Campus' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                    <input type="text" class="form-control" :value="campusLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('fields.institution') || 'Institution' }}</label>
                    <SearchableSelect
                        v-model="form.institution_id"
                        :options="institutions"
                        optionValue="id"
                        optionLabel="nom"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.institution_id" class="text-danger"><strong>{{ form.errors.institution_id }}</strong></span>
                </div>
            </div>
        </div>

        <!-- Section 3: Frais -->
        <h5 class="section-title mb-3 mt-4">{{ t('fields.fees') || 'Frais' }}</h5>
        <div class="row g-3">
            <!-- Frais de dossier -->
            <div class="col-sm-4">
                <div class="mb-3">
                    <label>{{ t('fields.frais_dossier') || 'Frais de dossier' }} (Base)</label>
                    <input type="number" v-model="form.frais_dossier" class="form-control" step="0.01" min="0" :disabled="isReadOnly" />
                </div>
            </div>
            <div class="col-sm-4">
                <div class="mb-3">
                    <label>{{ t('fields.frais_dossier_paye') || 'Frais dossier payé' }}</label>
                    <input type="number" v-model="form.frais_dossier_paye" class="form-control" step="0.01" min="0" :disabled="isReadOnly" />
                </div>
            </div>
            <div class="col-sm-4">
                <div class="mb-3">
                    <label>{{ t('fields.frais_dossier_restant') || 'Reste dossier' }}</label>
                    <input type="text" :value="fraisDossierRestant.toFixed(2)" class="form-control" readonly />
                </div>
            </div>

            <!-- Frais d'inscription -->
            <div class="col-sm-4">
                <div class="mb-3">
                    <label>{{ t('fields.frais_inscription') || 'Frais d\'inscription' }} (Base)</label>
                    <input type="number" v-model="form.frais_inscription" class="form-control" step="0.01" min="0" :disabled="isReadOnly" />
                </div>
            </div>
            <div class="col-sm-4">
                <div class="mb-3">
                    <label>{{ t('fields.frais_inscription_paye') || 'Frais inscription payé' }}</label>
                    <input type="number" v-model="form.frais_inscription_paye" class="form-control" step="0.01" min="0" :disabled="isReadOnly" />
                </div>
            </div>
            <div class="col-sm-4">
                <div class="mb-3">
                    <label>{{ t('fields.frais_inscription_restant') || 'Reste inscription' }}</label>
                    <input type="text" :value="fraisInscriptionRestant.toFixed(2)" class="form-control" readonly />
                </div>
            </div>

            <!-- Frais de scolarité -->
            <div class="col-sm-4">
                <div class="mb-3">
                    <label>{{ t('fields.frais_scolarite') || 'Frais de scolarité' }} (Base)</label>
                    <input type="number" v-model="form.frais_scolarite" class="form-control" step="0.01" min="0" :disabled="isReadOnly" />
                </div>
            </div>
            <div class="col-sm-4">
                <div class="mb-3">
                    <label>{{ t('fields.frais_scolarite_paye') || 'Frais scolarité payé' }}</label>
                    <input type="number" v-model="form.frais_scolarite_paye" class="form-control" step="0.01" min="0" :disabled="isReadOnly" />
                </div>
            </div>
            <div class="col-sm-4">
                <div class="mb-3">
                    <label>{{ t('fields.frais_scolarite_restant') || 'Reste scolarité' }}</label>
                    <input type="text" :value="fraisScolariteRestant.toFixed(2)" class="form-control" readonly />
                </div>
            </div>

            <!-- Totaux -->
            <div class="col-sm-6">
                <div class="mb-3 p-3 bg-light rounded">
                    <strong>{{ t('fields.total_paye') || 'Total payé' }}: {{ totalPaye.toFixed(2) }}</strong>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-3 p-3 bg-light rounded">
                    <strong>{{ t('fields.total_restant') || 'Total restant' }}: {{ totalRestant.toFixed(2) }}</strong>
                </div>
            </div>
        </div>

        <!-- Section 4: Dossier scolaire -->
        <h5 class="section-title mb-3 mt-4">{{ t('fields.school_folder') || 'Dossier scolaire' }}</h5>
        <div class="row g-3">
            <div class="col-sm-12">
                <div class="mb-3">
                    <label class="form-check-label">
                        <input type="checkbox" v-model="form.dossier_complet" class="form-check-input" :disabled="isReadOnly" />
                        {{ t('fields.dossier_complet') || 'Dossier complet' }}
                    </label>
                </div>
            </div>

            <!-- Fichiers -->
            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('fields.fiche_inscription') || 'Fiche d\'inscription' }}</label>
                    <input
                        v-if="!isReadOnly"
                        type="file"
                        @change="handleFileUpload('fiche_inscription', $event)"
                        class="form-control"
                        accept=".pdf,.jpg,.jpeg,.png"
                    />
                    <div v-else-if="form.fiche_inscription">
                        <a :href="getFileUrl(form.fiche_inscription)" target="_blank" class="btn btn-sm btn-link">
                            <i class="fa fa-download"></i> {{ t('actions.download') || 'Télécharger' }}
                        </a>
                    </div>
                    <span v-if="form.errors?.fiche_inscription" class="text-danger"><strong>{{ form.errors.fiche_inscription }}</strong></span>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('fields.carnet_vaccination') || 'Carnet de vaccination' }}</label>
                    <input
                        v-if="!isReadOnly"
                        type="file"
                        @change="handleFileUpload('carnet_vaccination', $event)"
                        class="form-control"
                        accept=".pdf,.jpg,.jpeg,.png"
                    />
                    <div v-else-if="form.carnet_vaccination">
                        <a :href="getFileUrl(form.carnet_vaccination)" target="_blank" class="btn btn-sm btn-link">
                            <i class="fa fa-download"></i> {{ t('actions.download') || 'Télécharger' }}
                        </a>
                    </div>
                    <span v-if="form.errors?.carnet_vaccination" class="text-danger"><strong>{{ form.errors.carnet_vaccination }}</strong></span>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('fields.photos_4x4') || 'Photos 4x4' }}</label>
                    <input
                        v-if="!isReadOnly"
                        type="file"
                        @change="handleFileUpload('photos_4x4', $event)"
                        class="form-control"
                        accept=".jpg,.jpeg,.png"
                    />
                    <div v-else-if="form.photos_4x4">
                        <a :href="getFileUrl(form.photos_4x4)" target="_blank" class="btn btn-sm btn-link">
                            <i class="fa fa-download"></i> {{ t('actions.download') || 'Télécharger' }}
                        </a>
                    </div>
                    <span v-if="form.errors?.photos_4x4" class="text-danger"><strong>{{ form.errors.photos_4x4 }}</strong></span>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('fields.copie_acte_naissance') || 'Copie acte de naissance' }}</label>
                    <input
                        v-if="!isReadOnly"
                        type="file"
                        @change="handleFileUpload('copie_acte_naissance', $event)"
                        class="form-control"
                        accept=".pdf,.jpg,.jpeg,.png"
                    />
                    <div v-else-if="form.copie_acte_naissance">
                        <a :href="getFileUrl(form.copie_acte_naissance)" target="_blank" class="btn btn-sm btn-link">
                            <i class="fa fa-download"></i> {{ t('actions.download') || 'Télécharger' }}
                        </a>
                    </div>
                    <span v-if="form.errors?.copie_acte_naissance" class="text-danger"><strong>{{ form.errors.copie_acte_naissance }}</strong></span>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('fields.piece1') || 'Pièce justificative 1' }}</label>
                    <input
                        v-if="!isReadOnly"
                        type="file"
                        @change="handleFileUpload('piece1', $event)"
                        class="form-control"
                        accept=".pdf,.jpg,.jpeg,.png"
                    />
                    <div v-else-if="form.piece1">
                        <a :href="getFileUrl(form.piece1)" target="_blank" class="btn btn-sm btn-link">
                            <i class="fa fa-download"></i> {{ t('actions.download') || 'Télécharger' }}
                        </a>
                    </div>
                    <span v-if="form.errors?.piece1" class="text-danger"><strong>{{ form.errors.piece1 }}</strong></span>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('fields.piece2') || 'Pièce justificative 2' }}</label>
                    <input
                        v-if="!isReadOnly"
                        type="file"
                        @change="handleFileUpload('piece2', $event)"
                        class="form-control"
                        accept=".pdf,.jpg,.jpeg,.png"
                    />
                    <div v-else-if="form.piece2">
                        <a :href="getFileUrl(form.piece2)" target="_blank" class="btn btn-sm btn-link">
                            <i class="fa fa-download"></i> {{ t('actions.download') || 'Télécharger' }}
                        </a>
                    </div>
                    <span v-if="form.errors?.piece2" class="text-danger"><strong>{{ form.errors.piece2 }}</strong></span>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('fields.piece3') || 'Pièce justificative 3' }}</label>
                    <input
                        v-if="!isReadOnly"
                        type="file"
                        @change="handleFileUpload('piece3', $event)"
                        class="form-control"
                        accept=".pdf,.jpg,.jpeg,.png"
                    />
                    <div v-else-if="form.piece3">
                        <a :href="getFileUrl(form.piece3)" target="_blank" class="btn btn-sm btn-link">
                            <i class="fa fa-download"></i> {{ t('actions.download') || 'Télécharger' }}
                        </a>
                    </div>
                    <span v-if="form.errors?.piece3" class="text-danger"><strong>{{ form.errors.piece3 }}</strong></span>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('fields.piece4') || 'Pièce justificative 4' }}</label>
                    <input
                        v-if="!isReadOnly"
                        type="file"
                        @change="handleFileUpload('piece4', $event)"
                        class="form-control"
                        accept=".pdf,.jpg,.jpeg,.png"
                    />
                    <div v-else-if="form.piece4">
                        <a :href="getFileUrl(form.piece4)" target="_blank" class="btn btn-sm btn-link">
                            <i class="fa fa-download"></i> {{ t('actions.download') || 'Télécharger' }}
                        </a>
                    </div>
                    <span v-if="form.errors?.piece4" class="text-danger"><strong>{{ form.errors.piece4 }}</strong></span>
                </div>
            </div>
        </div>

        <!-- File Preview Section -->
        <div v-if="selectedFiles.length > 0" class="mt-5 pt-4 border-top">
            <h5 class="section-title mb-4">
                <i class="fa fa-file"></i> {{ t('fields.files') || 'Fichiers sélectionnés' }}
                <span class="badge bg-primary ms-2">{{ selectedFiles.length }}</span>
            </h5>

            <div class="row g-3">
                <div v-for="file in selectedFiles" :key="file.key" class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0 preview-card">
                        <!-- Image Preview -->
                        <div v-if="file.data.isImage" class="position-relative overflow-hidden" style="height: 150px; background: #f8f9fa;">
                            <img
                                :src="file.data.preview"
                                :alt="file.label"
                                class="w-100 h-100 object-fit-cover"
                                style="object-fit: cover;"
                            />
                        </div>

                        <!-- PDF/File Icon Preview -->
                        <div v-else class="d-flex align-items-center justify-content-center" style="height: 150px; background: #f8f9fa;">
                            <div class="text-center">
                                <i v-if="file.data.isPdf" class="fa fa-file-pdf text-danger" style="font-size: 3rem;"></i>
                                <i v-else class="fa fa-file text-secondary" style="font-size: 3rem;"></i>
                            </div>
                        </div>

                        <!-- File Info -->
                        <div class="card-body">
                            <h6 class="card-title text-truncate mb-2" :title="file.label">
                                {{ file.label }}
                            </h6>
                            <small class="text-muted d-block">
                                <i class="fa fa-file-o"></i> {{ file.data.name }}
                            </small>
                            <small class="text-muted d-block">
                                <i class="fa fa-hdd-o"></i> {{ file.data.size }}
                            </small>
                        </div>

                        <!-- File Actions -->
                        <div class="card-footer bg-light border-0">
                            <a
                                v-if="file.data.isImage || file.data.isPdf"
                                :href="file.data.preview"
                                target="_blank"
                                class="btn btn-sm btn-outline-primary w-100"
                            >
                                <i class="fa fa-eye"></i> {{ t('actions.preview') || 'Aperçu' }}
                            </a>
                            <small v-else class="text-muted d-block text-center mt-2">
                                Fichier sélectionné
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 0.5rem;
}

.preview-card {
    transition: all 0.3s ease;
    border-radius: 0.5rem;
}

.preview-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

.preview-card img {
    object-fit: cover;
    width: 100%;
    height: 100%;
}

.card-title {
    font-size: 0.95rem;
    margin-bottom: 0.5rem;
}

.object-fit-cover {
    object-fit: cover;
}
</style>
