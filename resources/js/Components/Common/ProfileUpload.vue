<script setup>
import { ref, computed } from 'vue';
import ImageZoomModal from '@/Components/Common/ImageZoomModal.vue';

const props = defineProps({
    modelValue: {
        type: [File, null],
        default: null,
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
        default: '.png, .jpg, .jpeg, .svg',
    },
    inputId: {
        type: String,
        default: () => 'profileUpload_' + Math.random().toString(36).substr(2, 9),
    },
    defaultPreview: {
        type: String,
        default: 'https://caer.univ-amu.fr/wp-content/uploads/default-placeholder.png',
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const previewUrl = ref(null);
const showZoomModal = ref(false);

const displayPreview = computed(() => {
    if (previewUrl.value) {
        return previewUrl.value;
    }
    if (props.preview) {
        return props.preview.startsWith('http') ? props.preview : '/' + props.preview;
    }
    return props.defaultPreview;
});

const hasCustomImage = computed(() => {
    return previewUrl.value || props.preview;
});

const openZoomModal = () => {
    if (hasCustomImage.value) {
        showZoomModal.value = true;
    }
};

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        emit('update:modelValue', file);
        emit('change', event);

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewUrl.value = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    }
};
</script>

<template>
    <div class="profile-settings-wrapper">
        <!-- Banner -->
        <div class="preview-thumb profile-wallpaper">
            <div class="avatar-preview">
                <div class="profilePicPreview bg_img"></div>
            </div>
        </div>

        <!-- Avatar with upload button -->
        <div class="profile-thumb-content">
            <div class="preview-thumb profile-thumb">
                <div class="avatar-preview" @click="openZoomModal" :class="{ 'clickable': hasCustomImage }">
                    <div
                        class="profilePicPreview bg_img"
                        :style="{ backgroundImage: `url(${displayPreview})` }"
                        :title="hasCustomImage ? 'Cliquez pour agrandir' : ''"
                    ></div>
                </div>
                <!-- Bouton de zoom -->
                <button
                    v-if="hasCustomImage"
                    type="button"
                    class="zoom-btn"
                    @click="openZoomModal"
                    title="Agrandir l'image"
                >
                    <i class="fa fa-search-plus"></i>
                </button>
                <div v-if="!disabled" class="avatar-edit">
                    <input
                        type="file"
                        class="profilePicUpload"
                        :id="inputId"
                        :accept="accept"
                        @change="handleFileChange"
                        :disabled="disabled"
                    />
                    <label :for="inputId">
                        <i class="fa fa-upload"></i>
                    </label>
                </div>
            </div>
        </div>

        <!-- Modal de zoom -->
        <ImageZoomModal
            :show="showZoomModal"
            :image-url="displayPreview"
            alt="Photo de profil"
            @close="showZoomModal = false"
        />
    </div>
</template>

<style scoped>
.profile-settings-wrapper {
    position: relative;
    margin-bottom: 20px;
}

.preview-thumb.profile-wallpaper {
    height: 120px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 8px 8px 0 0;
}

.preview-thumb.profile-wallpaper .avatar-preview {
    height: 100%;
}

.preview-thumb.profile-wallpaper .profilePicPreview {
    height: 100%;
    background-size: cover;
    background-position: center;
}

.profile-thumb-content {
    display: flex;
    align-items: flex-end;
    margin-top: 4%;
}



.preview-thumb.profile-thumb {
    position: relative;
    display: inline-block;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: visible;
    border: 4px solid #fff;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    background-color: #fff;
}

.preview-thumb.profile-thumb .avatar-preview {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    overflow: hidden;
}

.preview-thumb.profile-thumb .profilePicPreview {
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

.avatar-edit {
    position: absolute;
    bottom: 0;
    right: 0;
    z-index: 10;
}

.avatar-edit input {
    display: none;
}

.avatar-edit label {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    margin-bottom: 0;
    border-radius: 50%;
    background: #007bff;
    color: #fff;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.avatar-edit label:hover {
    background: #0056b3;
}

.bg_img {
    background-color: #f5f5f5;
}

.avatar-preview.clickable {
    cursor: pointer;
}

.avatar-preview.clickable:hover .profilePicPreview {
    opacity: 0.8;
}

.zoom-btn {
    position: absolute;
    top: 0;
    left: 0;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #17a2b8;
    color: #fff;
    border: none;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.zoom-btn:hover {
    background: #138496;
}
</style>
