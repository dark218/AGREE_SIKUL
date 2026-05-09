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
                        :placeholder="t('placeholders.titre') || 'Entrez le titre de l\'annonce'"
                        required
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
                        :placeholder="t('placeholders.contenu') || 'Entrez le contenu de l\'annonce'"
                        required
                    ></textarea>
                    <div v-if="form.errors?.contenu" class="invalid-feedback d-block">
                        {{ form.errors.contenu[0] || form.errors.contenu }}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="date_publication">{{ t('fields.date_publication') || 'Date de publication' }}</label>
                    <input
                        id="date_publication"
                        v-model="form.date_publication"
                        type="date"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors?.date_publication }"
                        :disabled="isReadOnly"
                    />
                    <div v-if="form.errors?.date_publication" class="invalid-feedback d-block">
                        {{ form.errors.date_publication[0] || form.errors.date_publication }}
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="date_fin_publication">{{ t('fields.date_fin_publication') || 'Date de fin de publication' }}</label>
                    <input
                        id="date_fin_publication"
                        v-model="form.date_fin_publication"
                        type="date"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors?.date_fin_publication }"
                        :disabled="isReadOnly"
                    />
                    <div v-if="form.errors?.date_fin_publication" class="invalid-feedback d-block">
                        {{ form.errors.date_fin_publication[0] || form.errors.date_fin_publication }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statut Section -->
    <div class="section">
        <h6 class="section-title">{{ t('common.status') || 'Statut' }}</h6>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="statut">{{ t('common.status') || 'Statut' }} <span class="text-danger">*</span></label>
                    <select
                        id="statut"
                        v-model="form.statut"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors?.statut }"
                        :disabled="isReadOnly"
                        required
                    >
                        <option value="">{{ t('placeholders.select_option') || 'Sélectionner un statut' }}</option>
                        <option value="active">{{ t('common.active') || 'Actif' }}</option>
                        <option value="inactive">{{ t('common.inactive') || 'Inactif' }}</option>
                        <option value="archive">{{ t('common.archive') || 'Archivé' }}</option>
                    </select>
                    <div v-if="form.errors?.statut" class="invalid-feedback d-block">
                        {{ form.errors.statut[0] || form.errors.statut }}
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
