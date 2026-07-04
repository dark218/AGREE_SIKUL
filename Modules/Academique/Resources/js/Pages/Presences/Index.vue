<script setup>
import { ref, watch } from 'vue';
import { usePage, Link, router, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '@/Composables/usePermissions';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import FilterBar from '@/Components/Common/FilterBar.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { can } = usePermissions();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showUpdateLoader, hideLoader } = useLoader();

const props = defineProps({
    title: String,
    presences: Object,
    filters: Object,
});

const deleteMode = ref(false);
const statusMode = ref(false);
const showDeleteModal = ref(false);
const itemToDelete = ref(null);

// Filtres de recherche
const searchFilters = ref({
    search: props.filters?.search || '',
    statut: props.filters?.statut || '',
});

const statusOptions = [
    { id: 'present', libelle: 'Présent' },
    { id: 'retard', libelle: 'En retard' },
    { id: 'absent', libelle: 'Absent' },
    { id: 'malade', libelle: 'Malade' },
    { id: 'permis', libelle: 'Permis' },
];

const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Apprenant', icon: 'fa-search', width: '220px' },
    { key: 'statut', type: 'select', placeholder: 'Statut', options: statusOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
];

// Debounce timer
let searchTimeout;

const search = () => {
    router.get(route('academique.presences.index'), searchFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    const emptyFilters = {};
    Object.keys(searchFilters.value).forEach(key => {
        emptyFilters[key] = '';
    });
    searchFilters.value = emptyFilters;
    router.get(route('academique.presences.index'));
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    statusMode.value = false;
    showDeleteModal.value = true;
};

const confirmToggleStatus = (item) => {
    itemToDelete.value = item;
    deleteMode.value = false;
    statusMode.value = true;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        loaderMessage.value = t('messages.deleting') || 'Suppression...';
        router.visit(route('academique.presences.destroy', itemToDelete.value.id), {
            method: 'delete',
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                itemToDelete.value = null;
            },
            onFinish: () => hideLoader(),
        });
    }
};

const toggleStatus = () => {
    if (itemToDelete.value) {
        showUpdateLoader();
        loaderMessage.value = t('messages.saving') || 'Mise à jour...';
        router.put(route('academique.presences.statut', itemToDelete.value.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                itemToDelete.value = null;
                statusMode.value = false;
            },
            onFinish: () => hideLoader(),
        });
    }
};

const closeModal = () => {
    showDeleteModal.value = false;
    itemToDelete.value = null;
    deleteMode.value = false;
    statusMode.value = false;
};

const getStatusBadge = (statut) => {
    const colors = {
        'present': 'success',
        'retard': 'warning',
        'absent': 'danger',
        'malade': 'info',
        'permis': 'secondary',
    };
    return colors[statut] || 'light';
};

const getStatusLabel = (statut) => {
    const labels = {
        'present': t('common.present') || 'Présent',
        'retard': t('common.retard') || 'En retard',
        'absent': t('common.absent') || 'Absent',
        'malade': t('common.malade') || 'Malade',
        'permis': t('common.permis') || 'Permis',
    };
    return labels[statut] || statut;
};

// Real-time search with debounce
watch(
    () => searchFilters.value,
    () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            search();
        }, 500);
    },
    { deep: true }
);
</script>
<template>
    <Head :title="page.props.title" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('presences-create')">
                        <Link :href="route('academique.presences.create')" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Alert Message -->
            <AlertMessage />

            <div class="row m-0">
                <!-- Filtres de recherche -->
                <FilterBar v-model="searchFilters" :fields="filterFields" @search="search" @reset="resetFilters"></FilterBar>

                <!-- Tableau -->
                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('fields.apprenant') || 'Apprenant' }}</th>
                                        <th>{{ t('common.seance') || 'Séance' }}</th>
                                        <th>{{ t('common.date') || 'Date' }}</th>
                                        <th>{{ t('fields.heure_arrivee') || 'Heure d\'arrivée' }}</th>
                                        <th>{{ t('fields.status') || 'Statut' }}</th>
                                        <th>{{ t('fields.remarques') || 'Remarques' }}</th>
                                        <th>{{ t('fields.saisi_par') || 'Enregistré par' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="presences?.data && presences?.data.length > 0">
                                        <tr v-for="presence in presences?.data" :key="presence.id">
                                            <td>
                                                <strong>{{ presence.apprenant?.user?.prenoms }} {{ presence.apprenant?.user?.nom }}</strong>
                                                <br>
                                                <small class="text-muted">{{ presence.apprenant?.matricule || '-' }}</small>
                                            </td>
                                            <td>
                                                {{ presence.seance?.cours?.titre || 'N/A' }}
                                            </td>
                                            <td>
                                                <small>{{ presence.seance?.date ? new Date(presence.seance.date).toLocaleDateString('fr-FR') : '-' }}</small>
                                            </td>
                                            <td>
                                                <small>{{ presence.heure_arrivee ? presence.heure_arrivee.substring(0, 5) : '-' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge" :class="`bg-${getStatusBadge(presence.statut)}`">
                                                    {{ getStatusLabel(presence.statut) }}
                                                </span>
                                            </td>
                                            <td>
                                                <small v-if="presence.remarques" :title="presence.remarques">
                                                    {{ presence.remarques.substring(0, 30) }}{{ presence.remarques.length > 30 ? '...' : '' }}
                                                </small>
                                                <small v-else class="text-muted">-</small>
                                            </td>
                                            <td>
                                                <small>{{ presence.saisit_par?.prenoms }} {{ presence.saisit_par?.nom || '-' }}</small>
                                            </td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('academique.presences.show', presence.id)" class="btn btn-secondary btn-sm" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('academique.presences.edit', presence.id)" class="btn btn-primary btn-sm" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="confirmDelete(presence)" class="btn btn-danger btn-sm" :title="t('actions.delete')">
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                    <button @click="confirmToggleStatus(presence)" class="btn btn-sm" :class="presence.deleted_at ? 'btn-success' : 'btn-danger'" :title="presence.deleted_at ? t('actions.activate') : t('actions.deactivate')">
                                                        <span :class="presence.deleted_at ? 'fa fa-check' : 'fa fa-ban'"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="8" class="text-center">{{ t('common.emptyList') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card View -->
                        <div class="mobile-card-list">
                            <template v-if="presences?.data && presences?.data.length > 0">
                                <div v-for="presence in presences?.data" :key="'m-' + presence.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.apprenant') || 'Apprenant' }}</span>
                                            <span class="mobile-card-value"><strong>{{ presence.apprenant?.user?.prenoms }} {{ presence.apprenant?.user?.nom }}</strong></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.matricule') || 'Matricule' }}</span>
                                            <span class="mobile-card-value">{{ presence.apprenant?.matricule || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.seance') || 'Séance' }}</span>
                                            <span class="mobile-card-value">{{ presence.seance?.cours?.titre || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.date') || 'Date' }}</span>
                                            <span class="mobile-card-value">{{ presence.seance?.date ? new Date(presence.seance.date).toLocaleDateString('fr-FR') : '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.heure_arrivee') || 'Heure d\'arrivée' }}</span>
                                            <span class="mobile-card-value">{{ presence.heure_arrivee ? presence.heure_arrivee.substring(0, 5) : '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.status') || 'Statut' }}</span>
                                            <span class="mobile-card-value"><span class="badge" :class="`bg-${getStatusBadge(presence.statut)}`">{{ getStatusLabel(presence.statut) }}</span></span>
                                        </div>
                                        <div v-if="presence.remarques" class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.remarques') || 'Remarques' }}</span>
                                            <span class="mobile-card-value">{{ presence.remarques }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.saisi_par') || 'Enregistré par' }}</span>
                                            <span class="mobile-card-value">{{ presence.saisit_par?.prenoms }} {{ presence.saisit_par?.nom || '-' }}</span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('academique.presences.show', presence.id)" class="btn btn-secondary btn-sm"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('academique.presences.edit', presence.id)" class="btn btn-primary btn-sm"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(presence)" class="btn btn-danger btn-sm"><span class="fa fa-trash"></span></button>
                                        <button @click="confirmToggleStatus(presence)" class="btn btn-sm" :class="presence.deleted_at ? 'btn-success' : 'btn-danger'"><span :class="presence.deleted_at ? 'fa fa-check' : 'fa fa-ban'"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <Pagination :data="presences" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmation -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="deleteMode ? t('messages.confirm.delete.title') : t('messages.confirm.deactivate.title')"
            :message="deleteMode ? t('messages.confirm.delete.message') : t('messages.confirm.deactivate.message')"
            :sub-message="deleteMode ? t('messages.confirm.delete.warning') : ''"
            @close="closeModal"
            @confirm="deleteMode ? deleteItem() : toggleStatus()"
            :confirm-text="deleteMode ? t('actions.delete') : (itemToDelete?.deleted_at ? t('actions.activate') : t('actions.deactivate'))"
            :confirm-class="deleteMode ? 'btn-danger' : (itemToDelete?.deleted_at ? 'btn-success' : 'btn-danger')"
        />

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
.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.btn-group-sm .btn {
    padding: 0.375rem 0.5rem;
    font-size: 0.85rem;
}

.card {
    border: 1px solid #dee2e6;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.badge {
    font-weight: 600;
    padding: 0.35em 0.65em;
}
</style>
