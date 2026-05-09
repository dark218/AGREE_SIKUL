<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import FileUpload from '@/Components/Common/FileUpload.vue';
import ProfileUpload from '@/Components/Common/ProfileUpload.vue';
const { t } = useI18n();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    payss: {
        type: Array,
        default: () => [],
    },
    typePieces: {
        type: Array,
        default: () => [],
    },
    paysCurrent: {
        type: [Number, String, null],
        default: null,
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
    existingFiles: {
        type: Object,
        default: () => ({}),
    },
});
// Types de marchands
const marchandTypes = computed(() => [
    { value: 'informel', label: t('modules.business.marchands.types.informel') },
    { value: 'boutique', label: t('modules.business.marchands.types.boutique') },
    { value: 'grande_surface', label: t('modules.business.marchands.types.grande_surface') },
]);
const isReadOnly = computed(() => props.mode === 'show');
const isDisabledPays = computed(() => props.paysCurrent !== null || isReadOnly.value);
const paysOptions = computed(() => {
    return props.payss.map(p => ({ id: p.id, label: p.libelle }));
});
</script>
<template>
    <div class="custom-input">
        <!-- Photo de profil - Style Profile Upload -->
        <ProfileUpload v-model="form.photoprofile_id" :preview="existingFiles.photoprofile" :disabled="isReadOnly"
            inputId="marchandPhotoProfile" />
       
        <div class="row g-3">
            <!-- Pays -->
            <div class="col-md-4">
                <div class="mb-3">
                    <label>{{ t('fields.country') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                    <StylishSelect v-model="form.pays_id" :options="paysOptions" option-value="id" option-label="label"
                        :placeholder="t('actions.select') + ' ' + t('fields.country')" :disabled="isDisabledPays" />
                    <span v-if="form.errors?.pays_id" class="text-danger">
                        <strong>{{ form.errors.pays_id }}</strong>
                    </span>
                </div>
            </div>
            <!-- Nom -->
            <div class="col-md-4">
                <div class="mb-3">
                    <label>{{ t('fields.name') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                    <input type="text" v-model="form.nom" class="form-control" :placeholder="t('fields.name')"
                        :disabled="isReadOnly" />
                    <span v-if="form.errors?.nom" class="text-danger">
                        <strong>{{ form.errors.nom }}</strong>
                    </span>
                </div>
            </div>
            <!-- Prenoms -->
            <div class="col-md-4">
                <div class="mb-3">
                    <label>{{ t('fields.firstName') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                    <input type="text" v-model="form.prenoms" class="form-control" :placeholder="t('fields.firstName')"
                        :disabled="isReadOnly" />
                    <span v-if="form.errors?.prenoms" class="text-danger">
                        <strong>{{ form.errors.prenoms }}</strong>
                    </span>
                </div>
            </div>
            <!-- Raison sociale -->
            <div class="col-md-4">
                <div class="mb-3">
                    <label>{{ t('fields.businessName') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                    <input type="text" v-model="form.raison_sociale" class="form-control"
                        :placeholder="t('fields.businessName')" :disabled="isReadOnly" />
                    <span v-if="form.errors?.raison_sociale" class="text-danger">
                        <strong>{{ form.errors.raison_sociale }}</strong>
                    </span>
                </div>
            </div>
            <!-- Type -->
            <div class="col-md-4">
                <div class="mb-3">
                    <label>Type<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                    <StylishSelect v-model="form.type" :options="marchandTypes" option-value="value"
                        option-label="label" :placeholder="t('actions.select') + ' Type'" :disabled="isReadOnly" />
                    <span v-if="form.errors?.type" class="text-danger">
                        <strong>{{ form.errors.type }}</strong>
                    </span>
                </div>
            </div>
            <!-- Identifiant fiscal -->
            <div class="col-md-4">
                <div class="mb-3">
                    <label>{{ t('fields.taxId') }}</label>
                    <input type="text" v-model="form.identifiant_fiscal" class="form-control"
                        :placeholder="t('fields.taxId')" :disabled="isReadOnly" />
                    <span v-if="form.errors?.identifiant_fiscal" class="text-danger">
                        <strong>{{ form.errors.identifiant_fiscal }}</strong>
                    </span>
                </div>
            </div>
            <!-- Telephone -->
            <div class="col-md-4">
                <div class="mb-3">
                    <label>{{ t('fields.phone') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                    <input type="text" v-model="form.tel" class="form-control" :placeholder="t('fields.phone')"
                        :disabled="isReadOnly" />
                    <span v-if="form.errors?.tel" class="text-danger">
                        <strong>{{ form.errors.tel }}</strong>
                    </span>
                </div>
            </div>
            <!-- Email -->
            <div class="col-md-4">
                <div class="mb-3">
                    <label>{{ t('fields.email') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                    <input type="email" v-model="form.email" class="form-control" :placeholder="t('fields.email')"
                        :disabled="isReadOnly" />
                    <span v-if="form.errors?.email" class="text-danger">
                        <strong>{{ form.errors.email }}</strong>
                    </span>
                </div>
            </div>
            <!-- Type de piece -->
            <div class="col-md-4">
                <div class="mb-3">
                    <label>{{ t('fields.idType') }}</label>
                    <StylishSelect v-model="form.type_piece" :options="typePieces" option-value="value"
                        option-label="label" :placeholder="t('actions.select')" :disabled="isReadOnly" />
                    <span v-if="form.errors?.type_piece" class="text-danger">
                        <strong>{{ form.errors.type_piece }}</strong>
                    </span>
                </div>
            </div>
            <!-- Numero de piece -->
            <div class="col-md-4">
                <div class="mb-3">
                    <label>{{ t('fields.idNumber') }}</label>
                    <input type="text" v-model="form.numero_piece" class="form-control"
                        :placeholder="t('fields.idNumber')" :disabled="isReadOnly" />
                    <span v-if="form.errors?.numero_piece" class="text-danger">
                        <strong>{{ form.errors.numero_piece }}</strong>
                    </span>
                </div>
            </div>
            <!-- Date de delivrance -->
            <div class="col-md-4">
                <div class="mb-3">
                    <label>{{ t('fields.issueDate') }}</label>
                    <input type="date" v-model="form.date_delivrance" class="form-control" :disabled="isReadOnly" />
                    <span v-if="form.errors?.date_delivrance" class="text-danger">
                        <strong>{{ form.errors.date_delivrance }}</strong>
                    </span>
                </div>
            </div>
            <!-- Lieu de delivrance -->
            <div class="col-md-4">
                <div class="mb-3">
                    <label>{{ t('fields.issuePlace') }}</label>
                    <input type="text" v-model="form.lieu_delivrance" class="form-control"
                        :placeholder="t('fields.issuePlace')" :disabled="isReadOnly" />
                    <span v-if="form.errors?.lieu_delivrance" class="text-danger">
                        <strong>{{ form.errors.lieu_delivrance }}</strong>
                    </span>
                </div>
            </div>
            <!-- Date de naissance -->
            <div class="col-md-4">
                <div class="mb-3">
                    <label>{{ t('fields.birthDate') }}</label>
                    <input type="date" v-model="form.date_naissance" class="form-control" :disabled="isReadOnly" />
                    <span v-if="form.errors?.date_naissance" class="text-danger">
                        <strong>{{ form.errors.date_naissance }}</strong>
                    </span>
                </div>
            </div>
            <!-- Lieu de naissance -->
            <div class="col-md-4">
                <div class="mb-3">
                    <label>{{ t('fields.birthPlace') }}</label>
                    <input type="text" v-model="form.lieu_naissance" class="form-control"
                        :placeholder="t('fields.birthPlace')" :disabled="isReadOnly" />
                    <span v-if="form.errors?.lieu_naissance" class="text-danger">
                        <strong>{{ form.errors.lieu_naissance }}</strong>
                    </span>
                </div>
            </div>
            <div class="row">
                <!-- Piece recto -->
                <div class="col-md-6">
                    <FileUpload v-model="form.piecerecto_id" :label="t('fields.idFront')"
                        :preview="existingFiles.piecerecto" :disabled="isReadOnly" accept="image/*" />
                </div>
                <!-- Piece verso -->
                <div class="col-md-6">
                    <FileUpload v-model="form.pieceverso_id" :label="t('fields.idBack')"
                        :preview="existingFiles.pieceverso" :disabled="isReadOnly" accept="image/*" />
                </div>
            </div>
            <div class="row">
                <!-- DFE -->
                <div class="col-md-6">
                    <FileUpload v-model="form.dfe_id" :label="t('fields.dfe')" :preview="existingFiles.dfe"
                        :disabled="isReadOnly" accept="image/*" />
                </div>
                <!-- RCCM -->
                <div class="col-md-6">
                    <FileUpload v-model="form.rccm_id" :label="t('fields.rccm')" :preview="existingFiles.rccm"
                        :disabled="isReadOnly" accept="image/*" />
                </div>
            </div>
        </div>
    </div>
</template>
