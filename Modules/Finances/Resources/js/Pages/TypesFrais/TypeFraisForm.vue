<template>
    <form @submit.prevent="submit" class="type-frais-form">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ t('common.nom') }} <span class="text-danger">*</span></label>
                    <input
                        v-model="form.nom"
                        type="text"
                        class="form-control"
                        :class="{ 'is-invalid': errors.nom }"
                        :disabled="isReadOnly"
                        required
                    />
                    <div v-if="errors.nom" class="invalid-feedback">
                        {{ errors.nom[0] || errors.nom }}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ t('common.code') }}</label>
                    <input
                        v-model="form.code"
                        type="text"
                        class="form-control"
                        :class="{ 'is-invalid': errors.code }"
                        :disabled="isReadOnly"
                    />
                    <div v-if="errors.code" class="invalid-feedback">
                        {{ errors.code[0] || errors.code }}
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>{{ t('common.statut') }}</label>
                    <select
                        v-model="form.statut"
                        class="form-control"
                        :class="{ 'is-invalid': errors.statut }"
                        :disabled="isReadOnly"
                    >
                        <option value="actif">Actif</option>
                        <option value="inactif">Inactif</option>
                    </select>
                    <div v-if="errors.statut" class="invalid-feedback">
                        {{ errors.statut[0] || errors.statut }}
                    </div>
                </div>
            </div>
        </div>
        <div v-if="!isReadOnly" class="form-actions mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> {{ submitButtonLabel }}
            </button>
            <Link :href="route('finances.type-frais.index')" class="btn btn-secondary ms-2">
                {{ t('common.cancel') }}
            </Link>
        </div>
    </form>
</template>
<script setup>
import { ref } from 'vue';
import Select2 from '@/Components/Common/Select2.vue';
import { useI18n } from 'vue-i18n';
import { Link } from '@inertiajs/vue3';
const { t } = useI18n();
const props = defineProps({
    typeFrais: {
        type: Object,
        default: () => ({}),
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    isReadOnly: {
        type: Boolean,
        default: false,
    },
    submitButtonLabel: {
        type: String,
        default: 'Enregistrer',
    },
});
const emit = defineEmits(['submit']);
const form = ref({
    nom: props.typeFrais?.nom || '',
    code: props.typeFrais?.code || '',
    statut: props.typeFrais?.statut || 'actif',
});
function submit() {
    emit('submit', form.value);
}
defineExpose({
    getFormData: () => form.value,
    form,
});
</script>
<style scoped>
.type-frais-form {
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
