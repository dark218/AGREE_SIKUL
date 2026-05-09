<template>
    <form @submit.prevent="submit" class="inscription-transport-form">
        <div class="row g-3 custom-input">
            <!-- Section: Affectation -->
            <div class="col-sm-12">
                <h5 class="mb-3">{{ t('common.scolarite') }}</h5>
            </div>
            <div class="col-sm-6">
                <label class="form-label">{{ t('common.apprenant') }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.apprenant_id"
                    :options="apprenants"
                    :label="`${item?.nom || ''} - ${item?.prenoms || ''}`"
                    option-label-format="(item) => `${item.nom} - ${item.prenoms}`"
                    :disabled="isReadOnly"
                />
                <div v-if="errors.apprenant_id" class="invalid-feedback d-block">
                    {{ errors.apprenant_id[0] || errors.apprenant_id }}
                </div>
            </div>
            <div class="col-sm-6">
                <label class="form-label">{{ t('common.service_transport') }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.service_transport_id"
                    :options="servicesTransports"
                    :label="`${service?.zone || ''} - ${service?.ligne || ''}`"
                    option-label-format="(item) => `${item.zone} - ${item.ligne}`"
                    :disabled="isReadOnly"
                />
                <div v-if="errors.service_transport_id" class="invalid-feedback d-block">
                    {{ errors.service_transport_id[0] || errors.service_transport_id }}
                </div>
            </div>

            <!-- Section: Dates -->
            <div class="col-sm-12">
                <h5 class="mb-3 mt-4">{{ t('common.dates') }}</h5>
            </div>
            <div class="col-sm-6">
                <label class="form-label">{{ t('common.date_inscription') }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.date_inscription"
                    type="date"
                    class="form-control"
                    :class="{ 'is-invalid': errors.date_inscription }"
                    :disabled="isReadOnly"
                    required
                />
                <div v-if="errors.date_inscription" class="invalid-feedback d-block">
                    {{ errors.date_inscription[0] || errors.date_inscription }}
                </div>
            </div>
            <div class="col-sm-6">
                <label class="form-label">{{ t('common.date_debut') }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.date_debut"
                    type="date"
                    class="form-control"
                    :class="{ 'is-invalid': errors.date_debut }"
                    :disabled="isReadOnly"
                    required
                />
                <div v-if="errors.date_debut" class="invalid-feedback d-block">
                    {{ errors.date_debut[0] || errors.date_debut }}
                </div>
            </div>
            <div class="col-sm-6">
                <label class="form-label">{{ t('common.date_fin') }}</label>
                <input
                    v-model="form.date_fin"
                    type="date"
                    class="form-control"
                    :class="{ 'is-invalid': errors.date_fin }"
                    :disabled="isReadOnly"
                />
                <div v-if="errors.date_fin" class="invalid-feedback d-block">
                    {{ errors.date_fin[0] || errors.date_fin }}
                </div>
            </div>

            <!-- Section: Détails -->
            <div class="col-sm-12">
                <h5 class="mb-3 mt-4">{{ t('common.details') }}</h5>
            </div>
            <div class="col-sm-6">
                <label class="form-label">{{ t('common.point_prise_charge') }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.point_prise_charge"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.point_prise_charge }"
                    :disabled="isReadOnly"
                    placeholder="Ex: Coin rue des Fleurs"
                    required
                />
                <div v-if="errors.point_prise_charge" class="invalid-feedback d-block">
                    {{ errors.point_prise_charge[0] || errors.point_prise_charge }}
                </div>
            </div>
            <div class="col-sm-6">
                <label class="form-label">{{ t('common.statut') }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.statut"
                    :options="[{id:'active', libelle:'Actif'}, {id:'suspendue', libelle:'Suspendu'}, {id:'terminee', libelle:'Terminé'}, {id:'annulee', libelle:'Annulé'}]"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || 'Sélectionner'"
                    class="form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="errors.statut" class="invalid-feedback d-block">
                    {{ errors.statut[0] || errors.statut }}
                </div>
            </div>
            <div class="col-sm-12">
                <label class="form-label">{{ t('common.observations') }}</label>
                <textarea
                    v-model="form.observations"
                    class="form-control"
                    :class="{ 'is-invalid': errors.observations }"
                    :disabled="isReadOnly"
                    rows="3"
                    placeholder="Observations supplémentaires..."
                />
                <div v-if="errors.observations" class="invalid-feedback d-block">
                    {{ errors.observations[0] || errors.observations }}
                </div>
            </div>

            <!-- Actions -->
            <div v-if="!isReadOnly" class="col-sm-12">
                <div class="form-actions mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> {{ submitButtonLabel }}
                    </button>
                    <button type="button" class="btn btn-secondary ms-2" @click="cancel">
                        {{ t('common.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    inscriptionTransport: {
        type: Object,
        default: () => ({}),
    },
    apprenants: {
        type: Array,
        default: () => [],
    },
    servicesTransports: {
        type: Array,
        default: () => [],
    },
    anneesScolaires: {
        type: Array,
        default: () => [],
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    isReadOnly: {
        type: Boolean,
        default: false,
    },
    submitButtonLabel: {
        type: String,
        default: 'Enregistrer',
    },
});

const emit = defineEmits(['submit']);

const form = ref({
    apprenant_id: props.inscriptionTransport?.apprenant_id || null,
    service_transport_id: props.inscriptionTransport?.service_transport_id || null,
    date_inscription: props.inscriptionTransport?.date_inscription || '',
    date_debut: props.inscriptionTransport?.date_debut || '',
    date_fin: props.inscriptionTransport?.date_fin || '',
    point_prise_charge: props.inscriptionTransport?.point_prise_charge || '',
    observations: props.inscriptionTransport?.observations || '',
    statut: props.inscriptionTransport?.statut || 'active',
});

const item = computed(() => {
    return props.apprenants.find(a => a.id === form.value.apprenant_id);
});

const service = computed(() => {
    return props.servicesTransports.find(s => s.id === form.value.service_transport_id);
});

function submit() {
    emit('submit', form.value);
}

function cancel() {
    router.visit(route('inscriptions-transport.index'));
}

defineExpose({
    getFormData: () => form.value,
    form,
});
</script>

<style scoped>
.inscription-transport-form {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.form-label {
    font-weight: 500;
    margin-bottom: 8px;
    display: block;
    font-size: 0.95rem;
}

.form-control:disabled,
.select2-container--disabled .select2-selection,
:deep(.select2-container--disabled .select2-selection) {
    background-color: #e9ecef;
    cursor: not-allowed;
}

.form-actions {
    display: flex;
    gap: 10px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}

.form-actions button {
    padding: 10px 20px;
    font-weight: 500;
}

.invalid-feedback {
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 4px;
}

h5 {
    font-weight: 600;
    color: #333;
    font-size: 1rem;
}
</style>
