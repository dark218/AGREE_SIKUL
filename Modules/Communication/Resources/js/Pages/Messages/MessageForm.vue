<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import ModernDropdown from '@/Components/Common/ModernDropdown.vue';

const { t } = useI18n();

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    users: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});

const isReadOnly = ref(props.mode === 'show');

// Formater les utilisateurs pour Multiselect
const userOptions = computed(() => {
    return props.users.map(user => ({
        id: parseInt(user.id) || user.id,
        label: user.label || `${user.nom} ${user.prenoms}`,
        nom: user.nom,
        prenoms: user.prenoms,
        email: user.email,
    }));
});

</script>

<template>
    <!-- Information Section -->
    <div class="section">
        <h6 class="section-title">{{ t('common.information') || 'Information' }}</h6>

        <!-- Destinataires Selection (Multiple) -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="destinataires_ids">{{ t('fields.destinataires') || 'Destinataires' }} <span class="text-danger">*</span></label>

                    <!-- Modern Dropdown pour sélectionner les destinataires -->
                    <ModernDropdown
                        v-model="form.destinataires_ids"
                        :options="userOptions"
                        placeholder="-- Sélectionner des destinataires --"
                    />

                    <div v-if="form.errors?.destinataires_ids || form.errors?.['destinataires_ids.0']" class="invalid-feedback d-block">
                        {{ form.errors?.destinataires_ids?.[0] || form.errors?.['destinataires_ids.0']?.[0] || 'Veuillez sélectionner au moins un destinataire' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Subject -->
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="objet">{{ t('fields.objet') || 'Objet' }} <span class="text-danger">*</span></label>
                    <input
                        id="objet"
                        v-model="form.objet"
                        type="text"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors?.objet }"
                        :disabled="isReadOnly"
                        :placeholder="t('placeholders.objet') || 'Entrez l\'objet du message'"
                    />
                    <div v-if="form.errors?.objet" class="invalid-feedback d-block">
                        {{ form.errors.objet[0] || form.errors.objet }}
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
                        :placeholder="t('placeholders.contenu') || 'Entrez le contenu du message'"
                    />
                    <div v-if="form.errors?.contenu" class="invalid-feedback d-block">
                        {{ form.errors.contenu[0] || form.errors.contenu }}
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
