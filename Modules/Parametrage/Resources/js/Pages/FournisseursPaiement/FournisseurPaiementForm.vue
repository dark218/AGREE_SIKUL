<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
const { t } = useI18n();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    paysdevises: {
        type: Array,
        default: () => [],
    },
    types: {
        type: Object,
        default: () => ({}),
    },
    paysCurrent: {
        type: [Number, String, null],
        default: null,
    },
    logoPreview: {
        type: String,
        default: 'https://caer.univ-amu.fr/wp-content/uploads/default-placeholder.png',
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});
const emit = defineEmits(['logo-change']);
const isReadOnly = computed(() => props.mode === 'show');
const isDisabledPaysDevise = props.paysCurrent !== null || isReadOnly;
// Transformer types en array pour StylishSelect
const typesOptions = computed(() => {
    // Si types est un tableau d'objets avec value/label
    if (Array.isArray(props.types)) {
        return props.types.map(item => ({
            id: item.value,
            label: t(`modules.parametrage.paymentProviders.types.${item.value}`)
        }));
    }
    // Si types est un objet clé-valeur
    return Object.entries(props.types || {}).map(([key, label]) => ({
        id: key,
        label: t(`modules.parametrage.paymentProviders.types.${key}`)
    }));
});
// Statuts options
const statutOptions = computed(() => [
    { id: 'actif', label: t('statuts.actif') },
    { id: 'inactif', label: t('statuts.inactif') }
]);
// Transformer paysdevises pour afficher "Pays - Devise"
const paysdevisesOptions = computed(() => {
    return (props.paysdevises || []).map(item => ({
        id: item.id,
        libelle: `${item.pays?.libelle || ''} - ${item.devise?.code || ''}`
    }));
});
const handleLogoChange = (event) => {
    emit('logo-change', event);
};
const getTypeLabel = (type) => {
    return t(`modules.parametrage.paymentProviders.types.${type}`) || type;
};
</script>
<template>
    <div class="custom-input">
        <!-- Logo Upload -->
         <div class="profile-settings-wrapper">
            <!-- Bannière -->
            <div class="preview-thumb profile-wallpaper">
                <div class="avatar-preview">
                    <div class="profilePicPreview bg_img"></div>
                </div>
            </div>
            
            <!-- Avatar avec bouton upload -->
            <div class="profile-thumb-content">
                <div class="preview-thumb profile-thumb">
                    <div class="avatar-preview">
                        <div
                            class="profilePicPreview bg_img"
                            :style="{ backgroundImage: `url(${logoPreview})` }"
                        ></div>
                    </div>
                    <div v-if="!isReadOnly" class="avatar-edit">
                        <input
                            type="file"
                            class="profilePicUpload"
                            id="logoUpload"
                            accept=".png, .jpg, .jpeg, .svg"
                            @change="handleLogoChange"
                        />
                        <label for="logoUpload">
                            <i class="fa fa-upload"></i>
                        </label>
                    </div>
                </div>
            </div>
        </div>
       
        <div class="row g-3">
            <!-- Pays Devise -->
            <div class="col-sm-4">
                <div class="mb-3">
                    <label>
                        {{ t('fields.countryCurrency') }}
                        <span v-if="!isReadOnly" class="text-danger"> *</span>
                    </label>
                    <SearchableSelect
                        v-model="form.pays_devise_id"
                        :options="paysdevisesOptions"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="`${t('actions.select')} ${t('fields.countryCurrency')}`"
                        :disabled="isDisabledPaysDevise"
                    />
                    <span v-if="form.errors?.pays_devise_id" class="text-danger">
                        <strong>{{ form.errors.pays_devise_id }}</strong>
                    </span>
                </div>
            </div>
            <!-- Nom -->
            <div class="col-sm-4">
                <div class="mb-3">
                    <label>
                        {{ t('fields.name') }}
                        <span v-if="!isReadOnly" class="text-danger"> *</span>
                    </label>
                    <input
                        type="text"
                        v-model="form.libelle"
                        class="form-control"
                        :placeholder="t('fields.name')"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.libelle" class="text-danger">
                        <strong>{{ form.errors.libelle }}</strong>
                    </span>
                </div>
            </div>
            <!-- Code -->
            <div class="col-sm-4">
                <div class="mb-3">
                    <label>
                        {{ t('fields.code') }}
                        <span v-if="!isReadOnly" class="text-danger"> *</span>
                    </label>
                    <input
                        type="text"
                        v-model="form.code"
                        class="form-control"
                        :placeholder="t('fields.code')"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.code" class="text-danger">
                        <strong>{{ form.errors.code }}</strong>
                    </span>
                </div>
            </div>
            <!-- Type -->
            <div class="col-sm-6">
                <div class="mb-3">
                    <label>
                        {{ t('fields.type') }}
                        <span v-if="!isReadOnly" class="text-danger"> *</span>
                    </label>
                    <template v-if="isReadOnly">
                        <input
                            type="text"
                            :value="getTypeLabel(form.type)"
                            class="form-control"
                            disabled
                        />
                    </template>
                    <template v-else>
                        <SearchableSelect
                            v-model="form.type"
                            :options="typesOptions"
                            optionValue="id"
                            optionLabel="label"
                            :placeholder="t('actions.select') || '-- Sélectionner --'"
                            :disabled="isReadOnly"
                        />
                    </template>
                    <span v-if="form.errors?.type" class="text-danger">
                        <strong>{{ form.errors.type }}</strong>
                    </span>
                </div>
            </div>
            <!-- Statut -->
            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('common.status') }}</label>
                    <template v-if="isReadOnly">
                        <input
                            type="text"
                            :value="form.statut === 'actif' ? t('common.active') : t('common.inactive')"
                            class="form-control"
                            disabled
                        />
                    </template>
                    <template v-else>
                        <SearchableSelect
                            v-model="form.statut"
                            :options="statutOptions"
                            optionValue="id"
                            optionLabel="label"
                            :placeholder="t('actions.select') || '-- Sélectionner --'"
                            :disabled="isReadOnly"
                        />
                    </template>
                    <span v-if="form.errors?.statut" class="text-danger">
                        <strong>{{ form.errors.statut }}</strong>
                    </span>
                </div>
            </div>
            <!-- Config JSON -->
            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('fields.configuration') }}</label>
                    <textarea
                        v-model="form.config"
                        class="form-control"
                        rows="3"
                        placeholder='{"api_key":"xxxx","secret":"xxxx"}'
                        :disabled="isReadOnly"
                    ></textarea>
                    <span v-if="form.errors?.config" class="text-danger">
                        <strong>{{ form.errors.config }}</strong>
                    </span>
                </div>
            </div>
            <!-- Metadata -->
            <div class="col-sm-6">
                <div class="mb-3">
                    <label>{{ t('fields.metadata') }}</label>
                    <input
                        type="text"
                        v-model="form.metadata"
                        class="form-control"
                        placeholder='{"note":"exemple"}'
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.metadata" class="text-danger">
                        <strong>{{ form.errors.metadata }}</strong>
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
