<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import PosLayout from '@/Layouts/PosLayout.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import { useLoader } from '@/Composables/useLoader';
import { usePermissions } from '@/Composables/usePermissions';
import { useLocale } from '@/Composables/useLocale';
// Utiliser PosLayout comme layout
defineOptions({ layout: PosLayout });
const { t } = useLocale();
const { showLoader, hideLoader } = useLoader();
const { can } = usePermissions();
const props = defineProps({
    sessionsCaisse: Object,
    ventes: Object,
    stats: Object,
    statuts: Array,
    statutsVente: Array,
    modePaiements: Array,
    sessionsForFilter: Array,
    caisses: Array,
    employes: Array,
    terminaux: Array,
    filters: Object,
    venteFilters: Object,
    error: String,
});
// Onglet actif
const activeTab = ref('dashboard');
// État des filtres Sessions
const searchFilters = ref({
    caisse_id: props.filters?.caisse_id || '',
    caissier_id: props.filters?.caissier_id || '',
    terminal_id: props.filters?.terminal_id || '',
    statut: props.filters?.statut || '',
});
// État des filtres Ventes
const venteSearchFilters = ref({
    vente_statut: props.venteFilters?.vente_statut || '',
    vente_mode_paiement: props.venteFilters?.vente_mode_paiement || '',
    vente_session_id: props.venteFilters?.vente_session_id || '',
});
// État du modal d'attribution
const showAttributionModal = ref(false);
const attributionForm = ref({
    caisse_id: '',
    caissier_id: '',
    terminal_id: '',
});
// État du modal de détails de session
const showSessionDetailModal = ref(false);
const selectedSession = ref(null);
// État des modals de validation et annulation
const showValidationModal = ref(false);
const showAnnulationModal = ref(false);
const annulationMotif = ref('');
// État du modal de détails de vente
const showVenteDetailModal = ref(false);
const selectedVente = ref(null);
// État du modal de remboursement
const showRefundModal = ref(false);
const refundForm = ref({
    type: 'total', // 'total', 'partiel', 'lignes'
    montant: '',
    motif: '',
    lignes: [],
});
const refundLoading = ref(false);
// Recherche Sessions
const searchSessions = () => {
    router.get(route('session-caisse-manager.index'), {
        ...searchFilters.value,
        ...venteSearchFilters.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};
// Reset filtres Sessions
const resetSessionFilters = () => {
    searchFilters.value = {
        caisse_id: '',
        caissier_id: '',
        terminal_id: '',
        statut: '',
    };
    searchSessions();
};
// Recherche Ventes
const searchVentes = () => {
    router.get(route('session-caisse-manager.index'), {
        ...searchFilters.value,
        ...venteSearchFilters.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};
const resetVenteFilters = () => {
    venteSearchFilters.value = {
        vente_statut: '',
        vente_mode_paiement: '',
        vente_session_id: '',
    };
    searchVentes();
};
// Ouvrir le modal d'attribution
const openAttributionModal = () => {
    attributionForm.value = {
        caisse_id: '',
        caissier_id: '',
        terminal_id: '',
};
    showAttributionModal.value = true;
};
// Fermer le modal
const closeAttributionModal = () => {
    showAttributionModal.value = false;
    attributionForm.value = {
        caisse_id: '',
        caissier_id: '',
        terminal_id: '',
};
};
// Soumettre l'attribution
const submitAttribution = () => {
    showLoader(t('pos.sessionCaisse.messages.attributionEnCours'), t('pos.sessionCaisse.messages.veuilezPatienter'), 'primary');
    router.post(route('session-caisse-manager.attribution'), attributionForm.value, {
        onSuccess: () => {
            closeAttributionModal();
        },
        onFinish: () => {
            hideLoader();
        },
    });
};
// Classe du badge statut Session
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
// Classe du badge statut Vente
const getVenteStatutBadgeClass = (statut) => {
    const classes = {
        'en_attente': 'badge-warning',
        'validee': 'badge-success',
        'annulee': 'badge-danger',
        'remboursee': 'badge-info',
        'partielle': 'badge-secondary',
};
    return classes[statut] || 'badge-secondary';
};
// Libellé du statut traduit
const getStatutLabel = (statut) => {
    return t(`pos_session_statut_${statut}`) || statut;
};
// Libellé du statut vente traduit
const getVenteStatutLabel = (statut) => {
    return t(`pos.ventePos.statuts.${statut}`) || statut;
};
// Libellé mode paiement
const getModePaiementLabel = (mode) => {
    return t(`pos.ventePos.modePaiement.${mode}`) || mode;
};
// Filtrer les caisses disponibles
const caissesDisponibles = computed(() => {
    return props.caisses;
});
// Filtrer les caissiers disponibles
const caissiersDisponibles = computed(() => {
    return props.employes;
});
// Formater montant
const formatMontant = (montant) => {
    if (!montant) return '0';
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(montant);
};
// Ouvrir le modal de détails de session
const openSessionDetailModal = (session) => {
    selectedSession.value = session;
    showSessionDetailModal.value = true;
};
// Fermer le modal de détails
const closeSessionDetailModal = () => {
    showSessionDetailModal.value = false;
    selectedSession.value = null;
};
// Ouvrir le modal de validation
const openValidationModal = () => {
    showValidationModal.value = true;
};
// Soumettre la validation
const submitValidation = () => {
    if (!selectedSession.value) return;
    showValidationModal.value = false;
    showLoader(t('pos.sessionCaisse.messages.validationEnCours'), t('pos.sessionCaisse.messages.veuilezPatienter'), 'primary');
    router.post(route('session-caisse-manager.validate', selectedSession.value.id), {}, {
        onSuccess: () => {
            closeSessionDetailModal();
        },
        onFinish: () => hideLoader(),
    });
// Ouvrir le modal d'annulation
};
const openAnnulationModal = () => {
    annulationMotif.value = '';
    showAnnulationModal.value = true;
};
// Soumettre l'annulation
const submitAnnulation = () => {
    if (!selectedSession.value || !annulationMotif.value.trim()) return;
    showAnnulationModal.value = false;
    showLoader(t('pos.sessionCaisse.messages.annulationEnCours'), t('pos.sessionCaisse.messages.veuilezPatienter'), 'danger');
    router.post(route('session-caisse-manager.cancel', selectedSession.value.id), { motif: annulationMotif.value }, {
        onSuccess: () => {
            closeSessionDetailModal();
        },
        onFinish: () => hideLoader(),
    });
};
// ========================================
// GESTION MODAL VENTE
// ========================================
// Ouvrir le modal de détails de vente
const openVenteDetailModal = (vente) => {
    selectedVente.value = vente;
    showVenteDetailModal.value = true;
};
// Fermer le modal de détails de vente
const closeVenteDetailModal = () => {
    showVenteDetailModal.value = false;
    selectedVente.value = null;
};
// Vérifier si la vente est remboursable
const isVenteRefundable = (vente) => {
    return vente && (vente.statut === 'validee' || vente.statut === 'partielle');
};
// Calculer le montant restant à rembourser
const getMontantRestant = (vente) => {
    if (!vente) return 0;
    const total = parseFloat(vente.total) || 0;
    const dejaRembourse = parseFloat(vente.refund) || 0;
    return Math.max(0, total - dejaRembourse);
};
// Ouvrir le modal de remboursement
const openRefundModal = () => {
    const montantRestant = getMontantRestant(selectedVente.value);
    refundForm.value = {
        type: 'total',
        montant: montantRestant,
        motif: '',
        lignes: [],
};
    showRefundModal.value = true;
};
// Fermer le modal de remboursement
const closeRefundModal = () => {
    showRefundModal.value = false;
    refundForm.value = {
        type: 'total',
        montant: '',
        motif: '',
        lignes: [],
};
};
// Soumettre le remboursement
const submitRefund = () => {
    if (!selectedVente.value || !refundForm.value.motif.trim()) return;
    refundLoading.value = true;
    showRefundModal.value = false;
    showLoader(t('pos.ventePos.messages.refundEnCours'), t('pos.sessionCaisse.messages.veuilezPatienter'), 'warning');
    router.post(route('ventepos.refund', selectedVente.value.id), {
        montant: refundForm.value.montant,
        motif: refundForm.value.motif,
    }, {
        onSuccess: () => {
            closeVenteDetailModal();
            closeRefundModal();
        },
        onError: () => {
            showRefundModal.value = true;
        },
        onFinish: () => {
            refundLoading.value = false;
            hideLoader();
        },
    });
};
// Imprimer le ticket de vente directement depuis la liste
const printVenteDirecte = (vente) => {
    if (!vente) return;
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Ticket - ${vente.reference || 'N/A'}</title>
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
                <div class="ref">${vente.reference || 'N/A'}</div>
                <div class="point-vente">${vente.point_vente || ''}</div>
            </div>
            <div class="info-section">
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    <span>${vente.date || 'N/A'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Caissier:</span>
                    <span>${vente.caissier || 'N/A'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Mode:</span>
                    <span>${getModePaiementLabel(vente.mode_paiement)}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut:</span>
                    <span class="status status-${vente.statut}">${getVenteStatutLabel(vente.statut)}</span>
                </div>
            </div>
            <div class="divider"></div>
            <div class="articles">
                <div class="articles-title">ARTICLES</div>
                ${(vente.lignes || []).map(ligne => `
                    <div class="article-item">
                        <div class="article-name">${ligne.libelle || 'Article'}</div>
                        <div class="article-details">
                            <span>${ligne.quantite} x ${formatMontant(ligne.prix_unitaire)}</span>
                            <span class="article-total">${formatMontant(ligne.total_ligne)} ${vente.devise}</span>
                        </div>
                    </div>
                `).join('')}
            </div>
            <div class="total-section">
                <div class="total-row">
                    <span>TOTAL:</span>
                    <span class="total-value">${formatMontant(vente.total)} ${vente.devise}</span>
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
// Imprimer depuis le modal (utilise selectedVente)
const printVente = () => {
    if (!selectedVente.value) return;
    printVenteDirecte(selectedVente.value);
};
</script>
<template>
    <!-- Error from server -->
    <div v-if="error" class="server-error">
        <i class="fas fa-exclamation-circle"></i>
        <span>{{ error }}</span>
    </div>
    <!-- Main Content -->
    <div class="pos-main-scrollable">
        <div class="manager-dashboard">
            <!-- Stats Cards -->
            <section class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card stat-primary">
                        <div class="stat-icon">
                            <i class="fas fa-cash-register"></i>
                        </div>
                        <div class="stat-content">
                            <span class="stat-value">{{ stats?.sessions_ouvertes || 0 }}</span>
                            <span class="stat-label">{{ t('pos.manager.sessionsOuvertes') }}</span>
                        </div>
                    </div>
                    <div class="stat-card stat-warning">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <span class="stat-value">{{ stats?.sessions_attente_validation || 0 }}</span>
                            <span class="stat-label">{{ t('pos.manager.attenteValidation') }}</span>
                        </div>
                    </div>
                    <div class="stat-card stat-success">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-content">
                            <span class="stat-value">{{ stats?.ventes_aujourdhui || 0 }}</span>
                            <span class="stat-label">{{ t('pos.manager.ventesJour') }}</span>
                        </div>
                    </div>
                    <!-- CA par devise (dynamique) -->
                    <div
                        v-for="(ca, devise) in (stats?.ca_jour_display_par_devise || {})"
                        :key="devise"
                        class="stat-card stat-info"
                    >
                        <div class="stat-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="stat-content">
                            <span class="stat-value">{{ ca.display || '0' }}</span>
                            <span class="stat-label">{{ t('pos.manager.caJour') }} ({{ devise }})</span>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Tabs Navigation -->
            <div class="tabs-navigation">
                <button
                    class="tab-btn"
                    :class="{ active: activeTab === 'dashboard' }"
                    @click="activeTab = 'dashboard'"
                >
                    <i class="fas fa-th-large"></i>
                    {{ t('pos.manager.tabs.dashboard') }}
                </button>
                <button
                    class="tab-btn"
                    :class="{ active: activeTab === 'sessions' }"
                    @click="activeTab = 'sessions'"
                >
                    <i class="fas fa-cash-register"></i>
                    {{ t('pos.manager.tabs.sessions') }}
                    <span v-if="stats?.sessions_attente_validation > 0" class="tab-badge">
                        {{ stats.sessions_attente_validation }}
                    </span>
                </button>
                <button
                    class="tab-btn"
                    :class="{ active: activeTab === 'ventes' }"
                    @click="activeTab = 'ventes'"
                >
                    <i class="fas fa-receipt"></i>
                    {{ t('pos.manager.tabs.ventes') }}
                    <span v-if="stats?.ventes_en_attente > 0" class="tab-badge warning">
                        {{ stats.ventes_en_attente }}
                    </span>
                </button>
            </div>
            <!-- Tab Content: Dashboard -->
            <div v-if="activeTab === 'dashboard'" class="tab-content">
                <div class="dashboard-grid">
                    <!-- Quick Actions -->
                    <div class="quick-actions-card">
                        <h3 class="card-title">
                            <i class="fas fa-bolt"></i>
                            {{ t('pos.manager.quickActions') }}
                        </h3>
                        <div class="actions-grid">
                            <button
                                v-if="can('session-caisse-manager-create')"
                                class="action-btn action-primary"
                                @click="openAttributionModal"
                            >
                                <i class="fas fa-user-plus"></i>
                                <span>{{ t('pos.sessionCaisse.attribution') }}</span>
                            </button>
                            <button
                                class="action-btn action-secondary"
                                @click="activeTab = 'sessions'"
                            >
                                <i class="fas fa-list"></i>
                                <span>{{ t('pos.manager.voirSessions') }}</span>
                            </button>
                            <button
                                class="action-btn action-secondary"
                                @click="activeTab = 'ventes'"
                            >
                                <i class="fas fa-receipt"></i>
                                <span>{{ t('pos.manager.voirVentes') }}</span>
                            </button>
                            <!-- Transfert de stock - Demande (a href pour forcer rechargement complet) -->
                            <a
                                v-if="can('transfert-stock-create')"
                                :href="route('transfert-stock.create')"
                                class="action-btn action-secondary"
                            >
                                <i class="fas fa-exchange-alt"></i>
                                <span>{{ t('modules.business.transfertStock.demande') }}</span>
                            </a>
                            <!-- Transfert de stock - Voir demandes (a href pour forcer rechargement complet) -->
                            <a
                                v-if="can('transfert-stock-list')"
                                :href="route('transfert-stock.index')"
                                class="action-btn action-secondary action-btn-with-badge"
                            >
                                <i class="fas fa-clipboard-list"></i>
                                <span>{{ t('modules.business.transfertStock.demandesRecues') }}</span>
                                <span v-if="stats?.demandes_transfert_en_attente > 0" class="action-badge">
                                    {{ stats.demandes_transfert_en_attente }}
                                </span>
                            </a>
                           
                            <!-- Inventaire - Voir tous (a href pour forcer rechargement complet) -->
                            <a
                                v-if="can('inventaire-list')"
                                :href="route('inventaire.index')"
                                class="action-btn action-secondary"
                            >
                                <i class="fas fa-list-alt"></i>
                                <span>{{ t('modules.business.inventaire.voirTous') }}</span>
                            </a>
                        </div>
                    </div>
                    <!-- Sessions en attente de validation -->
                    <div class="pending-card" v-if="stats?.sessions_attente_validation > 0">
                        <h3 class="card-title warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            {{ t('pos.manager.sessionsAttenteValidation') }}
                        </h3>
                        <p class="pending-text">
                            {{ stats.sessions_attente_validation }} {{ t('pos.manager.sessionsRequierentValidation') }}
                        </p>
                        <button class="btn-view-pending" @click="activeTab = 'sessions'; searchFilters.statut = 'attente_validation'; searchSessions()">
                            <i class="fas fa-eye"></i>
                            {{ t('pos.manager.voirSessions') }}
                        </button>
                    </div>
                    <!-- Dernières ventes -->
                    <div class="recent-sales-card">
                        <h3 class="card-title">
                            <i class="fas fa-clock"></i>
                            {{ t('pos.manager.dernieresVentes') }}
                        </h3>
                        <div class="recent-sales-list" v-if="ventes?.data?.length > 0">
                            <Link
                                v-for="vente in ventes.data.slice(0, 5)"
                                :key="vente.id"
                                :href="route('ventepos.show', vente.id)"
                                class="recent-sale-item recent-sale-link"
                            >
                                <div class="sale-info">
                                    <span class="sale-ref">{{ vente.reference }}</span>
                                    <span class="sale-caissier">{{ vente.caissier }} (Session {{ vente.session_reference }})</span>
                                </div>
                                <div class="sale-meta">
                                    <span class="sale-amount">{{ formatMontant(vente.total) }} {{ vente.devise }}</span>
                                    <span :class="['sale-status', getVenteStatutBadgeClass(vente.statut)]">
                                        {{ getVenteStatutLabel(vente.statut) }}
                                    </span>
                                </div>
                            </Link>
                        </div>
                        <div v-else class="empty-recent">
                            <i class="fas fa-inbox"></i>
                            <span>{{ t('pos.manager.aucuneVenteRecente') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Tab Content: Sessions -->
            <div v-if="activeTab === 'sessions'" class="tab-content">
                <!-- Actions Header -->
                <div class="content-header-bar">
                    <div class="header-left">
                        <span class="result-count">
                            {{ sessionsCaisse?.total || 0 }} {{ t('pos.sessionCaisse.list') }}
                        </span>
                    </div>
                    <div class="header-right">
                        <button v-if="can('session-caisse-manager-create')" @click="openAttributionModal" class="btn-action btn-primary">
                            <i class="fas fa-user-plus"></i>
                            {{ t('pos.sessionCaisse.attribution') }}
                        </button>
                    </div>
                </div>
                <!-- Filtres Sessions -->
                <div class="filters-card">
                    <form @submit.prevent="searchSessions" class="filters-form">
                            
                        <div class="filter-group">
                            <StylishSelect
                                v-model="searchFilters.caisse_id"
                                :options="caisses"
                                option-value="value"
                                option-label="label"
                                :placeholder="t('pos.sessionCaisse.fields.caisse')"
                            />
                        </div>
                        <div class="filter-group">
                            <StylishSelect
                                v-model="searchFilters.caissier_id"
                                :options="employes"
                                option-value="value"
                                option-label="label"
                                :placeholder="t('pos.sessionCaisse.fields.caissier')"
                            />
                        </div>
                        <div class="filter-group">
                            <StylishSelect
                                v-model="searchFilters.statut"
                                :options="statuts"
                                option-value="value"
                                option-label="label"
                                :placeholder="t('pos.sessionCaisse.fields.statut')"
                            />
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn-filter btn-search">
                                <i class="fas fa-search"></i>
                            </button>
                            <button type="button" @click="resetSessionFilters" class="btn-filter btn-reset">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <!-- Tableau des sessions -->
                <div class="table-card">
                    <div class="table-responsive">
                        <table class="pos-table">
                            <thead>
                                <tr>
                                    <th>{{ t('pos.sessionCaisse.fields.reference') }}</th>
                                    <th>{{ t('pos.sessionCaisse.fields.caisse') }}</th>
                                    <th>{{ t('pos.sessionCaisse.fields.caissier') }}</th>
                                    <th>{{ t('pos.sessionCaisse.fields.terminal') }}</th>
                                    <th>{{ t('pos.sessionCaisse.fields.statut') }}</th>
                                    <th>{{ t('pos.sessionCaisse.fields.dateAssignation') }}</th>
                                    <th>{{ t('pos.sessionCaisse.fields.dateOuverture') }}</th>
                                    <th>{{ t('pos.sessionCaisse.fields.dateFermeture') }}</th>
                                    <th class="text-center">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="sessionsCaisse?.data && sessionsCaisse.data.length > 0">
                                    <tr v-for="session in sessionsCaisse.data" :key="session.id">
                                        <td>
                                            <span class="cell-main">{{ session.reference }}</span>
                                        </td>
                                        <td>
                                            <span class="cell-main">{{ session.caisse }}</span>
                                        </td>
                                        <td>
                                            <span class="cell-main">{{ session.caissier_nom }}</span>
                                        </td>
                                        <td>
                                            <span class="cell-muted">{{ session.terminal_numero || 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span :class="['status-badge', getStatutBadgeClass(session.statut)]">
                                                {{ getStatutLabel(session.statut) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="cell-muted">{{ session.created_at }}</span>
                                        </td>
                                        <td>
                                            <span class="cell-muted">{{ session.date_ouverture || '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="cell-muted">{{ session.date_fermeture || '-' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="action-buttons">
                                                <button
                                                    @click="openSessionDetailModal(session)"
                                                    class="btn-table-action"
                                                    :title="t('actions.view')"
                                                >
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="9" class="empty-state">
                                        <div class="empty-content">
                                            <i class="fas fa-inbox"></i>
                                            <span>{{ t('common.emptyList') }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div class="table-footer">
                        <Pagination :data="sessionsCaisse" />
                    </div>
                </div>
            </div>
            <!-- Tab Content: Ventes -->
            <div v-if="activeTab === 'ventes'" class="tab-content">
                <!-- Actions Header -->
                <div class="content-header-bar">
                    <div class="header-left">
                        <span class="result-count">
                            {{ ventes?.total || 0 }} {{ t('pos.ventePos.list') }}
                        </span>
                    </div>
                </div>
                <!-- Filtres Ventes -->
                <div class="filters-card">
                    <form @submit.prevent="searchVentes" class="filters-form">
                        <div class="filter-group">
                            <StylishSelect
                                v-model="venteSearchFilters.vente_session_id"
                                :options="sessionsForFilter"
                                option-value="value"
                                option-label="label"
                                :placeholder="t('pos.ventePos.fields.session')"
                            />
                        </div>
                        <div class="filter-group">
                            <StylishSelect
                                v-model="venteSearchFilters.vente_statut"
                                :options="statutsVente"
                                option-value="value"
                                option-label="label"
                                :placeholder="t('pos.ventePos.fields.statut')"
                            />
                        </div>
                        <div class="filter-group">
                            <StylishSelect
                                v-model="venteSearchFilters.vente_mode_paiement"
                                :options="modePaiements"
                                option-value="value"
                                option-label="label"
                                :placeholder="t('pos.ventePos.fields.modePaiement')"
                            />
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn-filter btn-search">
                                <i class="fas fa-search"></i>
                            </button>
                            <button type="button" @click="resetVenteFilters" class="btn-filter btn-reset">
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
                                    <th class="text-center">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="ventes?.data && ventes.data.length > 0">
                                    <tr v-for="vente in ventes.data" :key="vente.id">
                                        <td>
                                            <span class="ref-badge">{{ vente.reference }}</span>
                                        </td>
                                        <td class="amount-cell">
                                            {{ formatMontant(vente.total) }} {{ vente.devise }}
                                        </td>
                                        <td>
                                            <span class="mode-badge">{{ getModePaiementLabel(vente.mode_paiement) }}</span>
                                        </td>
                                        <td>
                                            <span :class="['status-badge', getVenteStatutBadgeClass(vente.statut)]">
                                                {{ getVenteStatutLabel(vente.statut) }}
                                            </span>
                                        </td>
                                        <td>{{ vente.caissier }}</td>
                                        <td>{{ vente.date }}</td>
                                        <td class="text-center">
                                            <div class="action-buttons">
                                                <!-- Voir détails -->
                                                <Link
                                                    :href="route('ventepos.show', vente.id)"
                                                    class="btn-table-action"
                                                    :title="t('actions.view')"
                                                >
                                                    <i class="fas fa-eye"></i>
                                                </Link>
                                                <!-- Imprimer directement -->
                                                <button
                                                    @click="printVenteDirecte(vente)"
                                                    class="btn-table-action"
                                                    :title="t('actions.print')"
                                                >
                                                    <i class="fas fa-print"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="7" class="empty-state">
                                        <div class="empty-content">
                                            <i class="fas fa-shopping-cart"></i>
                                            <span>{{ t('common.emptyList') }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div class="table-footer">
                        <Pagination :data="ventes" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal d'attribution -->
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="showAttributionModal" class="modal-overlay" @click.self="closeAttributionModal">
                <div class="modal-container">
                    <div class="modal-header">
                        <div class="modal-icon modal-icon-primary">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h3 class="modal-title">{{ t('pos.sessionCaisse.attributionTitle') }}</h3>
                        <button class="modal-close" @click="closeAttributionModal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="submitAttribution">
                            <!-- Sélection de la caisse -->
                            <div class="form-group">
                                <label class="form-label">
                                    {{ t('pos.sessionCaisse.labels.selectCaisse') }}
                                    <span class="text-danger">*</span>
                                </label>
                                <StylishSelect
                                    v-model="attributionForm.caisse_id"
                                    :options="caissesDisponibles"
                                    option-value="value"
                                    option-label="label"
                                    :placeholder="t('pos.sessionCaisse.placeholders.selectCaisse')"
                                    required
                                />
                            </div>
                            <!-- Sélection du caissier -->
                            <div class="form-group">
                                <label class="form-label">
                                    {{ t('pos.sessionCaisse.labels.selectCaissier') }}
                                    <span class="text-danger">*</span>
                                </label>
                                <StylishSelect
                                    v-model="attributionForm.caissier_id"
                                    :options="caissiersDisponibles"
                                    option-value="value"
                                    option-label="label"
                                    :placeholder="t('pos.sessionCaisse.placeholders.selectCaissier')"
                                    required
                                />
                            </div>
                            <!-- Sélection du terminal -->
                            <div class="form-group">
                                <label class="form-label">{{ t('pos.sessionCaisse.labels.selectTerminal') }}</label>
                                <StylishSelect
                                    v-model="attributionForm.terminal_id"
                                    :options="terminaux"
                                    option-value="value"
                                    option-label="label"
                                    :placeholder="t('pos.sessionCaisse.placeholders.selectTerminal')"
                                />
                            </div>
                            <!-- Résumé -->
                            <div v-if="attributionForm.caisse_id && attributionForm.caissier_id" class="attribution-summary">
                                <div class="summary-title">
                                    <i class="fas fa-info-circle"></i>
                                    {{ t('pos.sessionCaisse.labels.resume') }}
                                </div>
                                <ul class="summary-list">
                                    <li>
                                        <strong>{{ t('pos.sessionCaisse.fields.caisse') }}:</strong>
                                        {{ caisses.find(c => c.value === attributionForm.caisse_id)?.label }}
                                    </li>
                                    <li>
                                        <strong>{{ t('pos.sessionCaisse.fields.caissier') }}:</strong>
                                        {{ employes.find(e => e.value === attributionForm.caissier_id)?.label }}
                                    </li>
                                    <li v-if="attributionForm.terminal_id">
                                        <strong>{{ t('pos.sessionCaisse.fields.terminal') }}:</strong>
                                        {{ terminaux.find(t => t.value === attributionForm.terminal_id)?.label }}
                                    </li>
                                </ul>
                            </div>
                            <!-- Actions -->
                            <div class="modal-actions">
                                <button type="button" class="btn-modal btn-secondary" @click="closeAttributionModal">
                                    {{ t('actions.cancel') }}
                                </button>
                                <button
                                    type="submit"
                                    class="btn-modal btn-primary"
                                    :disabled="!attributionForm.caisse_id || !attributionForm.caissier_id"
                                >
                                    <i class="fas fa-check"></i>
                                    {{ t('pos.sessionCaisse.labels.attribuerCaisse') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
    <!-- Modal de détails de session -->
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="showSessionDetailModal && selectedSession" class="modal-overlay" @click.self="closeSessionDetailModal">
                <div class="modal-container modal-large">
                    <div class="modal-header">
                        <div class="modal-icon modal-icon-primary">
                            <i class="fas fa-cash-register"></i>
                        </div>
                        <h3 class="modal-title">{{ selectedSession.caisse }} ({{ selectedSession.reference }})</h3>
                        <span :class="['status-badge', getStatutBadgeClass(selectedSession.statut)]">
                            {{ getStatutLabel(selectedSession.statut) }}
                        </span>
                        <button class="modal-close" @click="closeSessionDetailModal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Informations générales -->
                        <div class="info-section">
                            <h4 class="section-title">
                                <i class="fas fa-info-circle"></i>
                                {{ t('pos.sessionCaisse.sections.generalInfo') }}
                            </h4>
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="info-content">
                                        <span class="info-label">{{ t('pos.sessionCaisse.fields.caissier') }}</span>
                                        <span class="info-value">{{ selectedSession.caissier_nom }}</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-tablet-alt"></i>
                                    </div>
                                    <div class="info-content">
                                        <span class="info-label">{{ t('pos.sessionCaisse.fields.terminal') }}</span>
                                        <span class="info-value">{{ selectedSession.terminal_numero || 'N/A' }}</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-door-open"></i>
                                    </div>
                                    <div class="info-content">
                                        <span class="info-label">{{ t('pos.sessionCaisse.fields.dateOuverture') }}</span>
                                        <span class="info-value">{{ selectedSession.date_ouverture || 'N/A' }}</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-door-closed"></i>
                                    </div>
                                    <div class="info-content">
                                        <span class="info-label">{{ t('pos.sessionCaisse.fields.dateFermeture') }}</span>
                                        <span class="info-value">{{ selectedSession.date_fermeture || 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Informations financières -->
                        <div class="info-section" v-if="selectedSession.statut === 'ouverte' || selectedSession.statut === 'fermee' || selectedSession.statut === 'attente_validation'">
                            <h4 class="section-title">
                                <i class="fas fa-coins"></i>
                                {{ t('pos.sessionCaisse.sections.financialInfo') }}
                            </h4>
                            <div class="financial-grid">
                                <div class="financial-item">
                                    <span class="financial-label">{{ t('pos.sessionCaisse.financial.fondOuverture') }}</span>
                                    <span class="financial-value">{{ selectedSession.fond_ouverture }} {{ selectedSession.devise }}</span>
                                </div>
                                <div class="financial-item" v-if="selectedSession.total_encaisse !== null">
                                    <span class="financial-label">{{ t('pos.sessionCaisse.financial.totalEncaisse') }}</span>
                                    <span class="financial-value">{{ selectedSession.total_encaisse }} {{ selectedSession.devise }}</span>
                                </div>
                                <div class="financial-item" v-if="selectedSession.total_reel !== null">
                                    <span class="financial-label">{{ t('pos.sessionCaisse.financial.totalReel') }}</span>
                                    <span class="financial-value">{{ formatMontant(selectedSession.total_reel) }} {{ selectedSession.devise }}</span>
                                </div>
                                <div class="financial-item highlight" v-if="selectedSession.ecart !== null">
                                    <span class="financial-label">{{ t('pos.sessionCaisse.financial.ecart') }}</span>
                                    <span class="financial-value" :class="selectedSession.ecart >= 0 ? 'text-success' : 'text-danger'">
                                        {{ formatMontant(selectedSession.ecart) }} {{ selectedSession.devise }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <!-- Actions -->
                        <div class="session-modal-actions">
                            <!-- Valider la cloture (si attente_validation) -->
                            <button
                                v-if="selectedSession.statut === 'attente_validation'"
                                @click="openValidationModal"
                                class="btn-action btn-success"
                            >
                                <i class="fas fa-check"></i>
                                {{ t('pos.sessionCaisse.actions.validerCloture') }}
                            </button>
                            <!-- Annuler la session -->
                            <button
                                v-if="(selectedSession.statut === 'attente' || selectedSession.statut === 'attente_validation') && can('session-caisse-manager-cancel')"
                                @click="openAnnulationModal"
                                class="btn-action btn-danger"
                            >
                                <i class="fas fa-times"></i>
                                {{ t('pos.sessionCaisse.actions.annuler') }}
                            </button>
                            <!-- Fermer -->
                            <button @click="closeSessionDetailModal" class="btn-action btn-secondary">
                                <i class="fas fa-times"></i>
                                {{ t('actions.close') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
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
    <!-- Modal de détails de vente -->
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="showVenteDetailModal && selectedVente" class="modal-overlay" @click.self="closeVenteDetailModal">
                <div class="modal-container modal-large">
                    <div class="modal-header">
                        <div class="modal-icon modal-icon-primary">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <h3 class="modal-title">{{ selectedVente.reference }}</h3>
                        <span :class="['status-badge', getVenteStatutBadgeClass(selectedVente.statut)]">
                            {{ getVenteStatutLabel(selectedVente.statut) }}
                        </span>
                        <button class="modal-close" @click="closeVenteDetailModal">
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
                                    <span class="financial-value">{{ formatMontant(selectedVente.total) }} {{ selectedVente.devise }}</span>
                                </div>
                            </div>
                        </div>
                        <!-- Actions -->
                        <div class="session-modal-actions">
                            <!-- Bouton imprimer -->
                            <button
                                @click="printVente"
                                class="btn-action btn-primary"
                            >
                                <i class="fas fa-print"></i>
                                {{ t('pos.ventePos.actions.print') }}
                            </button>
                            <!-- Bouton remboursement (si vente validée ou partielle) -->
                            <button
                                v-if="isVenteRefundable(selectedVente) && can('ventepos-refund')"
                                @click="openRefundModal"
                                class="btn-action btn-warning"
                            >
                                <i class="fas fa-undo"></i>
                                {{ t('pos.ventePos.actions.refund') }}
                            </button>
                            <!-- Fermer -->
                            <button @click="closeVenteDetailModal" class="btn-action btn-secondary">
                                <i class="fas fa-times"></i>
                                {{ t('actions.close') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
    <!-- Modal Remboursement -->
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="showRefundModal" class="modal-overlay" @click.self="closeRefundModal">
                <div class="modal-container">
                    <div class="modal-header">
                        <div class="modal-icon modal-icon-warning">
                            <i class="fas fa-undo"></i>
                        </div>
                        <h3 class="modal-title">{{ t('pos.ventePos.modals.refundTitle') }}</h3>
                        <button class="modal-close" @click="closeRefundModal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Info vente -->
                        <div class="refund-vente-info">
                            <div class="refund-info-row">
                                <span class="refund-info-label">{{ t('pos.ventePos.fields.reference') }}:</span>
                                <span class="refund-info-value">{{ selectedVente?.reference }}</span>
                            </div>
                            <div class="refund-info-row">
                                <span class="refund-info-label">{{ t('pos.ventePos.fields.total') }}:</span>
                                <span class="refund-info-value">{{ formatMontant(selectedVente?.total) }} {{ selectedVente?.devise }}</span>
                            </div>
                            <div class="refund-info-row" v-if="selectedVente?.refund > 0">
                                <span class="refund-info-label">{{ t('pos.ventePos.fields.dejaRembourse') }}:</span>
                                <span class="refund-info-value text-warning">- {{ formatMontant(selectedVente?.refund) }} {{ selectedVente?.devise }}</span>
                            </div>
                            <div class="refund-info-row">
                                <span class="refund-info-label">{{ t('pos.ventePos.fields.resteARembourser') }}:</span>
                                <span class="refund-info-value highlight">{{ formatMontant(getMontantRestant(selectedVente)) }} {{ selectedVente?.devise }}</span>
                            </div>
                        </div>
                        <!-- Formulaire remboursement -->
                        <form @submit.prevent="submitRefund">
                            <div class="form-group">
                                <label class="form-label">
                                    {{ t('pos.ventePos.fields.montantRefund') }}
                                    <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="number"
                                    v-model="refundForm.montant"
                                    class="form-control"
                                    step="0.01"
                                    min="0.01"
                                    :max="getMontantRestant(selectedVente)"
                                    required
                                />
                                <small class="form-hint">{{ t('pos.ventePos.hints.maxRefund') }}: {{ formatMontant(getMontantRestant(selectedVente)) }} {{ selectedVente?.devise }}</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    {{ t('pos.ventePos.fields.motifRefund') }}
                                    <span class="text-danger">*</span>
                                </label>
                                <textarea
                                    v-model="refundForm.motif"
                                    class="form-control"
                                    rows="3"
                                    :placeholder="t('pos.ventePos.placeholders.motifRefund')"
                                    required
                                ></textarea>
                            </div>
                            <div class="modal-actions">
                                <button type="button" class="btn-modal btn-secondary" @click="closeRefundModal">
                                    {{ t('actions.cancel') }}
                                </button>
                                <button
                                    type="submit"
                                    class="btn-modal btn-warning"
                                    :disabled="!refundForm.motif.trim() || !refundForm.montant || refundLoading"
                                >
                                    <i class="fas fa-undo"></i>
                                    {{ t('pos.ventePos.actions.confirmRefund') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
<style scoped>
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
/* Liens cliquables pour les ventes récentes */
.recent-sale-link {
    text-decoration: none;
    color: inherit;
    display: flex;
    cursor: pointer;
    transition: all 0.2s ease;
}
.recent-sale-link:hover {
    background: #f8f9fc;
    transform: translateX(4px);
}
.recent-sale-link:hover .sale-ref {
    color: #3498db;
}
/* Badge pour actions rapides */
.action-btn-with-badge {
    position: relative;
}
.action-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    background: #e74c3c;
    color: white;
    font-size: 11px;
    font-weight: 600;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(231, 76, 60, 0.3);
}
</style>
