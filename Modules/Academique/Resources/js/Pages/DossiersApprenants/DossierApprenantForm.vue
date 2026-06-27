<script setup>
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import InheritedContextBar from '@/Components/Common/InheritedContextBar.vue';
import { useApprenantCascade } from '@/Composables/useApprenantCascade';
const { t } = useI18n();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    apprenants: Array,
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});
const isReadOnly = computed(() => props.mode === 'show');
const isEditMode = computed(() => props.mode === 'edit');

// Cascade : Apprenant → Classe + École + Campus + ...
useApprenantCascade(props.form, () => props.apprenants);

// Convert Proxy(Array) to normal array to fix SearchableSelect
const appartenantsList = computed(() => {
    if (!props.apprenants) return [];
    return Array.isArray(props.apprenants) ? props.apprenants : [...props.apprenants];
});

// Get selected apprenant full data for auto-fill
const selectedApprenant = computed(() => {
    if (!props.form?.apprenant_id) return null;
    const apprenant = appartenantsList.value.find(a => {
        // Handle both number and string IDs
        return String(a.id) === String(props.form.apprenant_id);
    });
    if (!apprenant) {
        console.warn('⚠️ Apprenant not found in list for ID:', props.form.apprenant_id);
    }
    return apprenant;
});

// Auto-fill apprenant info when selected
watch(() => props.form?.apprenant_id, (newVal) => {
    if (newVal && selectedApprenant.value) {
        console.log('📋 Auto-filling apprenant info:', selectedApprenant.value);
    }
}, { immediate: true });

console.log('🔍 DossierApprenantForm - Props received:', {
    apprenants_count: props.apprenants?.length || 0,
    apprenants_list_count: appartenantsList.value?.length || 0,
    apprenants: props.apprenants,
    apprenants_converted: appartenantsList.value,
    mode: props.mode,
    form_apprenant_id: props.form?.apprenant_id,
});

// Check if value is a File object
const isFile = (value) => {
    return value && typeof value === 'object' && value.constructor?.name === 'File';
};

// File upload handler
const handleFileUpload = (field, event) => {
    const file = event.target.files?.[0];
    if (file) {
        props.form[field] = file;
        console.log(`✅ File selected for ${field}:`, file.name, file.size);
    }
};

// Get file preview data
const getFilePreviewData = (field) => {
    const file = props.form[field];
    if (!file) return null;

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

// File fields configuration
const fileFields = [
    { key: 'extrait_naissance', label: 'Extrait de Naissance' },
    { key: 'certificat_residence', label: 'Certificat de Résidence' },
    { key: 'dernier_bulletin', label: 'Dernier Bulletin' },
    { key: 'carnet_sante', label: 'Carnet de Santé' },
];
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- Apprenant (required) -->
        <div class="col-sm-12">
            <div class="mb-3">
                <label>{{ t('common.apprenant') || 'Apprenant' }} <span class="text-danger">*</span></label>
                <!-- Show read-only in edit/show mode -->
                <input
                    v-if="isEditMode || isReadOnly"
                    type="text"
                    class="form-control"
                    :value="selectedApprenant?.libelle || '(Apprenant non trouvé)'"
                    disabled
                    readonly
                />
                <!-- Show dropdown in create mode -->
                <SearchableSelect
                    v-else
                    v-model="form.apprenant_id"
                    :options="appartenantsList"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <small class="text-muted d-block mt-2">📋 {{ appartenantsList?.length || 0 }} apprenants disponibles</small>
                <span v-if="form.errors?.apprenant_id" class="text-danger d-block mt-2">
                    <strong>{{ form.errors.apprenant_id[0] || form.errors.apprenant_id }}</strong>
                </span>
            </div>
            <InheritedContextBar
                :source="apprenants?.find(a => String(a.id) === String(form.apprenant_id)) || null"
                title="Hérité de l'apprenant"
            />
        </div>

        <!-- File Fields Section -->
        <div class="col-sm-12 mt-4">
            <h5 class="section-title mb-3">📄 {{ t('fields.documents') || 'Fichiers Justificatifs' }}</h5>
        </div>

        <!-- Extrait de Naissance -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.extrait_naissance') || 'Extrait de Naissance' }}</label>
                <input
                    v-if="!isReadOnly"
                    type="file"
                    @change="handleFileUpload('extrait_naissance', $event)"
                    class="form-control"
                    accept=".pdf,.jpg,.jpeg,.png"
                />
                <div v-else-if="form.extrait_naissance">
                    <a :href="getFilePreviewData('extrait_naissance')?.preview" target="_blank" class="btn btn-sm btn-link">
                        <i class="fa fa-download"></i> Télécharger
                    </a>
                </div>
                <span v-if="form.errors?.extrait_naissance" class="text-danger d-block mt-2">
                    <strong>{{ form.errors.extrait_naissance[0] || form.errors.extrait_naissance }}</strong>
                </span>
                <div v-if="getFilePreviewData('extrait_naissance')" class="mt-3 p-3 border rounded">
                    <div class="d-flex align-items-start gap-3">
                        <!-- Image Preview -->
                        <div v-if="getFilePreviewData('extrait_naissance')?.isImage" class="flex-shrink-0">
                            <img
                                :src="getFilePreviewData('extrait_naissance')?.preview"
                                :alt="getFilePreviewData('extrait_naissance')?.name"
                                class="img-thumbnail"
                                style="max-width: 150px; max-height: 150px; object-fit: cover;"
                            />
                        </div>
                        <div v-else-if="getFilePreviewData('extrait_naissance')?.isPdf" class="flex-shrink-0">
                            <div class="bg-danger text-white p-3 rounded text-center" style="width: 150px; height: 150px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa fa-file-pdf-o fa-3x"></i>
                            </div>
                        </div>
                        <!-- File Info -->
                        <div class="flex-grow-1">
                            <p class="mb-1"><strong>{{ getFilePreviewData('extrait_naissance')?.name }}</strong></p>
                            <p class="mb-0 text-muted small">{{ getFilePreviewData('extrait_naissance')?.size }}</p>
                            <a v-if="isFile(form.extrait_naissance)" :href="getFilePreviewData('extrait_naissance')?.preview" target="_blank" class="btn btn-sm btn-link mt-2">
                                <i class="fa fa-eye"></i> Aperçu
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Certificat de Résidence -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.certificat_residence') || 'Certificat de Résidence' }}</label>
                <input
                    v-if="!isReadOnly"
                    type="file"
                    @change="handleFileUpload('certificat_residence', $event)"
                    class="form-control"
                    accept=".pdf,.jpg,.jpeg,.png"
                />
                <div v-else-if="form.certificat_residence">
                    <a :href="getFilePreviewData('certificat_residence')?.preview" target="_blank" class="btn btn-sm btn-link">
                        <i class="fa fa-download"></i> Télécharger
                    </a>
                </div>
                <span v-if="form.errors?.certificat_residence" class="text-danger d-block mt-2">
                    <strong>{{ form.errors.certificat_residence[0] || form.errors.certificat_residence }}</strong>
                </span>
                <div v-if="getFilePreviewData('certificat_residence')" class="mt-3 p-3 border rounded">
                    <div class="d-flex align-items-start gap-3">
                        <div v-if="getFilePreviewData('certificat_residence')?.isImage" class="flex-shrink-0">
                            <img :src="getFilePreviewData('certificat_residence')?.preview" :alt="getFilePreviewData('certificat_residence')?.name" class="img-thumbnail" style="max-width: 150px; max-height: 150px; object-fit: cover;" />
                        </div>
                        <div v-else-if="getFilePreviewData('certificat_residence')?.isPdf" class="flex-shrink-0">
                            <div class="bg-danger text-white p-3 rounded text-center" style="width: 150px; height: 150px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa fa-file-pdf-o fa-3x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <p class="mb-1"><strong>{{ getFilePreviewData('certificat_residence')?.name }}</strong></p>
                            <p class="mb-0 text-muted small">{{ getFilePreviewData('certificat_residence')?.size }}</p>
                            <a v-if="isFile(form.certificat_residence)" :href="getFilePreviewData('certificat_residence')?.preview" target="_blank" class="btn btn-sm btn-link mt-2">
                                <i class="fa fa-eye"></i> Aperçu
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carnet de Santé -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.carnet_sante') || 'Carnet de Santé' }}</label>
                <input
                    v-if="!isReadOnly"
                    type="file"
                    @change="handleFileUpload('carnet_sante', $event)"
                    class="form-control"
                    accept=".pdf,.jpg,.jpeg,.png"
                />
                <div v-else-if="form.carnet_sante">
                    <a :href="getFilePreviewData('carnet_sante')?.preview" target="_blank" class="btn btn-sm btn-link">
                        <i class="fa fa-download"></i> Télécharger
                    </a>
                </div>
                <span v-if="form.errors?.carnet_sante" class="text-danger d-block mt-2">
                    <strong>{{ form.errors.carnet_sante[0] || form.errors.carnet_sante }}</strong>
                </span>
                <div v-if="getFilePreviewData('carnet_sante')" class="mt-3 p-3 border rounded">
                    <div class="d-flex align-items-start gap-3">
                        <div v-if="getFilePreviewData('carnet_sante')?.isImage" class="flex-shrink-0">
                            <img :src="getFilePreviewData('carnet_sante')?.preview" :alt="getFilePreviewData('carnet_sante')?.name" class="img-thumbnail" style="max-width: 150px; max-height: 150px; object-fit: cover;" />
                        </div>
                        <div v-else-if="getFilePreviewData('carnet_sante')?.isPdf" class="flex-shrink-0">
                            <div class="bg-danger text-white p-3 rounded text-center" style="width: 150px; height: 150px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa fa-file-pdf-o fa-3x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <p class="mb-1"><strong>{{ getFilePreviewData('carnet_sante')?.name }}</strong></p>
                            <p class="mb-0 text-muted small">{{ getFilePreviewData('carnet_sante')?.size }}</p>
                            <a v-if="isFile(form.carnet_sante)" :href="getFilePreviewData('carnet_sante')?.preview" target="_blank" class="btn btn-sm btn-link mt-2">
                                <i class="fa fa-eye"></i> Aperçu
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dernier Bulletin -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.dernier_bulletin') || 'Dernier Bulletin' }}</label>
                <input
                    v-if="!isReadOnly"
                    type="file"
                    @change="handleFileUpload('dernier_bulletin', $event)"
                    class="form-control"
                    accept=".pdf,.jpg,.jpeg,.png"
                />
                <div v-else-if="form.dernier_bulletin">
                    <a :href="getFilePreviewData('dernier_bulletin')?.preview" target="_blank" class="btn btn-sm btn-link">
                        <i class="fa fa-download"></i> Télécharger
                    </a>
                </div>
                <span v-if="form.errors?.dernier_bulletin" class="text-danger d-block mt-2">
                    <strong>{{ form.errors.dernier_bulletin[0] || form.errors.dernier_bulletin }}</strong>
                </span>
                <div v-if="getFilePreviewData('dernier_bulletin')" class="mt-3 p-3 border rounded">
                    <div class="d-flex align-items-start gap-3">
                        <div v-if="getFilePreviewData('dernier_bulletin')?.isImage" class="flex-shrink-0">
                            <img :src="getFilePreviewData('dernier_bulletin')?.preview" :alt="getFilePreviewData('dernier_bulletin')?.name" class="img-thumbnail" style="max-width: 150px; max-height: 150px; object-fit: cover;" />
                        </div>
                        <div v-else-if="getFilePreviewData('dernier_bulletin')?.isPdf" class="flex-shrink-0">
                            <div class="bg-danger text-white p-3 rounded text-center" style="width: 150px; height: 150px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa fa-file-pdf-o fa-3x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <p class="mb-1"><strong>{{ getFilePreviewData('dernier_bulletin')?.name }}</strong></p>
                            <p class="mb-0 text-muted small">{{ getFilePreviewData('dernier_bulletin')?.size }}</p>
                            <a v-if="isFile(form.dernier_bulletin)" :href="getFilePreviewData('dernier_bulletin')?.preview" target="_blank" class="btn btn-sm btn-link mt-2">
                                <i class="fa fa-eye"></i> Aperçu
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
