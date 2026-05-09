<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import PosLayout from '@/Layouts/PosLayout.vue';
import { useLoader } from '@/Composables/useLoader';
import { usePermissions } from '@/Composables/usePermissions';
import { useLocale } from '@/Composables/useLocale';
// Utiliser PosLayout comme layout
defineOptions({ layout: PosLayout });
const { t } = useLocale();
const { showLoader, hideLoader } = useLoader();
const { can } = usePermissions();
const props = defineProps({
    title: String,
    session: Object,
});
const showValidationModal = ref(false);
const showAnnulationModal = ref(false);
const annulationMotif = ref('');
// Formater les montants
const formatMontant = (montant, devise = null) => {
    if (montant === null || montant === undefined) return '0';
    const currency = devise || props.session?.devise || 'XOF';
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(montant) + ' ' + currency;
};
// Classe du badge statut
const getStatutBadgeClass = (statut) => {
    const classes = {
        'attente': 'badge-warning',
        'ouverte': 'badge-success',
        'fermee': 'badge-secondary',
        'annulee': 'badge-danger',
        'attente_validation': 'badge-info',
};
    return classes[statut] || 'badge-secondary';
};
// Libellé du statut
const getStatutLabel = (statut) => {
    return t(`pos_session_statut_${statut}`) || statut;
};
// Retour
const goBack = () => {
    router.visit(route('session-caisse-manager.index'));
};
// Valider la cloture
const openValidationModal = () => {
    showValidationModal.value = true;
};
const submitValidation = () => {
    showValidationModal.value = false;
    showLoader(t('pos.sessionCaisse.messages.validationEnCours'), t('pos.sessionCaisse.messages.veuilezPatienter'), 'primary');
    router.post(route('session-caisse-manager.validate', props.session.id), {}, {
        onFinish: () => hideLoader(),
    });
};
// Annuler la session
const openAnnulationModal = () => {
    annulationMotif.value = '';
    showAnnulationModal.value = true;
};
const submitAnnulation = () => {
    if (!annulationMotif.value.trim()) return;
    showAnnulationModal.value = false;
    showLoader(t('pos.sessionCaisse.messages.annulationEnCours'), t('pos.sessionCaisse.messages.veuilezPatienter'), 'danger');
    router.post(route('session-caisse-manager.cancel', props.session.id), { motif: annulationMotif.value }, {
        onFinish: () => hideLoader(),
    });
};
</script>
<template>
    <!-- Main Content -->
    <div class="pos-main-scrollable">
            <div class="session-details-container">
                <!-- Carte principale -->
                <div class="session-card">
                    <!-- Header de la session -->
                    <div class="session-header">
                        <div class="session-info">
                            <div class="session-caisse">
                                <i class="fas fa-cash-register"></i>
                                <span>{{ session.caisse_nom }}</span>
                            </div>
                            <span :class="['status-badge', getStatutBadgeClass(session.statut)]">
                                {{ getStatutLabel(session.statut) }}
                            </span>
                        </div>
                    </div>
                    <!-- Corps de la carte -->
                    <div class="session-body">
                        <!-- Informations de base -->
                        <div class="info-section">
                            <h3 class="section-title">
                                <i class="fas fa-info-circle"></i>
                                {{ t('pos.sessionCaisse.sections.generalInfo') }}
                            </h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="info-content">
                                        <span class="info-label">{{ t('pos.sessionCaisse.fields.caissier') }}</span>
                                        <span class="info-value">{{ session.caissier_nom }}</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-tablet-alt"></i>
                                    </div>
                                    <div class="info-content">
                                        <span class="info-label">{{ t('pos.sessionCaisse.fields.terminal') }}</span>
                                        <span class="info-value">{{ session.terminal_numero || 'N/A' }}</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-door-open"></i>
                                    </div>
                                    <div class="info-content">
                                        <span class="info-label">{{ t('pos.sessionCaisse.fields.dateOuverture') }}</span>
                                        <span class="info-value">{{ session.date_ouverture || 'N/A' }}</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-door-closed"></i>
                                    </div>
                                    <div class="info-content">
                                        <span class="info-label">{{ t('pos.sessionCaisse.fields.dateFermeture') }}</span>
                                        <span class="info-value">{{ session.date_fermeture || 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Informations financieres -->
                        <div class="info-section" v-if="session.statut === 'ouverte' || session.statut === 'fermee' || session.statut === 'attente_validation'">
                            <h3 class="section-title">
                                <i class="fas fa-coins"></i>
                                {{ t('pos.sessionCaisse.sections.financialInfo') }}
                            </h3>
                            <div class="financial-grid">
                                <div class="financial-item">
                                    <span class="financial-label">{{ t('pos.sessionCaisse.financial.fondOuverture') }}</span>
                                    <span class="financial-value">{{ session.fond_ouverture }} {{ session.devise }}</span>
                                </div>
                                <div class="financial-item" v-if="session.total_encaisse !== null">
                                    <span class="financial-label">{{ t('pos.sessionCaisse.financial.totalEncaisse') }}</span>
                                    <span class="financial-value">{{ session.total_encaisse }} {{ session.devise }}</span>
                                </div>
                                <div class="financial-item" v-if="session.total_reel !== null">
                                    <span class="financial-label">{{ t('pos.sessionCaisse.financial.totalReel') }}</span>
                                    <span class="financial-value">{{ formatMontant(session.total_reel, session.devise) }}</span>
                                </div>
                                <div class="financial-item highlight" v-if="session.ecart !== null">
                                    <span class="financial-label">{{ t('pos.sessionCaisse.financial.ecart') }}</span>
                                    <span class="financial-value" :class="session.ecart >= 0 ? 'text-success' : 'text-danger'">
                                        {{ formatMontant(session.ecart, session.devise) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Actions -->
                    <div class="session-actions">
                        <!-- Valider la cloture (si attente_validation) -->
                        <button
                            v-if="session.statut === 'attente_validation' && can('session-caisse-manager-validate')"
                            @click="openValidationModal"
                            class="btn-action btn-success"
                        >
                            <i class="fas fa-check"></i>
                            {{ t('pos.sessionCaisse.actions.validerCloture') }}
                        </button>
                        <!-- Annuler la session -->
                        <button
                            v-if="(session.statut === 'attente' || session.statut === 'attente_validation') && can('session-caisse-manager-cancel')"
                            @click="openAnnulationModal"
                            class="btn-action btn-danger"
                        >
                            <i class="fas fa-times"></i>
                            {{ t('pos.sessionCaisse.actions.annuler') }}
                        </button>
                        <!-- Retour -->
                        <button @click="goBack" class="btn-action btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Validation -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showValidationModal" class="modal-overlay" @click.self="showValidationModal = false">
                    <div class="modal-container">
                        <div class="modal-header">
                            <div class="modal-icon modal-icon-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3 class="modal-title">{{ t('pos.sessionCaisse.modals.validateTitle') }}</h3>
                            <button class="modal-close" @click="showValidationModal = false">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p class="modal-message">{{ t('pos.sessionCaisse.confirmations.validerCloture') }}</p>
                            <div class="modal-actions">
                                <button class="btn-modal btn-secondary" @click="showValidationModal = false">
                                    {{ t('actions.cancel') }}
                                </button>
                                <button class="btn-modal btn-success" @click="submitValidation">
                                    <i class="fas fa-check"></i>
                                    {{ t('actions.confirm') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
        <!-- Modal Annulation -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showAnnulationModal" class="modal-overlay" @click.self="showAnnulationModal = false">
                    <div class="modal-container">
                        <div class="modal-header">
                            <div class="modal-icon modal-icon-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h3 class="modal-title">{{ t('pos.sessionCaisse.modals.cancelTitle') }}</h3>
                            <button class="modal-close" @click="showAnnulationModal = false">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label class="form-label">{{ t('pos.sessionCaisse.fields.motif') }} <span class="text-danger">*</span></label>
                                <textarea
                                    v-model="annulationMotif"
                                    class="form-control"
                                    rows="3"
                                    :placeholder="t('pos.sessionCaisse.placeholders.motif')"
                                ></textarea>
                            </div>
                            <div class="modal-actions">
                                <button class="btn-modal btn-secondary" @click="showAnnulationModal = false">
                                    {{ t('actions.cancel') }}
                                </button>
                                <button class="btn-modal btn-danger" @click="submitAnnulation" :disabled="!annulationMotif.trim()">
                                    <i class="fas fa-times"></i>
                                    {{ t('pos.sessionCaisse.actions.confirmAnnuler') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
</template>
<style>
/* Les styles sont dans pos-caissier.css global */
/* Session details container */
.session-details-container {
    max-width: 800px;
    margin: 0 auto;
    width: 100%;
}
/* Session card */
.session-card {
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}
.session-header {
    background: #000;
    color: #fff;
    padding: 1.5rem 2rem;
}
.session-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.session-caisse {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.25rem;
    font-weight: 600;
}
.session-caisse i {
    font-size: 1.5rem;
}
/* Status Badge (in header - white text) */
.status-badge {
    padding: 0.5rem 1.25rem;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.9rem;
}
.badge-success { background: var(--pos-success, #2ECC71); color: #fff; }
.badge-warning { background: var(--pos-warning, #F39C12); color: #fff; }
.badge-danger { background: var(--pos-danger, #E74C3C); color: #fff; }
.badge-secondary { background: #6C757D; color: #fff; }
.badge-info { background: var(--pos-info, #3498DB); color: #fff; }
/* Session Body */
.session-body {
    padding: 2rem;
}
.info-section {
    margin-bottom: 2rem;
}
.info-section:last-child {
    margin-bottom: 0;
}
/* Info Grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}
.info-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--pos-bg, #F1F3F6);
    border-radius: 12px;
}
.info-icon {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #000;
    color: #fff;
    border-radius: 10px;
    font-size: 1.1rem;
}
.info-content {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}
.info-label {
    font-size: 0.8rem;
    color: var(--pos-text-muted, #718096);
}
.info-value {
    font-weight: 600;
    color: var(--pos-text, #2D3748);
}
/* Financial Grid */
.financial-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}
.financial-item {
    padding: 1.25rem;
    background: var(--pos-bg, #F1F3F6);
    border-radius: 12px;
    text-align: center;
}
.financial-item.highlight {
    background: rgba(0, 0, 0, 0.08);
    border: 2px solid #000;
}
.financial-label {
    display: block;
    font-size: 0.85rem;
    color: var(--pos-text-muted, #718096);
    margin-bottom: 0.5rem;
}
.financial-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--pos-text, #2D3748);
}
.text-success { color: var(--pos-success, #2ECC71) !important; }
.text-danger { color: var(--pos-danger, #E74C3C) !important; }
/* Session Actions */
.session-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    padding: 1.5rem 2rem;
    border-top: 2px solid var(--pos-border, #E2E8F0);
    justify-content: center;
}
.btn-action.btn-success { background: var(--pos-success, #2ECC71); color: #fff; }
.btn-action.btn-success:hover { background: #27AE60; transform: translateY(-2px); }
.btn-action.btn-danger { background: var(--pos-danger, #E74C3C); color: #fff; }
.btn-action.btn-danger:hover { background: #C0392B; transform: translateY(-2px); }
.btn-action.btn-secondary { background: var(--pos-bg, #F1F3F6); color: var(--pos-text, #2D3748); }
.btn-action.btn-secondary:hover { background: var(--pos-border, #E2E8F0); }
/* Modal message */
.modal-message {
    text-align: center;
    color: var(--pos-text, #2D3748);
    margin-bottom: 1.5rem;
}
/* Modal actions */
.modal-actions {
    display: flex;
    gap: 1rem;
}
.btn-modal {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-modal.btn-secondary { background: var(--pos-bg, #F1F3F6); color: var(--pos-text, #2D3748); }
.btn-modal.btn-secondary:hover { background: var(--pos-border, #E2E8F0); }
.btn-modal.btn-success { background: var(--pos-success, #2ECC71); color: #fff; }
.btn-modal.btn-success:hover { background: #27AE60; }
.btn-modal.btn-danger { background: var(--pos-danger, #E74C3C); color: #fff; }
.btn-modal.btn-danger:hover:not(:disabled) { background: #C0392B; }
.btn-modal:disabled { opacity: 0.5; cursor: not-allowed; }
/* Responsive */
@media (max-width: 768px) {
    .session-header {
        padding: 1rem 1.5rem;
    }
    .session-info {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    .session-body {
        padding: 1.5rem;
    }
    .info-grid,
    .financial-grid {
        grid-template-columns: 1fr;
    }
    .session-actions {
        flex-direction: column;
    }
    .btn-action {
        width: 100%;
        justify-content: center;
    }
}
</style>
