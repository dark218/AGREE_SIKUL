<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import PosLayout from '@/Layouts/PosLayout.vue';
import { useLoader } from '@/Composables/useLoader';
import { useLocale } from '@/Composables/useLocale';
import { usePermissions } from '@/Composables/usePermissions';
const toast = useToast();
// Utiliser PosLayout comme layout
defineOptions({ layout: PosLayout });
const { t } = useLocale();
const { showLoader, hideLoader } = useLoader();
const { can } = usePermissions();
const props = defineProps({
    vente: Object,
    isManager: {
        type: Boolean,
        default: false,
    },
});
const showCancelModal = ref(false);
const showRefundModal = ref(false);
const showRefundLigneModal = ref(false);
const showRefundQuantiteModal = ref(false);
const showRefundMixteModal = ref(false);
const showRefundMixteQuantiteModal = ref(false);
const showPaiementMixteModal = ref(false);
const cancelMotif = ref('');
// Formulaire pour le paiement mixte lors de la validation
const formPaiementMixte = ref({
    montant_espece: 0,
    montant_electronique: 0,
});
const refundForm = ref({
    montant: '',
    motif: '',
});
const refundLigneForm = ref({
    lignes: [],
    motif: '',
});
const refundQuantiteForm = ref({
    lignes: [],
    motif: '',
});
const refundMixteForm = ref({
    montant_espece: '',
    montant_electronique: '',
    motif: '',
    max_espece: 0,
    max_electronique: 0,
    deja_rembourse: 0,
});
const refundMixteQuantiteForm = ref({
    lignes: [],
    montant_libre: '',
    motif: '',
});
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
// Formater le montant
const formatMontant = (montant) => {
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(montant || 0);
};
const shareReceipt = () => {
  //  console.log('shareReceipt');
}
// Navigation - redirige vers la bonne page selon le rôle
const goBack = () => {
    if (props.isManager) {
        router.visit(route('session-caisse-manager.index'));
    } else {
        router.visit(route('ventepos.index'));
    }
};
// Valider le paiement
const validatePaiement = () => {
    // Si mode mixte, ouvrir le modal pour saisir les montants
    if (props.vente.mode_paiement === 'mixte') {
        formPaiementMixte.value.montant_espece = 0;
        formPaiementMixte.value.montant_electronique = 0;
        showPaiementMixteModal.value = true;
        return;
    }
    showLoader(t('pos.ventePos.messages.validationEnCours'), t('pos.ventePos.messages.veuilezPatienter'), 'primary');
    router.post(route('ventepos.validate-paiement', props.vente.id), {}, {
        onError: (errors) => {
            const errorMsg = Object.values(errors).flat().join(', ') || t('common.errorOccurred');
            toast.error(errorMsg);
        },
        onFinish: () => hideLoader(),
});
    };
// Valider le paiement mixte avec les montants
const submitPaiementMixte = () => {
    const espece = parseFloat(formPaiementMixte.value.montant_espece) || 0;
    const electronique = parseFloat(formPaiementMixte.value.montant_electronique) || 0;
    const total = espece + electronique;
    // Vérifier que le total correspond au total de la vente
    if (Math.abs(total - props.vente.total) > 0.01) {
        toast.error(t('pos.ventePos.errors.montantMixteInvalide') || 'Le total des montants doit correspondre au total de la vente');
        return;
    }
    // Vérifier qu'il y a au moins un montant
    if (espece <= 0 && electronique <= 0) {
        toast.error(t('pos.ventePos.errors.montantMixteVide') || 'Veuillez saisir au moins un montant');
        return;
    }
    showPaiementMixteModal.value = false;
    showLoader(t('pos.ventePos.messages.validationEnCours'), t('pos.ventePos.messages.veuilezPatienter'), 'primary');
    router.post(route('ventepos.validate-paiement', props.vente.id), {
        montant_espece: espece,
        montant_electronique: electronique,
    }, {
        onError: (errors) => {
            const errorMsg = Object.values(errors).flat().join(', ') || t('common.errorOccurred');
            toast.error(errorMsg);
        },
        onFinish: () => hideLoader(),
});
    };
// Annuler la vente
const openCancelModal = () => {
    cancelMotif.value = '';
    showCancelModal.value = true;
};
const submitCancel = () => {
    if (!cancelMotif.value.trim()) return;
    showCancelModal.value = false;
    showLoader(t('pos.ventePos.messages.annulationEnCours'), t('pos.ventePos.messages.veuilezPatienter'), 'danger');
    router.post(route('ventepos.cancel', props.vente.id), { motif: cancelMotif.value }, {
        onError: (errors) => {
            const errorMsg = Object.values(errors).flat().join(', ') || t('common.errorOccurred');
            toast.error(errorMsg);
        },
        onFinish: () => hideLoader(),
});
    };
// Remboursement Total
const openRefundModal = () => {
    // Max = total - déjà remboursé
    const maxRemboursable = props.vente.total - (props.vente.total_rembourse_cents || 0);
    refundForm.value = { montant: maxRemboursable, montant_max: maxRemboursable, motif: '' };
    showRefundModal.value = true;
};
const submitRefund = () => {
    if (!refundForm.value.montant || !refundForm.value.motif.trim()) return;
    showRefundModal.value = false;
    showLoader(t('pos.ventePos.messages.remboursementEnCours'), t('pos.ventePos.messages.veuilezPatienter'), 'info');
    router.post(route('ventepos.refund', props.vente.id), { ...refundForm.value, type: 'total' }, {
        onError: (errors) => {
            const errorMsg = Object.values(errors).flat().join(', ') || t('common.errorOccurred');
            toast.error(errorMsg);
        },
        onFinish: () => hideLoader(),
});
    };
// Remboursement par ligne
const openRefundLigneModal = () => {
    refundLigneForm.value = {
        lignes: props.vente.lignes.map(l => ({
            ligne_id: l.id,
            libelle: l.libelle,
            // Max = total_ligne - déjà remboursé sur cette ligne
            montant_max: l.total_ligne - (l.rembourse_cents || 0),
            montant: 0,
            selected: false,
        })),
        motif: '',
};
    showRefundLigneModal.value = true;
};
// Vérifier si le formulaire de remboursement par ligne est valide
const isRefundLigneValid = () => {
    const lignesSelected = refundLigneForm.value.lignes.filter(l => l.selected && l.montant > 0);
    return lignesSelected.length > 0 && refundLigneForm.value.motif.trim() !== '';
};
const submitRefundLigne = () => {
    const lignesSelected = refundLigneForm.value.lignes.filter(l => l.selected && l.montant > 0);
    if (lignesSelected.length === 0 || !refundLigneForm.value.motif.trim()) return;
    showRefundLigneModal.value = false;
    showLoader(t('pos.ventePos.messages.remboursementEnCours'), t('pos.ventePos.messages.veuilezPatienter'), 'info');
    router.post(route('ventepos.refund-lines', props.vente.id), {
        type: 'lignes',
        lignes: lignesSelected.map(l => ({ ligne_id: l.ligne_id, montant: l.montant })),
        motif: refundLigneForm.value.motif,
    }, {
        onError: (errors) => {
            const errorMsg = Object.values(errors).flat().join(', ') || t('common.errorOccurred');
            toast.error(errorMsg);
        },
        onFinish: () => hideLoader(),
});
    };
// Limiter le montant de remboursement par ligne au maximum autorisé
const limitRefundLigneMontant = (idx) => {
    const ligne = refundLigneForm.value.lignes[idx];
    if (ligne.montant < 0) {
        ligne.montant = 0;
    } else if (ligne.montant > ligne.montant_max) {
        ligne.montant = ligne.montant_max;
    }
};
// Remboursement par quantite
const openRefundQuantiteModal = () => {
    refundQuantiteForm.value = {
        lignes: props.vente.lignes.map(l => ({
            ligne_id: l.id,
            libelle: l.libelle,
            // Max = quantite - quantite deja remboursee
            quantite_max: l.quantite - (l.quantite_remboursee || 0),
            prix_unitaire: l.prix_unitaire,
            quantite: 0,
        })),
        motif: '',
};
    showRefundQuantiteModal.value = true;
};
// Verifier si le formulaire de remboursement par quantite est valide
const isRefundQuantiteValid = () => {
    const lignesWithQte = refundQuantiteForm.value.lignes.filter(l => l.quantite >= 1);
    return lignesWithQte.length > 0 && refundQuantiteForm.value.motif.trim() !== '';
};
const submitRefundQuantite = () => {
    const lignesWithQte = refundQuantiteForm.value.lignes.filter(l => l.quantite > 0);
    if (lignesWithQte.length === 0 || !refundQuantiteForm.value.motif.trim()) return;
    showRefundQuantiteModal.value = false;
    showLoader(t('pos.ventePos.messages.remboursementEnCours'), t('pos.ventePos.messages.veuilezPatienter'), 'info');
    router.post(route('ventepos.refund-quantite', props.vente.id), {
        type: 'quantite',
        lignes: lignesWithQte.map(l => ({ ligne_id: l.ligne_id, quantite: l.quantite })),
        motif: refundQuantiteForm.value.motif,
    }, {
        onError: (errors) => {
            const errorMsg = Object.values(errors).flat().join(', ') || t('common.errorOccurred');
            toast.error(errorMsg);
        },
        onFinish: () => hideLoader(),
});
    };
// Limiter la quantité de remboursement au maximum autorisé
const limitRefundQuantite = (idx) => {
    const ligne = refundQuantiteForm.value.lignes[idx];
    if (ligne.quantite < 0) {
        ligne.quantite = 0;
    } else if (ligne.quantite > ligne.quantite_max) {
        ligne.quantite = ligne.quantite_max;
    }
};
// Remboursement mixte (par mode de paiement)
const openRefundMixteModal = () => {
    // Calculer les max par mode de paiement en tenant compte des remboursements deja effectues
    // Max especes = montant_espece - rembourse_espece_cents (converti)
    // Max electronique = montant_non_espece - rembourse_non_espece_cents (converti)
    const remboursEspece = (props.vente.rembourse_espece_cents || 0) ;
    const remboursNonEspece = (props.vente.rembourse_non_espece_cents || 0) ;
    const maxEspece = (props.vente.montant_espece || 0) - remboursEspece;
    const maxElectronique = (props.vente.montant_non_espece || 0) - remboursNonEspece;
    const dejaRembourse = props.vente.total_rembourse_cents || 0;
    refundMixteForm.value = {
        montant_espece: '',
        montant_electronique: '',
        motif: '',
        max_espece: Math.max(0, maxEspece),
        max_electronique: Math.max(0, maxElectronique),
        deja_rembourse: dejaRembourse,
};
    showRefundMixteModal.value = true;
};
const submitRefundMixte = () => {
    const montantEspece = parseFloat(refundMixteForm.value.montant_espece) || 0;
    const montantElectronique = parseFloat(refundMixteForm.value.montant_electronique) || 0;
    if ((montantEspece === 0 && montantElectronique === 0) || !refundMixteForm.value.motif.trim()) return;
    showRefundMixteModal.value = false;
    showLoader(t('pos.ventePos.messages.remboursementEnCours'), t('pos.ventePos.messages.veuilezPatienter'), 'info');
    router.post(route('ventepos.refund-mixte', props.vente.id), {
        type: 'mixte',
        espece: montantEspece,
        electronique: montantElectronique,
        motif: refundMixteForm.value.motif,
    }, {
        onError: (errors) => {
            const errorMsg = Object.values(errors).flat().join(', ') || t('common.errorOccurred');
            toast.error(errorMsg);
        },
        onFinish: () => hideLoader(),
});
    };
// Calculer le max disponible pour especes (basé sur montant_espece - rembourse_espece)
const maxEspeceDisponible = () => {
    return refundMixteForm.value.max_espece || 0;
};
// Calculer le max disponible pour electronique (basé sur montant_non_espece - rembourse_non_espece)
const maxElectroniqueDisponible = () => {
    return refundMixteForm.value.max_electronique || 0;
};
// Limiter les montants du remboursement mixte
const limitRefundMixteEspece = () => {
    const max = maxEspeceDisponible();
    let val = parseFloat(refundMixteForm.value.montant_espece) || 0;
    if (val < 0) val = 0;
    if (val > max) val = max;
    refundMixteForm.value.montant_espece = val > 0 ? val : '';
};
const limitRefundMixteElectronique = () => {
    const max = maxElectroniqueDisponible();
    let val = parseFloat(refundMixteForm.value.montant_electronique) || 0;
    if (val < 0) val = 0;
    if (val > max) val = max;
    refundMixteForm.value.montant_electronique = val > 0 ? val : '';
};
// Calculer le total du remboursement mixte (somme des deux montants)
const totalRefundMixte = () => {
    const espece = parseFloat(refundMixteForm.value.montant_espece) || 0;
    const electronique = parseFloat(refundMixteForm.value.montant_electronique) || 0;
    return espece + electronique;
};
// Verifier si le formulaire de remboursement mixte est valide
const isRefundMixteValid = () => {
    const montantEspece = parseFloat(refundMixteForm.value.montant_espece) || 0;
    const montantElectronique = parseFloat(refundMixteForm.value.montant_electronique) || 0;
    return (montantEspece > 0 || montantElectronique > 0) && refundMixteForm.value.motif.trim() !== '';
};
// Remboursement mixte par quantite (quantite + montant libre)
const openRefundMixteQuantiteModal = () => {
    refundMixteQuantiteForm.value = {
        lignes: props.vente.lignes.map(l => ({
            ligne_id: l.id,
            libelle: l.libelle,
            quantite_max: l.quantite - (l.quantite_remboursee || 0),
            prix_unitaire: l.prix_unitaire,
            quantite: 0,
        })),
        montant_libre: '',
        motif: '',
};
    showRefundMixteQuantiteModal.value = true;
};
// Verifier si le formulaire de remboursement mixte par quantite est valide
const isRefundMixteQuantiteValid = () => {
    const lignesWithQte = refundMixteQuantiteForm.value.lignes.filter(l => l.quantite >= 1);
    const montantLibre = parseFloat(refundMixteQuantiteForm.value.montant_libre) || 0;
    return (lignesWithQte.length > 0 || montantLibre > 0) && refundMixteQuantiteForm.value.motif.trim() !== '';
};
const submitRefundMixteQuantite = () => {
    const lignesWithQte = refundMixteQuantiteForm.value.lignes.filter(l => l.quantite > 0);
    const montantLibre = parseFloat(refundMixteQuantiteForm.value.montant_libre) || 0;
    if (lignesWithQte.length === 0 && montantLibre === 0) return;
    if (!refundMixteQuantiteForm.value.motif.trim()) return;
    showRefundMixteQuantiteModal.value = false;
    showLoader(t('pos.ventePos.messages.remboursementEnCours'), t('pos.ventePos.messages.veuilezPatienter'), 'info');
    router.post(route('ventepos.refund-mixte-quantite', props.vente.id), {
        type: 'mixte_quantite',
        lignes: lignesWithQte.map(l => ({ ligne_id: l.ligne_id, quantite: l.quantite })),
        montant_libre: montantLibre,
        motif: refundMixteQuantiteForm.value.motif,
    }, {
        onError: (errors) => {
            const errorMsg = Object.values(errors).flat().join(', ') || t('common.errorOccurred');
            toast.error(errorMsg);
        },
        onFinish: () => hideLoader(),
});
    };
// Limiter la quantité de remboursement mixte par quantite au maximum autorisé
const limitRefundMixteQuantite = (idx) => {
    const ligne = refundMixteQuantiteForm.value.lignes[idx];
    if (ligne.quantite < 0) {
        ligne.quantite = 0;
    } else if (ligne.quantite > ligne.quantite_max) {
        ligne.quantite = ligne.quantite_max;
    }
};
// Calculer le total du remboursement mixte par quantite
const totalRefundMixteQuantite = () => {
    const totalLignes = refundMixteQuantiteForm.value.lignes.reduce((sum, l) => {
        return sum + (l.quantite * l.prix_unitaire);
    }, 0);
    const montantLibre = parseFloat(refundMixteQuantiteForm.value.montant_libre) || 0;
    return totalLignes + montantLibre;
};
// Calculer le maximum possible a rembourser (somme des quantite_max * prix_unitaire)
const maxRefundMixteQuantite = () => {
    return refundMixteQuantiteForm.value.lignes.reduce((sum, l) => {
        return sum + (l.quantite_max * l.prix_unitaire);
    }, 0);
};
// Vérifier si la vente est remboursable
const isRefundable = () => {
    return props.vente.statut === 'validee' || props.vente.statut === 'partielle';
};
// Impression - génère un ticket formaté
const printReceipt = () => {
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Ticket - ${props.vente.reference}</title>
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
                <div class="ref">${props.vente.reference}</div>
                <div class="point-vente">${props.vente.point_vente || ''}</div>
            </div>
            <div class="info-section">
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    <span>${props.vente.date}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Caissier:</span>
                    <span>${props.vente.caissier}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Mode:</span>
                    <span>${getModePaiementLabel(props.vente.mode_paiement)}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut:</span>
                    <span class="status status-${props.vente.statut}">${getStatutLabel(props.vente.statut)}</span>
                </div>
            </div>
            <div class="divider"></div>
            <div class="articles">
                <div class="articles-title">ARTICLES</div>
                ${props.vente.lignes.map(ligne => `
                    <div class="article-item">
                        <div class="article-name">${ligne.libelle}</div>
                        <div class="article-details">
                            <span>${ligne.quantite} x ${formatMontant(ligne.prix_unitaire)}</span>
                            <span class="article-total">${formatMontant(ligne.total_ligne)} ${props.vente.devise}</span>
                        </div>
                    </div>
                `).join('')}
            </div>
            <div class="total-section">
                <div class="total-row">
                    <span>TOTAL:</span>
                    <span class="total-value">${formatMontant(props.vente.total)} ${props.vente.devise}</span>
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
                <!-- Header avec retour -->
                <div class="content-header">
                    <button @click="goBack" class="btn-back">
                        <i class="fas fa-arrow-left"></i>
                        <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                    </button>
                    <div class="header-actions">
                        <button @click="shareReceipt" class="btn-print">
                            <i class="fas fa-share"></i>
                            {{ t('actions.share') }}
                        </button>
                        <button @click="printReceipt" class="btn-print">
                            <i class="fas fa-print"></i>
                            {{ t('actions.print') }}
                        </button>
                    </div>
                </div>
                <div class="vente-details-container">
                    <!-- Carte principale -->
                    <div class="vente-card">
                        <!-- En-tête de la vente -->
                        <div class="vente-header">
                            <div class="vente-ref">
                                <span class="ref-label">{{ t('pos.ventePos.fields.reference') }}</span>
                                <span class="ref-value">{{ vente.reference }}</span>
                            </div>
                            <div class="vente-status">
                                <span :class="['status-badge', getStatutBadgeClass(vente.statut)]">
                                    {{ getStatutLabel(vente.statut) }}
                                </span>
                            </div>
                        </div>
                        <!-- Boutons de remboursement en haut (si vente remboursable) -->
                        <div class="refund-actions-bar text-center" v-if="isRefundable() && can('ventepos-refund')">
                            <div class="refund-buttons d-flex justify-content-center">
                                <button @click="openRefundModal" class="btn-refund btn-refund-total">
                                    <i class="fas fa-undo-alt"></i>
                                    {{ t('pos.ventePos.refund.total') }}
                                </button>
                                <button @click="openRefundLigneModal" class="btn-refund btn-refund-ligne">
                                    <i class="fas fa-list-ul"></i>
                                    {{ t('pos.ventePos.refund.byLine') }}
                                </button>
                                <button @click="openRefundQuantiteModal" class="btn-refund btn-refund-quantite">
                                    <i class="fas fa-sort-numeric-down"></i>
                                    {{ t('pos.ventePos.refund.byQuantity') }}
                                </button>
                                <!-- Bouton Mixte uniquement si mode de paiement est mixte -->
                                <button v-if="vente.mode_paiement === 'mixte'" @click="openRefundMixteModal" class="btn-refund btn-refund-mixte">
                                    <i class="fas fa-exchange-alt"></i>
                                    {{ t('pos.ventePos.refund.mixed') }}
                                </button>
                                <!-- Bouton Mixte Quantité uniquement si mode de paiement est mixte -->
                                <button  @click="openRefundMixteQuantiteModal" class="btn-refund btn-refund-mixte-quantite">
                                    <i class="fas fa-boxes"></i>
                                    {{ t('pos.ventePos.refund.mixedQuantity') }}
                                </button>
                            </div>
                        </div>
                        <!-- Infos vente -->
                        <div class="vente-info-grid">
                            <div class="info-item">
                                <i class="fas fa-user"></i>
                                <div class="info-content">
                                    <span class="info-label">{{ t('pos.ventePos.fields.caissier') }}</span>
                                    <span class="info-value">{{ vente.caissier }}</span>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-calendar"></i>
                                <div class="info-content">
                                    <span class="info-label">{{ t('pos.ventePos.fields.date') }}</span>
                                    <span class="info-value">{{ vente.date }}</span>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-credit-card"></i>
                                <div class="info-content">
                                    <span class="info-label">{{ t('pos.ventePos.fields.modePaiement') }}</span>
                                    <span class="info-value">{{ getModePaiementLabel(vente.mode_paiement) }}</span>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-coins"></i>
                                <div class="info-content">
                                    <span class="info-label">{{ t('pos.ventePos.fields.devise') }}</span>
                                    <span class="info-value">{{ vente.devise }}</span>
                                </div>
                            </div>
                            <div class="info-item" v-if="vente.point_vente">
                                <i class="fas fa-store"></i>
                                <div class="info-content">
                                    <span class="info-label">{{ t('pos.ventePos.fields.pointVente') }}</span>
                                    <span class="info-value">{{ vente.point_vente }}</span>
                                </div>
                            </div>
                        </div>
                        <!-- Tableau des articles -->
                        <div class="articles-section">
                            <h3 class="section-title">
                                <i class="fas fa-shopping-basket"></i>
                                {{ t('pos.ventePos.sections.lignes') }}
                            </h3>
                            <div class="table-responsive">
                                <table class="pos-table">
                                    <thead>
                                        <tr>
                                            <th>{{ t('pos.ventePos.ligne.libelle') }}</th>
                                            <th class="text-center">{{ t('pos.ventePos.ligne.quantite') }}</th>
                                            <th class="text-end">{{ t('pos.ventePos.ligne.prixUnitaire') }}</th>
                                            <th class="text-end">{{ t('pos.ventePos.ligne.remise') }}</th>
                                            <th class="text-end">{{ t('pos.ventePos.ligne.taxe') }}</th>
                                            <th class="text-end">{{ t('pos.ventePos.ligne.totalLigne') }}</th>
                                            <!-- Colonnes remboursement si statut remboursee ou partielle -->
                                            <th v-if="vente.statut === 'remboursee' || vente.statut === 'partielle'" class="text-center">{{ t('pos.ventePos.ligne.qteRemboursee') }}</th>
                                            <th v-if="vente.statut === 'remboursee' || vente.statut === 'partielle'" class="text-end">{{ t('pos.ventePos.ligne.montantRembourse') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="ligne in vente.lignes" :key="ligne.id">
                                            <td>
                                                <div class="article-info">
                                                    <span class="article-name">{{ ligne.libelle }}</span>
                                                    <span class="article-sku">{{ ligne.sku }}</span>
                                                </div>
                                            </td>
                                            <td class="text-center">{{ ligne.quantite }}</td>
                                            <td class="text-end">{{ formatMontant(ligne.prix_unitaire) }}</td>
                                            <td class="text-end">{{ formatMontant(ligne.remise) }}</td>
                                            <td class="text-end">{{ formatMontant(ligne.taxe) }}</td>
                                            <td class="text-end amount-cell">{{ formatMontant(ligne.total_ligne) }}</td>
                                            <!-- Colonnes remboursement si statut remboursee ou partielle -->
                                            <td v-if="vente.statut === 'remboursee' || vente.statut === 'partielle'" class="text-center refund-cell">
                                                {{ ligne.quantite_remboursee || 0 }}
                                            </td>
                                            <td v-if="vente.statut === 'remboursee' || vente.statut === 'partielle'" class="text-end refund-cell">
                                                {{ formatMontant(ligne.rembourse_cents || 0) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Total -->
                        <div class="vente-total">
                             <div class="total-row sub" v-if="vente.montant_espece">
                                <span class="total-label">{{ t('pos.ventePos.fields.montantEspece') }}</span>
                                <span class="total-value">{{ formatMontant(vente.montant_espece) }} {{ vente.devise }}</span>
                            </div>
                            <div class="total-row sub" v-if="vente.montant_non_espece">
                                <span class="total-label">{{ t('pos.ventePos.fields.montantElectronique') }}</span>
                                <span class="total-value">{{ formatMontant(vente.montant_non_espece) }} {{ vente.devise }}</span>
                            </div>
                            <div class="total-row main-total">
                                <span class="total-label">{{ t('pos.ventePos.cart.total') }}</span>
                                <span class="total-value">{{ formatMontant(vente.total) }} {{ vente.devise }}</span>
                            </div>
                            <!-- Total remboursé espèce -->
                            <div class="total-row sub refund-row-espece" v-if="(vente.statut === 'remboursee' || vente.statut === 'partielle') && vente.rembourse_espece_cents > 0">
                                <span class="total-label">{{ t('pos.ventePos.fields.RemboursementEspece') }}</span>
                                <span class="total-value text-refund">-{{ formatMontant(vente.rembourse_espece_cents) }} {{ vente.devise }}</span>
                            </div>
                             <!-- Total remboursé espèce -->
                            <div class="total-row sub refund-row-espece" v-if="(vente.statut === 'remboursee' || vente.statut === 'partielle') && vente.rembourse_non_espece_cents > 0">
                                <span class="total-label">{{ t('pos.ventePos.fields.RemboursementElectronique') }}</span>
                                <span class="total-value text-refund">-{{ formatMontant(vente.rembourse_non_espece_cents) }} {{ vente.devise }}</span>
                            </div>
                            <!-- Total remboursé espèce -->
                            <div class="total-row sub refund-row-espece" v-if="(vente.statut === 'remboursee' || vente.statut === 'partielle') && vente.total_rembourse_cents > 0">
                                <span class="total-label">{{ t('pos.ventePos.fields.totalRembourse') }}</span>
                                <span class="total-value text-refund">-{{ formatMontant(vente.total_rembourse_cents) }} {{ vente.devise }}</span>
                            </div>
                          
                        </div>
                        <!-- Actions -->
                        <div class="vente-actions">
                            <!-- Bouton Valider paiement (si en attente) -->
                            <button
                                v-if="vente.statut === 'en_attente' && can('ventepos-validate')"
                                @click="validatePaiement"
                                class="btn-action-main btn-success"
                            >
                                <i class="fas fa-check"></i>
                                {{ t('pos.ventePos.actions.validatePaiement') }}
                            </button>
                            <!-- Bouton Modifier (si en attente) -->
                            <Link
                                v-if="vente.statut === 'en_attente' && can('ventepos-edit')"
                                :href="route('ventepos.edit', vente.id)"
                                class="btn-action-main btn-primary"
                            >
                                <i class="fas fa-edit"></i>
                                <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                            </Link>
                            <!-- Bouton Annuler (si en attente ou validée) -->
                            <button
                                v-if="(vente.statut === 'en_attente' || vente.statut === 'validee') && can('ventepos-cancel')"
                                @click="openCancelModal"
                                class="btn-action-main btn-danger"
                            >
                                <i class="fas fa-times"></i>
                                {{ t('pos.ventePos.actions.cancel') }}
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <!-- Modal Annulation -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showCancelModal" class="modal-overlay">
                    <div class="modal-container modal-cancel">
                        <div class="modal-header">
                            <div class="modal-icon modal-icon-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h3 class="modal-title">{{ t('pos.ventePos.modals.cancelTitle') }}</h3>
                            <button class="modal-close" @click="showCancelModal = false">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <!-- Résumé de la vente -->
                            <div class="cancel-vente-summary">
                                <div class="summary-row">
                                    <span class="summary-label">{{ t('pos.ventePos.fields.reference') }}</span>
                                    <span class="summary-value font-mono">{{ vente.reference }}</span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-label">{{ t('pos.ventePos.fields.total') }}</span>
                                    <span class="summary-value text-primary">{{ formatMontant(vente.total) }} {{ vente.devise }}</span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-label">{{ t('pos.ventePos.fields.date') }}</span>
                                    <span class="summary-value">{{ vente.date }}</span>
                                </div>
                            </div>
                            <!-- Avertissement -->
                            <div class="cancel-warning">
                                <i class="fas fa-exclamation-circle"></i>
                                <span>{{ t('pos.ventePos.confirmations.cancelWarning') }}</span>
                            </div>
                            <form @submit.prevent="submitCancel">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-comment-alt"></i>
                                        {{ t('pos.ventePos.fields.motif') }}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <textarea
                                        v-model="cancelMotif"
                                        class="form-control"
                                        rows="3"
                                        :placeholder="t('pos.ventePos.placeholders.motif')"
                                        required
                                    ></textarea>
                                </div>
                                <div class="modal-actions">
                                    <button type="button" class="btn-modal btn-secondary" @click="showCancelModal = false">
                                        {{ t('actions.cancel') }}
                                    </button>
                                    <button type="submit" class="btn-modal btn-danger" :disabled="!cancelMotif.trim()">
                                        <i class="fas fa-times"></i>
                                        {{ t('pos.ventePos.actions.confirmCancel') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
        <!-- Modal Remboursement -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showRefundModal" class="modal-overlay">
                    <div class="modal-container">
                        <div class="modal-header">
                            <div class="modal-icon modal-icon-info">
                                <i class="fas fa-undo"></i>
                            </div>
                            <h3 class="modal-title">{{ t('pos.ventePos.modals.refundTitle') }}</h3>
                            <button class="modal-close" @click="showRefundModal = false">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <!-- Info déjà remboursé -->
                            <div class="refund-payment-info" v-if="vente.total_rembourse_cents > 0">
                                <div class="payment-info-row">
                                    <span class="payment-info-label">{{ t('pos.ventePos.fields.totalRembourse') }}</span>
                                    <span class="payment-info-value text-refund">{{ formatMontant(vente.total_rembourse_cents) }} {{ vente.devise }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ t('pos.ventePos.fields.montantRemboursement') }} <span class="text-danger">*</span></label>
                                <input
                                    type="number"
                                    step="1"
                                    min="1"
                                    :max="refundForm.montant_max"
                                    v-model="refundForm.montant"
                                    class="form-control"
                                    :placeholder="t('pos.ventePos.placeholders.montant')"
                                />
                                <small class="text-muted">{{ t('pos.ventePos.labels.maxMontant') }}: {{ formatMontant(refundForm.montant_max) }} {{ vente.devise }}</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ t('pos.ventePos.fields.motif') }} <span class="text-danger">*</span></label>
                                <textarea
                                    v-model="refundForm.motif"
                                    class="form-control"
                                    rows="3"
                                    :placeholder="t('pos.ventePos.placeholders.motif')"
                                ></textarea>
                            </div>
                            <div class="modal-actions">
                                <button class="btn-modal btn-secondary" @click="showRefundModal = false">
                                    {{ t('actions.cancel') }}
                                </button>
                                <button class="btn-modal btn-info" @click="submitRefund" :disabled="!refundForm.montant || refundForm.montant <= 0 || !refundForm.motif.trim()">
                                    <i class="fas fa-undo"></i>
                                    {{ t('pos.ventePos.actions.confirmRefund') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
        <!-- Modal Remboursement par Ligne -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showRefundLigneModal" class="modal-overlay">
                    <div class="modal-container modal-large">
                        <div class="modal-header">
                            <div class="modal-icon modal-icon-info">
                                <i class="fas fa-list-ul"></i>
                            </div>
                            <h3 class="modal-title">{{ t('pos.ventePos.refund.byLineTitle') }}</h3>
                            <button class="modal-close" @click="showRefundLigneModal = false">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="refund-lines-list">
                                <template v-for="(ligne, idx) in refundLigneForm.lignes" :key="idx">
                                    <div class="refund-line-item" v-if="ligne.montant_max > 0">
                                        <div class="refund-line-check">
                                            <input type="checkbox" v-model="ligne.selected" :id="'ligne-' + idx" />
                                        </div>
                                        <label :for="'ligne-' + idx" class="refund-line-info">
                                            <span class="refund-line-name">{{ ligne.libelle }}</span>
                                            <span class="refund-line-max">Max: {{ formatMontant(ligne.montant_max) }} {{ vente.devise }}</span>
                                        </label>
                                        <div class="refund-line-amount">
                                            <input
                                                type="number"
                                                v-model="ligne.montant"
                                                :disabled="!ligne.selected"
                                                :max="ligne.montant_max"
                                                min="0"
                                                step="1"
                                                class="form-control form-control-sm"
                                                :placeholder="t('pos.ventePos.refund.amount')"
                                                @input="limitRefundLigneMontant(idx)"
                                            />
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="form-group mt-3">
                                <label class="form-label">{{ t('pos.ventePos.fields.motif') }} <span class="text-danger">*</span></label>
                                <textarea
                                    v-model="refundLigneForm.motif"
                                    class="form-control"
                                    rows="2"
                                    :placeholder="t('pos.ventePos.placeholders.motif')"
                                ></textarea>
                            </div>
                            <div class="modal-actions">
                                <button class="btn-modal btn-secondary" @click="showRefundLigneModal = false">
                                    {{ t('actions.cancel') }}
                                </button>
                                <button class="btn-modal btn-info" @click="submitRefundLigne" :disabled="!isRefundLigneValid()">
                                    <i class="fas fa-check"></i>
                                    {{ t('pos.ventePos.refund.validate') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
        <!-- Modal Remboursement par Quantite -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showRefundQuantiteModal" class="modal-overlay">
                    <div class="modal-container modal-large">
                        <div class="modal-header">
                            <div class="modal-icon modal-icon-info">
                                <i class="fas fa-sort-numeric-down"></i>
                            </div>
                            <h3 class="modal-title">{{ t('pos.ventePos.refund.byQuantityTitle') }}</h3>
                            <button class="modal-close" @click="showRefundQuantiteModal = false">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="refund-lines-list">
                                <template v-for="(ligne, idx) in refundQuantiteForm.lignes" :key="idx">
                                    <div class="refund-line-item" v-if="ligne.quantite_max > 0">
                                        <div class="refund-line-info flex-grow">
                                            <span class="refund-line-name">{{ ligne.libelle }}</span>
                                            <span class="refund-line-max">
                                                Max: {{ ligne.quantite_max }} | Prix unit.: {{ formatMontant(ligne.prix_unitaire) }} {{ vente.devise }}
                                            </span>
                                        </div>
                                        <div class="refund-line-quantity">
                                            <label class="form-label-sm">{{ t('pos.ventePos.refund.quantity') }}</label>
                                            <input
                                                type="number"
                                                v-model="ligne.quantite"
                                                :max="ligne.quantite_max"
                                                min="0"
                                                step="1"
                                                class="form-control form-control-sm"
                                                @input="limitRefundQuantite(idx)"
                                            />
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="form-group mt-3">
                                <label class="form-label">{{ t('pos.ventePos.fields.motif') }} <span class="text-danger">*</span></label>
                                <textarea
                                    v-model="refundQuantiteForm.motif"
                                    class="form-control"
                                    rows="2"
                                    :placeholder="t('pos.ventePos.placeholders.motif')"
                                ></textarea>
                            </div>
                            <div class="modal-actions">
                                <button class="btn-modal btn-secondary" @click="showRefundQuantiteModal = false">
                                    {{ t('actions.cancel') }}
                                </button>
                                <button class="btn-modal btn-info" @click="submitRefundQuantite" :disabled="!isRefundQuantiteValid()">
                                    <i class="fas fa-check"></i>
                                    {{ t('pos.ventePos.refund.validate') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
        <!-- Modal Remboursement Mixte -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showRefundMixteModal" class="modal-overlay">
                    <div class="modal-container">
                        <div class="modal-header">
                            <div class="modal-icon modal-icon-info">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                            <h3 class="modal-title">{{ t('pos.ventePos.refund.mixedTitle') }}</h3>
                            <button class="modal-close" @click="showRefundMixteModal = false">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <!-- Info paiement initial -->
                            <div class="refund-payment-info">
                                <div class="payment-info-row">
                                    <span class="payment-info-label">{{ t('pos.ventePos.fields.total') }}</span>
                                    <span class="payment-info-value">{{ formatMontant(vente.total) }} {{ vente.devise }}</span>
                                </div>
                                <!-- Afficher deja rembourse si applicable -->
                                <div class="payment-info-row" v-if="vente.total_rembourse_cents > 0">
                                    <span class="payment-info-label">{{ t('pos.ventePos.fields.totalRembourse') }}</span>
                                    <span class="payment-info-value text-refund">-{{ formatMontant(vente.total_rembourse_cents) }} {{ vente.devise }}</span>
                                </div>
                                <div class="payment-info-row" v-if="vente.montant_espece">
                                    <span class="payment-info-label">{{ t('pos.ventePos.fields.montantEspece') }}</span>
                                    <span class="payment-info-value">{{ formatMontant(vente.montant_espece) }} {{ vente.devise }}</span>
                                </div>
                                <div class="payment-info-row" v-if="vente.montant_non_espece">
                                    <span class="payment-info-label">{{ t('pos.ventePos.fields.montantElectronique') }}</span>
                                    <span class="payment-info-value">{{ formatMontant(vente.montant_non_espece) }} {{ vente.devise }}</span>
                                </div>
                                <!-- Max remboursable -->
                                <!-- <div class="payment-info-row payment-info-highlight" v-if="refundMixteForm.max_total !== undefined">
                                    <span class="payment-info-label">{{ t('pos.ventePos.labels.maxMontant') }}</span>
                                    <span class="payment-info-value text-primary">{{ formatMontant(refundMixteForm.max_total) }} {{ vente.devise }}</span>
                                </div> -->
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-money-bill-wave"></i>
                                    {{ t('pos.ventePos.refund.amountCash') }}
                                </label>
                                <input
                                    type="number"
                                    step="1"
                                    min="0"
                                    :max="maxEspeceDisponible()"
                                    v-model="refundMixteForm.montant_espece"
                                    class="form-control"
                                    :placeholder="t('pos.ventePos.placeholders.montant')"
                                    @input="limitRefundMixteEspece"
                                />
                                <small class="text-muted">Max: {{ formatMontant(maxEspeceDisponible()) }} {{ vente.devise }}</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-credit-card"></i>
                                    {{ t('pos.ventePos.refund.amountElectronic') }}
                                </label>
                                <input
                                    type="number"
                                    step="1"
                                    min="0"
                                    :max="maxElectroniqueDisponible()"
                                    v-model="refundMixteForm.montant_electronique"
                                    class="form-control"
                                    :placeholder="t('pos.ventePos.placeholders.montant')"
                                    @input="limitRefundMixteElectronique"
                                />
                                <small class="text-muted">Max: {{ formatMontant(maxElectroniqueDisponible()) }} {{ vente.devise }}</small>
                            </div>
                            <!-- Total du remboursement mixte -->
                            <div class="refund-mixte-total">
                                <div class="refund-total-row">
                                    <span class="refund-total-label">{{ t('pos.ventePos.refund.totalRefund') }}</span>
                                    <span class="refund-total-value" :class="{ 'text-success': totalRefundMixte() > 0 }">
                                        {{ formatMontant(totalRefundMixte()) }} {{ vente.devise }}
                                    </span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ t('pos.ventePos.fields.motif') }} <span class="text-danger">*</span></label>
                                <textarea
                                    v-model="refundMixteForm.motif"
                                    class="form-control"
                                    rows="2"
                                    :placeholder="t('pos.ventePos.placeholders.motif')"
                                ></textarea>
                            </div>
                            <div class="modal-actions">
                                <button class="btn-modal btn-secondary" @click="showRefundMixteModal = false">
                                    {{ t('actions.cancel') }}
                                </button>
                                <button class="btn-modal btn-info" @click="submitRefundMixte" :disabled="!isRefundMixteValid()">
                                    <i class="fas fa-check"></i>
                                    {{ t('pos.ventePos.refund.validate') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
        <!-- Modal Remboursement Mixte par Quantite -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showRefundMixteQuantiteModal" class="modal-overlay">
                    <div class="modal-container modal-large">
                        <div class="modal-header">
                            <div class="modal-icon modal-icon-info">
                                <i class="fas fa-boxes"></i>
                            </div>
                            <h3 class="modal-title">{{ t('pos.ventePos.refund.mixedQuantityTitle') }}</h3>
                            <button class="modal-close" @click="showRefundMixteQuantiteModal = false">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="refund-lines-list">
                                <template v-for="(ligne, idx) in refundMixteQuantiteForm.lignes" :key="idx">
                                    <div class="refund-line-item" v-if="ligne.quantite_max > 0">
                                        <div class="refund-line-info flex-grow">
                                            <span class="refund-line-name">{{ ligne.libelle }}</span>
                                            <span class="refund-line-max">
                                                Max: {{ ligne.quantite_max }} | Prix unit.: {{ formatMontant(ligne.prix_unitaire) }} {{ vente.devise }}
                                            </span>
                                        </div>
                                        <div class="refund-line-quantity">
                                            <label class="form-label-sm">{{ t('pos.ventePos.refund.quantity') }}</label>
                                            <input
                                                type="number"
                                                v-model="ligne.quantite"
                                                :max="ligne.quantite_max"
                                                min="0"
                                                step="1"
                                                class="form-control form-control-sm"
                                                @input="limitRefundMixteQuantite(idx)"
                                            />
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <!-- Montant libre -->
                            <div class="form-group mt-3">
                                <label class="form-label">
                                    <i class="fas fa-money-bill-wave"></i>
                                    {{ t('pos.ventePos.refund.freeAmount') }}
                                </label>
                                <input
                                    type="number"
                                    step="1"
                                    min="0"
                                    v-model="refundMixteQuantiteForm.montant_libre"
                                    class="form-control"
                                    :placeholder="t('pos.ventePos.placeholders.montant')"
                                />
                            </div>
                            <!-- Total du remboursement -->
                            <div class="refund-mixte-total">
                                <div class="refund-total-row">
                                    <span class="refund-total-label">{{ t('pos.ventePos.refund.totalRefund') }}</span>
                                    <span class="refund-total-value" :class="{ 'text-success': totalRefundMixteQuantite() > 0 }">
                                        {{ formatMontant(totalRefundMixteQuantite()) }} {{ vente.devise }}
                                    </span>
                                </div>
                                <div class="refund-total-row refund-max-row" v-if="maxRefundMixteQuantite() > 0">
                                    <span class="refund-total-label text-muted">Maximum (articles)</span>
                                    <span class="refund-total-value text-muted">
                                        {{ formatMontant(maxRefundMixteQuantite()) }} {{ vente.devise }}
                                    </span>
                                </div>
                            </div>
                            <div class="form-group mt-3">
                                <label class="form-label">{{ t('pos.ventePos.fields.motif') }} <span class="text-danger">*</span></label>
                                <textarea
                                    v-model="refundMixteQuantiteForm.motif"
                                    class="form-control"
                                    rows="2"
                                    :placeholder="t('pos.ventePos.placeholders.motif')"
                                ></textarea>
                            </div>
                            <div class="modal-actions">
                                <button class="btn-modal btn-secondary" @click="showRefundMixteQuantiteModal = false">
                                    {{ t('actions.cancel') }}
                                </button>
                                <button class="btn-modal btn-info" @click="submitRefundMixteQuantite" :disabled="!isRefundMixteQuantiteValid()">
                                    <i class="fas fa-check"></i>
                                    {{ t('pos.ventePos.refund.validate') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
        <!-- Modal Paiement Mixte -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showPaiementMixteModal" class="modal-overlay" @click.self="showPaiementMixteModal = false">
                    <div class="modal-container modal-mixte">
                        <div class="modal-header">
                            <div class="modal-icon modal-icon-success">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                            <h3 class="modal-title">{{ t('pos.ventePos.paiement.mixteTitle') || 'Paiement Mixte' }}</h3>
                            <button class="modal-close" @click="showPaiementMixteModal = false">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="paiement-total">
                                <span class="paiement-label">TOTAL</span>
                                <span class="paiement-value">{{ formatMontant(vente.total) }}</span>
                            </div>
                            <p class="paiement-instruction">{{ t('pos.ventePos.paiement.mixteInstruction') || 'Saisissez les montants pour chaque mode de paiement' }}</p>
                            <div class="mixte-inputs">
                                <div class="mixte-input-group">
                                    <label class="mixte-label">
                                        <i class="fas fa-money-bill-wave"></i>
                                        {{ t('pos.ventePos.modePaiement.espece') }}
                                    </label>
                                    <input
                                        type="number"
                                        v-model="formPaiementMixte.montant_espece"
                                        class="mixte-input"
                                        min="0"
                                        step="1"
                                        :placeholder="t('pos.ventePos.placeholders.montantEspece') || 'Montant en espèces'"
                                    />
                                </div>
                                <div class="mixte-input-group">
                                    <label class="mixte-label">
                                        <i class="fas fa-credit-card"></i>
                                        {{ t('pos.ventePos.modePaiement.electronique') }}
                                    </label>
                                    <input
                                        type="number"
                                        v-model="formPaiementMixte.montant_electronique"
                                        class="mixte-input"
                                        min="0"
                                        step="1"
                                        :placeholder="t('pos.ventePos.placeholders.montantElectronique') || 'Montant électronique'"
                                    />
                                </div>
                            </div>
                            <div class="mixte-total">
                                <span class="mixte-total-label">{{ t('pos.ventePos.paiement.totalSaisi') || 'TOTAL SAISI' }}</span>
                                <span class="mixte-total-value">{{ formatMontant((parseFloat(formPaiementMixte.montant_espece) || 0) + (parseFloat(formPaiementMixte.montant_electronique) || 0)) }}</span>
                            </div>
                            <div class="mixte-actions">
                                <button class="btn-cancel-mixte" @click="showPaiementMixteModal = false">
                                    <i class="fas fa-times"></i>
                                    {{ t('actions.cancel') }}
                                </button>
                                <button class="btn-validate-mixte" @click="submitPaiementMixte">
                                    <i class="fas fa-check"></i>
                                    {{ t('pos.ventePos.cart.validatePaiement') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
</template>
<style>
/* Les styles principaux sont dans pos-caissier.css global */
/* Styles spécifiques pour Show.vue */
/* Fix: Styles explicites pour la table POS afin d'éviter les problèmes de CSS lors de la navigation SPA */
.vente-card .pos-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
}
.vente-card .pos-table th {
    padding: 1rem;
    text-align: left;
    font-size: 0.8rem;
    font-weight: 600;
    color: #ffffff !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: #000000 !important;
    border-bottom: none;
}
.vente-card .pos-table td {
    padding: 1rem;
    font-size: 0.9rem;
    color: #2D3748 !important;
    border-bottom: 1px solid #E2E8F0;
    vertical-align: middle;
    background: #fff;
}
.vente-card .pos-table tbody tr:hover {
    background: #F8F9FC;
}
.vente-card .pos-table tbody tr:hover td {
    background: #F8F9FC;
}
.vente-details-container {
    max-width: 900px;
    margin: 0 auto;
}
.vente-card {
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}
.vente-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    background: #000;
    color: #fff;
}
.vente-ref {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}
.ref-label {
    font-size: 0.85rem;
    opacity: 0.8;
}
.ref-value {
    font-size: 1.5rem;
    font-weight: 700;
    font-family: monospace;
}
.status-badge {
    padding: 0.5rem 1.25rem;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.9rem;
}
.badge-success { background: #2ECC71; color: #fff; }
.badge-warning { background: #F39C12; color: #fff; }
.badge-danger { background: #E74C3C; color: #fff; }
.badge-info { background: #3498DB; color: #fff; }
.badge-secondary { background: #6C757D; color: #fff; }
.vente-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    padding: 1.5rem 2rem;
    background: #F8F9FC !important;
}
/* Fix: Styles explicites pour les info-items */
.vente-card .info-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #fff !important;
    border-radius: 12px;
}
.vente-card .info-item i {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #000 !important;
    color: #fff !important;
    border-radius: 10px;
    font-size: 1rem;
}
.vente-card .info-content {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}
.vente-card .info-label {
    font-size: 0.8rem;
    color: #718096 !important;
}
.vente-card .info-value {
    font-weight: 600;
    color: #2D3748 !important;
}
/* Fix: Styles explicites pour les total-row */
.vente-card .total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: #000 !important;
    border-radius: 12px;
    color: #fff !important;
    margin-bottom: 0.75rem;
}
.vente-card .total-row .total-label,
.vente-card .total-row .total-value {
    color: #fff !important;
}
.vente-card .total-row.sub {
    background: #fff !important;
    color: #2D3748 !important;
    border: 1px solid #E2E8F0;
}
.vente-card .total-row.sub .total-label,
.vente-card .total-row.sub .total-value {
    color: #2D3748 !important;
}
.vente-card .total-row.main-total {
    background: #000 !important;
    color: #fff !important;
}
.vente-card .total-row.main-total .total-label,
.vente-card .total-row.main-total .total-value {
    color: #fff !important;
}
.info-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #fff;
    border-radius: 12px;
}
.info-item i {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #000;
    color: #fff;
    border-radius: 10px;
    font-size: 1rem;
}
.info-content {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}
.info-label {
    font-size: 0.8rem;
    color: #718096;
}
.info-value {
    font-weight: 600;
    color: #2D3748;
}
.articles-section {
    padding: 1.5rem 2rem;
}
.section-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.1rem;
    font-weight: 600;
    color: #2D3748;
    margin: 0 0 1rem;
}
.section-title i {
    color: #000;
}
.article-info {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}
.article-name {
    font-weight: 600;
    color: #2D3748;
}
.article-sku {
    font-size: 0.75rem;
    color: #718096;
    font-family: monospace;
}
.text-center { text-align: center; }
.text-end { text-align: right; }
.amount-cell {
    font-weight: 700;
    color: #2ECC71;
}
.vente-total {
    padding: 1.5rem 2rem;
    background: #F8F9FC;
}
.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: #000;
    border-radius: 12px;
    color: #fff;
    margin-bottom: 0.75rem;
}
.total-row.sub {
    background: #fff;
    color: #2D3748;
    border: 1px solid #E2E8F0;
}
.total-label {
    font-size: 1rem;
}
.total-value {
    font-size: 1.5rem;
    font-weight: 700;
}
.total-row.sub .total-value {
    font-size: 1.1rem;
}
.total-row.main-total {
    background: #000 !important;
    color: #fff !important;
}
.total-row.main-total .total-label,
.total-row.main-total .total-value {
    color: #fff !important;
}
.vente-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    padding: 1.5rem 2rem;
    border-top: 2px solid #E2E8F0;
    justify-content: center;
}
.btn-action-main {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}
.btn-action-main.btn-success { background: #2ECC71; color: #fff; }
.btn-action-main.btn-success:hover { background: #27AE60; transform: translateY(-2px); }
.btn-action-main.btn-primary { background: #3498DB; color: #fff; }
.btn-action-main.btn-primary:hover { background: #2980B9; transform: translateY(-2px); }
.btn-action-main.btn-danger { background: #E74C3C; color: #fff; }
.btn-action-main.btn-danger:hover { background: #C0392B; transform: translateY(-2px); }
.btn-action-main.btn-info { background: #9B59B6; color: #fff; }
.btn-action-main.btn-info:hover { background: #8E44AD; transform: translateY(-2px); }
/* Header actions */
.header-actions {
    display: flex;
    gap: 0.75rem;
}
.btn-print {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: #F8F9FC;
    border: 2px solid #E2E8F0;
    border-radius: 12px;
    color: #4A5568;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-print:hover {
    background: #E2E8F0;
}
/* Modal icon variants */
.modal-icon-danger { background: rgba(231, 76, 60, 0.1); color: #E74C3C; }
.modal-icon-info { background: rgba(155, 89, 182, 0.1); color: #9B59B6; }
.modal-icon-success { background: rgba(46, 204, 113, 0.1); color: #2ECC71; }
/* Modal button variants */
.btn-modal.btn-danger { background: #E74C3C; color: #fff; }
.btn-modal.btn-danger:hover:not(:disabled) { background: #C0392B; }
.btn-modal.btn-info { background: #9B59B6; color: #fff; }
.btn-modal.btn-info:hover:not(:disabled) { background: #8E44AD; }
.text-muted {
    color: #718096;
    font-size: 0.85rem;
}
/* Styles pour les boutons de remboursement en haut */
.refund-actions-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 0.5rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
}
.refund-actions-title {
    display: none;
}
.refund-buttons {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.5rem;
}
.btn-refund {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.5rem 0.85rem;
    border: none;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}
.btn-refund i {
    font-size: 0.75rem;
}
.btn-refund-total {
    background: #9B59B6;
    color: #fff;
}
.btn-refund-total:hover {
    background: #8E44AD;
    transform: translateY(-1px);
}
.btn-refund-ligne {
    background: #3498DB;
    color: #fff;
}
.btn-refund-ligne:hover {
    background: #2980B9;
    transform: translateY(-1px);
}
.btn-refund-quantite {
    background: #E67E22;
    color: #fff;
}
.btn-refund-quantite:hover {
    background: #D35400;
    transform: translateY(-1px);
}
.btn-refund-mixte {
    background: #1ABC9C;
    color: #fff;
}
.btn-refund-mixte:hover {
    background: #16A085;
    transform: translateY(-1px);
}
.btn-refund-mixte-quantite {
    background: #8E44AD;
    color: #fff;
}
.btn-refund-mixte-quantite:hover {
    background: #7D3C98;
    transform: translateY(-1px);
}
/* Styles pour les modals de remboursement */
.refund-lines-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}
.refund-line-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e2e8f0;
}
.refund-line-item:last-child {
    border-bottom: none;
}
.refund-line-check input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}
.refund-line-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    cursor: pointer;
}
.refund-line-info.flex-grow {
    flex: 1;
}
.refund-line-name {
    font-weight: 600;
    color: #2d3748;
    font-size: 0.9rem;
}
.refund-line-max {
    font-size: 0.75rem;
    color: #718096;
}
.refund-line-amount,
.refund-line-quantity {
    width: 120px;
}
.refund-line-amount input,
.refund-line-quantity input {
    text-align: right;
}
.form-control-sm {
    padding: 0.35rem 0.5rem;
    font-size: 0.85rem;
}
.form-label-sm {
    font-size: 0.7rem;
    color: #718096;
    display: block;
    margin-bottom: 0.2rem;
}
.refund-payment-info {
    background: #f8f9fc;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}
.payment-info-row {
    display: flex;
    justify-content: space-between;
    padding: 0.4rem 0;
    border-bottom: 1px dashed #e2e8f0;
}
.payment-info-row:last-child {
    border-bottom: none;
}
.payment-info-label {
    color: #718096;
    font-size: 0.85rem;
}
.payment-info-value {
    font-weight: 600;
    color: #2d3748;
}
.mt-3 {
    margin-top: 1rem;
}
.modal-large {
    max-width: 600px;
    width: 95%;
}
@media (max-width: 768px) {
    .refund-actions-bar {
        flex-direction: column;
        align-items: flex-start;
    }
    .refund-buttons {
        width: 100%;
    }
    .btn-refund {
        flex: 1;
        justify-content: center;
        min-width: 45%;
    }
    .refund-line-item {
        flex-wrap: wrap;
    }
    .refund-line-amount,
    .refund-line-quantity {
        width: 100%;
        margin-top: 0.5rem;
    }
}
/* Styles pour les colonnes de remboursement */
.refund-cell {
    color: #E74C3C;
    font-weight: 600;
}
.text-refund {
    color: #E74C3C !important;
    font-weight: 600;
}
.refund-row {
    background: #FEF2F2 !important;
    border-color: #FECACA !important;
}
.refund-row .total-label,
.refund-row .total-value {
    color: #DC2626 !important;
}
.refund-row-espece {
    background: #FEF3E2 !important;
    border-color: #FED7AA !important;
}
.refund-row-espece .total-label,
.refund-row-espece .total-value {
    color: #C2410C !important;
}
.refund-row-electronique {
    background: #EFF6FF !important;
    border-color: #BFDBFE !important;
}
.refund-row-electronique .total-label,
.refund-row-electronique .total-value {
    color: #1D4ED8 !important;
}
/* Style pour le highlight dans les infos de paiement */
.payment-info-highlight {
    background: #EEF2FF;
    border-radius: 6px;
    padding: 0.5rem !important;
    margin-top: 0.5rem;
}
.text-primary {
    color: #3B82F6 !important;
}
/* Styles pour le total du remboursement mixte */
.refund-mixte-total {
    background: #f8f9fc;
    border-radius: 8px;
    padding: 1rem;
    margin: 1rem 0;
}
.refund-total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.refund-total-label {
    font-weight: 600;
    color: #4a5568;
}
.refund-total-value {
    font-size: 1.1rem;
    font-weight: 700;
}
.text-success {
    color: #2ECC71 !important;
}
.text-warning {
    color: #F39C12 !important;
}
/* Print styles */
@media print {
    .navbar,
    .content-header,
    .vente-actions,
    .modal-overlay {
        display: none !important;
    }
    .pos-main {
        padding: 0 !important;
    }
    .vente-card {
        box-shadow: none !important;
    }
}
/* ========================================
   MODAL PAIEMENT MIXTE
   ======================================== */
.modal-mixte {
    max-width: 480px !important;
}
.paiement-total {
    text-align: center;
    padding: 1.5rem;
    background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
    border-radius: 16px;
    margin-bottom: 1.5rem;
}
.paiement-label {
    display: block;
    font-size: 0.85rem;
    color: #a0aec0;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.paiement-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #fff;
    line-height: 1;
}
.paiement-instruction {
    text-align: center;
    color: #4a5568;
    font-size: 0.95rem;
    margin-bottom: 1.25rem;
}
.mixte-inputs {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.mixte-input-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.mixte-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
    font-weight: 600;
    color: #2d3748;
}
.mixte-label i {
    color: #4a5568;
}
.mixte-input {
    padding: 0.875rem 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.2s ease;
    background: #fff;
}
.mixte-input:focus {
    outline: none;
    border-color: #1a202c;
    box-shadow: 0 0 0 3px rgba(26, 32, 44, 0.1);
}
.mixte-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border: 1px solid #e2e8f0;
}
.mixte-total-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: #4a5568;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.mixte-total-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a202c;
}
.mixte-actions {
    display: flex;
    gap: 1rem;
}
.btn-cancel-mixte,
.btn-validate-mixte {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
}
.btn-cancel-mixte {
    background: #e2e8f0;
    color: #2d3748;
}
.btn-cancel-mixte:hover {
    background: #cbd5e0;
    transform: translateY(-1px);
}
.btn-validate-mixte {
    background: #1a202c;
    color: #fff;
}
.btn-validate-mixte:hover {
    background: #2d3748;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(26, 32, 44, 0.2);
}
/* Responsive pour modal mixte */
@media (max-width: 480px) {
    .mixte-actions {
        flex-direction: column;
    }
    .mixte-total-value {
        font-size: 1.25rem;
    }
    .paiement-value {
        font-size: 2rem;
    }
}
</style>
