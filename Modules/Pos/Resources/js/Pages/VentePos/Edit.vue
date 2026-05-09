<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import PosLayout from '@/Layouts/PosLayout.vue';
import { useLoader } from '@/Composables/useLoader';
import { useLocale } from '@/Composables/useLocale';
// Utiliser PosLayout comme layout
defineOptions({ layout: PosLayout });
const { t } = useLocale();
const { showLoader, hideLoader } = useLoader();
const props = defineProps({
    vente: Object,
    modePaiements: Array,
    devises: Array,
});
// Formulaire initialisé avec les données de la vente
const form = ref({
    mode_paiement: props.vente.mode_paiement,
    montant_espece: parseFloat(props.vente.montant_espece) || 0,
    montant_electronique: parseFloat(props.vente.montant_electronique) || 0,
    lignes: props.vente.lignes.map(ligne => ({
        id: ligne.id,
        sku: ligne.sku,
        libelle: ligne.libelle,
        quantite: ligne.quantite,
        quantite_originale: ligne.quantite, // Pour tracker les changements
        prix_unitaire: parseFloat(ligne.prix_unitaire) || 0,
        remise: parseFloat(ligne.remise) || 0,
        taxe: parseFloat(ligne.taxe) || 0,
        stock: ligne.stock || 999,
        deleted: false, // Pour marquer les lignes à supprimer
    })),
});
// Erreurs
const errors = ref({});
// Indicateur de modifications en attente
const hasChanges = computed(() => {
    // Vérifier si le mode de paiement a changé
    if (form.value.mode_paiement !== props.vente.mode_paiement) return true;
    // Vérifier les lignes modifiées ou supprimées
    return form.value.lignes.some(ligne => {
        if (ligne.deleted) return true;
        if (ligne.quantite !== ligne.quantite_originale) return true;
        return false;
});
});
// Nombre de lignes actives (non supprimées)
const activeLignes = computed(() => {
    return form.value.lignes.filter(l => !l.deleted);
});
// Total (uniquement les lignes non supprimées)
const totalVente = computed(() => {
    return activeLignes.value.reduce((total, ligne) => {
        return total + calculerSousTotal(ligne);
    }, 0);
});
// Formater le montant
const formatMontant = (montant) => {
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(montant || 0);
};
// Calculer le sous-total d'une ligne
const calculerSousTotal = (ligne) => {
    return (ligne.prix_unitaire * ligne.quantite) - (ligne.remise || 0) + (ligne.taxe || 0);
};
// Modifier la quantité
const modifierQuantite = (index, delta) => {
    const ligne = form.value.lignes[index];
    const newQty = ligne.quantite + delta;
    if (newQty >= 1 && newQty <= ligne.stock) {
        ligne.quantite = newQty;
    }
};
// Supprimer une ligne (marquer pour suppression, pas d'envoi immédiat)
const supprimerLigne = (index) => {
    // Compter les lignes actives (non supprimées)
    const lignesActives = form.value.lignes.filter(l => !l.deleted);
    if (lignesActives.length <= 1) {
        errors.value.lignes = t('pos.ventePos.errors.minOneLigne');
        return;
    }
    if (!confirm(t('pos.ventePos.confirmations.deleteLigne'))) return;
    // Marquer la ligne comme supprimée (sera envoyée lors de la validation)
    form.value.lignes[index].deleted = true;
    errors.value = {};
};
// Restaurer une ligne supprimée
const restaurerLigne = (index) => {
    form.value.lignes[index].deleted = false;
};
// Annuler toutes les modifications
const annulerModifications = () => {
    if (!hasChanges.value) {
        goBack();
        return;
    }
    if (!confirm(t('pos.ventePos.confirmations.leaveWithCart'))) return;
    // Restaurer les valeurs originales
    form.value.mode_paiement = props.vente.mode_paiement;
    form.value.lignes = props.vente.lignes.map(ligne => ({
        id: ligne.id,
        sku: ligne.sku,
        libelle: ligne.libelle,
        quantite: ligne.quantite,
        quantite_originale: ligne.quantite,
        prix_unitaire: parseFloat(ligne.prix_unitaire) || 0,
        remise: parseFloat(ligne.remise) || 0,
        taxe: parseFloat(ligne.taxe) || 0,
        stock: ligne.stock || 999,
        deleted: false,
    }));
    goBack();
};
// Gérer le changement de mode de paiement
const onModePaiementChange = (newMode) => {
    if (newMode !== 'mixte') {
        // Si on passe à un autre mode, réinitialiser les montants mixtes
        form.value.montant_espece = 0;
        form.value.montant_electronique = 0;
    }
};
// Soumettre toutes les modifications
const submit = () => {
    // Vérifier qu'il reste au moins une ligne active
    if (activeLignes.value.length === 0) {
        errors.value.lignes = t('pos.ventePos.errors.noLignes');
        return;
    }
    errors.value = {};
    showLoader(t('pos.ventePos.messages.updateEnCours'), t('pos.ventePos.messages.veuilezPatienter'), 'primary');
    // Préparer les données à envoyer
    const payload = {
        mode_paiement: form.value.mode_paiement,
        montant_espece: 0,
        montant_electronique: 0,
        lignes: form.value.lignes.map(ligne => ({
            id: ligne.id,
            quantite: ligne.quantite,
            prix_unitaire: ligne.prix_unitaire,
            deleted: ligne.deleted,
        })),
};
    router.put(route('ventepos.update', props.vente.id), payload, {
        onError: (errs) => {
            errors.value = errs;
        },
        onFinish: () => hideLoader(),
    });
};
// Navigation
const goBack = () => {
    router.visit(route('ventepos.show', props.vente.id));
};
</script>
<template>
    <!-- Main Content -->
    <div class="pos-edit-main">
            <!-- Infos vente (gauche) -->
            <section class="edit-info-section">
                <div class="info-card">
                    <div class="info-header">
                        <i class="fas fa-receipt"></i>
                        <h3>{{ vente.reference }}</h3>
                    </div>
                    <div class="info-body">
                        <div class="info-row">
                            <span class="info-label">{{ t('pos.ventePos.fields.caissier') }}</span>
                            <span class="info-value">{{ vente.caissier }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ t('pos.ventePos.fields.date') }}</span>
                            <span class="info-value">{{ vente.date }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ t('pos.ventePos.fields.devise') }}</span>
                            <span class="info-value">{{ vente.devise }}</span>
                        </div>
                    </div>
                </div>
                <!-- Mode de paiement -->
                <div class="mode-paiement-card">
                    <h4>{{ t('pos.ventePos.fields.modePaiement') }}</h4>
                    <div class="mode-options">
                        <label v-for="mode in modePaiements" :key="mode.value" class="mode-option" :class="{ active: form.mode_paiement === mode.value }">
                            <input type="radio" v-model="form.mode_paiement" :value="mode.value" class="mode-radio" @change="onModePaiementChange(mode.value)" />
                            <i :class="mode.value === 'espece' ? 'fas fa-money-bill-wave' : mode.value === 'electronique' ? 'fas fa-credit-card' : 'fas fa-exchange-alt'"></i>
                            <span>{{ mode.label }}</span>
                        </label>
                    </div>
                </div>
            </section>
            <!-- Panier (droite) -->
            <section class="edit-cart-section">
                <div class="cart-header">
                    <div class="cart-title">
                        <i class="fas fa-shopping-cart"></i>
                        <h2>{{ t('pos.ventePos.sections.lignes') }}</h2>
                        <span class="cart-count">{{ activeLignes.length }}</span>
                    </div>
                    <!-- Indicateur de modifications en attente -->
                    <div v-if="hasChanges" class="changes-indicator">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>{{ t('pos.ventePos.edit.pendingChanges') }}</span>
                    </div>
                </div>
                <!-- Erreurs -->
                <div v-if="errors.lignes" class="cart-error">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ errors.lignes }}
                </div>
                <!-- Liste des articles -->
                <div class="cart-items">
                    <TransitionGroup name="cart-item" tag="div" class="cart-items-list">
                        <div v-for="(ligne, index) in form.lignes" :key="ligne.id" class="cart-item" :class="{ 'cart-item-deleted': ligne.deleted, 'cart-item-modified': !ligne.deleted && ligne.quantite !== ligne.quantite_originale }">
                            <div class="cart-item-info">
                                <span class="item-name">{{ ligne.libelle }}</span>
                                <span class="item-sku">{{ ligne.sku }}</span>
                                <span v-if="ligne.deleted" class="item-status deleted">{{ t('pos.ventePos.edit.markedForDeletion') }}</span>
                                <span v-else-if="ligne.quantite !== ligne.quantite_originale" class="item-status modified">
                                    {{ t('pos.ventePos.edit.quantityChanged') }}: {{ ligne.quantite_originale }} → {{ ligne.quantite }}
                                </span>
                            </div>
                            <div class="cart-item-qty" v-if="!ligne.deleted">
                                <button class="qty-btn" @click="modifierQuantite(index, -1)" :disabled="ligne.quantite <= 1">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="qty-value">{{ ligne.quantite }}</span>
                                <button class="qty-btn" @click="modifierQuantite(index, 1)" :disabled="ligne.quantite >= ligne.stock">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <div class="cart-item-price" v-if="!ligne.deleted">
                                <span class="item-total">{{ formatMontant(calculerSousTotal(ligne)) }}</span>
                                <span class="item-unit">{{ formatMontant(ligne.prix_unitaire) }}/u</span>
                            </div>
                            <!-- Bouton supprimer ou restaurer -->
                            <button v-if="!ligne.deleted" class="btn-remove-item" @click="supprimerLigne(index)" :disabled="activeLignes.length <= 1" :title="activeLignes.length <= 1 ? t('pos.ventePos.errors.minOneLigne') : t('actions.delete')">
                                <i class="fas fa-times"></i>
                            </button>
                            <button v-else class="btn-restore-item" @click="restaurerLigne(index)" :title="t('pos.ventePos.edit.restore')">
                                <i class="fas fa-undo"></i>
                            </button>
                        </div>
                    </TransitionGroup>
                </div>
                <!-- Total et actions -->
                <div class="cart-footer">
                    <div class="cart-total">
                        <span class="total-label">{{ t('pos.ventePos.cart.total') }}</span>
                        <span class="total-value">{{ formatMontant(totalVente) }} {{ vente.devise }}</span>
                    </div>
                    <div class="cart-actions">
                        <button class="btn-cancel" @click="goBack">
                            <i class="fas fa-arrow-left"></i>
                            {{ t('actions.cancel') }}
                        </button>
                        <button class="btn-validate" @click="submit" :disabled="form.lignes.length === 0">
                            <i class="fas fa-save"></i>
                            {{ t('actions.save') }}
                        </button>
                    </div>
                </div>
            </section>
        </div>
</template>
<style>
/* ========================================
   TOTAL DU PANIER - STYLE
   ======================================== */
.cart-total {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    padding: 1.25rem 1.5rem !important;
    background: #000 !important;
    border-radius: 12px !important;
    margin-bottom: 1rem !important;
}
.total-label {
    font-size: 0.9rem !important;
    font-weight: 600 !important;
    color: #fff !important;
    text-transform: uppercase !important;
    letter-spacing: 1px !important;
}
.total-value {
    font-size: 1.75rem !important;
    font-weight: 700 !important;
    color: #fff !important;
}
</style>
