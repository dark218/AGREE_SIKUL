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
import { usePermissions } from '@/Composables/usePermissions';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import { useLoader } from '@/composables/useLoader';
const { t } = useI18n();
const page = usePage();
const { can } = usePermissions();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showLoader, hideLoader } = useLoader();
const props = defineProps({
    inventaire: Object,
});
const showValidationModal = ref(false);
const showCancelModal = ref(false);
const commentaireValidation = ref('');
const raisonAnnulation = ref('');
// Recherche et pagination
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
    let result = props.inventaire.lignes || [];
    // Filtre par recherche texte
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(ligne =>
            (ligne.article?.nom && ligne.article.nom.toLowerCase().includes(query)) ||
            (ligne.article?.sku && ligne.article.sku.toLowerCase().includes(query)) ||
            (ligne.article?.marque && ligne.article.marque.toLowerCase().includes(query))
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
// Navigation pagination
const goToPage = (pageNum) => {
    if (pageNum >= 1 && pageNum <= totalPages.value) {
        currentPage.value = pageNum;
    }
};
const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};
const getStatutBadgeClass = (statut) => {
    const classes = {
        'brouillon': 'bg-warning text-dark',
        'valide': 'bg-success text-white',
        'annule': 'bg-danger text-white',
    };
    return classes[statut] || 'bg-secondary';
};
const getStatutLabel = (statut) => {
    const labels = {
        'brouillon': t('modules.business.inventaire.statuts.brouillon'),
        'valide': t('modules.business.inventaire.statuts.valide'),
        'annule': t('modules.business.inventaire.statuts.annule')
    };
    return labels[statut] || statut;
};
const validerInventaire = () => {
    showLoader({
        message: 'Validation en cours...',
        subMessage: 'Veuillez patienter',
        variant: 'success'
    });
    router.put(route('inventaire.validate', props.inventaire.id), {
        commentaire: commentaireValidation.value
    }, {
        onFinish: () => {
            hideLoader();
        }
    });
    showValidationModal.value = false;
};
const annulerInventaire = () => {
    if (!raisonAnnulation.value) {
        alert('Veuillez entrer une raison d\'annulation');
        return;
    }
    showLoader({
        message: 'Annulation en cours...',
        subMessage: 'Veuillez patienter',
        variant: 'danger'
    });
    router.put(route('inventaire.cancel', props.inventaire.id), {
        raison_annulation: raisonAnnulation.value
    }, {
        onFinish: () => {
            hideLoader();
        }
    });
    showCancelModal.value = false;
};
const goBack = () => {
    router.visit(route('inventaire.index'));
};
</script>
<template>
    <Head :title="t('Détails Inventaire') + ' - ' + inventaire.emplacement?.nom" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ t('Détails Inventaire') }}</h4>
                    
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>{{ t('Informations générales') }}</h5>
                            <table class="table table-borderless">
                                <tbody>
                                    <tr>
                                        <td class="fw-bold">{{ t('Emplacement') }} :</td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <i class="fa fa-store me-1"></i>
                                                {{ inventaire.emplacement?.nom }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ t('Date inventaire') }} :</td>
                                        <td>{{ formatDate(inventaire.date_inventaire) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ t('Statut') }} :</td>
                                        <td>
                                            <span :class="['badge', getStatutBadgeClass(inventaire.statut)]">
                                                {{ getStatutLabel(inventaire.statut) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ t('Créé par') }} :</td>
                                        <td>{{ inventaire.cree_par || '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>{{ t('Validation') }}</h5>
                            <table class="table table-borderless">
                                <tbody>
                                    <tr>
                                        <td class="fw-bold">{{ t('Date validation') }} :</td>
                                        <td>{{ formatDate(inventaire.date_validation) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ t('Validé par') }} :</td>
                                        <td>{{ inventaire.valide_par || '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div v-if="inventaire.commentaire" class="row">
                        <div class="col-12">
                            <h5>{{ t('Commentaire') }}</h5>
                            <p class="p-3 bg-light rounded">{{ inventaire.commentaire }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">{{ t('Articles comptés') }} ({{ inventaire.lignes?.length || 0 }})</h5>
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
                                <span v-if="searchQuery || filterStatut"> {{ t('sur') }} {{ inventaire.lignes?.length || 0 }}</span>
                            </small>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ t('Article') }}</th>
                                    <th>{{ t('SKU') }}</th>
                                    <th>{{ t('Quantité Système') }}</th>
                                    <th>{{ t('Quantité Comptée') }}</th>
                                    <th>{{ t('Écart') }}</th>
                                    <th>{{ t('Commentaire') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="ligne in paginatedLignes" :key="ligne.id">
                                    <td>({{ ligne.article?.marque }}) {{ ligne.article?.nom }}</td>
                                    <td>{{ ligne.article?.sku }}</td>
                                    <td>{{ ligne.quantite_systeme }}</td>
                                    <td>{{ ligne.quantite_comptabilisee }}</td>
                                    <td>
                                        <span :class="['badge', ligne.ecart > 0 ? 'bg-success' : ligne.ecart < 0 ? 'bg-danger' : 'bg-secondary']">
                                            {{ ligne.ecart > 0 ? '+' : '' }}{{ ligne.ecart }}
                                        </span>
                                    </td>
                                    <td>{{ ligne.commentaire || '-' }}</td>
                                </tr>
                                <tr v-if="paginatedLignes.length === 0">
                                    <td colspan="6" class="text-center text-muted">{{ t('Aucun article trouvé') }}</td>
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
                </div>
            </div>
            
            <!-- Actions -->
            <div class="mt-4 d-flex gap-2 justify-content-center">
                <template v-if="inventaire.statut === 'brouillon'">
                    <button
                        v-if="can('inventaire-validate')"
                        class="btn btn-success"
                        @click="showValidationModal = true"
                    >
                        <i class="fa fa-check me-1"></i>
                        {{ t('Valider inventaire') }}
                    </button>
                    <button
                        v-if="can('inventaire-edit')"
                        class="btn btn-primary"
                        @click="router.visit(route('inventaire.edit', inventaire.id))"
                    >
                        <i class="fa fa-edit me-1"></i>
                        <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                    </button>
                    <button
                        v-if="can('inventaire-validate')"
                        class="btn btn-danger"
                        @click="showCancelModal = true"
                    >
                        <i class="fa fa-ban me-1"></i>
                        {{ t('Annuler') }}
                    </button>
                </template>
                <button class="btn btn-secondary" @click="goBack">
                    <i class="fa fa-arrow-left me-1"></i>
                    <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                </button>
            </div>
        </div>
    </div>
    <!-- Modal de validation -->
    <Teleport to="body">
        <div v-if="showValidationModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ t('Valider inventaire') }}</h5>
                        <button type="button" class="btn-close" @click="showValidationModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <p>{{ t('Êtes-vous sûr de vouloir valider cet inventaire ? Cette action ajustera les stocks en conséquence.') }}</p>
                        <div class="mb-3">
                            <label class="form-label">{{ t('Commentaire (optionnel)') }}</label>
                            <textarea
                                v-model="commentaireValidation"
                                class="form-control"
                                rows="3"
                                maxlength="1000"
                            ></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showValidationModal = false">
                            {{ t('common.cancel') }}
                        </button>
                        <button type="button" class="btn btn-success" @click="validerInventaire">
                            <i class="fa fa-check me-1"></i>
                            {{ t('Confirmer') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
    <!-- Modal d'annulation -->
    <Teleport to="body">
        <div v-if="showCancelModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ t('Annuler inventaire') }}</h5>
                        <button type="button" class="btn-close" @click="showCancelModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ t('Raison de l\'annulation') }} <span class="text-danger">*</span></label>
                            <textarea
                                v-model="raisonAnnulation"
                                class="form-control"
                                rows="3"
                                maxlength="1000"
                                required
                            ></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showCancelModal = false">
                            {{ t('common.cancel') }}
                        </button>
                        <button type="button" class="btn btn-danger" @click="annulerInventaire">
                            <i class="fa fa-ban me-1"></i>
                            {{ t('Confirmer annulation') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
    <!-- Loader pleine page -->
    <FullPageLoader
        :show="isLoading"
        :message="loaderMessage"
        :sub-message="loaderSubMessage"
        :variant="loaderVariant"
    />
</template>
<style scoped>
.modal.show {
    display: block;
}
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
