<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '@/Composables/usePermissions';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import FilterBar from '@/Components/Common/FilterBar.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { can } = usePermissions();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, hideLoader } = useLoader();

const props = defineProps({
    financings: Object,
    filters: Object,
});

const searchFilters = ref({
    etat: props.filters?.etat || '',
    exam_id: props.filters?.exam_id || '',
    recette_id: props.filters?.recette_id || '',
    ecole_id: props.filters?.ecole_id || '',
});

const etatOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'en-attente', libelle: 'En attente' },
    { id: 'clôturé', libelle: 'Clôturé' },
];

const filterFields = [
    { key: 'etat', type: 'select', placeholder: 'État...', options: etatOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
];

let searchTimeout;

const search = () => {
    router.get(route('academique.exam-finance.index'), searchFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    Object.keys(searchFilters.value).forEach(key => { searchFilters.value[key] = ''; });
    router.get(route('academique.exam-finance.index'));
};

watch(() => searchFilters.value, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => search(), 500);
}, { deep: true });

const etatBadgeClass = (etat) => {
    switch (etat) {
        case 'actif': return 'bg-success';
        case 'en-attente': return 'bg-warning text-dark';
        case 'clôturé': return 'bg-secondary';
        default: return 'bg-secondary';
    }
};

const etatLabel = (etat) => {
    switch (etat) {
        case 'actif': return 'Actif';
        case 'en-attente': return 'En attente';
        case 'clôturé': return 'Clôturé';
        default: return etat;
    }
};

// Delete
const showDeleteModal = ref(false);
const itemToDelete = ref(null);

const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('academique.exam-finance.destroy', itemToDelete.value.id), {
            method: 'delete',
            preserveScroll: true,
            onSuccess: () => { showDeleteModal.value = false; itemToDelete.value = null; },
            onFinish: () => hideLoader(),
        });
    }
};
</script>

<template>
    <Head :title="page.props.title || 'Financements Examens'" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="result-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1"><i class="fa fa-coins me-2"></i> Financements d'Examens</h4>
                        <small class="opacity-75">Gérez les associations entre examens et postes de recette</small>
                    </div>
                    <div class="dashboard-btn" v-if="can('exam-finance-create')">
                        <Link :href="route('academique.exam-finance.create')" class="btn btn-light">
                            <i class="fa fa-plus me-1"></i> Nouvelle Association
                        </Link>
                    </div>
                </div>
            </div>

            <AlertMessage />

            <!-- Filtres -->
            <div class="row m-0 mt-3">
                <FilterBar v-model="searchFilters" :fields="filterFields" @search="search" @reset="resetFilters"></FilterBar>

                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Classe</th>
                                        <th>Matière</th>
                                        <th>Date Examen</th>
                                        <th>Poste Recette</th>
                                        <th class="text-center">Montant</th>
                                        <th class="text-center">État</th>
                                        <th class="text-center">Jours Restants</th>
                                        <th class="fit">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="financings?.data && financings.data.length > 0">
                                        <tr v-for="f in financings.data" :key="f.id" :class="{ 'table-warning-light': f.est_expirée }">
                                            <td class="fw-bold">{{ f.exam?.classe || '-' }}</td>
                                            <td>{{ f.exam?.matiere || '-' }}</td>
                                            <td>{{ f.exam?.date || '-' }}</td>
                                            <td>
                                                <small class="text-muted">{{ f.poste?.code }}</small>
                                                {{ f.poste?.libelle }}
                                            </td>
                                            <td class="text-center">
                                                <strong v-if="f.montant_finance">{{ Number(f.montant_finance).toLocaleString('fr-FR') }} FCFA</strong>
                                                <span v-else class="text-muted">-</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge" :class="etatBadgeClass(f.etat_financement)">
                                                    {{ etatLabel(f.etat_financement) }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span v-if="f.est_expirée" class="text-danger fw-bold">
                                                    <i class="fa fa-exclamation-triangle me-1"></i> Expiré
                                                </span>
                                                <span v-else-if="f.jours_restants !== null && f.jours_restants !== undefined">
                                                    <span :class="f.jours_restants <= 3 ? 'text-warning fw-bold' : ''">
                                                        {{ f.jours_restants }} j
                                                    </span>
                                                </span>
                                                <span v-else class="text-muted">-</span>
                                            </td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('academique.exam-finance.show', f.id)" class="btn btn-secondary" title="Voir">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('academique.exam-finance.edit', f.id)" class="btn btn-primary" title="Modifier">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="confirmDelete(f)" class="btn btn-danger" title="Supprimer">
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <i class="fa fa-inbox fa-2x mb-2 d-block"></i>
                                            Aucun financement trouvé.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile cards -->
                        <div class="mobile-card-list">
                            <template v-if="financings?.data && financings.data.length > 0">
                                <div v-for="f in financings.data" :key="'m-' + f.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Classe</span>
                                            <span class="mobile-card-value fw-bold">{{ f.exam?.classe || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Matière</span>
                                            <span class="mobile-card-value">{{ f.exam?.matiere || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Poste</span>
                                            <span class="mobile-card-value">{{ f.poste?.libelle }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Montant</span>
                                            <span class="mobile-card-value fw-bold">{{ f.montant_finance ? Number(f.montant_finance).toLocaleString('fr-FR') + ' FCFA' : '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">État</span>
                                            <span class="badge" :class="etatBadgeClass(f.etat_financement)">{{ etatLabel(f.etat_financement) }}</span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('academique.exam-finance.show', f.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('academique.exam-finance.edit', f.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(f)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>Aucun financement trouvé.</p>
                            </div>
                        </div>

                        <Pagination :data="financings" />
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="showDeleteModal"
            :title="t('messages.confirm.delete.title')"
            :message="t('messages.confirm.delete.message')"
            :sub-message="t('messages.confirm.delete.warning')"
            @close="showDeleteModal = false; itemToDelete = null;"
            @confirm="deleteItem"
            :confirm-text="t('actions.delete')"
            confirm-class="btn-danger"
        />
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>

<style scoped>
.result-header {
    background: linear-gradient(135deg, #0B5697, #0a4a82);
    border-radius: 12px;
    padding: 1.2rem 1.5rem;
    color: white;
}

.table-warning-light { background-color: #fff8e1 !important; }
</style>
