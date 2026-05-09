<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import ImageZoomModal from '@/Components/Common/ImageZoomModal.vue';

const { t } = useI18n();

const props = defineProps({
    modelValue: {
        type: [File, null],
        default: null,
    },
    label: {
        type: String,
        default: 'Fichier',
    },
    preview: {
        type: String,
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    accept: {
        type: String,
        default: 'image/*',
    },
});

const emit = defineEmits(['update:modelValue']);

const fileInput = ref(null);
const previewUrl = ref(null);
const fileName = ref(null);
const showZoomModal = ref(false);

const displayPreview = computed(() => {
    if (previewUrl.value) {
        return previewUrl.value;
    }
    if (props.preview) {
        return props.preview.startsWith('http') ? props.preview : '/' + props.preview;
    }
    return null;
});

const openZoomModal = () => {
    if (displayPreview.value) {
        showZoomModal.value = true;
    }
};

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        emit('update:modelValue', file);
        fileName.value = file.name;

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewUrl.value = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    }
};

const triggerFileInput = () => {
    if (!props.disabled && fileInput.value) {
        fileInput.value.click();
    }
};

const clearFile = () => {
    emit('update:modelValue', null);
    previewUrl.value = null;
    fileName.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};
</script>

<template>
    <div class="file-upload-wrapper mb-3">
        <label class="form-label">{{ label }}</label>

        <div class="file-upload-container" :class="{ 'disabled': disabled }">
            <!-- Preview area -->
            <div v-if="displayPreview" class="preview-area mb-2">
                <img
                    :src="displayPreview"
                    alt="Preview"
                    class="preview-image"
                    @click="openZoomModal"
                    title="Cliquez pour agrandir"
                />
                <button
                    type="button"
                    class="btn btn-sm btn-info zoom-btn"
                    @click="openZoomModal"
                    title="Agrandir l'image"
                >
                    <i class="fa fa-search-plus"></i>
                </button>
                <button
                    v-if="!disabled"
                    type="button"
                    class="btn btn-sm btn-danger remove-btn"
                    @click="clearFile"
                >
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <!-- Placeholder when no preview -->
            <div v-else class="placeholder-area mb-2" @click="triggerFileInput">
                <i class="fa fa-image fa-3x text-muted"></i>
                <p class="text-muted mt-2 mb-0">{{ t('actions.clickToUpload') }}</p>
            </div>

            <!-- File input -->
            <div class="d-flex align-items-center" v-if="!disabled">
                <input
                    ref="fileInput"
                    type="file"
                    class="d-none"
                    :accept="accept"
                    @change="handleFileChange"
                    :disabled="disabled"
                />
                <button
                    type="button"
                    class="btn btn-outline-primary btn-sm"
                    @click="triggerFileInput"
                >
                    {{ t('actions.chooseFile') }}
                </button>
                <span v-if="fileName" class="ms-2 text-muted small">{{ fileName }}</span>
                <span v-else class="ms-2 text-muted small">{{ t('common.noFileChosen') }}</span>
            </div>
        </div>

        <!-- Modal de zoom -->
        <ImageZoomModal
            :show="showZoomModal"
            :image-url="displayPreview"
            :alt="label"
            @close="showZoomModal = false"
        />
    </div>
</template>

<style scoped>
.file-upload-wrapper {
    width: 100%;
}

.file-upload-container {
    border: 1px dashed #ddd;
    border-radius: 8px;
    padding: 15px;
    background-color: #f9f9f9;
}

.file-upload-container.disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.preview-area {
    position: relative;
    display: inline-block;
}

.preview-image {
    max-width: 150px;
    max-height: 150px;
    border-radius: 4px;
    object-fit: cover;
    cursor: pointer;
    transition: opacity 0.3s;
}

.preview-image:hover {
    opacity: 0.8;
}

.zoom-btn {
    position: absolute;
    top: -8px;
    left: -8px;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.remove-btn {
    position: absolute;
    top: -8px;
    right: -8px;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.placeholder-area {
    cursor: pointer;
    text-align: center;
    padding: 20px;
    border: 2px dashed #ddd;
    border-radius: 8px;
    background-color: #fff;
    transition: border-color 0.3s;
}

.placeholder-area:hover {
    border-color: #007bff;
}

.disabled .placeholder-area {
    cursor: not-allowed;
}
</style>
