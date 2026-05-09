<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import PosLayout from '@/Layouts/PosLayout.vue';
import { useLoader } from '@/Composables/useLoader';
import { useLocale } from '@/Composables/useLocale';
// Utiliser PosLayout comme layout
defineOptions({ layout: PosLayout });
const { t } = useLocale();
const { showLoader, hideLoader } = useLoader();
const props = defineProps({
    sessionActive: Object,
    devises: Array,
    error: String,
    dernieresVentes: {
        type: Array,
        default: () => []
    },
});
// Etats des modals
const showOuvertureModal = ref(false);
const showClotureModal = ref(false);
const showHelpModal = ref(false);
// Formulaire d'ouverture
const ouvertureForm = ref({
    fond_ouverture: '',
    devise: '',
});
// Formulaire de cloture
const clotureForm = ref({
    total_reel: '',
});
// Initialiser la devise par defaut si disponible
watch(() => props.devises, (newDevises) => {
    if (newDevises && newDevises.length > 0 && !ouvertureForm.value.devise) {
        ouvertureForm.value.devise = newDevises[0].value;
    }
}, { immediate: true });
// Formater les montants avec la devise de la session
const formatMontant = (montant, devise = null) => {
    if (montant === null || montant === undefined) return '0';
    const currency = devise || props.sessionActive?.devise || 'XOF';
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 0
    }).format(montant);
};
// Ouvrir le modal d'ouverture
const openOuvertureModal = () => {
    ouvertureForm.value = {
        fond_ouverture: '',
        devise: props.devises?.[0]?.value || '',
};
    showOuvertureModal.value = true;
};
// Fermer le modal d'ouverture
const closeOuvertureModal = () => {
    showOuvertureModal.value = false;
    ouvertureForm.value = {
        fond_ouverture: '',
        devise: props.devises?.[0]?.value || '',
};
};
// Soumettre l'ouverture
const submitOuverture = () => {
    if (!ouvertureForm.value.fond_ouverture || !ouvertureForm.value.devise) {
        return;
    }
    showLoader(t('pos.caissier.openingCaisse'), t('pos.caissier.pleaseWait'), 'primary');
    router.post(route('session-caisse-caissier.ouverture'), {
        fond_ouverture: parseFloat(ouvertureForm.value.fond_ouverture),
        session_id: props.sessionActive.id,
        devise: ouvertureForm.value.devise,
    }, {
        onSuccess: () => {
            closeOuvertureModal();
        },
        onFinish: () => {
            hideLoader();
        },
});
};
// Ouvrir le modal de cloture
const openClotureModal = () => {
    clotureForm.value = {
        total_reel: '',
    };
    showClotureModal.value = true;
};
// Fermer le modal de cloture
const closeClotureModal = () => {
    showClotureModal.value = false;
    clotureForm.value = {
        total_reel: '',
};
};
// Soumettre la cloture
const submitCloture = () => {
    if (!clotureForm.value.total_reel) {
        return;
    }
    showLoader(t('pos.caissier.closingCaisse'), t('pos.caissier.pleaseWait'), 'primary');
    router.post(route('session-caisse-caissier.fermerture'), {
        session_id: props.sessionActive.id,
        total_reel: parseFloat(clotureForm.value.total_reel),
    }, {
        onSuccess: () => {
            closeClotureModal();
        },
        onFinish: () => {
            hideLoader();
        },
});
};
// Aller vers la liste des ventes
const goToVentes = () => {
    router.visit(route('ventepos.index'));
};
// Aller vers la creation d'une vente
const goToNewVente = () => {
    router.visit(route('ventepos.create'));
};
// Aller vers le detail d'une vente
const goToVenteDetail = (venteId) => {
    router.visit(route('ventepos.show', venteId));
};
// Verifier si la caisse est ouverte
const isCaisseOuverte = computed(() => {
    return props.sessionActive?.statut === 'ouverte';
});
// Verifier si la caisse est en attente
const isCaisseAttente = computed(() => {
    return props.sessionActive?.statut === 'attente';
});
// Calculer le total theorique
const totalTheorique = computed(() => {
    if (!props.sessionActive) return 0;
    const fond = props.sessionActive.fond_ouverture_cents || 0;
    const encaisse = props.sessionActive.total_encaisse_cents || 0;
    return fond + encaisse;
});
// Calculer l'écart de caisse
const ecartCaisse = computed(() => {
    if (!clotureForm.value.total_reel) return 0;
    const totalReel = parseFloat(clotureForm.value.total_reel) ;
    return totalReel - totalTheorique.value;
});
// Formater l'écart pour l'affichage
const ecartFormate = computed(() => {
    const ecart = ecartCaisse.value ;
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(ecart);
});
// Classe CSS selon l'écart
const ecartClass = computed(() => {
    if (ecartCaisse.value === 0) return 'text-success';
    return ecartCaisse.value > 0 ? 'text-success' : 'text-danger';
});
</script>
<template>
    <!-- Error from server -->
    <div v-if="error" class="server-error">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ error }}</span>
        </div>
        <!-- Main Content -->
        <div class="pos-main-scrollable">
            <div class="pos-main">
            <!-- Sidebar gauche - Informations et actions -->
            <aside class="pos-sidebar">
                <!-- Carte info caisse -->
                <div class="card info-card" v-if="sessionActive">
                    <div class="card-header">
                        <i class="fas fa-calculator"></i>
                        <h3>{{ t('pos.caissier.caisseInfo') }}</h3>
                        <div class="session-badge" :class="isCaisseOuverte ? 'badge-success' : 'badge-warning'">
                            <span class="badge-dot"></span>
                            <span>{{ isCaisseOuverte ? t('pos.caissier.sessionOpen') : t('pos.caissier.sessionWaiting')
                                }}</span>
                        </div>
                         <div class="devise-badge" v-if="sessionActive.devise">
                {{ sessionActive.devise }}
            </div>
                    </div>
                    <div class="card-body">
                         <div class="info-row">
                            <span class="info-label">{{ t('pos.caissier.pointVente') }}</span>
                            <span class="info-value">{{ sessionActive.point_vente_nom }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ t('pos.caissier.caisse') }}</span>
                            <span class="info-value">{{ sessionActive.caisse_nom }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ t('pos.caissier.terminal') }}</span>
                            <span class="info-value">{{ sessionActive.terminal_numero }}</span>
                        </div>
                        <div class="info-row" v-if="sessionActive.date_ouverture">
                            <span class="info-label">{{ t('pos.caissier.openedAt') }}</span>
                            <span class="info-value">{{ sessionActive.date_ouverture }}</span>
                        </div>
                    </div>
                </div>
                <!-- Actions principales -->
                <div class="card actions-card">
                    <div class="card-header">
                        <i class="fas fa-bolt"></i>
                        <h3>{{ t('pos.caissier.actions') }}</h3>
                    </div>
                    <div class="card-body">
                        <button @click="openOuvertureModal" class="action-btn action-success"
                            :disabled="!isCaisseAttente">
                            <i class="fas fa-lock-open"></i>
                            <div class="action-text">
                                <span class="action-title">{{ t('pos.caissier.openCaisse') }}</span>
                                <span class="action-desc">{{ t('pos.caissier.openCaisseShort') }}</span>
                            </div>
                        </button>
                        <button @click="goToNewVente" class="action-btn action-primary" :disabled="!isCaisseOuverte">
                            <i class="fas fa-shopping-cart"></i>
                            <div class="action-text">
                                <span class="action-title">{{ t('pos.caissier.newSale') }}</span>
                                <span class="action-desc">{{ t('pos.caissier.newSaleShort') }}</span>
                            </div>
                        </button>
                        <button @click="goToVentes" class="action-btn action-dark" :disabled="!isCaisseOuverte">
                            <i class="fas fa-file-invoice"></i>
                            <div class="action-text">
                                <span class="action-title">{{ t('pos.caissier.mySales') }}</span>
                                <span class="action-desc">{{ t('pos.caissier.mySalesShort') }}</span>
                            </div>
                        </button>
                        <button @click="openClotureModal" class="action-btn action-danger" :disabled="!isCaisseOuverte">
                            <i class="fas fa-lock"></i>
                            <div class="action-text">
                                <span class="action-title">{{ t('pos.caissier.closeCaisse') }}</span>
                                <span class="action-desc">{{ t('pos.caissier.closeCaisseShort') }}</span>
                            </div>
                        </button>
                    </div>
                </div>
                <!-- Message si pas de session -->
                <div class="card no-session-card" v-if="!sessionActive">
                    <div class="no-session-content">
                        <div class="no-session-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h4>{{ t('pos.caissier.noSession') }}</h4>
                        <p>{{ t('pos.caissier.contactManager') }}</p>
                    </div>
                </div>
            </aside>
            <!-- Zone centrale - Dashboard -->
            <section class="pos-content">
                <!-- Etat: Pas de session -->
                <div class="welcome-screen" v-if="!sessionActive">
                    <div class="welcome-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <h1>{{ t('pos.caissier.welcomeTitle') }}</h1>
                    <p class="welcome-subtitle">{{ t('pos.caissier.waitingAttribution') }}</p>
                    <div class="welcome-steps">
                        <div class="step-item">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <strong>{{ t('pos.caissier.step1Title') }}</strong>
                                <span>{{ t('pos.caissier.step1Desc') }}</span>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <strong>{{ t('pos.caissier.step2Title') }}</strong>
                                <span>{{ t('pos.caissier.step2Desc') }}</span>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <strong>{{ t('pos.caissier.step3Title') }}</strong>
                                <span>{{ t('pos.caissier.step3Desc') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Etat: Session en attente d'ouverture -->
                <div class="open-screen" v-else-if="isCaisseAttente">
                    <div class="open-icon">
                        <i class="fas fa-unlock"></i>
                    </div>
                    <h1>{{ t('pos.caissier.readyToOpen') }}</h1>
                    <p class="open-subtitle">{{ t('pos.caissier.openToStart') }}</p>
                    <button class="btn-big-action" @click="openOuvertureModal">
                        <i class="fas fa-play"></i>
                        <span>{{ t('pos.caissier.openCaisse') }}</span>
                    </button>
                    <div class="open-instructions">
                        <h4><i class="fas fa-info-circle"></i> {{ t('pos.caissier.howToOpen') }}</h4>
                        <ol>
                            <li>{{ t('pos.caissier.openStep1') }}</li>
                            <li>{{ t('pos.caissier.openStep2') }}</li>
                            <li>{{ t('pos.caissier.openStep3') }}</li>
                        </ol>
                    </div>
                </div>
                <!-- Etat: Caisse ouverte - Dashboard principal -->
                <div class="dashboard-screen" v-else-if="isCaisseOuverte">
                    <!-- Resume financier -->
                    <div class="stats-grid">
                        <div class="stat-card stat-dark">
                            <div class="stat-icon">
                                <i class="fas fa-money-bill"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-label">{{ t('pos.caissier.fondCaisse') }}</span>
                                <span class="stat-value">{{ sessionActive.fond_ouverture }} {{ sessionActive.devise
                                    }}</span>
                            </div>
                        </div>
                        <div class="stat-card stat-success">
                            <div class="stat-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-label">{{ t('pos.caissier.totalEncaisse') }}</span>
                                <span class="stat-value">{{ sessionActive.total_encaisse || 0 }} {{ sessionActive.devise
                                    }}</span>
                            </div>
                        </div>
                        <div class="stat-card stat-warning">
                            <div class="stat-icon">
                                <i class="fas fa-calculator"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-label">{{ t('pos.caissier.totalTheorique') }}</span>
                                <span class="stat-value">{{ totalTheorique }} {{ sessionActive.devise }}</span>
                            </div>
                        </div>
                    </div>
                    <!-- Actions rapides -->
                    <div class="quick-actions">
                        <button class="quick-card quick-primary" @click="goToNewVente">
                            <div class="quick-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="quick-info">
                                <h3>{{ t('pos.caissier.newSale') }}</h3>
                                <p>{{ t('pos.caissier.newSaleDesc') }}</p>
                            </div>
                        </button>
                        <button class="quick-card quick-secondary" @click="goToVentes">
                            <div class="quick-icon">
                                <i class="fas fa-list"></i>
                            </div>
                            <div class="quick-info">
                                <h3>{{ t('pos.caissier.viewSales') }}</h3>
                                <p>{{ t('pos.caissier.viewSalesDesc') }}</p>
                            </div>
                        </button>
                    </div>
                    <!-- Dernières ventes -->
                    <div class="card recent-sales-card">
                        <div class="card-header">
                            <i class="fas fa-history"></i>
                            <h4>{{ t('pos.caissier.recentSales') }}</h4>
                            <button class="btn-see-all" @click="goToVentes" v-if="dernieresVentes.length > 0">
                                {{ t('pos.caissier.seeAll') }}
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <!-- Liste des ventes -->
                            <div class="recent-sales-list" v-if="dernieresVentes.length > 0">
                                <div class="recent-sale-item clickable" v-for="vente in dernieresVentes" :key="vente.id" @click="goToVenteDetail(vente.id)">
                                    <div class="sale-left">
                                        <div class="sale-icon">
                                            <i class="fas fa-receipt"></i>
                                        </div>
                                        <div class="sale-details">
                                            <span class="sale-ref">{{ vente.reference }}</span>
                                            <span class="sale-caissier">{{ vente.caissier }} (Session {{ sessionActive.reference }})</span>
                                            <div class="sale-sub-info">
                                                <span class="sale-time">
                                                    <i class="fas fa-clock"></i>
                                                    {{ vente.date }}
                                                </span>
                                                <span class="sale-mode" v-if="vente.mode_paiement">
                                                    <i :class="vente.mode_paiement === 'espece' ? 'fas fa-money-bill-wave' : vente.mode_paiement === 'electronique' ? 'fas fa-credit-card' : 'fas fa-exchange-alt'"></i>
                                                    {{ vente.mode_paiement === 'espece' ? 'Espèces' : vente.mode_paiement === 'electronique' ? 'Électronique' : 'Mixte' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="sale-right">
                                        <span class="sale-amount">{{ formatMontant(vente.total, vente.devise) }}</span>
                                        <span class="sale-status" :class="'badge-' + vente.statut_class">
                                            {{ vente.statut_label }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <!-- État vide -->
                            <div class="empty-sales" v-else>
                                <div class="empty-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <p>{{ t('pos.caissier.noRecentSales') }}</p>
                                <button class="btn-start-sale" @click="goToNewVente">
                                    <i class="fas fa-plus"></i>
                                    {{ t('pos.caissier.startFirstSale') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            </div>
        </div>
        <!-- Modal Ouverture Caisse -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showOuvertureModal" class="modal-overlay" @click.self="closeOuvertureModal">
                    <div class="modal-container modal-form">
                        <div class="modal-header">
                            <div class="modal-icon modal-icon-success">
                                <i class="fas fa-lock-open"></i>
                            </div>
                            <h3 class="modal-title">{{ t('pos.caissier.openCaisse') }}</h3>
                            <button class="modal-close" @click="closeOuvertureModal">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p class="modal-description">{{ t('pos.caissier.openCaisseDescription') }}</p>
                            <form @submit.prevent="submitOuverture">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-dollar-sign"></i>
                                        {{ t('pos.caissier.devise') }}
                                        <span class="required">*</span>
                                    </label>
                                    <select v-model="ouvertureForm.devise" class="form-control" required>
                                        <option value="" disabled>{{ t('pos.caissier.selectDevise') }}</option>
                                        <option v-for="devise in devises" :key="devise.value" :value="devise.value">
                                            {{ devise.label }}
                                        </option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-money-bill-alt"></i>
                                        {{ t('pos.caissier.fondCaisseInitial') }}
                                        <span class="required">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" v-model="ouvertureForm.fond_ouverture" class="form-control"
                                            :placeholder="t('pos.caissier.fondCaissePlaceholder')" min="0" step="1"
                                            required autofocus />
                                        <span class="input-group-text">{{ ouvertureForm.devise || 'XOF' }}</span>
                                    </div>
                                    <span class="form-hint">{{ t('pos.caissier.fondCaisseHelp') }}</span>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-dark" @click="closeOuvertureModal">
                                        {{ t('common.cancel') }}
                                    </button>
                                    <button type="submit" class="btn btn-success"
                                        :disabled="!ouvertureForm.fond_ouverture || !ouvertureForm.devise">
                                        <i class="fas fa-check"></i>
                                        {{ t('pos.caissier.confirmOpen') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
        <!-- Modal Cloture Caisse -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showClotureModal" class="modal-overlay" @click.self="closeClotureModal">
                    <div class="modal-container modal-form modal-cloture">
                        <div class="modal-header">
                            <div class="modal-icon modal-icon-danger">
                                <i class="fas fa-lock"></i>
                            </div>
                            <h3 class="modal-title">{{ t('pos.caissier.closeCaisse') }}</h3>
                            <button class="modal-close" @click="closeClotureModal">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p class="modal-description">{{ t('pos.caissier.closeCaisseDescription') }}</p>
                            <!-- Resume avant cloture - style amélioré -->
                            <div class="cloture-summary-card" v-if="sessionActive">
                                <div class="cloture-summary-item">
                                    <div class="cloture-summary-icon">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                    <div class="cloture-summary-content">
                                        <span class="cloture-summary-label">{{ t('pos.caissier.fondCaisse') }}</span>
                                        <span class="cloture-summary-value">{{ sessionActive.fond_ouverture }} {{ sessionActive.devise }}</span>
                                    </div>
                                </div>
                                <div class="cloture-summary-item success">
                                    <div class="cloture-summary-icon">
                                        <i class="fas fa-coins"></i>
                                    </div>
                                    <div class="cloture-summary-content">
                                        <span class="cloture-summary-label">{{ t('pos.caissier.totalEncaisse') }}</span>
                                        <span class="cloture-summary-value">{{ formatMontant(sessionActive.total_encaisse || 0, sessionActive.devise) }}</span>
                                    </div>
                                </div>
                                <div class="cloture-summary-item highlight">
                                    <div class="cloture-summary-icon">
                                        <i class="fas fa-calculator"></i>
                                    </div>
                                    <div class="cloture-summary-content">
                                        <span class="cloture-summary-label">{{ t('pos.caissier.totalTheorique') }}</span>
                                        <span class="cloture-summary-value">{{ formatMontant(totalTheorique || 0, sessionActive.devise) }}</span>
                                    </div>
                                </div>
                            </div>
                            <form @submit.prevent="submitCloture">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-hand-holding-usd"></i>
                                        {{ t('pos.caissier.totalReel') }}
                                        <span class="required">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" v-model="clotureForm.total_reel" class="form-control form-control-lg"
                                            :placeholder="t('pos.caissier.totalReelPlaceholder')" min="0" step="1"
                                            required autofocus />
                                        <span class="input-group-text">{{ sessionActive?.devise || 'XOF' }}</span>
                                    </div>
                                    <span class="form-hint">{{ t('pos.caissier.totalReelHelp') }}</span>
                                </div>
                                <!-- Écart calculé automatiquement -->
                                <div class="form-group" v-if="clotureForm.total_reel">
                                    <label class="form-label">
                                        <i class="fas fa-balance-scale"></i>
                                        {{ t('pos.caissier.ecart') }}
                                    </label>
                                    <div class="input-group">
                                        <input type="text" :value="ecartFormate" class="form-control form-control-lg"
                                            :class="ecartClass" disabled />
                                        <span class="input-group-text">{{ sessionActive?.devise || 'XOF' }}</span>
                                    </div>
                                    <span class="form-hint" :class="ecartClass">
                                        <i :class="ecartCaisse >= 0 ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'"></i>
                                        {{ ecartCaisse >= 0 ? t('pos.caissier.ecartPositif') : t('pos.caissier.ecartNegatif') }}
                                    </span>
                                </div>
                                <div class="alert alert-warning" v-if="ecartCaisse !== 0 && clotureForm.total_reel">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span>{{ t('pos.caissier.ecartInfo') }}</span>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-dark" @click="closeClotureModal">
                                        {{ t('common.cancel') }}
                                    </button>
                                    <button type="submit" class="btn btn-danger" :disabled="!clotureForm.total_reel">
                                        <i class="fas fa-check"></i>
                                        {{ t('pos.caissier.confirmClose') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
        <!-- Modal Aide -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showHelpModal" class="modal-overlay" @click.self="showHelpModal = false">
                    <div class="modal-container modal-help">
                        <div class="modal-header">
                            <div class="modal-icon modal-icon-warning">
                                <i class="fas fa-question"></i>
                            </div>
                            <h3 class="modal-title">{{ t('pos.caissier.helpTitle') }}</h3>
                            <button class="modal-close" @click="showHelpModal = false">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="help-section">
                                <h4><i class="fas fa-lock-open"></i> {{ t('pos.caissier.helpOpenTitle') }}</h4>
                                <p>{{ t('pos.caissier.helpOpenDesc') }}</p>
                            </div>
                            <div class="help-section">
                                <h4><i class="fas fa-shopping-cart"></i> {{ t('pos.caissier.helpSaleTitle') }}</h4>
                                <p>{{ t('pos.caissier.helpSaleDesc') }}</p>
                            </div>
                            <div class="help-section">
                                <h4><i class="fas fa-lock"></i> {{ t('pos.caissier.helpCloseTitle') }}</h4>
                                <p>{{ t('pos.caissier.helpCloseDesc') }}</p>
                            </div>
                            <div class="help-section">
                                <h4><i class="fas fa-headset"></i> {{ t('pos.caissier.helpSupportTitle') }}</h4>
                                <p>{{ t('pos.caissier.helpSupportDesc') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
</template>
<style scoped>
/* Styles pour les dernières ventes améliorées */
.recent-sales-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.recent-sale-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 12px;
    transition: all 0.2s ease;
    border: 1px solid #e9ecef;
}
.recent-sale-item:hover {
    background: #fff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}
.recent-sale-item.clickable {
    cursor: pointer;
}
.sale-left {
    display: flex;
    align-items: center;
    gap: 14px;
}
.sale-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: linear-gradient(135deg, #000 0%, #333 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 18px;
}
.sale-details {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.sale-ref {
    font-weight: 600;
    font-size: 15px;
    color: #1a1a2e;
}
.sale-caissier {
    font-size: 12px;
    color: #6c757d;
    font-weight: 500;
}
.sale-sub-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.sale-time,
.sale-mode {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    color: #6c757d;
}
.sale-time i,
.sale-mode i {
    font-size: 11px;
    color: #adb5bd;
}
.sale-mode {
    padding: 2px 8px;
    background: #e9ecef;
    border-radius: 6px;
}
.sale-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
}
.sale-amount {
    font-weight: 700;
    font-size: 16px;
    color: #1a1a2e;
}
.sale-status {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}
.badge-success {
    background: #d4edda;
    color: #155724;
}
.badge-warning {
    background: #fff3cd;
    color: #856404;
}
.badge-danger {
    background: #f8d7da;
    color: #721c24;
}
.badge-info {
    background: #d1ecf1;
    color: #0c5460;
}
.badge-secondary {
    background: #e2e8f0;
    color: #475569;
}
@media (max-width: 576px) {
    .recent-sale-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    .sale-right {
        width: 100%;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
    .sale-sub-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }
}
/* Fix pour les stat-cards - couleur du texte */
:deep(.stat-card) {
    color: #FFFFFF !important;
}
:deep(.stat-card .stat-label),
:deep(.stat-card .stat-value) {
    color: #FFFFFF !important;
}
:deep(.stat-dark) {
    background: #000 !important;
}
:deep(.stat-success) {
    background: #2ECC71 !important;
}
:deep(.stat-warning) {
    background: #F39C12 !important;
}
</style>
