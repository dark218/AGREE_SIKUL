<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);
const toggleCollapse = () => { isCollapsed.value = !isCollapsed.value; };

const props = defineProps({
    exams: { type: Array, default: () => [] },
    postes: { type: Array, default: () => [] },
});

const etatOptions = [
    { id: 'en-attente', libelle: 'En attente' },
    { id: 'actif', libelle: 'Actif' },
    { id: 'clôturé', libelle: 'Clôturé' },
];

const form = useForm({
    exam_id: null,
    recette_id: null,
    montant_finance: null,
    pourcentage_couverture: null,
    date_facturation: '',
    date_limite_paiement: '',
    etat_financement: 'en-attente',
    notes: '',
    raison: '',
});

const submitForm = () => {
    showStoreLoader();
    form.post(route('academique.exam-finance.store'), {
        onSuccess: () => { setTimeout(() => hideLoader(), 500); },
        onError: () => { hideLoader(); },
    });
};
</script>

<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('actions.create') || 'Créer' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <div class="row g-3 custom-input">
                                    <!-- Section 1: Association -->
                                    <div class="col-sm-6">
                                        <label class="form-label">Examen <span class="text-danger">*</span></label>
                                        <SearchableSelect v-model="form.exam_id" :options="exams" optionValue="id" optionLabel="label" placeholder="Sélectionner un examen" class="form-control-sm" />
                                        <div v-if="form.errors.exam_id" class="text-danger small mt-1">{{ form.errors.exam_id }}</div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">Poste de Recette <span class="text-danger">*</span></label>
                                        <SearchableSelect v-model="form.recette_id" :options="postes" optionValue="id" optionLabel="label" placeholder="Sélectionner un poste" class="form-control-sm" />
                                        <div v-if="form.errors.recette_id" class="text-danger small mt-1">{{ form.errors.recette_id }}</div>
                                    </div>

                                    <!-- Section 2: Montants -->
                                    <div class="col-sm-6">
                                        <label class="form-label">Montant Financé (FCFA)</label>
                                        <input v-model.number="form.montant_finance" type="number" step="0.01" min="0" class="form-control form-control-sm" placeholder="0.00" />
                                        <div v-if="form.errors.montant_finance" class="text-danger small mt-1">{{ form.errors.montant_finance }}</div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">Pourcentage Couverture (%)</label>
                                        <input v-model.number="form.pourcentage_couverture" type="number" step="0.01" min="0" max="100" class="form-control form-control-sm" placeholder="0.00" />
                                        <div v-if="form.errors.pourcentage_couverture" class="text-danger small mt-1">{{ form.errors.pourcentage_couverture }}</div>
                                    </div>

                                    <!-- Section 3: Dates -->
                                    <div class="col-sm-6">
                                        <label class="form-label">Date Facturation</label>
                                        <input v-model="form.date_facturation" type="date" class="form-control form-control-sm" />
                                        <div v-if="form.errors.date_facturation" class="text-danger small mt-1">{{ form.errors.date_facturation }}</div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">Date Limite Paiement</label>
                                        <input v-model="form.date_limite_paiement" type="date" class="form-control form-control-sm" />
                                        <div v-if="form.errors.date_limite_paiement" class="text-danger small mt-1">{{ form.errors.date_limite_paiement }}</div>
                                    </div>

                                    <!-- Section 4: Notes -->
                                    <div class="col-sm-6">
                                        <label class="form-label">Notes</label>
                                        <textarea v-model="form.notes" class="form-control form-control-sm" rows="2" placeholder="Observations..."></textarea>
                                        <div v-if="form.errors.notes" class="text-danger small mt-1">{{ form.errors.notes }}</div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">Raison (optionnel)</label>
                                        <textarea v-model="form.raison" class="form-control form-control-sm" rows="2" placeholder="Justification..."></textarea>
                                    </div>

                                    <!-- Section 5: État (dernier) -->
                                    <div class="col-sm-6">
                                        <label class="form-label">Statut</label>
                                        <SearchableSelect v-model="form.etat_financement" :options="etatOptions" optionValue="id" optionLabel="libelle" placeholder="Sélectionner" class="form-control-sm" />
                                    </div>
                                </div>

                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col text-end">
                                        <Link :href="route('academique.exam-finance.index')" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}
                                        </Link>
                                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                            <i class="fa fa-save"></i> {{ t('actions.validate') || 'Valider' }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
