<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const isCollapsed = ref(false);
const toggleCollapse = () => { isCollapsed.value = !isCollapsed.value; };

const props = defineProps({
    financing: Object,
});

const form = useForm({
    montant_finance: props.financing.montant_finance || null,
    pourcentage_couverture: props.financing.pourcentage_couverture || null,
    date_facturation: props.financing.date_facturation || '',
    date_limite_paiement: props.financing.date_limite_paiement || '',
    notes: props.financing.notes || '',
    raison: '',
});

const etatBadgeClass = (etat) => {
    return { 'actif': 'bg-success', 'en-attente': 'bg-warning text-dark', 'clôturé': 'bg-secondary' }[etat] || 'bg-secondary';
};
const etatLabel = (etat) => {
    return { 'actif': 'Actif', 'en-attente': 'En attente', 'clôturé': 'Clôturé' }[etat] || etat;
};

const submitForm = () => {
    showUpdateLoader();
    form.put(route('academique.exam-finance.update', props.financing.id), {
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
                                <h5 class="title mb-0">{{ t('actions.edit') || 'Modifier' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />

                            <!-- Info état actuel -->
                            <div class="alert alert-info mb-3 py-2 small">
                                <strong>État actuel :</strong>
                                <span class="badge ms-1" :class="etatBadgeClass(financing.etat_financement)">{{ etatLabel(financing.etat_financement) }}</span>
                            </div>

                            <form @submit.prevent="submitForm">
                                <div class="row g-3 custom-input">
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
                                    <div class="col-sm-6">
                                        <label class="form-label">Notes</label>
                                        <textarea v-model="form.notes" class="form-control form-control-sm" rows="2" placeholder="Observations..."></textarea>
                                        <div v-if="form.errors.notes" class="text-danger small mt-1">{{ form.errors.notes }}</div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">Raison de la modification (optionnel)</label>
                                        <textarea v-model="form.raison" class="form-control form-control-sm" rows="2" placeholder="Justification..."></textarea>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col text-end">
                                        <Link :href="route('academique.exam-finance.show', financing.id)" class="btn btn-danger">
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
