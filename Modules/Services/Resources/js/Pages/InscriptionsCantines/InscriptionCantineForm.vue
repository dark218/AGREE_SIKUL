<script setup>
import { ref, computed, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    inscriptionCantine: {
        type: Object,
        default: () => ({}),
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
    apprenants: {
        type: Array,
        default: () => [],
    },
    servicesCantines: {
        type: Array,
        default: () => [],
    },
    anneeScolaires: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['submit']);

const isCollapsed = ref(false);

const form = ref({
    annee_scolaire_id: props.inscriptionCantine?.annee_scolaire_id || null,
    apprenant_id: props.inscriptionCantine?.apprenant_id || null,
    service_cantine_id: props.inscriptionCantine?.service_cantine_id || null,
    date_inscription: props.inscriptionCantine?.date_inscription || '',
    date_debut: props.inscriptionCantine?.date_debut || '',
    date_fin: props.inscriptionCantine?.date_fin || null,
    nombre_jours: props.inscriptionCantine?.nombre_jours || null,
    observations: props.inscriptionCantine?.observations || '',
    statut: props.inscriptionCantine?.statut || 'active',
});

// Auto-calculate nombre_jours from date_debut and date_fin
watch([() => form.value.date_debut, () => form.value.date_fin], () => {
    if (form.value.date_debut && form.value.date_fin) {
        const debut = new Date(form.value.date_debut);
        const fin = new Date(form.value.date_fin);
        if (fin >= debut) {
            const diffTime = Math.abs(fin - debut);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 to include both start and end dates
            form.value.nombre_jours = diffDays;
        }
    }
});

function submit() {
    console.log('InscriptionCantineForm::submit() - Form data:', form.value);
    emit('submit', form.value);
}

function toggleCollapse() {
    isCollapsed.value = !isCollapsed.value;
}

defineExpose({
    getFormData: () => form.value,
    form,
});
</script>

<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge"><i class="fa fa-plus"></i></span>
                                <h5 class="title mb-0">{{ t('common.inscription-cantine') || 'Inscription Cantine' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <form @submit.prevent="submit">
                                <div class="row g-3 custom-input">
                                    <!-- Section 1: Sélection Apprenant et Service -->
                                    <div class="col-12">
                                        <h5 class="section-title mb-3 mt-0">{{ t('common.basic_information') || 'Sélection' }}</h5>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label>{{ t('common.academic_year') || 'Année Scolaire' }}</label>
                                            <SearchableSelect
                                                v-model="form.annee_scolaire_id"
                                                :options="anneeScolaires"
                                                optionValue="id"
                                                optionLabel="libelle"
                                                :placeholder="t('actions.select') || 'Sélectionner'"
                                                class="form-control-sm"
                                                :disabled="isReadOnly"
                                            />
                                            <span v-if="errors.annee_scolaire_id" class="text-danger">
                                                <strong>{{ errors.annee_scolaire_id }}</strong>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label>{{ t('common.apprenant') }} <span class="text-danger">*</span></label>
                                            <SearchableSelect
                                                v-model="form.apprenant_id"
                                                :options="apprenants.map(a => ({id: a.id, libelle: `${a.nom} ${a.prenoms}`}))"
                                                optionValue="id"
                                                optionLabel="libelle"
                                                :placeholder="t('actions.select') || 'Sélectionner'"
                                                class="form-control-sm"
                                                :disabled="isReadOnly"
                                            />
                                            <span v-if="errors.apprenant_id" class="text-danger">
                                                <strong>{{ errors.apprenant_id }}</strong>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label>{{ t('common.service-cantine') }} <span class="text-danger">*</span></label>
                                            <SearchableSelect
                                                v-model="form.service_cantine_id"
                                                :options="servicesCantines"
                                                optionValue="id"
                                                optionLabel="libelle"
                                                :placeholder="t('actions.select') || 'Sélectionner'"
                                                class="form-control-sm"
                                                :disabled="isReadOnly"
                                            />
                                            <span v-if="errors.service_cantine_id" class="text-danger">
                                                <strong>{{ errors.service_cantine_id }}</strong>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Section 2: Dates -->
                                    <div class="col-12">
                                        <h5 class="section-title mb-3 mt-4">{{ t('common.dates') || 'Dates' }}</h5>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label>{{ t('fields.date_inscription') || 'Date d\'inscription' }} <span class="text-danger">*</span></label>
                                            <input
                                                v-model="form.date_inscription"
                                                type="date"
                                                class="form-control"
                                                :class="{ 'is-invalid': errors.date_inscription }"
                                                :disabled="isReadOnly"
                                                required
                                            />
                                            <span v-if="errors.date_inscription" class="text-danger">
                                                <strong>{{ errors.date_inscription }}</strong>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label>{{ t('common.date_debut') || 'Date de début' }} <span class="text-danger">*</span></label>
                                            <input
                                                v-model="form.date_debut"
                                                type="date"
                                                class="form-control"
                                                :class="{ 'is-invalid': errors.date_debut }"
                                                :disabled="isReadOnly"
                                                required
                                            />
                                            <span v-if="errors.date_debut" class="text-danger">
                                                <strong>{{ errors.date_debut }}</strong>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label>{{ t('common.date_fin') || 'Date de fin' }}</label>
                                            <input
                                                v-model="form.date_fin"
                                                type="date"
                                                class="form-control"
                                                :class="{ 'is-invalid': errors.date_fin }"
                                                :disabled="isReadOnly"
                                            />
                                            <span v-if="errors.date_fin" class="text-danger">
                                                <strong>{{ errors.date_fin }}</strong>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label>{{ t('fields.nombre_jours') || 'Nombre de jours' }} <span class="text-muted">(auto)</span></label>
                                            <input
                                                v-model.number="form.nombre_jours"
                                                type="number"
                                                class="form-control"
                                                :class="{ 'is-invalid': errors.nombre_jours }"
                                                disabled
                                                min="0"
                                            />
                                            <span v-if="errors.nombre_jours" class="text-danger">
                                                <strong>{{ errors.nombre_jours }}</strong>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Section 3: Observations -->
                                    <div class="col-12">
                                        <h5 class="section-title mb-3 mt-4">{{ t('common.additional_info') || 'Informations supplémentaires' }}</h5>
                                    </div>

                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label>{{ t('fields.observations') || 'Observations' }}</label>
                                            <textarea
                                                v-model="form.observations"
                                                class="form-control"
                                                :class="{ 'is-invalid': errors.observations }"
                                                :disabled="isReadOnly"
                                                rows="3"
                                            ></textarea>
                                            <span v-if="errors.observations" class="text-danger">
                                                <strong>{{ errors.observations }}</strong>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Section 4: État -->
                                    <div class="col-12">
                                        <h5 class="section-title mb-3 mt-4">{{ t('common.status_section') || 'État' }}</h5>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label>{{ t('common.statut') }} <span class="text-danger">*</span></label>
                                            <SearchableSelect
                                                v-model="form.statut"
                                                :options="[{id:'active', libelle:'Actif'}, {id:'suspendue', libelle:'Suspendu'}, {id:'terminee', libelle:'Terminé'}, {id:'annulee', libelle:'Annulé'}]"
                                                optionValue="id"
                                                optionLabel="libelle"
                                                :placeholder="t('actions.select') || 'Sélectionner'"
                                                class="form-control-sm"
                                                :disabled="isReadOnly"
                                            />
                                            <span v-if="errors.statut" class="text-danger">
                                                <strong>{{ errors.statut }}</strong>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Buttons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('inscriptions-cantine.index')" class="btn btn-danger">
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                            </Link>
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                            >
                                                <i class="fa fa-save"></i> {{ submitButtonLabel }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dash-payment-item-wrapper {
    margin-bottom: 20px;
}

.dash-payment-item {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
}

.dash-payment-title-area {
    background: #f5f5f5;
    padding: 15px 20px;
    cursor: pointer;
    user-select: none;
}

.dash-payment-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: #f0f0f0;
    border-radius: 50%;
    margin-right: 15px;
    color: #666;
    font-size: 18px;
}

.title {
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

.collapse-toggle {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 16px;
    color: #666;
    transition: transform 0.3s ease;
}

.collapse-toggle.collapsed {
    transform: rotate(180deg);
}

.dash-payment-body {
    padding: 20px;
    transition: max-height 0.3s ease;
}

.dash-payment-body.collapsed {
    display: none;
}

.section-title {
    font-size: 14px;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #f97316;
    padding-bottom: 10px;
    margin-bottom: 15px !important;
}

.form-control {
    min-height: 38px;
}

.form-control:disabled {
    background-color: #e9ecef;
    cursor: not-allowed;
}

.custom-input .col-sm-6,
.custom-input .col-12 {
    padding: 0.75rem;
}

.text-danger {
    color: #dc3545;
}

.invalid-feedback {
    color: #dc3545;
    font-size: 0.875em;
    margin-top: 0.25rem;
}

.text-end {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn {
    padding: 8px 16px;
    font-size: 14px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.btn-primary {
    background: #f97316;
    border: 1px solid #f97316;
    color: white;
}

.btn-primary:hover {
    background: #ea580c;
    border-color: #ea580c;
}

.btn-danger {
    background: #dc3545;
    border: 1px solid #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
    border-color: #c82333;
}
</style>
