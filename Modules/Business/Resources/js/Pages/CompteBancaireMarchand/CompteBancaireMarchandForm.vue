<script setup>
import { useI18n } from 'vue-i18n';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
const { t } = useI18n();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    marchands: {
        type: Array,
        default: () => [],
    },
    banques: {
        type: Array,
        default: () => [],
    },
    is_marchand: {
        type: Boolean,
        default: false,
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
        <!-- Marchand -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.marchand') }}
                    <span v-if="!isReadOnly" class="text-danger"> *</span>
                </label>
                <StylishSelect
                    v-if="!isReadOnly"
                    v-model="form.marchand_id"
                    :options="marchands"
                    option-value="id"
                    option-label="raison_sociale"
                    :placeholder="t('fields.marchand')"
                    :disabled="is_marchand"
                />
                <input
                    v-else
                    type="text"
                    :value="marchands.find(m => m.id === form.marchand_id)?.raison_sociale || '-'"
                    class="form-control"
                    disabled
                />
                <span v-if="form.errors?.marchand_id" class="text-danger">
                    <strong>{{ form.errors.marchand_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Banque -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.bank') }}
                    <span v-if="!isReadOnly" class="text-danger"> *</span>
                </label>
                <StylishSelect
                    v-if="!isReadOnly"
                    v-model="form.banque_id"
                    :options="banques"
                    option-value="id"
                    option-label="nom"
                    :placeholder="t('fields.bank')"
                />
                <input
                    v-else
                    type="text"
                    :value="banques.find(b => b.id === form.banque_id)?.nom || '-'"
                    class="form-control"
                    disabled
                />
                <span v-if="form.errors?.banque_id" class="text-danger">
                    <strong>{{ form.errors.banque_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Nom du compte -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.accountName') }}
                    <span v-if="!isReadOnly" class="text-danger"> *</span>
                </label>
                <input
                    type="text"
                    v-model="form.nom_compte"
                    class="form-control"
                    :placeholder="t('fields.accountName')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.nom_compte" class="text-danger">
                    <strong>{{ form.errors.nom_compte }}</strong>
                </span>
            </div>
        </div>
        <!-- Numéro de compte -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.accountNumber') }}
                    <span v-if="!isReadOnly" class="text-danger"> *</span>
                </label>
                <input
                    type="text"
                    v-model="form.numero_compte"
                    class="form-control"
                    :placeholder="t('fields.accountNumber')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.numero_compte" class="text-danger">
                    <strong>{{ form.errors.numero_compte }}</strong>
                </span>
            </div>
        </div>
        <!-- IBAN -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.iban') }}
                </label>
                <input
                    type="text"
                    v-model="form.iban"
                    class="form-control"
                    :placeholder="t('fields.iban')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.iban" class="text-danger">
                    <strong>{{ form.errors.iban }}</strong>
                </span>
            </div>
        </div>
        <!-- BIC/SWIFT -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>
                    {{ t('fields.bicSwift') }}
                </label>
                <input
                    type="text"
                    v-model="form.bic_swift"
                    class="form-control"
                    :placeholder="t('fields.bicSwift')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.bic_swift" class="text-danger">
                    <strong>{{ form.errors.bic_swift }}</strong>
                </span>
            </div>
        </div>
        <!-- Compte principal -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label class="form-label fw-bold">
                    {{ t('fields.isPrincipal') }}
                </label>
                <div v-if="!isReadOnly" class="d-flex align-items-center mt-4">
                    <div class="form-check form-switch">
                        <input
                            type="checkbox"
                            v-model="form.is_principal"
                            class="form-check-input switch-input"
                            id="isPrincipalSwitch"
                        />
                    </div>
                    <label for="isPrincipalSwitch" class="form-check-label ms-5">
                        {{ form.is_principal ? t('common.yes') : t('common.no') }}
                    </label>
                </div>
                <input
                    v-else
                    type="text"
                    :value="form.is_principal ? t('common.yes') : t('common.no')"
                    class="form-control"
                    disabled
                />
                <span v-if="form.errors?.is_principal" class="text-danger">
                    <strong>{{ form.errors.is_principal }}</strong>
                </span>
            </div>
        </div>
        <!-- Compte actif -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label class="form-label fw-bold">
                    {{ t('fields.isActive') }}
                </label>
                <div v-if="!isReadOnly" class="d-flex align-items-center mt-4">
                    <div class="form-check form-switch">
                        <input
                            type="checkbox"
                            v-model="form.is_active"
                            class="form-check-input switch-input"
                            id="isActiveSwitch"
                        />
                    </div>
                    <label for="isActiveSwitch" class="form-check-label ms-5">
                        {{ form.is_active ? t('common.yes') : t('common.no') }}
                    </label>
                </div>
                <input
                    v-else
                    type="text"
                    :value="form.is_active ? t('common.yes') : t('common.no')"
                    class="form-control"
                    disabled
                />
                <span v-if="form.errors?.is_active" class="text-danger">
                    <strong>{{ form.errors.is_active }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>
