<script setup>
import { ref, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Rejeter',
    },
    message: {
        type: String,
        default: '',
    },
    motifLabel: {
        type: String,
        default: '',
    },
    confirmText: {
        type: String,
        default: '',
    },
    confirmClass: {
        type: String,
        default: 'btn-danger',
    },
});

const emit = defineEmits(['close', 'confirm']);

const motif = ref('');

// Utiliser les props personnalisées ou les valeurs par défaut
const motifLabelText = computed(() => props.motifLabel || t('fields.rejectReason'));
const confirmButtonText = computed(() => props.confirmText || t('actions.reject'));

watch(() => props.show, (newVal) => {
    if (newVal) {
        motif.value = '';
    }
});

const handleConfirm = () => {
    if (motif.value.trim()) {
        emit('confirm', motif.value);
    }
};

const handleClose = () => {
    emit('close');
};
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="modal-backdrop fade show"></div>
        <div v-if="show" class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ title }}</h5>
                        <button type="button" class="btn-close" @click="handleClose"></button>
                    </div>
                    <div class="modal-body">
                        <p v-if="message" class="text-muted mb-3">{{ message }}</p>
                        <div class="mb-3">
                            <label class="form-label">{{ motifLabelText }} <span class="text-danger">*</span></label>
                            <textarea
                                v-model="motif"
                                class="form-control"
                                rows="4"
                                :placeholder="t('fields.motifPlaceholder')"
                                required
                            ></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-dark" @click="handleClose">
                            {{ t('actions.cancel') }}
                        </button>
                        <button
                            type="button"
                            :class="['btn', confirmClass]"
                            @click="handleConfirm"
                            :disabled="!motif.trim()"
                        >
                            {{ confirmButtonText }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1040;
}
.modal {
    z-index: 1050;
}
</style>
