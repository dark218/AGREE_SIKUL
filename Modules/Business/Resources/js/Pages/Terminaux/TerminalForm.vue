<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import { usePermissions } from '@/Composables/usePermissions';
const { t } = useI18n();
const { can } = usePermissions();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    pointsVente: {
        type: Array,
        default: () => [],
    },
    marchands: {
        type: Array,
        default: () => [],
    },
    typesTerminaux: {
        type: Array,
        default: () => [],
    },
    statuts: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});
const isReadOnly = computed(() => props.mode === 'show');
const isEditMode = computed(() => props.mode === 'edit');
// Computed pour traduire les labels des types de terminaux
const translatedTypesTerminaux = computed(() => {
    return props.typesTerminaux.map(type => ({
        ...type,
        label: t(`modules.business.terminaux.types.${type.value}`)
    }));
});
// Computed pour traduire les labels des statuts
const translatedStatuts = computed(() => {
    return props.statuts.map(statut => ({
        ...statut,
        label: t(`modules.business.terminaux.statuts.${statut.value}`)
    }));
});
// Computed pour gérer la conversion JSON du metadata
const metadataString = computed({
    get() {
        if (!props.form.metadata) return '';
        if (typeof props.form.metadata === 'string') return props.form.metadata;
        try {
            return JSON.stringify(props.form.metadata, null, 2);
        } catch (e) {
            return '';
        }
    },
    set(value) {
        if (!value) {
            props.form.metadata = null;
            return;
        }
        try {
            props.form.metadata = JSON.parse(value);
        } catch (e) {
            // Si le JSON n'est pas valide, garder la string
            props.form.metadata = value;
        }
    }
});
</script>
<template>
    <div class="custom-input">
        <div class="row g-3">
            <!-- Type Terminal -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>{{ t('fields.terminalType') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                    <StylishSelect
                        v-model="form.type_terminal"
                        :options="translatedTypesTerminaux"
                        option-value="value"
                        option-label="label"
                        :placeholder="t('actions.select') + ' ' + t('fields.terminalType')"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.type_terminal" class="text-danger">
                        <strong>{{ form.errors.type_terminal }}</strong>
                    </span>
                </div>
            </div>
            <!-- Numéro de série -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>{{ t('fields.serialNumber') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                    <input
                        type="text"
                        v-model="form.numero_serie"
                        class="form-control"
                        :placeholder="t('fields.serialNumber')"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.numero_serie" class="text-danger">
                        <strong>{{ form.errors.numero_serie }}</strong>
                    </span>
                </div>
            </div>
            <!-- Fabricant -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>{{ t('fields.manufacturer') }}</label>
                    <input
                        type="text"
                        v-model="form.fabricant"
                        class="form-control"
                        :placeholder="t('fields.manufacturer')"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.fabricant" class="text-danger">
                        <strong>{{ form.errors.fabricant }}</strong>
                    </span>
                </div>
            </div>
            <!-- Modèle -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>{{ t('fields.model') }}</label>
                    <input
                        type="text"
                        v-model="form.modele"
                        class="form-control"
                        :placeholder="t('fields.model')"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.modele" class="text-danger">
                        <strong>{{ form.errors.modele }}</strong>
                    </span>
                </div>
            </div>
            <!-- Point de Vente -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>{{ t('fields.pointOfSale') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                    <StylishSelect
                        v-model="form.points_vente_id"
                        :options="pointsVente"
                        option-value="id"
                        option-label="label"
                        :placeholder="t('actions.select') + ' ' + t('fields.pointOfSale')"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.points_vente_id" class="text-danger">
                        <strong>{{ form.errors.points_vente_id }}</strong>
                    </span>
                </div>
            </div>
            <!-- Marchand -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>{{ t('fields.merchant') }}</label>
                    <StylishSelect
                        v-model="form.marchand_id"
                        :options="marchands"
                        option-value="id"
                        option-label="label"
                        :placeholder="t('actions.select') + ' ' + t('fields.merchant')"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.marchand_id" class="text-danger">
                        <strong>{{ form.errors.marchand_id }}</strong>
                    </span>
                </div>
            </div>
            <!-- Version Firmware -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>{{ t('fields.firmwareVersion') }}</label>
                    <input
                        type="text"
                        v-model="form.version_firmware"
                        class="form-control"
                        :placeholder="t('fields.firmwareVersion')"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.version_firmware" class="text-danger">
                        <strong>{{ form.errors.version_firmware }}</strong>
                    </span>
                </div>
            </div>
            <!-- PKI Cert ID -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>{{ t('fields.pkiCertId') }}</label>
                    <input
                        type="text"
                        v-model="form.pki_cert_id"
                        class="form-control"
                        :placeholder="t('fields.pkiCertId')"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.pki_cert_id" class="text-danger">
                        <strong>{{ form.errors.pki_cert_id }}</strong>
                    </span>
                </div>
            </div>
            <!-- Statut (only in edit mode) -->
            <div class="col-md-6" v-if="can('terminal-statut') && (isEditMode || isReadOnly)">
                <div class="mb-3">
                    <label>{{ t('common.status') }}<span v-if="isEditMode" class="text-danger"> *</span></label>
                    <StylishSelect
                        v-model="form.statut"
                        :options="translatedStatuts"
                        option-value="value"
                        option-label="label"
                        :placeholder="t('actions.select') + ' ' + t('common.status')"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.statut" class="text-danger">
                        <strong>{{ form.errors.statut }}</strong>
                    </span>
                </div>
            </div>
            <!-- Metadata -->
            <div class="col-md-12">
                <div class="mb-3">
                    <label>{{ t('modules.business.terminaux.fields.metadata') }}</label>
                    <textarea
                        v-model="metadataString"
                        class="form-control"
                        :placeholder="t('modules.business.terminaux.fields.metadata') + ' (JSON)'"
                        :disabled="isReadOnly"
                        rows="3"
                    ></textarea>
                    <small class="text-muted">Format JSON: {"key": "value"}</small>
                    <span v-if="form.errors?.metadata" class="text-danger">
                        <strong>{{ form.errors.metadata }}</strong>
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
