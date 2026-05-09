<script setup>
import { computed, watch, ref } from 'vue';
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
    typePieces: {
        type: Array,
        default: () => [],
    },
    typeEmployes: {
        type: Array,
        default: () => [],
    },
    marchands: {
        type: Array,
        default: () => [],
    },
    pointsVente: {
        type: Array,
        default: () => [],
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
    isMarchand: {
        type: Boolean,
        default: false,
    },
    marchandCurrent: {
        type: Object,
        default: null,
    },
});
const emit = defineEmits(['marchand-changed']);
const isReadOnly = computed(() => props.mode === 'show');
// Computed pour vérifier si on doit afficher le select marchand
const showMarchandSelect = computed(() => !props.isMarchand);
// Computed pour le nom du marchand actuel (si is_marchand)
const marchandCurrentLabel = computed(() => {
    return props.marchandCurrent?.raison_sociale || props.marchandCurrent?.label || '';
});
const localPointsVente = ref(props.pointsVente || []);
// Si is_marchand, on utilise le marchand_current, sinon la valeur du formulaire
const selectedMarchand = ref(props.isMarchand ? props.marchandCurrent?.id : (props.form.marchand_id || ''));
watch(selectedMarchand, async (newVal) => {
    if (newVal && props.mode !== 'show') {
        try {
            const response = await fetch(`/api/business/marchand/${newVal}/pointsvente`);
            const data = await response.json();
            localPointsVente.value = data.pointsVente || [];
        } catch (e) {
            localPointsVente.value = [];
        }
        props.form.marchand_id = newVal;
    }
    // En mode show, ne pas modifier localPointsVente ni marchand_id
});
watch(() => props.pointsVente, (newVal) => {
    if (newVal && newVal.length > 0) {
        localPointsVente.value = newVal;
    }
}, { immediate: true });
</script>
<template>
    <div class="custom-input">
        <!-- Photo de profil - Style Profile Upload -->
        <ProfileUpload
            v-model="form.photoprofile_id"
            :preview="existingFiles.photoprofile"
            :disabled="isReadOnly"
            inputId="employePhotoProfile"
        />
        <div class="row g-3">
        <!-- Marchand (masqué si l'utilisateur est un marchand) -->
        <div v-if="showMarchandSelect" class="col-md-6">
            <div class="mb-3">
                <label>{{ t('fields.merchant') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                <StylishSelect
                    v-model="selectedMarchand"
                    :options="marchands"
                    option-value="id"
                    option-label="label"
                    :placeholder="t('actions.select') + ' ' + t('fields.merchant')"
                    :disabled="isReadOnly"
                />
            </div>
        </div>
        <!-- Affichage du marchand actuel en lecture seule (si is_marchand) -->
        <div v-else class="col-md-6">
            <div class="mb-3">
                <label>{{ t('fields.merchant') }}</label>
                <input
                    type="text"
                    :value="marchandCurrentLabel"
                    class="form-control"
                    disabled
                />
            </div>
        </div>
        <!-- Point de vente -->
        <div class="col-md-6">
            <div class="mb-3">
                <label>{{ t('fields.pointOfSale') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                <StylishSelect
                    v-model="form.points_vente_id"
                    :options="localPointsVente"
                    option-value="id"
                    option-label="label"
                    :placeholder="t('actions.select') + ' ' + t('fields.pointOfSale')"
                    :disabled="isReadOnly || !selectedMarchand"
                />
                <span v-if="form.errors?.points_vente_id" class="text-danger">
                    <strong>{{ form.errors.points_vente_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Type employe -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.employeeType') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                <StylishSelect
                    v-model="form.type_employe"
                    :options="typeEmployes"
                    option-value="value"
                    option-label="label"
                    :placeholder="t('actions.select') + ' ' + t('fields.employeeType')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.type_employe" class="text-danger">
                    <strong>{{ form.errors.type_employe }}</strong>
                </span>
            </div>
        </div>
        <!-- Date embauche -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.hireDate') }}</label>
                <input
                    type="date"
                    v-model="form.date_embauche"
                    class="form-control"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.date_embauche" class="text-danger">
                    <strong>{{ form.errors.date_embauche }}</strong>
                </span>
            </div>
        </div>
        <!-- Nom -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.name') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                <input
                    type="text"
                    v-model="form.nom"
                    class="form-control"
                    :placeholder="t('fields.name')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.nom" class="text-danger">
                    <strong>{{ form.errors.nom }}</strong>
                </span>
            </div>
        </div>
        <!-- Prenoms -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.firstName') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                <input
                    type="text"
                    v-model="form.prenoms"
                    class="form-control"
                    :placeholder="t('fields.firstName')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.prenoms" class="text-danger">
                    <strong>{{ form.errors.prenoms }}</strong>
                </span>
            </div>
        </div>
        <!-- Telephone -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.phone') }}<span v-if="!isReadOnly" class="text-danger"> *</span></label>
                <input
                    type="text"
                    v-model="form.tel"
                    class="form-control"
                    :placeholder="t('fields.phone')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.tel" class="text-danger">
                    <strong>{{ form.errors.tel }}</strong>
                </span>
            </div>
        </div>
        <!-- Email -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.email') }}</label>
                <input
                    type="email"
                    v-model="form.email"
                    class="form-control"
                    :placeholder="t('fields.email')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.email" class="text-danger">
                    <strong>{{ form.errors.email }}</strong>
                </span>
            </div>
        </div>
        <!-- Type de piece -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.idType') }}</label>
                <StylishSelect
                    v-model="form.type_piece"
                    :options="typePieces"
                    option-value="value"
                    option-label="label"
                    :placeholder="t('actions.select')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.type_piece" class="text-danger">
                    <strong>{{ form.errors.type_piece }}</strong>
                </span>
            </div>
        </div>
        <!-- Numero de piece -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.idNumber') }}</label>
                <input
                    type="text"
                    v-model="form.numero_piece"
                    class="form-control"
                    :placeholder="t('fields.idNumber')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.numero_piece" class="text-danger">
                    <strong>{{ form.errors.numero_piece }}</strong>
                </span>
            </div>
        </div>
        <!-- Date de delivrance -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.issueDate') }}</label>
                <input
                    type="date"
                    v-model="form.date_delivrance"
                    class="form-control"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.date_delivrance" class="text-danger">
                    <strong>{{ form.errors.date_delivrance }}</strong>
                </span>
            </div>
        </div>
        <!-- Lieu de delivrance -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.issuePlace') }}</label>
                <input
                    type="text"
                    v-model="form.lieu_delivrance"
                    class="form-control"
                    :placeholder="t('fields.issuePlace')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.lieu_delivrance" class="text-danger">
                    <strong>{{ form.errors.lieu_delivrance }}</strong>
                </span>
            </div>
        </div>
        <!-- Date de naissance -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.birthDate') }}</label>
                <input
                    type="date"
                    v-model="form.date_naissance"
                    class="form-control"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.date_naissance" class="text-danger">
                    <strong>{{ form.errors.date_naissance }}</strong>
                </span>
            </div>
        </div>
        <!-- Lieu de naissance -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.birthPlace') }}</label>
                <input
                    type="text"
                    v-model="form.lieu_naissance"
                    class="form-control"
                    :placeholder="t('fields.birthPlace')"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.lieu_naissance" class="text-danger">
                    <strong>{{ form.errors.lieu_naissance }}</strong>
                </span>
            </div>
        </div>
        <!-- Piece recto -->
        <div class="col-md-6">
            <FileUpload
                v-model="form.piecerecto_id"
                :label="t('fields.idFront')"
                :preview="existingFiles.piecerecto"
                :disabled="isReadOnly"
                accept="image/*"
            />
        </div>
        <!-- Piece verso -->
        <div class="col-md-6">
            <FileUpload
                v-model="form.pieceverso_id"
                :label="t('fields.idBack')"
                :preview="existingFiles.pieceverso"
                :disabled="isReadOnly"
                accept="image/*"
            />
        </div>
        </div>
    </div>
</template>
