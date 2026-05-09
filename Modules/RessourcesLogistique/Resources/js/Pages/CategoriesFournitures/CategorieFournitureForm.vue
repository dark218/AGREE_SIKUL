<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});

const isReadOnly = props.mode === 'show';
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Code -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code') || 'Code' }}</label>
                <input
                    v-model="form.code"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.code') || 'Code'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.code" class="text-danger">
                    <strong>{{ form.errors.code }}</strong>
                </span>
            </div>
        </div>

        <!-- Libellé -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.libelle') || 'Libellé' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.libelle"
                    type="text"
                    class="form-control"
                    :placeholder="t('common.libelle') || 'Libellé'"
                    required
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.libelle" class="text-danger">
                    <strong>{{ form.errors.libelle }}</strong>
                </span>
            </div>
        </div>

        <!-- Description -->
        <div class="col-sm-12">
            <div class="mb-3">
                <label>{{ t('fields.description') || 'Description' }}</label>
                <textarea
                    v-model="form.description"
                    class="form-control"
                    :placeholder="t('fields.description') || 'Description'"
                    rows="3"
                    :disabled="isReadOnly"
                ></textarea>
                <span v-if="form.errors?.description" class="text-danger">
                    <strong>{{ form.errors.description }}</strong>
                </span>
            </div>
        </div>

        <!-- Statut -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.statut') || 'Statut' }}</label>
                <select v-model="form.statut" class="form-control" :disabled="isReadOnly">
                    <option value="actif">{{ t('statut.actif') || 'Actif' }}</option>
                    <option value="inactif">{{ t('statut.inactif') || 'Inactif' }}</option>
                </select>
                <span v-if="form.errors?.statut" class="text-danger">
                    <strong>{{ form.errors.statut }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
</style>
