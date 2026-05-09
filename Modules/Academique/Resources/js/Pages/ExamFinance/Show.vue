<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const isCollapsed = ref(false);
const toggleCollapse = () => { isCollapsed.value = !isCollapsed.value; };

const props = defineProps({
    financing: Object,
    auditLogs: Array,
});

const showRejectModal = ref(false);
const rejectReason = ref('');
const form = useForm({ raison: '' });

const validateFinancing = () => {
    form.post(route('academique.exam-finance.validate', props.financing.id), {
        onSuccess: () => form.reset(),
    });
};

const rejectFinancing = () => {
    form.raison = rejectReason.value;
    form.post(route('academique.exam-finance.reject', props.financing.id), {
        onSuccess: () => { form.reset(); showRejectModal.value = false; rejectReason.value = ''; },
    });
};

const closeFinancing = () => {
    form.post(route('academique.exam-finance.close', props.financing.id), {
        onSuccess: () => form.reset(),
    });
};

const etatBadgeClass = (etat) => {
    return { 'actif': 'bg-success', 'en-attente': 'bg-warning text-dark', 'clôturé': 'bg-secondary' }[etat] || 'bg-secondary';
};
const etatLabel = (etat) => {
    return { 'actif': 'Actif', 'en-attente': 'En attente', 'clôturé': 'Clôturé' }[etat] || etat;
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
                                <span class="dash-payment-badge"><i class="fa fa-eye"></i></span>
                                <h5 class="title mb-0">{{ t('actions.view') || 'Détails' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />

                            <div class="row g-3">
                                <!-- Colonne principale -->
                                <div class="col-lg-8">
                                    <!-- Infos Examen -->
                                    <div class="info-card mb-3">
                                        <h6 class="info-card-title"><i class="fa fa-calendar-check me-2"></i> Informations Examen</h6>
                                        <div class="row g-2">
                                            <div class="col-sm-6">
                                                <div class="info-item"><span class="info-label">École</span><span class="info-value">{{ financing.exam?.ecole || '-' }}</span></div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="info-item"><span class="info-label">Classe</span><span class="info-value">{{ financing.exam?.classe || '-' }}</span></div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="info-item"><span class="info-label">Matière</span><span class="info-value">{{ financing.exam?.matiere || '-' }}</span></div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="info-item"><span class="info-label">Date Examen</span><span class="info-value">{{ financing.exam?.date || '-' }}</span></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Poste Recette -->
                                    <div class="info-card mb-3">
                                        <h6 class="info-card-title"><i class="fa fa-coins me-2"></i> Poste de Recette</h6>
                                        <div class="row g-2">
                                            <div class="col-sm-6">
                                                <div class="info-item"><span class="info-label">Code</span><span class="info-value">{{ financing.poste?.code || '-' }}</span></div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="info-item"><span class="info-label">Libellé</span><span class="info-value">{{ financing.poste?.libelle || '-' }}</span></div>
                                            </div>
                                            <div class="col-12">
                                                <div class="info-item"><span class="info-label">Compte Comptable</span><span class="info-value">{{ financing.poste?.compte_comptable || '-' }}</span></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Détails Financement -->
                                    <div class="info-card mb-3">
                                        <h6 class="info-card-title"><i class="fa fa-file-invoice-dollar me-2"></i> Détails du Financement</h6>
                                        <div class="row g-2">
                                            <div class="col-sm-6">
                                                <div class="info-item">
                                                    <span class="info-label">Montant Financé</span>
                                                    <span class="info-value fw-bold">{{ financing.montant_finance ? Number(financing.montant_finance).toLocaleString('fr-FR') + ' FCFA' : '-' }}</span>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="info-item"><span class="info-label">Couverture</span><span class="info-value">{{ financing.pourcentage_couverture ? financing.pourcentage_couverture + '%' : '-' }}</span></div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="info-item"><span class="info-label">Date Facturation</span><span class="info-value">{{ financing.date_facturation || '-' }}</span></div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="info-item"><span class="info-label">Date Limite</span><span class="info-value">{{ financing.date_limite_paiement || '-' }}</span></div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="info-item">
                                                    <span class="info-label">État</span>
                                                    <span class="badge" :class="etatBadgeClass(financing.etat_financement)">{{ etatLabel(financing.etat_financement) }}</span>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="info-item">
                                                    <span class="info-label">Jours Restants</span>
                                                    <span :class="financing.est_expirée ? 'text-danger fw-bold' : ''">
                                                        {{ financing.est_expirée ? 'Expiré' : (financing.jours_restants ?? '-') + ' j' }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-12" v-if="financing.notes">
                                                <div class="info-item"><span class="info-label">Notes</span><span class="info-value">{{ financing.notes }}</span></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Historique -->
                                    <div class="info-card" v-if="auditLogs && auditLogs.length">
                                        <h6 class="info-card-title"><i class="fa fa-history me-2"></i> Historique</h6>
                                        <div v-for="log in auditLogs" :key="log.id" class="audit-log-item">
                                            <div class="d-flex justify-content-between">
                                                <strong>{{ log.action }}</strong>
                                                <small class="text-muted">{{ log.created_at }}</small>
                                            </div>
                                            <small class="text-muted">Par {{ log.auteur }}</small>
                                            <p v-if="log.raison" class="mb-0 small mt-1">{{ log.raison }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Colonne Actions -->
                                <div class="col-lg-4">
                                    <div class="info-card">
                                        <h6 class="info-card-title"><i class="fa fa-cogs me-2"></i> Actions</h6>
                                        <div class="d-grid gap-2">
                                            <Link :href="route('academique.exam-finance.edit', financing.id)" class="btn btn-primary">
                                                <i class="fa fa-edit me-1"></i> Modifier
                                            </Link>
                                            <button v-if="financing.etat_financement === 'en-attente'" @click="validateFinancing" :disabled="form.processing" class="btn btn-success">
                                                <i class="fa fa-check me-1"></i> Valider
                                            </button>
                                            <button v-if="financing.etat_financement !== 'clôturé'" @click="showRejectModal = true" class="btn btn-warning">
                                                <i class="fa fa-times me-1"></i> Rejeter
                                            </button>
                                            <button v-if="financing.etat_financement !== 'clôturé'" @click="closeFinancing" :disabled="form.processing" class="btn btn-secondary">
                                                <i class="fa fa-lock me-1"></i> Clôturer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bouton retour -->
                            <div class="row mt-3">
                                <div class="col text-end">
                                    <Link :href="route('academique.exam-finance.index')" class="btn btn-danger">
                                        <i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Rejet -->
        <div v-if="showRejectModal" class="modal-overlay" @click.self="showRejectModal = false">
            <div class="modal-box" style="width: 450px;">
                <div class="modal-box-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fa fa-times-circle me-2"></i> Rejeter le Financement</h5>
                </div>
                <div class="modal-box-body">
                    <label class="form-label fw-bold">Raison du rejet <span class="text-danger">*</span></label>
                    <textarea v-model="rejectReason" class="form-control" rows="4" placeholder="Expliquez pourquoi..."></textarea>
                </div>
                <div class="modal-box-footer">
                    <button class="btn btn-secondary" @click="showRejectModal = false">Annuler</button>
                    <button class="btn btn-warning" @click="rejectFinancing" :disabled="form.processing || !rejectReason.trim()">
                        <i class="fa fa-times me-1"></i> Rejeter
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.info-card {
    background: white;
    border-radius: 10px;
    padding: 1rem 1.2rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    border: 1px solid #f0f0f0;
}
.info-card-title {
    font-weight: 700;
    color: #0B5697;
    border-bottom: 2px solid #0FBCAF;
    padding-bottom: 0.4rem;
    margin-bottom: 0.8rem;
}
.info-item {
    display: flex;
    flex-direction: column;
    padding: 0.3rem 0;
}
.info-label {
    font-size: 0.75rem;
    color: #777;
    text-transform: uppercase;
}
.info-value {
    font-size: 0.95rem;
    color: #333;
}
.audit-log-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
    border-left: 3px solid #0B5697;
    padding-left: 0.8rem;
    margin-bottom: 0.5rem;
}

.modal-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.5); display: flex;
    align-items: center; justify-content: center; z-index: 9999;
}
.modal-box { background: white; border-radius: 12px; max-width: 90%; overflow: hidden; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); }
.modal-box-header { padding: 1rem 1.5rem; }
.modal-box-body { padding: 1.5rem; }
.modal-box-footer { padding: 1rem 1.5rem; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 0.5rem; }
</style>
