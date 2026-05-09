<script>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PosLayout from '@/Layouts/PosLayout.vue';
export default {
    layout: (h, page) => {
        // Déterminer le layout selon le rôle de l'utilisateur
        const userRoles = page.props.auth?.user?.roles || [];
        const useDashboardLayout = userRoles.some(role =>
            ['Super Admin', 'Admin', 'Marchand'].includes(role.name)
        );
        const Layout = useDashboardLayout ? DashboardLayout : PosLayout;
        return h(Layout, () => page);
    }
};
</script>
<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();
const props = defineProps({
    pointsVente: Array,
    articles: Array,
});
const form = ref({
    emplacement_id: '',
    date_inventaire: new Date().toISOString().split('T')[0],
    commentaire: '',
    lignes: []
});
const selectedPointVente = ref('');
const searchQuery = ref('');
const filterStatut = ref('');
const currentPage = ref(1);
const perPage = 10;
// Options de filtre par statut d'écart
const statutOptions = [
    { value: 'tous', label: 'Tous les articles' },
    { value: 'ecart_positif', label: 'Écart positif (+)' },
    { value: 'ecart_negatif', label: 'Écart négatif (-)' },
    { value: 'ecart_zero', label: 'Sans écart (0)' },
];
// Vérifier s'il n'y a qu'un seul emplacement
const hasOnlyOneEmplacement = computed(() => {
    return props.pointsVente && props.pointsVente.length === 1;
});
// Articles disponibles pour l'emplacement sélectionné
const articlesDisponibles = computed(() => {
    if (!selectedPointVente.value) return [];
    return props.articles.filter(a => a.point_vente_id === selectedPointVente.value);
});
// Articles filtrés par recherche et statut
const filteredLignes = computed(() => {
    let result = form.value.lignes;
    // Filtre par recherche texte
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(ligne =>
            ligne.article_nom.toLowerCase().includes(query) ||
            (ligne.sku && ligne.sku.toLowerCase().includes(query))
        );
    }
    // Filtre par statut d'écart
    if (filterStatut.value && filterStatut.value !== 'tous') {
        result = result.filter(ligne => {
            if (filterStatut.value === 'ecart_positif') return ligne.ecart > 0;
            if (filterStatut.value === 'ecart_negatif') return ligne.ecart < 0;
            if (filterStatut.value === 'ecart_zero') return ligne.ecart === 0;
            return true;
        });
    }
    return result;
});
// Pagination
const totalPages = computed(() => Math.ceil(filteredLignes.value.length / perPage));
const paginatedLignes = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    const end = start + perPage;
    return filteredLignes.value.slice(start, end);
});
// Reset page quand on filtre
watch([searchQuery, filterStatut], () => {
    currentPage.value = 1;
});
// Charger automatiquement tous les articles quand l'emplacement change
const chargerArticles = () => {
    form.value.emplacement_id = selectedPointVente.value;
    form.value.lignes = articlesDisponibles.value.map(article => ({
        article_id: article.value,
        article_nom: article.label,
        sku: article.sku || '-',
        quantite_systeme: article.stock,
        quantite_comptabilisee: 0,
        ecart: -article.stock,
        commentaire: ''
    }));
    currentPage.value = 1;
    searchQuery.value = '';
};
// Copier le stock système vers la quantité comptée pour une ligne
const copierStockSysteme = (ligne) => {
    ligne.quantite_comptabilisee = ligne.quantite_systeme;
    ligne.ecart = 0;
};
// Copier le stock système vers la quantité comptée pour toutes les lignes (filtrées ou non)
const copierToutStockSysteme = () => {
    form.value.lignes.forEach(ligne => {
        ligne.quantite_comptabilisee = ligne.quantite_systeme;
        ligne.ecart = 0;
    });
};
// Mettre à jour l'écart quand la quantité comptée change
const updateEcart = (ligne) => {
    ligne.ecart = ligne.quantite_comptabilisee - ligne.quantite_systeme;
};
// Watcher pour l'emplacement
watch(selectedPointVente, () => {
    if (selectedPointVente.value) {
        chargerArticles();
    }
});
// Initialiser si un seul emplacement
onMounted(() => {
    if (hasOnlyOneEmplacement.value) {
        selectedPointVente.value = props.pointsVente[0].value;
    }
});
const submit = () => {
    if (!form.value.emplacement_id) {
        alert('Veuillez sélectionner un emplacement');
        return;
    }
    if (form.value.lignes.length === 0) {
        alert('Aucun article disponible pour cet emplacement');
        return;
    }
    showStoreLoader();
    router.post(route('inventaire.store'), form.value, {
        onFinish: () => {
            hideLoader();
        }
    });
};
const cancel = () => {
    router.visit(route('inventaire.index'));
};
// Navigation pagination
const goToPage = (pageNum) => {
    if (pageNum >= 1 && pageNum <= totalPages.value) {
        currentPage.value = pageNum;
    }
};
</script>
<template>
    <Head :title="t('modules.business.inventaire.create')" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ t('modules.business.inventaire.create') }}</h4>
                  
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <form @submit.prevent="submit">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">{{ t('Emplacement') }} <span class="text-danger">*</span></label>
                                <template v-if="hasOnlyOneEmplacement">
                                    <input
                                        type="text"
                                        :value="pointsVente[0].label"
                                        class="form-control"
                                        readonly
                                    />
                                </template>
                                <template v-else>
                                    <StylishSelect
                                        v-model="selectedPointVente"
                                        :options="pointsVente"
                                        option-value="value"
                                        option-label="label"
                                        placeholder="Sélectionner un emplacement"
                                    />
                                </template>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ t('Date inventaire') }} <span class="text-danger">*</span></label>
                                <input
                                    type="date"
                                    v-model="form.date_inventaire"
                                    class="form-control"
                                    required
                                />
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-12">
                                <label class="form-label">{{ t('Commentaire') }}</label>
                                <textarea
                                    v-model="form.commentaire"
                                    class="form-control"
                                    rows="2"
                                    maxlength="1000"
                                ></textarea>
                            </div>
                        </div>
                        <hr class="my-4">
                        <!-- Liste des articles -->
                        <div v-if="form.lignes.length > 0">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">{{ t('Articles comptés') }} ({{ form.lignes.length }})</h5>
                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm"
                                    @click="copierToutStockSysteme"
                                >
                                    <i class="fa fa-copy me-1"></i>
                                    {{ t('Tout copier stock système') }}
                                </button>
                            </div>
                            <!-- Filtre de recherche -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="stylish-search-wrapper">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="search-icon">
                                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                                        </svg>
                                        <input
                                            type="text"
                                            v-model="searchQuery"
                                            class="stylish-search-input"
                                            :placeholder="t('Rechercher un article...')"
                                        />
                                        <button
                                            v-if="searchQuery"
                                            type="button"
                                            class="clear-btn"
                                            @click="searchQuery = ''"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-8 text-end d-flex align-items-center justify-content-end">
                                    <small class="text-muted">
                                        {{ filteredLignes.length }} {{ t('article(s)') }}
                                        <span v-if="searchQuery || filterStatut"> {{ t('sur') }} {{ form.lignes.length }}</span>
                                    </small>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ t('Article') }}</th>
                                            <th>{{ t('SKU') }}</th>
                                            <th class="text-center">{{ t('Stock Système') }}</th>
                                            <th class="text-center" style="width: 150px;">{{ t('Quantité Comptée') }}</th>
                                            <th class="text-center">{{ t('Écart') }}</th>
                                            <th style="width: 200px;">{{ t('Commentaire') }}</th>
                                            <th class="text-center" style="width: 80px;">{{ t('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="ligne in paginatedLignes" :key="ligne.article_id">
                                            <td>{{ ligne.article_nom }}</td>
                                            <td>
                                                <span class="badge bg-light text-dark">{{ ligne.sku }}</span>
                                            </td>
                                            <td class="text-center">
                                                <strong>{{ ligne.quantite_systeme }}</strong>
                                            </td>
                                            <td>
                                                <input
                                                    type="number"
                                                    v-model.number="ligne.quantite_comptabilisee"
                                                    @input="updateEcart(ligne)"
                                                    class="form-control form-control-sm text-center"
                                                    min="0"
                                                />
                                            </td>
                                            <td class="text-center">
                                                <span :class="['badge', ligne.ecart > 0 ? 'bg-success' : ligne.ecart < 0 ? 'bg-danger' : 'bg-secondary']">
                                                    {{ ligne.ecart > 0 ? '+' : '' }}{{ ligne.ecart }}
                                                </span>
                                            </td>
                                            <td>
                                                <input
                                                    type="text"
                                                    v-model="ligne.commentaire"
                                                    class="form-control form-control-sm"
                                                    :placeholder="t('Commentaire...')"
                                                />
                                            </td>
                                            <td class="text-center">
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-info btn-sm"
                                                    @click="copierStockSysteme(ligne)"
                                                    :title="t('Copier stock système')"
                                                >
                                                    <i class="fa fa-copy"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination -->
                            <div v-if="totalPages > 1" class="pagination-wrapper">
                                <nav class="pagination-nav">
                                    <ul class="pagination-list">
                                        <li class="pagination-item pagination-prev">
                                            <button
                                                type="button"
                                                class="pagination-link pagination-link-nav"
                                                :class="{ disabled: currentPage === 1 }"
                                                @click="goToPage(currentPage - 1)"
                                                :disabled="currentPage === 1"
                                            >
                                                <span class="pagination-icon">«</span>
                                            </button>
                                        </li>
                                        <template v-for="pageNum in totalPages" :key="pageNum">
                                            <li
                                                v-if="pageNum >= currentPage - 2 && pageNum <= currentPage + 2"
                                                class="pagination-item"
                                            >
                                                <button
                                                    type="button"
                                                    class="pagination-link"
                                                    :class="{ active: pageNum === currentPage }"
                                                    @click="goToPage(pageNum)"
                                                >
                                                    {{ pageNum }}
                                                </button>
                                            </li>
                                        </template>
                                        <li class="pagination-item pagination-next">
                                            <button
                                                type="button"
                                                class="pagination-link pagination-link-nav"
                                                :class="{ disabled: currentPage === totalPages }"
                                                @click="goToPage(currentPage + 1)"
                                                :disabled="currentPage === totalPages"
                                            >
                                                <span class="pagination-icon">»</span>
                                            </button>
                                        </li>
                                    </ul>
                                </nav>
                                <div class="pagination-info">
                                    <span>
                                        {{ t('affichagede') }} {{ (currentPage - 1) * perPage + 1 }} {{ t('to') }} {{ Math.min(currentPage * perPage, filteredLignes.length) }} {{ t('on') }} {{ filteredLignes.length }} {{ t('result') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-else-if="selectedPointVente" class="alert alert-warning">
                            {{ t('Aucun article disponible pour cet emplacement') }}
                        </div>
                        <div v-else class="alert alert-info">
                            {{ t('Veuillez sélectionner un emplacement pour voir les articles') }}
                        </div>
                        <div class="mt-4 d-flex gap-2 justify-content-center">
                            <button type="submit" class="btn btn-success" :disabled="form.lignes.length === 0">
                                <i class="fa fa-save me-1"></i>
                                {{ t('Enregistrer') }}
                            </button>
                            <button type="button" class="btn btn-secondary" @click="cancel">
                                <i class="fa fa-arrow-left me-1"></i>
                                {{ t('common.back') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Loader pleine page -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
<style scoped>
/* Stylish Search Input */
.stylish-search-wrapper {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 2px 2px;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 14px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(30, 41, 59, 0.08);
}
.stylish-search-wrapper:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.15);
}
.stylish-search-wrapper:focus-within {
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15), 0 4px 16px rgba(59, 130, 246, 0.15);
}
.stylish-search-wrapper .search-icon {
    width: 18px;
    height: 18px;
    color: #94a3b8;
    flex-shrink: 0;
}
.stylish-search-input {
    flex: 1;
    margin-left: 10px;
    padding: 0;
    border: none;
    background: transparent;
    font-size: 14px;
    font-weight: 500;
    color: #1e293b;
    outline: none;
}
.stylish-search-input::placeholder {
    color: #94a3b8;
}
.stylish-search-wrapper .clear-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    padding: 0;
    border: none;
    background: #f1f5f9;
    border-radius: 50%;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-left: 8px;
}
.stylish-search-wrapper .clear-btn:hover {
    background: #fee2e2;
    color: #dc2626;
}
.stylish-search-wrapper .clear-btn svg {
    width: 14px;
    height: 14px;
}
.pagination-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
    margin-top: 25px;
    padding: 20px 0;
}
.pagination-nav {
    display: flex;
    justify-content: center;
}
.pagination-list {
    display: flex;
    align-items: center;
    gap: 8px;
    list-style: none;
    padding: 0;
    margin: 0;
}
.pagination-item {
    display: flex;
}
.pagination-link {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 42px;
    height: 42px;
    padding: 0 12px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    color: #64748b;
    background: #f1f5f9;
    text-decoration: none;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
}
.pagination-link:hover:not(.disabled):not(.active) {
    background: #e2e8f0;
    color: #334155;
    transform: translateY(-1px);
}
.pagination-link.active {
    background: #000;
    color: #fff;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(26, 26, 46, 0.3);
}
.pagination-link.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}
.pagination-link-nav {
    gap: 8px;
    padding: 0 16px;
    background: transparent;
    color: #64748b;
}
.pagination-link-nav:hover:not(.disabled) {
    background: #f1f5f9;
    color: #1a1a2e;
}
.pagination-icon {
    font-size: 12px;
    font-weight: bold;
}
.pagination-info {
    font-size: 13px;
    color: #94a3b8;
}
</style>
