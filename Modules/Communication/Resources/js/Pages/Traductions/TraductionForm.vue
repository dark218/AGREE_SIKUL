<script setup>
import { defineProps } from 'vue';
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

const etatOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- SECTION 1: FRANÇAIS -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-0">{{ t('common.french') || 'Français' }}</h5>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code_fr') || 'Code FR' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.code_fr"
                    :disabled="isReadOnly"
                    type="text"
                    class="form-control"
                    placeholder="Code FR"
                />
                <span v-if="form.errors?.code_fr" class="text-danger">
                    <strong>{{ form.errors.code_fr }}</strong>
                </span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.intitule_fr') || 'Intitulé FR' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.intitule_fr"
                    :disabled="isReadOnly"
                    type="text"
                    class="form-control"
                    placeholder="Intitulé FR"
                />
                <span v-if="form.errors?.intitule_fr" class="text-danger">
                    <strong>{{ form.errors.intitule_fr }}</strong>
                </span>
            </div>
        </div>

        <!-- SECTION 2: ANGLAIS -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.english') || 'Anglais' }}</h5>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code_en') || 'Code EN' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.code_en"
                    :disabled="isReadOnly"
                    type="text"
                    class="form-control"
                    placeholder="Code EN"
                />
                <span v-if="form.errors?.code_en" class="text-danger">
                    <strong>{{ form.errors.code_en }}</strong>
                </span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.intitule_en') || 'Intitulé EN' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.intitule_en"
                    :disabled="isReadOnly"
                    type="text"
                    class="form-control"
                    placeholder="Intitulé EN"
                />
                <span v-if="form.errors?.intitule_en" class="text-danger">
                    <strong>{{ form.errors.intitule_en }}</strong>
                </span>
            </div>
        </div>

        <!-- SECTION 3: OPTIONS -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.options') || 'Options' }}</h5>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.groupe') || 'Groupe' }}</label>
                <input
                    v-model="form.groupe"
                    :disabled="isReadOnly"
                    type="text"
                    class="form-control"
                    placeholder="Groupe (optional)"
                />
                <span v-if="form.errors?.groupe" class="text-danger">
                    <strong>{{ form.errors.groupe }}</strong>
                </span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.status') || 'État' }}</label>
                <select
                    v-model="form.etat"
                    :disabled="isReadOnly"
                    class="form-select"
                >
                    <option value="">-- {{ t('actions.select') || 'Sélectionner' }} --</option>
                    <option v-for="option in etatOptions" :key="option.id" :value="option.id">
                        {{ t('common.' + option.id) || option.libelle }}
                    </option>
                </select>
                <span v-if="form.errors?.etat" class="text-danger">
                    <strong>{{ form.errors.etat }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-input {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.section-title {
    font-weight: 700;
    color: #2c3e50;
    font-size: 1.15rem;
    border-bottom: 3px solid #007bff;
    padding-bottom: 0.75rem;
    margin-bottom: 1.5rem !important;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-title::before {
    content: '';
    display: inline-block;
    width: 4px;
    height: 24px;
    background: linear-gradient(180deg, #007bff 0%, #0056b3 100%);
    border-radius: 2px;
}

.row.g-3 > [class*='col-'] {
    margin-bottom: 0.5rem;
}

label {
    font-weight: 600;
    color: #495057;
    font-size: 0.95rem;
    margin-bottom: 0.6rem !important;
    display: block;
}

.form-control,
.form-select {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 0.65rem 0.875rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.form-control:focus,
.form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
}

.form-control:disabled,
.form-control[disabled],
.form-select:disabled,
.form-select[disabled] {
    background-color: #f8f9fa;
    color: #6c757d;
    cursor: not-allowed;
    border-color: #dee2e6;
}

.form-control.is-invalid {
    border-color: #dc3545;
}

.text-danger {
    color: #dc3545;
    font-size: 0.85rem;
    margin-top: 0.35rem;
    display: block;
}
</style>
