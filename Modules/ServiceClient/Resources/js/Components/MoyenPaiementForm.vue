<script setup>
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import StylishSelect from '@/Components/Common/StylishSelect.vue';

const { t } = useI18n();

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    types: {
        type: Array,
        default: () => [],
    },
    fournisseurs: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit'].includes(value),
    },
    identifiantMasque: {
        type: String,
        default: '',
    },
    identifiantChiffre: {
        type: String,
        default: '',
    },
    showIdentifiant: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['toggle-identifiant', 'identifiant-change']);

const isReadOnly = computed(() => props.mode === 'show');

// Filtrer les fournisseurs en fonction du type sélectionné
const fournisseursFiltres = computed(() => {
    if (!props.form.type) return [];

    return props.fournisseurs.filter(f => {
        // Mapper les types de fournisseurs aux types de moyens de paiement
        const typeMapping = {
            'mm': 'mm',        // Mobile Money
            'card': 'bank',    // Carte bancaire via banque
            'iban': 'bank',    // IBAN via banque
            'wallet': 'mm',    // Wallet via mobile money ou aggregateur
};

        return f.type === typeMapping[props.form.type];
});
});

// Réinitialiser le fournisseur si le type change
watch(() => props.form.type, (newType) => {
    if (newType) {
        props.form.fournisseur_id = '';
    }
});

// Placeholder pour l'identifiant selon le type
const identifiantPlaceholder = computed(() => {
    switch (props.form.type) {
        case 'mm':
            return t('modules.service_client.moyen_paiement.placeholder_phone');
        case 'card':
            return t('modules.service_client.moyen_paiement.placeholder_card');
        case 'iban':
            return t('modules.service_client.moyen_paiement.placeholder_iban');
        case 'wallet':
            return t('modules.service_client.moyen_paiement.placeholder_wallet');
        default:
            return '';
    }
});

// Label pour l'identifiant selon le type
const identifiantLabel = computed(() => {
    switch (props.form.type) {
        case 'mm':
            return t('modules.service_client.moyen_paiement.numero_telephone');
        case 'card':
            return t('modules.service_client.moyen_paiement.numero_carte');
        case 'iban':
            return t('modules.service_client.moyen_paiement.numero_iban');
        case 'wallet':
            return t('modules.service_client.moyen_paiement.identifiant_wallet');
        default:
            return t('modules.service_client.moyen_paiement.identifiant');
    }
});
</script>

<template>
    <div class="custom-input">
        <div class="row g-3">
            <!-- Type de moyen de paiement -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>{{ t('modules.service_client.moyen_paiement.type_label') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                    <StylishSelect
                        v-model="form.type"
                        :options="types"
                        option-value="value"
                        option-label="label"
                        :placeholder="t('actions.select') + ' ' + t('modules.service_client.moyen_paiement.type')"
                        :disabled="isReadOnly" />
                    <span v-if="form.errors?.type" class="text-danger">
                        <strong>{{ form.errors.type }}</strong>
                    </span>
                </div>
            </div>

            <!-- Fournisseur de paiement -->
            <div class="col-md-6" v-if="form.type">
                <div class="mb-3">
                    <label>{{ t('modules.service_client.moyen_paiement.fournisseur') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                    <StylishSelect
                        v-model="form.fournisseur_id"
                        :options="fournisseursFiltres"
                        option-value="value"
                        option-label="label"
                        :placeholder="t('actions.select') + ' ' + t('modules.service_client.moyen_paiement.fournisseur')"
                        :disabled="isReadOnly" />
                    <span v-if="form.errors?.fournisseur_id" class="text-danger">
                        <strong>{{ form.errors.fournisseur_id }}</strong>
                    </span>
                </div>
            </div>

            <!-- Identifiant -->
            <div class="col-md-6" v-if="form.type">
                <div class="mb-3">
                    <label>{{ identifiantLabel }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>

                    <div class="input-group">
                        <input
                            type="text"
                            v-model="form.identifiant"
                            class="form-control"
                            :placeholder="identifiantPlaceholder"
                            :disabled="isReadOnly"
                            @input="emit('identifiant-change')" />
                        <button
                            v-if="mode === 'edit' && identifiantMasque"
                            @click.prevent="emit('toggle-identifiant')"
                            class="btn btn-outline-secondary"
                            type="button"
                            :title="showIdentifiant ? 'Masquer' : 'Afficher'">
                            <i :class="showIdentifiant ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
                        </button>
                    </div>

                    <span v-if="form.errors?.identifiant" class="text-danger">
                        <strong>{{ form.errors.identifiant }}</strong>
                    </span>
                </div>
            </div>

            <!-- Label (optionnel) -->
            <div class="col-md-6" v-if="form.type">
                <div class="mb-3">
                    <label>{{ t('modules.service_client.moyen_paiement.libelle_optionnel') }}</label>
                    <input
                        type="text"
                        v-model="form.label"
                        class="form-control"
                        :placeholder="t('modules.service_client.moyen_paiement.placeholder_libelle')"
                        :disabled="isReadOnly" />
                    <span v-if="form.errors?.label" class="text-danger">
                        <strong>{{ form.errors.label }}</strong>
                    </span>
                </div>
            </div>

            <!-- Token provider (optionnel, pour les intégrations API) -->
            <div class="col-md-12" v-if="form.type && mode === 'create'">
                <div class="mb-3">
                    <label>{{ t('modules.service_client.moyen_paiement.token_provider') }}</label>
                    <input
                        type="text"
                        v-model="form.token_provider"
                        class="form-control"
                        :placeholder="t('modules.service_client.moyen_paiement.token_provider_placeholder')"
                        :disabled="isReadOnly" />
                    <span v-if="form.errors?.token_provider" class="text-danger">
                        <strong>{{ form.errors.token_provider }}</strong>
                    </span>
                    <small class="text-muted">{{ t('modules.service_client.moyen_paiement.token_provider_help') }}</small>
                </div>
            </div>

            <!-- Définir par défaut -->
            <div class="col-md-12">
                <div class="mb-3">
                    <div class="form-check">
                        <input
                            type="checkbox"
                            v-model="form.is_defaut"
                            class="form-check-input"
                            id="isDefaut"
                            :disabled="isReadOnly">
                        <label class="form-check-label" for="isDefaut">
                            {{ t('modules.service_client.moyen_paiement.defaut_comme') }}
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>