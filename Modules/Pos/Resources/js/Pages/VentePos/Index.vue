<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import PosLayout from '@/Layouts/PosLayout.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import { useLocale } from '@/Composables/useLocale';
import { usePermissions } from '@/Composables/usePermissions';
import { useLoader } from '@/Composables/useLoader';
// Utiliser PosLayout comme layout
defineOptions({ layout: PosLayout });
const { t } = useLocale();
const { can } = usePermissions();
const { showLoader, hideLoader } = useLoader();
const props = defineProps({
    ventes: Object,
    sessions: Array,
    employes: Array,
    pointsVente: Array,
    modePaiements: Array,
    filters: Object,
});
// Filtres
const searchFilters = ref({
    session_id: props.filters?.session_id || '',
    statut: props.filters?.statut || '',
    mode_paiement: props.filters?.mode_paiement || '',
    employe_id: props.filters?.employe_id || '',
});
// Modal de détails de vente
const showVenteModal = ref(false);
const selectedVente = ref(null);
// Modal de confirmation validation
const showValidationModal = ref(false);
// Modal de confirmation annulation
const showAnnulationModal = ref(false);
const annulationMotif = ref('');
// Loading state
const actionLoading = ref(false);
// Statuts disponibles
const statuts = [
    { value: 'en_attente', label: t('pos.ventePos.statuts.en_attente') },
    { value: 'validee', label: t('pos.ventePos.statuts.validee') },
    { value: 'annulee', label: t('pos.ventePos.statuts.annulee') },
    { value: 'remboursee', label: t('pos.ventePos.statuts.remboursee') },
    { value: 'partielle', label: t('pos.ventePos.statuts.partielle') },
];
// Recherche
const search = () => {
    router.get(route('ventepos.index'), searchFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};
const resetFilters = () => {
    searchFilters.value = {
        session_id: '',
        statut: '',
        mode_paiement: '',
        employe_id: '',
    };
    search();
};
// Classe du badge statut
const getStatutBadgeClass = (statut) => {
    const classes = {
        'en_attente': 'badge-warning',
        'validee': 'badge-success',
        'annulee': 'badge-danger',
        'remboursee': 'badge-info',
        'partielle': 'badge-secondary',
};
    return classes[statut] || 'badge-secondary';
};
// Libellé du statut
const getStatutLabel = (statut) => {
    return t(`pos.ventePos.statuts.${statut}`) || statut;
};
// Libellé du mode de paiement
const getModePaiementLabel = (mode) => {
    return t(`pos.ventePos.modePaiement.${mode}`) || mode;
};
// Navigation
const goBack = () => {
    router.visit(route('session-caisse-caissier.index'));
};
const goToNewVente = () => {
    router.visit(route('ventepos.create'));
};
// Formater montant
const formatMontant = (montant) => {
    if (!montant) return '0';
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(montant);
};
// ========================================
// GESTION MODAL VENTE
// ========================================
// Ouvrir le modal de détails de vente
const openVenteModal = (vente) => {
    selectedVente.value = vente;
    showVenteModal.value = true;
};
// Fermer le modal de détails de vente
const closeVenteModal = () => {
    showVenteModal.value = false;
    selectedVente.value = null;
};
// ========================================
// VALIDATION DU PAIEMENT
// ========================================
// Ouvrir le modal de confirmation de validation
const openValidationModal = () => {
    showValidationModal.value = true;
};
// Soumettre la validation du paiement
const submitValidation = () => {
    if (!selectedVente.value) return;
    actionLoading.value = true;
    showValidationModal.value = false;
    showLoader(t('pos.ventePos.messages.validationEnCours'), t('pos.sessionCaisse.messages.veuilezPatienter'), 'success');
    router.post(route('ventepos.validate-paiement', selectedVente.value.id), {}, {
        onSuccess: () => {
            closeVenteModal();
        },
        onFinish: () => {
            actionLoading.value = false;
            hideLoader();
        },
    });
};
// ========================================
// ANNULATION DE LA VENTE
// ========================================
// Ouvrir le modal d'annulation
const openAnnulationModal = () => {
    annulationMotif.value = '';
    showAnnulationModal.value = true;
};
// Soumettre l'annulation de la vente
const submitAnnulation = () => {
    if (!selectedVente.value || !annulationMotif.value.trim()) return;
    actionLoading.value = true;
    showAnnulationModal.value = false;
    showLoader(t('pos.ventePos.messages.annulationEnCours'), t('pos.sessionCaisse.messages.veuilezPatienter'), 'danger');
    router.post(route('ventepos.cancel', selectedVente.value.id), {
        motif: annulationMotif.value,
    }, {
        onSuccess: () => {
            closeVenteModal();
        },
        onFinish: () => {
            actionLoading.value = false;
            hideLoader();
        },
});
    };
// ========================================
// IMPRESSION
// ========================================
// Imprimer le ticket de vente directement
const printVente = (vente = null) => {
    const venteData = vente || selectedVente.value;
    if (!venteData) return;
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Ticket - ${venteData.reference || 'N/A'}</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Courier New', monospace; font-size: 12px; max-width: 300px; margin: 0 auto; padding: 15px; }
                .header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 10px; margin-bottom: 10px; }
                .header h2 { font-size: 18px; margin-bottom: 5px; }
                .header .ref { font-size: 14px; font-weight: bold; }
                .header .point-vente { font-size: 11px; color: #555; margin-top: 3px; }
                .info-section { margin: 10px 0; }
                .info-row { display: flex; justify-content: space-between; margin: 4px 0; font-size: 11px; }
                .info-label { color: #666; }
                .divider { border-bottom: 1px dashed #000; margin: 10px 0; }
                .articles { margin: 10px 0; }
                .articles-title { font-weight: bold; margin-bottom: 8px; font-size: 12px; border-bottom: 1px solid #000; padding-bottom: 3px; }
                .article-item { margin: 6px 0; padding: 4px 0; border-bottom: 1px dotted #ccc; }
                .article-name { font-weight: bold; font-size: 11px; }
                .article-details { display: flex; justify-content: space-between; font-size: 10px; color: #555; margin-top: 2px; }
                .article-total { text-align: right; font-weight: bold; font-size: 11px; }
                .total-section { margin-top: 15px; padding-top: 10px; border-top: 2px dashed #000; }
                .total-row { display: flex; justify-content: space-between; font-size: 14px; font-weight: bold; }
                .total-value { font-size: 16px; }
                .footer { text-align: center; margin-top: 15px; padding-top: 10px; border-top: 1px dashed #000; font-size: 10px; color: #666; }
                .status { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
                .status-validee { background: #d4edda; color: #155724; }
                .status-en_attente { background: #fff3cd; color: #856404; }
                .status-annulee { background: #f8d7da; color: #721c24; }
                .status-remboursee { background: #d1ecf1; color: #0c5460; }
                .status-partielle { background: #e2e3e5; color: #383d41; }
                @media print { body { max-width: none; } }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>SmilPay POS</h2>
                <div class="ref">${venteData.reference || 'N/A'}</div>
                <div class="point-vente">${venteData.point_vente || ''}</div>
            </div>
            <div class="info-section">
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    <span>${venteData.date || 'N/A'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Caissier:</span>
                    <span>${venteData.caissier || 'N/A'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Mode:</span>
                    <span>${getModePaiementLabel(venteData.mode_paiement)}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut:</span>
                    <span class="status status-${venteData.statut}">${getStatutLabel(venteData.statut)}</span>
                </div>
            </div>
            <div class="divider"></div>
            <div class="articles">
                <div class="articles-title">ARTICLES</div>
                ${(venteData.lignes || []).map(ligne => `
                    <div class="article-item">
                        <div class="article-name">${ligne.libelle || 'Article'}</div>
                        <div class="article-details">
                            <span>${ligne.quantite} x ${formatMontant(ligne.prix_unitaire)}</span>
                            <span class="article-total">${formatMontant(ligne.total_ligne)} ${venteData.devise}</span>
                        </div>
                    </div>
                `).join('')}
            </div>
            <div class="total-section">
                <div class="total-row">
                    <span>TOTAL:</span>
                    <span class="total-value">${formatMontant(venteData.total)} ${venteData.devise}</span>
                </div>
            </div>
            <div class="footer">
                <p>Merci pour votre achat!</p>
                <p>SmilPay - ${new Date().toLocaleDateString('fr-FR')}</p>
            </div>
        </body>
        </html>
    `;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.print();
};
</script>
<template>
    <!-- Main Content -->
    <div class="pos-main-full">
            <section class="pos-content">
                <!-- Actions Header -->
                <div class="content-header">
                    <button @click="goBack" class="btn-back">
                        <i class="fas fa-arrow-left"></i>
                        <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                    </button>
                    <button v-if="can('ventepos-create')" @click="goToNewVente" class="btn-primary-pos">
                        <i class="fas fa-plus"></i>
                        {{ t('pos.ventePos.actions.create') }}
                    </button>
                </div>
                <!-- Filtres -->
                <div class="filters-card">
                    <form class="filters-form" @submit.prevent="search">
                        <!-- <div class="filter-group">
                            <select v-model="searchFilters.session_id" class="filter-select">
                                <option value="">{{ t('pos.ventePos.fields.session') }}</option>
                                <option v-for="session in sessions" :key="session.value" :value="session.value">
                                    {{ session.label }}
                                </option>
                            </select>
                        </div> -->
                        <div class="filter-group">
                            <select v-model="searchFilters.statut" class="filter-select">
                                <option value="">{{ t('pos.ventePos.fields.statut') }}</option>
                                <option v-for="statut in statuts" :key="statut.value" :value="statut.value">
                                    {{ statut.label }}
                                </option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <select v-model="searchFilters.mode_paiement" class="filter-select">
                                <option value="">{{ t('pos.ventePos.fields.modePaiement') }}</option>
                                <option v-for="mode in modePaiements" :key="mode.value" :value="mode.value">
                                    {{ mode.label }}
                                </option>
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn-filter">
                                <i class="fas fa-search"></i>
                            </button>
                            <button type="button" class="btn-filter-reset" @click="resetFilters">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <!-- Tableau des ventes -->
                <div class="table-card">
                    <div class="table-responsive">
                        <table class="pos-table">
                            <thead>
                                <tr>
                                    <th>{{ t('pos.ventePos.fields.reference') }}</th>
                                    <th>{{ t('pos.ventePos.fields.total') }}</th>
                                    <th>{{ t('pos.ventePos.fields.modePaiement') }}</th>
                                    <th>{{ t('pos.ventePos.fields.statut') }}</th>
                                    <th>{{ t('pos.ventePos.fields.caissier') }}</th>
                                    <th>{{ t('pos.ventePos.fields.date') }}</th>
                                    <th class="actions-col">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="ventes.data && ventes.data.length > 0">
                                    <tr v-for="vente in ventes.data" :key="vente.id">
                                        <td class="ref-cell">
                                            <span class="ref-badge">{{ vente.reference }}</span>
                                        </td>
                                        <td class="amount-cell">{{ vente.total }}</td>
                                        <td>
                                            <span class="mode-badge">{{ getModePaiementLabel(vente.mode_paiement) }}</span>
                                        </td>
                                        <td>
                                            <span :class="['status-badge', getStatutBadgeClass(vente.statut)]">
                                                {{ getStatutLabel(vente.statut) }}
                                            </span>
                                        </td>
                                        <td>{{ vente.caissier }}</td>
                                        <td>{{ vente.date }}</td>
                                        <td class="actions-col">
                                            <div class="action-buttons">
                                                <!-- Bouton voir détails - redirige vers page Show -->
                                                <Link
                                                    :href="route('ventepos.show', vente.id)"
                                                    class="btn-action btn-view"
                                                    :title="t('actions.view')"
                                                >
                                                    <i class="fas fa-eye"></i>
                                                </Link>
                                                <!-- Bouton modifier (si en attente) -->
                                                <Link
                                                    v-if="can('ventepos-edit') && vente.statut === 'en_attente'"
                                                    :href="route('ventepos.edit', vente.id)"
                                                    class="btn-action btn-edit"
                                                    :title="t('actions.edit')"
                                                >
                                                    <i class="fas fa-edit"></i>
                                                </Link>
                                                <!-- Bouton imprimer -->
                                                <button
                                                    @click="printVente(vente)"
                                                    class="btn-action btn-print"
                                                    :title="t('pos.ventePos.actions.print')"
                                                >
                                                    <i class="fas fa-print"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="7" class="empty-cell">
                                        <div class="empty-state">
                                            <i class="fas fa-shopping-cart"></i>
                                            <span>{{ t('common.emptyList') }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div class="pagination-wrapper">
                        <Pagination :data="ventes" />
                    </div>
                </div>
            </section>
        </div>
    <!-- Modal de détails de vente -->
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="showVenteModal && selectedVente" class="modal-overlay" @click.self="closeVenteModal">
                <div class="modal-container modal-large">
                    <div class="modal-header">
                        <div class="modal-icon modal-icon-primary">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <h3 class="modal-title">{{ selectedVente.reference }}</h3>
                        <span :class="['status-badge', getStatutBadgeClass(selectedVente.statut)]">
                            {{ getStatutLabel(selectedVente.statut) }}
                        </span>
                        <button class="modal-close" @click="closeVenteModal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Informations générales -->
                        <div class="info-section">
                            <h4 class="section-title">
                                <i class="fas fa-info-circle"></i>
                                {{ t('pos.ventePos.sections.generalInfo') }}
                            </h4>
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="info-content">
                                        <span class="info-label">{{ t('pos.ventePos.fields.caissier') }}</span>
                                        <span class="info-value">{{ selectedVente.caissier }}</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                    <div class="info-content">
                                        <span class="info-label">{{ t('pos.ventePos.fields.date') }}</span>
                                        <span class="info-value">{{ selectedVente.date }}</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div class="info-content">
                                        <span class="info-label">{{ t('pos.ventePos.fields.modePaiement') }}</span>
                                        <span class="info-value">{{ getModePaiementLabel(selectedVente.mode_paiement) }}</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-cash-register"></i>
                                    </div>
                                    <div class="info-content">
                                        <span class="info-label">{{ t('pos.ventePos.fields.session') }}</span>
                                        <span class="info-value">Session {{ selectedVente.session_id }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Informations financières -->
                        <div class="info-section">
                            <h4 class="section-title">
                                <i class="fas fa-coins"></i>
                                {{ t('pos.ventePos.sections.financialInfo') }}
                            </h4>
                            <div class="financial-grid">
                                <div class="financial-item highlight">
                                    <span class="financial-label">{{ t('pos.ventePos.fields.total') }}</span>
                                    <span class="financial-value">{{ formatMontant(selectedVente.total) }} XOF</span>
                                </div>
                            </div>
                        </div>
                        <!-- Actions -->
                        <div class="vente-modal-actions">
                            <!-- Bouton Valider (si en_attente) -->
                            <button
                                v-if="selectedVente.statut === 'en_attente'"
                                @click="openValidationModal"
                                class="btn-action btn-success"
                                :disabled="actionLoading"
                            >
                                <i class="fas fa-check"></i>
                                {{ t('pos.ventePos.actions.validatePaiement') }}
                            </button>
                            <!-- Bouton Annuler (si en_attente) -->
                            <button
                                v-if="selectedVente.statut === 'en_attente'"
                                @click="openAnnulationModal"
                                class="btn-action btn-danger"
                                :disabled="actionLoading"
                            >
                                <i class="fas fa-times"></i>
                                {{ t('pos.ventePos.actions.cancel') }}
                            </button>
                            <!-- Bouton Imprimer -->
                            <button
                                @click="printVente()"
                                class="btn-action btn-primary"
                            >
                                <i class="fas fa-print"></i>
                                {{ t('pos.ventePos.actions.print') }}
                            </button>
                            <!-- Bouton Fermer -->
                            <button @click="closeVenteModal" class="btn-action btn-secondary">
                                <i class="fas fa-times"></i>
                                {{ t('actions.close') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
    <!-- Modal Confirmation Validation -->
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="showValidationModal" class="modal-overlay" @click.self="showValidationModal = false">
                <div class="modal-container">
                    <div class="modal-header">
                        <div class="modal-icon modal-icon-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3 class="modal-title">{{ t('pos.ventePos.modals.validateTitle') }}</h3>
                        <button class="modal-close" @click="showValidationModal = false">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="modal-message">{{ t('pos.ventePos.confirmations.validatePaiement') }}</p>
                        <div class="validation-summary">
                            <div class="summary-row">
                                <span class="summary-label">{{ t('pos.ventePos.fields.reference') }}:</span>
                                <span class="summary-value">{{ selectedVente?.reference }}</span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">{{ t('pos.ventePos.fields.total') }}:</span>
                                <span class="summary-value highlight">{{ formatMontant(selectedVente?.total) }} XOF</span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">{{ t('pos.ventePos.fields.modePaiement') }}:</span>
                                <span class="summary-value">{{ getModePaiementLabel(selectedVente?.mode_paiement) }}</span>
                            </div>
                        </div>
                        <div class="modal-actions">
                            <button class="btn-modal btn-secondary" @click="showValidationModal = false">
                                {{ t('actions.cancel') }}
                            </button>
                            <button class="btn-modal btn-success" @click="submitValidation" :disabled="actionLoading">
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
                        <h3 class="modal-title">{{ t('pos.ventePos.modals.cancelTitle') }}</h3>
                        <button class="modal-close" @click="showAnnulationModal = false">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="modal-warning">{{ t('pos.ventePos.confirmations.cancelVente') }}</p>
                        <div class="form-group">
                            <label class="form-label">{{ t('pos.ventePos.fields.motif') }} <span class="text-danger">*</span></label>
                            <textarea
                                v-model="annulationMotif"
                                class="form-control"
                                rows="3"
                                :placeholder="t('pos.ventePos.placeholders.motifAnnulation')"
                            ></textarea>
                        </div>
                        <div class="modal-actions">
                            <button class="btn-modal btn-secondary" @click="showAnnulationModal = false">
                                {{ t('actions.cancel') }}
                            </button>
                            <button
                                class="btn-modal btn-danger"
                                @click="submitAnnulation"
                                :disabled="!annulationMotif.trim() || actionLoading"
                            >
                                <i class="fas fa-times"></i>
                                {{ t('pos.ventePos.actions.confirmCancel') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
<style scoped>
/* ========================================
   MODAL STYLES
   ======================================== */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
}
.modal-container {
    background: #fff;
    border-radius: 20px;
    width: 100%;
    max-width: 480px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}
.modal-large {
    max-width: 700px;
}
.modal-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    border-bottom: 1px solid #E2E8F0;
    flex-wrap: wrap;
}
.modal-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}
.modal-icon-primary {
    background: rgba(0, 0, 0, 0.08);
    color: #000;
}
.modal-icon-success {
    background: rgba(46, 204, 113, 0.15);
    color: #2ECC71;
}
.modal-icon-danger {
    background: rgba(231, 76, 60, 0.15);
    color: #E74C3C;
}
.modal-title {
    flex: 1;
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
}
.modal-header .status-badge {
    margin-left: auto;
    margin-right: 0.5rem;
}
.modal-close {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #F1F3F6;
    border: none;
    border-radius: 10px;
    color: #718096;
    cursor: pointer;
    transition: all 0.2s;
}
.modal-close:hover {
    background: #E2E8F0;
    color: #2D3748;
}
.modal-body {
    padding: 1.5rem;
}
.modal-message {
    text-align: center;
    color: #2D3748;
    margin-bottom: 1.5rem;
}
.modal-warning {
    text-align: center;
    color: #E74C3C;
    font-weight: 500;
    margin-bottom: 1.5rem;
}
.modal-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}
/* Section styles */
.info-section {
    margin-bottom: 1.5rem;
}
.section-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1rem;
    font-weight: 600;
    color: #2D3748;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #E2E8F0;
}
.section-title i {
    color: #718096;
}
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}
.info-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem;
    background: #F1F3F6;
    border-radius: 12px;
}
.info-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #000;
    color: #fff;
    border-radius: 10px;
    font-size: 1rem;
    flex-shrink: 0;
}
.info-content {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
}
.info-label {
    font-size: 0.8rem;
    color: #718096;
}
.info-value {
    font-weight: 600;
    color: #2D3748;
    word-break: break-word;
}
.financial-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}
.financial-item {
    padding: 1rem;
    background: #F1F3F6;
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
    color: #718096;
    margin-bottom: 0.5rem;
}
.financial-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #2D3748;
}
/* Vente Modal Actions */
.vente-modal-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #E2E8F0;
    justify-content: center;
}
.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-action:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.btn-action.btn-success {
    background: #2ECC71;
    color: #fff;
}
.btn-action.btn-success:hover:not(:disabled) {
    background: #27AE60;
    transform: translateY(-2px);
}
.btn-action.btn-danger {
    background: #E74C3C;
    color: #fff;
}
.btn-action.btn-danger:hover:not(:disabled) {
    background: #C0392B;
    transform: translateY(-2px);
}
.btn-action.btn-primary {
    background: #000;
    color: #fff;
}
.btn-action.btn-primary:hover:not(:disabled) {
    background: #333;
    transform: translateY(-2px);
}
.btn-action.btn-secondary {
    background: #F1F3F6;
    color: #2D3748;
}
.btn-action.btn-secondary:hover:not(:disabled) {
    background: #E2E8F0;
}
/* Button modal */
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
.btn-modal.btn-secondary {
    background: #F1F3F6;
    color: #2D3748;
}
.btn-modal.btn-secondary:hover {
    background: #E2E8F0;
}
.btn-modal.btn-success {
    background: #2ECC71;
    color: #fff;
}
.btn-modal.btn-success:hover:not(:disabled) {
    background: #27AE60;
}
.btn-modal.btn-danger {
    background: #E74C3C;
    color: #fff;
}
.btn-modal.btn-danger:hover:not(:disabled) {
    background: #C0392B;
}
.btn-modal:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
/* Validation summary */
.validation-summary {
    background: #F1F3F6;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
}
.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
}
.summary-row:not(:last-child) {
    border-bottom: 1px solid #E2E8F0;
}
.summary-label {
    color: #718096;
    font-size: 0.9rem;
}
.summary-value {
    font-weight: 600;
    color: #2D3748;
}
.summary-value.highlight {
    color: #000;
    font-size: 1.1rem;
}
/* Form controls */
.form-group {
    margin-bottom: 1.25rem;
}
.form-label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #2D3748;
}
.text-danger {
    color: #E74C3C;
}
.form-control {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid #E2E8F0;
    border-radius: 12px;
    font-size: 0.95rem;
    transition: border-color 0.2s;
    resize: vertical;
}
.form-control:focus {
    outline: none;
    border-color: #000;
}
/* Status badge */
.status-badge {
    display: inline-block;
    padding: 0.35rem 0.85rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}
.badge-success {
    background: rgba(46, 204, 113, 0.15);
    color: #27AE60;
}
.badge-warning {
    background: rgba(243, 156, 18, 0.15);
    color: #F39C12;
}
.badge-danger {
    background: rgba(231, 76, 60, 0.15);
    color: #E74C3C;
}
.badge-info {
    background: rgba(52, 152, 219, 0.15);
    color: #3498DB;
}
.badge-secondary {
    background: rgba(108, 117, 125, 0.15);
    color: #6C757D;
}
/* Button print in table */
.btn-print {
    background: #3498DB !important;
    color: #fff !important;
}
.btn-print:hover {
    background: #2980B9 !important;
}
/* Modal Transition */
.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-from .modal-container,
.modal-leave-to .modal-container {
    transform: scale(0.9);
}
/* Responsive */
@media (max-width: 768px) {
    .modal-large {
        max-width: 95%;
    }
    .info-grid,
    .financial-grid {
        grid-template-columns: 1fr;
    }
    .vente-modal-actions {
        flex-direction: column;
    }
    .vente-modal-actions .btn-action {
        width: 100%;
        justify-content: center;
    }
}
/* Force table header noir */
:deep(.pos-table th) {
    background: #000000 !important;
    color: #ffffff !important;
    padding: 1rem;
    text-align: left;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: none;
}
:deep(.pos-table thead) {
    background: #000000 !important;
}
:deep(.pos-table thead tr) {
    background: #000000 !important;
}
</style>
