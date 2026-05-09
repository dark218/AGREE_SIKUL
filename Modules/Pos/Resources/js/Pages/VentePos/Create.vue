<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import PosLayout from '@/Layouts/PosLayout.vue';
import { useLoader } from '@/composables/useLoader';
import { useLocale } from '@/Composables/useLocale';
// Utiliser PosLayout comme layout
defineOptions({ layout: PosLayout });
const { t } = useLocale();
const { showLoader, hideLoader } = useLoader();
const props = defineProps({
    sessions: Array,
    employes: Array,
    pointsVente: Array,
    modePaiements: Array,
    devises: Array,
    selectedSessionId: [Number, String],
    articles: Object,
});
const showPaiementModal = ref(false);
// Formulaire
const form = ref({
    session_id: props.selectedSessionId || props.sessions?.[0]?.value || '',
    mode_paiement: 'espece',
    montant_espece: 0,
    montant_electronique: 0,
    lignes: [],
});
// Recherche d'articles
const searchQuery = ref('');
const searchResults = ref([]);
const showSearchResults = ref(false);
const isSearching = ref(false);
const searchPerformed = ref(false); // Pour savoir si une recherche a été effectuée
const searchRef = ref(null);
const searchInputRef = ref(null);
let searchTimeout = null;
function handleClickOutside(event) {
    // Fermer les résultats de recherche si clic en dehors
    if (searchRef.value && !searchRef.value.contains(event.target)) {
        showSearchResults.value = false;
    }
}
onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    // Focus sur la recherche au chargement
    if (searchInputRef.value) {
        searchInputRef.value.focus();
    }
});
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
// Erreurs
const errors = ref({});
// Session active
const sessionActive = computed(() => {
    return props.sessions?.find(s => s.value === form.value.session_id);
});
// Articles filtrés par la recherche (grille)
const filteredArticles = computed(() => {
    if (!props.articles?.data) return [];
    const query = searchQuery.value.trim().toLowerCase();
    // Si pas de recherche, retourner tous les articles
    if (query.length < 2) {
        return props.articles.data;
    }
    // Filtrer par nom ou SKU
    return props.articles.data.filter(article => {
        const nom = (article.nom || '').toLowerCase();
        const sku = (article.sku || '').toLowerCase();
        return nom.includes(query) || sku.includes(query);
});
});
// Total de la vente
const totalVente = computed(() => {
    return form.value.lignes.reduce((total, ligne) => {
        return total + calculerSousTotal(ligne);
    }, 0);
});
// Formater le montant
const formatMontant = (montant) => {
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(montant);
};
// Calculer le sous-total d'une ligne
const calculerSousTotal = (ligne) => {
    return (ligne.prix_unitaire * ligne.quantite) - (ligne.remise || 0) + (ligne.taxe || 0);
};
// Recherche d'articles (debounced)
watch(searchQuery, (newQuery) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    if (newQuery.length < 2) {
        searchResults.value = [];
        showSearchResults.value = false;
        searchPerformed.value = false;
        return;
    }
    searchTimeout = setTimeout(async () => {
        await searchArticles(newQuery);
    }, 300);
});
// Récupérer le point de vente de la session active
const getPointVenteId = () => {
    // On utilise le premier point de vente disponible
    return props.pointsVente?.[0]?.value || null;
};
// Recherche par SKU exact via API (pour scan code-barres)
const searchArticleBySku = async (sku) => {
    const pointVenteId = getPointVenteId();
    if (!pointVenteId) {
        return null;
    }
    try {
        const response = await axios.get('/api/business/article/findBySkuAndPointVente', {
            params: {
                sku: sku,
                points_vente_id: pointVenteId,
            }
        });
        if (response.data.success && response.data.data) {
            const article = response.data.data;
            return {
                id: article.id,
                sku: article.sku,
                nom: article.nom,
                prix_display: article.prix,
                devise: article.devise,
                stock: article.quantite_stock,
                taxes: article.taxes || [],
            };
        }
        return null;
    } catch (error) {
        // Pas d'erreur si pas trouvé, on continue avec la recherche classique
        return null;
    }
};
const searchArticles = async (query) => {
    if (!form.value.session_id) {
        errors.value.session_id = t('pos.ventePos.errors.selectSession');
        return;
    }
    isSearching.value = true;
    try {
        // D'abord essayer de trouver par SKU exact (scan code-barres)
        const exactMatch = await searchArticleBySku(query);
        if (exactMatch && exactMatch.stock > 0) {
            // Si on trouve un match exact avec du stock, l'ajouter directement au panier
            ajouterArticle(exactMatch);
            isSearching.value = false;
            return;
        }
        // Sinon, faire une recherche classique par nom ou SKU partiel
        const response = await axios.get(route('ventepos.search-articles'), {
            params: {
                q: query,
                session_id: form.value.session_id,
            }
        });
        searchResults.value = response.data;
        searchPerformed.value = true;
        showSearchResults.value = true; // Toujours afficher pour montrer "Aucun résultat" si vide
    } catch (error) {
        console.error('Erreur recherche articles:', error);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
};
// Ajouter un article au panier
const ajouterArticle = (article) => {
    // Vérifier si l'article est déjà dans le panier
    const existingIndex = form.value.lignes.findIndex(l => l.sku === article.sku);
    if (existingIndex !== -1) {
        // Incrémenter la quantité
        const ligne = form.value.lignes[existingIndex];
        if (ligne.quantite < article.stock) {
            ligne.quantite++;
        }
    } else {
        // Construire le libelle avec la marque si disponible
        let libelle = article.nom;
        if (article.marque) {
            libelle += ` (${article.marque})`;
        }
        // Ajouter nouvelle ligne
        form.value.lignes.push({
            id: Date.now(),
            article_id: article.id,
            sku: article.sku,
            libelle: libelle,
            quantite: 1,
            prix_unitaire: article.prix_display,
            remise: 0,
            taxe: 0,
            stock: article.stock,
});
    }
    // Reset recherche
    searchQuery.value = '';
    searchResults.value = [];
    showSearchResults.value = false;
    // Focus sur la recherche
    if (searchInputRef.value) {
        searchInputRef.value.focus();
    }
};
// Modifier la quantité
const modifierQuantite = (index, delta) => {
    const ligne = form.value.lignes[index];
    const newQty = ligne.quantite + delta;
    if (newQty >= 1 && newQty <= ligne.stock) {
        ligne.quantite = newQty;
    }
};
// Supprimer une ligne
const supprimerLigne = (index) => {
    form.value.lignes.splice(index, 1);
};
// Vider le panier
const viderPanier = () => {
    if (form.value.lignes.length === 0) return;
    if (confirm(t('pos.ventePos.confirmations.clearCart'))) {
        form.value.lignes = [];
    }
};
// Ouvrir le modal de paiement
const ouvrirPaiement = () => {
    if (form.value.lignes.length === 0) {
        errors.value.lignes = t('pos.ventePos.errors.noLignes');
        return;
    }
    errors.value = {};
    showPaiementModal.value = true;
};
// Soumettre la vente
const submitVente = (modePaiement) => {
    form.value.mode_paiement = modePaiement;
    form.value.montant_espece = 0;
    form.value.montant_electronique = 0;
    showPaiementModal.value = false;
    showLoader(t('pos.ventePos.messages.creationEnCours'), t('pos.ventePos.messages.veuilezPatienter'), 'primary');
    router.post(route('ventepos.store'), form.value, {
        onError: (errs) => {
            errors.value = errs;
            hideLoader();
        },
        onFinish: () => hideLoader(),
    });
};
// Navigation
const goBack = () => {
    if (form.value.lignes.length > 0) {
        if (!confirm(t('pos.ventePos.confirmations.leaveWithCart'))) {
            return;
        }
    }
    router.visit(route('session-caisse-caissier.index'));
};
// Gestion du clavier pour la recherche
const handleSearchKeydown = (event) => {
    if (event.key === 'Enter' && searchResults.value.length > 0) {
        ajouterArticle(searchResults.value[0]);
    }
};
// Pagination
const goToPage = (url) => {
    if (!url) return;
    router.get(url, {}, {
        preserveState: true,
        preserveScroll: true,
    });
};
// Ajouter un article depuis la grille
const ajouterArticleFromGrid = (article) => {
    ajouterArticle({
        id: article.id,
        sku: article.sku,
        nom: article.nom,
        marque: article.marque,
        prix_display: article.prix_display,
        devise: article.devise,
        stock: article.stock,
        taxes: article.taxes || [],
    });
};
</script>
<template>
    <!-- Main Content - POS Layout -->
    <div class="pos-sale-main">
            <!-- Zone gauche - Recherche et articles -->
            <section class="pos-products-section">
                <!-- Barre de recherche -->
                <div class="search-section" ref="searchRef">
                    <div class="search-input-wrapper">
                        
                        <input
                            ref="searchInputRef"
                            type="text"
                            v-model="searchQuery"
                            class="search-input"
                            :placeholder="t('pos.ventePos.placeholders.searchArticle')"
                            @keydown="handleSearchKeydown"
                            autocomplete="off"
                        />
                        <div v-if="isSearching" class="search-spinner">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                    </div>
                    <!-- Résultats de recherche -->
                    <Transition name="dropdown">
                        <div v-if="showSearchResults" class="search-results">
                            <!-- Articles trouvés -->
                            <template v-if="searchResults.length > 0">
                                <button
                                    v-for="article in searchResults"
                                    :key="article.id"
                                    class="search-result-item"
                                    @click="ajouterArticle(article)"
                                >
                                    <div class="result-info">
                                        <span class="result-sku">{{ article.sku }}</span>
                                        <span class="result-name">{{ article.nom }}<template v-if="article.marque"> ({{ article.marque }})</template></span>
                                    </div>
                                    <div class="result-meta">
                                        <span class="result-price">{{ formatMontant(article.prix_display) }}</span>
                                        <span class="result-stock">Stock: {{ article.stock }}</span>
                                    </div>
                                </button>
                            </template>
                            <!-- Aucun résultat -->
                            <div v-else-if="searchPerformed && !isSearching" class="search-no-results">
                                <i class="fas fa-search"></i>
                                <span>{{ t('no_results') }}</span>
                            </div>
                            <!-- Recherche en cours -->
                            <div v-else-if="isSearching" class="search-loading">
                                <i class="fas fa-spinner fa-spin"></i>
                                <span>{{ t('pos.ventePos.messages.recherche') }}</span>
                            </div>
                        </div>
                    </Transition>
                    <div v-if="errors.session_id" class="search-error">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ errors.session_id }}
                    </div>
                </div>
                <!-- Grille d'articles -->
                <div class="articles-grid" v-if="filteredArticles.length > 0">
                    <div
                        v-for="article in filteredArticles"
                        :key="article.id"
                        class="article-card"
                        @click="ajouterArticleFromGrid(article)"
                    >
                        <div class="article-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="article-info">
                            <span class="article-name">{{ article.nom }}<template v-if="article.marque"> ({{ article.marque }})</template></span>
                            <span class="article-sku">{{ article.sku }}</span>
                        </div>
                        <div class="article-meta">
                            <span class="article-price">{{ formatMontant(article.prix_display) }} {{ article.devise }}</span>
                            <span class="article-stock">{{ t('pos.ventePos.stock') }}: {{ article.stock }}</span>
                        </div>
                    </div>
                </div>
                <!-- Message si aucun article -->
                <div class="no-articles" v-else-if="articles && articles.data && articles.data.length === 0">
                    <i class="fas fa-box-open"></i>
                    <p>{{ t('pos.ventePos.noArticles') }}</p>
                </div>
                <!-- Message si filtre sans résultat -->
                <div class="no-articles" v-else-if="filteredArticles.length === 0 && searchQuery.length >= 2">
                    <i class="fas fa-search"></i>
                    <p>{{ t('no_results') }}</p>
                </div>
                <!-- Pagination -->
                <div class="articles-pagination" v-if="articles && articles.last_page > 1">
                    <button
                        class="pagination-btn"
                        :disabled="!articles.prev_page_url"
                        @click="goToPage(articles.prev_page_url)"
                    >
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span class="pagination-info">
                        {{ articles.current_page }} / {{ articles.last_page }}
                    </span>
                    <button
                        class="pagination-btn"
                        :disabled="!articles.next_page_url"
                        @click="goToPage(articles.next_page_url)"
                    >
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </section>
            <!-- Zone droite - Panier -->
            <section class="pos-cart-section">
                <div class="cart-header">
                    <div class="cart-title">
                        <i class="fas fa-shopping-cart"></i>
                        <h2>{{ t('pos.ventePos.cart.title') }}</h2>
                        <span class="cart-count">{{ form.lignes.length }}</span>
                    </div>
                    <button v-if="form.lignes.length > 0" class="btn-clear-cart" @click="viderPanier">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <!-- Liste des articles du panier -->
                <div class="cart-items">
                    <div v-if="form.lignes.length === 0" class="cart-empty">
                        <i class="fas fa-shopping-basket"></i>
                        <p>{{ t('pos.ventePos.cart.empty') }}</p>
                    </div>
                    <TransitionGroup name="cart-item" tag="div" class="cart-items-list">
                        <div v-for="(ligne, index) in form.lignes" :key="ligne.id" class="cart-item">
                            <div class="cart-item-info">
                                <span class="item-name">{{ ligne.libelle }}</span>
                                <span class="item-sku">{{ ligne.sku }}</span>
                            </div>
                            <div class="cart-item-qty">
                                <button class="qty-btn" @click="modifierQuantite(index, -1)" :disabled="ligne.quantite <= 1">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="qty-value">{{ ligne.quantite }}</span>
                                <button class="qty-btn" @click="modifierQuantite(index, 1)" :disabled="ligne.quantite >= ligne.stock">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <div class="cart-item-price">
                                <span class="item-total">{{ formatMontant(calculerSousTotal(ligne)) }}</span>
                                <span class="item-unit">{{ formatMontant(ligne.prix_unitaire) }}/u</span>
                            </div>
                            <button class="btn-remove-item" @click="supprimerLigne(index)">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </TransitionGroup>
                </div>
                <!-- Total et actions -->
                <div class="cart-footer">
                    <div class="cart-total">
                        <span class="total-label">{{ t('pos.ventePos.cart.total') }}</span>
                        <span class="total-value">{{ formatMontant(totalVente) }}</span>
                    </div>
                    <div class="cart-actions">
                        <button class="btn-cancel" @click="goBack">
                            <i class="fas fa-arrow-left"></i>
                            {{ t('actions.cancel') }}
                        </button>
                        <button
                            class="btn-validate"
                            @click="ouvrirPaiement"
                            :disabled="form.lignes.length === 0"
                        >
                            <i class="fas fa-check"></i>
                            {{ t('pos.ventePos.cart.validate') }}
                        </button>
                    </div>
                </div>
            </section>
        </div>
        <!-- Modal Paiement -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showPaiementModal" class="modal-overlay" @click.self="showPaiementModal = false">
                    <div class="modal-container modal-paiement">
                        <div class="modal-header">
                            <div class="modal-icon modal-icon-success">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <h3 class="modal-title">{{ t('pos.ventePos.paiement.title') }}</h3>
                            <button class="modal-close" @click="showPaiementModal = false">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="paiement-total">
                                <span class="paiement-label">{{ t('pos.ventePos.cart.total') }}</span>
                                <span class="paiement-value">{{ formatMontant(totalVente) }}</span>
                            </div>
                            <p class="paiement-instruction">{{ t('pos.ventePos.paiement.selectMode') }}</p>
                            <div class="paiement-options">
                                <button class="paiement-btn paiement-espece" @click="submitVente('espece')">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>{{ t('pos.ventePos.modePaiement.espece') }}</span>
                                </button>
                                <button class="paiement-btn paiement-electronique" @click="submitVente('electronique')">
                                    <i class="fas fa-credit-card"></i>
                                    <span>{{ t('pos.ventePos.modePaiement.electronique') }}</span>
                                </button>
                                <button class="paiement-btn paiement-mixte" @click="submitVente('mixte')">
                                    <i class="fas fa-exchange-alt"></i>
                                    <span>{{ t('pos.ventePos.modePaiement.mixte') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
</template>
<style>
/* ========================================
   MODAL PAIEMENT - DESIGN CARDS NOIR
   ======================================== */
.modal-paiement {
    max-width: 520px !important;
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
.paiement-options {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr) !important;
    gap: 1rem !important;
    flex-direction: unset !important;
}
.paiement-btn {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.75rem !important;
    padding: 1.5rem 1rem !important;
    background: #f8f9fc !important;
    border: 2px solid #e2e8f0 !important;
    border-radius: 16px !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    min-height: 120px !important;
}
.paiement-btn i {
    font-size: 1.5rem !important;
    width: 56px !important;
    height: 56px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    background: #e2e8f0 !important;
    border-radius: 12px !important;
    color: #4a5568 !important;
    transition: all 0.3s ease !important;
}
.paiement-btn span {
    font-size: 0.9rem !important;
    font-weight: 600 !important;
    color: #2d3748 !important;
}
.paiement-btn:hover {
    transform: translateY(-4px) !important;
    border-color: #1a202c !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15) !important;
}
.paiement-btn:hover i {
    background: #1a202c !important;
    color: #fff !important;
    transform: scale(1.05) !important;
}
/* Style actif/sélectionné */
.paiement-espece,
.paiement-electronique,
.paiement-mixte {
    background: #f8f9fc !important;
    border-color: #e2e8f0 !important;
}
.paiement-espece i,
.paiement-electronique i,
.paiement-mixte i {
    background: #e2e8f0 !important;
    color: #4a5568 !important;
}
.paiement-espece:hover,
.paiement-electronique:hover,
.paiement-mixte:hover {
    border-color: #1a202c !important;
    background: #fff !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15) !important;
}
.paiement-espece:hover i,
.paiement-electronique:hover i,
.paiement-mixte:hover i {
    background: #1a202c !important;
    color: #fff !important;
}
/* Responsive pour modal paiement */
@media (max-width: 480px) {
    .paiement-options {
        grid-template-columns: 1fr !important;
    }
    .paiement-btn {
        flex-direction: row !important;
        min-height: auto !important;
        padding: 1rem 1.25rem !important;
        gap: 1rem !important;
    }
    .paiement-btn i {
        font-size: 1.25rem !important;
        width: 44px !important;
        height: 44px !important;
    }
    .paiement-value {
        font-size: 2rem !important;
    }
}
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
