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
import { ref, computed, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import { useLoader } from '@/composables/useLoader';
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showUpdateLoader, hideLoader } = useLoader();
const props = defineProps({
    inventaire: Object,
    lignes: Array,
});
// Formater la date au format YYYY-MM-DD pour l'input date
const formatDateForInput = (dateString) => {
    if (!dateString) return '';
    // Si la date est déjà au bon format (YYYY-MM-DD), la retourner telle quelle
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
        return dateString;
    }
    // Sinon, convertir la date
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};
const form = ref({
    date_inventaire: formatDateForInput(props.inventaire.date_inventaire),
    commentaire: props.inventaire.commentaire || '',
    lignes: props.lignes.map(l => ({
        id: l.id,
        article_id: l.article_id,
        article_nom: l.article_nom,
        article_marque: l.article_marque,
        sku: l.sku,
        quantite_systeme: l.quantite_systeme,
        quantite_comptabilisee: l.quantite_comptabilisee,
        ecart: l.quantite_comptabilisee - l.quantite_systeme,
        commentaire: l.commentaire || ''
    }))
});
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
// Articles filtrés par recherche et statut
const filteredLignes = computed(() => {
    let result = form.value.lignes;
    // Filtre par recherche texte
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(ligne =>
            ligne.article_nom.toLowerCase().includes(query) ||
            (ligne.sku && ligne.sku.toLowerCase().includes(query))||
            (ligne.article_marque && ligne.article_marque.toLowerCase().includes(query))
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
// Reset page on filter
watch([searchQuery, filterStatut], () => {
    currentPage.value = 1;
});
const updateEcart = (ligne) => {
    ligne.ecart = ligne.quantite_comptabilisee - ligne.quantite_systeme;
};
// Copier le stock système vers la quantité comptée pour une ligne
const copierStockSysteme = (ligne) => {
    ligne.quantite_comptabilisee = ligne.quantite_systeme;
    ligne.ecart = 0;
};
// Copier le stock système vers la quantité comptée pour toutes les lignes
const copierToutStockSysteme = () => {
    form.value.lignes.forEach(ligne => {
        ligne.quantite_comptabilisee = ligne.quantite_systeme;
        ligne.ecart = 0;
    });
};
const submit = () => {
    if (form.value.lignes.length === 0) {
        alert('Veuillez ajouter au moins un article');
        return;
    }
    showUpdateLoader();
    router.put(route('inventaire.update', props.inventaire.id), form.value, {
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
    <Head :title="t('Modifier Inventaire') + ' - ' + inventaire.emplacement?.nom" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ t('Modifier Inventaire') }}</h4>
                   
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <form @submit.prevent="submit">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">{{ t('Emplacement') }}</label>
                                <input
                                    type="text"
                                    :value="inventaire.emplacement?.nom"
                                    class="form-control"
                                    readonly
                                />
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
                        <!-- Liste des articles -->
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
                                    <tr v-for="ligne in paginatedLignes" :key="ligne.id">
                                        <td>({{ ligne.article_marque }}) {{ ligne.article_nom }}</td>
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
                                                required
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
                                                maxlength="255"
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
                        <div v-if="totalPages > 1" class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <small class="text-muted">
                                    {{ t('Page') }} {{ currentPage }} {{ t('sur') }} {{ totalPages }}
                                </small>
                            </div>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item" :class="{ disabled: currentPage === 1 }">
                                        <button type="button" class="page-link" @click="goToPage(1)">
                                            <i class="fa fa-angle-double-left"></i>
                                        </button>
                                    </li>
                                    <li class="page-item" :class="{ disabled: currentPage === 1 }">
                                        <button type="button" class="page-link" @click="goToPage(currentPage - 1)">
                                            <i class="fa fa-angle-left"></i>
                                        </button>
                                    </li>
                                    <template v-for="pageNum in totalPages" :key="pageNum">
                                        <li
                                            v-if="pageNum >= currentPage - 2 && pageNum <= currentPage + 2"
                                            class="page-item"
                                            :class="{ active: pageNum === currentPage }"
                                        >
                                            <button type="button" class="page-link" @click="goToPage(pageNum)">
                                                {{ pageNum }}
                                            </button>
                                        </li>
                                    </template>
                                    <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                                        <button type="button" class="page-link" @click="goToPage(currentPage + 1)">
                                            <i class="fa fa-angle-right"></i>
                                        </button>
                                    </li>
                                    <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                                        <button type="button" class="page-link" @click="goToPage(totalPages)">
                                            <i class="fa fa-angle-double-right"></i>
                                        </button>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                        <div class="mt-4 d-flex gap-2 justify-content-center">
                            <button type="submit" class="btn btn-success">
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
</style>
