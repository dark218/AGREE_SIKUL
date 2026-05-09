<script setup>
import { ref } from 'vue';
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

const isReadOnly = ref(props.mode === 'show');
</script>

<template>
    <!-- Information Section -->
    <div class="section">
        <h6 class="section-title">{{ t('common.information') || 'Information' }}</h6>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="titre">{{ t('fields.titre') || 'Titre' }} <span class="text-danger">*</span></label>
                    <input
                        id="titre"
                        v-model="form.titre"
                        type="text"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors?.titre }"
                        :disabled="isReadOnly"
                        :placeholder="t('placeholders.titre') || 'Entrez le titre de la notification'"
                    />
                    <div v-if="form.errors?.titre" class="invalid-feedback d-block">
                        {{ form.errors.titre[0] || form.errors.titre }}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="contenu">{{ t('fields.contenu') || 'Contenu' }} <span class="text-danger">*</span></label>
                    <textarea
                        id="contenu"
                        v-model="form.contenu"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors?.contenu }"
                        :disabled="isReadOnly"
                        rows="5"
                        :placeholder="t('placeholders.contenu') || 'Entrez le contenu de la notification'"
                    />
                    <div v-if="form.errors?.contenu" class="invalid-feedback d-block">
                        {{ form.errors.contenu[0] || form.errors.contenu }}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="type">{{ t('fields.type') || 'Type' }}</label>
                    <input
                        id="type"
                        v-model="form.type"
                        type="text"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors?.type }"
                        :disabled="isReadOnly"
                        :placeholder="t('placeholders.type') || 'Ex: info, warning, error'"
                    />
                    <div v-if="form.errors?.type" class="invalid-feedback d-block">
                        {{ form.errors.type[0] || form.errors.type }}
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="lue">{{ t('fields.lue') || 'Lue' }}</label>
                    <select
                        id="lue"
                        v-model="form.lue"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors?.lue }"
                        :disabled="isReadOnly"
                    >
                        <option :value="false">{{ t('common.no') || 'Non' }}</option>
                        <option :value="true">{{ t('common.yes') || 'Oui' }}</option>
                    </select>
                    <div v-if="form.errors?.lue" class="invalid-feedback d-block">
                        {{ form.errors.lue[0] || form.errors.lue }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- État Section -->
    <div class="section">
        <h6 class="section-title">{{ t('common.status') || 'État' }}</h6>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="etat">{{ t('common.status') || 'État' }}</label>
                    <select
                        id="etat"
                        v-model="form.etat"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors?.etat }"
                        :disabled="isReadOnly"
                    >
                        <option value="actif">{{ t('common.actif') || 'Actif' }}</option>
                        <option value="inactif">{{ t('common.inactif') || 'Inactif' }}</option>
                    </select>
                    <div v-if="form.errors?.etat" class="invalid-feedback d-block">
                        {{ form.errors.etat[0] || form.errors.etat }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.section {
    margin-bottom: 24px;
    padding-bottom: 24px;
    border-bottom: 1px solid #e0e0e0;
}

.section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.section-title {
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    color: #333;
    margin-bottom: 16px;
    letter-spacing: 0.5px;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 6px;
    font-size: 14px;
    color: #333;
}

.form-control {
    height: 36px;
    font-size: 14px;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 6px 10px;
    transition: all 0.3s ease;
}

textarea.form-control {
    height: auto;
    resize: vertical;
    padding: 10px;
}

.form-control:focus {
    border-color: #0B5697;
    box-shadow: 0 0 0 3px rgba(11, 86, 151, 0.1);
}

.form-control:disabled {
    background-color: #f5f5f5;
    color: #999;
    cursor: not-allowed;
}

.form-control.is-invalid {
    border-color: #dc3545;
}

.form-control.is-invalid:focus {
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
}

.invalid-feedback {
    color: #dc3545;
    font-size: 12px;
    margin-top: 4px;
}

.text-danger {
    color: #dc3545;
}
</style>
