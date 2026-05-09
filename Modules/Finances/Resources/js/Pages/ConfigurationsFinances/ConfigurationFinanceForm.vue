<template>
    <form @submit.prevent="submitForm" class="form-container">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ t('common.code') }} *</label>
                    <input
                        v-model="form.code"
                        type="text"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.code }"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors.code" class="invalid-feedback">
                        {{ form.errors.code }}
                    </span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ t('common.libelle') }} *</label>
                    <input
                        v-model="form.libelle"
                        type="text"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.libelle }"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors.libelle" class="invalid-feedback">
                        {{ form.errors.libelle }}
                    </span>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ t('common.type') }} *</label>
                    <StylishSelect
                        v-model="form.type"
                        :options="types"
                        option-value="value"
                        option-label="label"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors.type" class="invalid-feedback d-block">
                        {{ form.errors.type }}
                    </span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ t('common.valeur') }} *</label>
                    <input
                        v-model="form.valeur"
                        :type="getInputType()"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.valeur }"
                        :placeholder="getPlaceholder()"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors.valeur" class="invalid-feedback">
                        {{ form.errors.valeur }}
                    </span>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>{{ t('common.description') }}</label>
                    <textarea
                        v-model="form.description"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.description }"
                        rows="3"
                        :disabled="isReadOnly"
                    ></textarea>
                    <span v-if="form.errors.description" class="invalid-feedback">
                        {{ form.errors.description }}
                    </span>
                </div>
            </div>
        </div>
        <div class="row" v-if="!isReadOnly">
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ t('common.statut') }} *</label>
                    <StylishSelect
                        v-model="form.statut"
                        :options="[
                            { value: 'actif', label: 'Actif' },
                            { value: 'inactif', label: 'Inactif' },
                        ]"
                        option-value="value"
                        option-label="label"
                    />
                    <span v-if="form.errors.statut" class="invalid-feedback d-block">
                        {{ form.errors.statut }}
                    </span>
                </div>
            </div>
        </div>
        <div v-if="!isReadOnly" class="form-actions">
            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                <i class="fa fa-save"></i>
                {{ isEditMode ? t('actions.update') : t('actions.create') }}
            </button>
            <Link :href="route('finances.configurations-finances.index')" class="btn btn-secondary">
                {{ t('actions.cancel') }}
            </Link>
        </div>
        <div v-else class="form-actions">
            <Link :href="route('finances.configurations-finances.edit', configuration.id)" class="btn btn-primary">
                <i class="fa fa-edit"></i> <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
            </Link>
            <Link :href="route('finances.configurations-finances.index')" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
            </Link>
        </div>
    </form>
</template>
<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
const { t } = useI18n();
const props = defineProps({
    configuration: {
        type: Object,
        default: null,
    },
    types: {
        type: Array,
        required: true,
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});
const emit = defineEmits(['submitted']);
const isReadOnly = computed(() => props.mode === 'show');
const isEditMode = computed(() => props.mode === 'edit');
const form = useForm({
    code: props.configuration?.code || '',
    libelle: props.configuration?.libelle || '',
    valeur: props.configuration?.valeur || '',
    type: props.configuration?.type || 'texte',
    description: props.configuration?.description || '',
    statut: props.configuration?.statut || 'actif',
});
function getInputType() {
    switch (form.type) {
        case 'monetaire':
        case 'nombre':
            return 'number';
        case 'pourcentage':
            return 'number';
        case 'booleen':
            return 'hidden';
        default:
            return 'text';
    }
}
function getPlaceholder() {
    const placeholders = {
        texte: 'Entrez une valeur texte',
        monetaire: 'Entrez un montant (XOF)',
        pourcentage: 'Entrez un pourcentage (%)',
        nombre: 'Entrez un nombre',
        booleen: 'Sélectionnez Oui ou Non',
}
    return placeholders[form.type] || 'Entrez une valeur';
}
function submitForm() {
    if (isEditMode.value) {
        form.post(route('finances.configurations-finances.update', props.configuration.id), {
            method: 'put',
            onSuccess: () => {
                emit('submitted');
            },
        });
    } else {
        form.post(route('finances.configurations-finances.store'), {
            onSuccess: () => {
                emit('submitted');
            },
        });
    }
}
</script>
<style scoped>
.form-container {
    background: white;
    padding: 20px;
    border-radius: 5px;
    margin-bottom: 20px;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    font-weight: 600;
    margin-bottom: 5px;
    display: block;
}
.form-group input,
.form-group textarea,
.form-group select {
    border: 1px solid #ddd;
    border-radius: 3px;
}
.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
.form-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
}
.form-actions .btn {
    min-width: 120px;
}
.invalid-feedback {
    display: block;
    color: #dc3545;
    font-size: 12px;
    margin-top: 5px;
}
.is-invalid {
    border-color: #dc3545 !important;
}
</style>
