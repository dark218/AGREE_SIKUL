<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '@/Composables/usePermissions';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { can } = usePermissions();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showDeactivateLoader, showActivateLoader, hideLoader } = useLoader();
const props = defineProps({
    title: String,
    anneesScolaires: Object,
    filters: Object,
});
const deleteMode = ref(false);
const deactivateMode = ref(false);
const activateMode = ref(false);
const makeCurrentMode = ref(false);
// Filtres de recherche
const searchFilters = ref({
    libelle: props.filters?.libelle || '',
    statut: props.filters?.statut || '',
    etat: props.filters?.etat || '',
    });
const statutOptions = [
    { id: 'planifiee', libelle: 'Planifiée' },
    { id: 'en_cours', libelle: 'En cours' },
    { id: 'terminee', libelle: 'Terminée' },
];
const etatOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
// Debounce timer for real-time search
let searchTimeout;
// Real-time search with debounce
const performSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        search();
    }, 300);
};
// Modal de suppression
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const search = () => {
    router.get(route('parametrage.annees_scolaires.index'), searchFilters.value, {
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
  router.get(route('parametrage.annees_scolaires.index'));
};
const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};
const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('parametrage.annees_scolaires.destroy', itemToDelete.value?.id), { method: 'delete', preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                itemToDelete.value = null;
            },
            onFinish: () => hideLoader(), });
    }
};
const confirmDeactivate = (item) => {
    itemToDelete.value = item;
    deactivateMode.value = true;
    deleteMode.value = false;
    showDeleteModal.value = true;
};
const confirmActivate = (item) => {
    itemToDelete.value = item;
    activateMode.value = true;
    deleteMode.value = false;
    showDeleteModal.value = true;
};
const confirmMakeCurrent = (item) => {
    itemToDelete.value = item;
    makeCurrentMode.value = true;
    deleteMode.value = false;
    deactivateMode.value = false;
    activateMode.value = false;
    showDeleteModal.value = true;
};
const toggleStatus = () => {
    if (itemToDelete.value) {
        if (makeCurrentMode.value) {
            showActivateLoader();
            router.visit(route('parametrage.annees_scolaires.current', itemToDelete.value?.id), { method: 'put',
                preserveScroll: true,
                onSuccess: () => {
                    showDeleteModal.value = false;
                    itemToDelete.value = null;
                    makeCurrentMode.value = false;
                },
                onFinish: () => hideLoader(),
            });
        } else {
            if (deactivateMode.value) {
                showDeactivateLoader();
            } else if (activateMode.value) {
                showActivateLoader();
            } else {
                showDeleteLoader();
            }
            router.visit(route('parametrage.annees_scolaires.statut', itemToDelete.value?.id), { method: 'put',
                preserveScroll: true,
                onSuccess: () => {
                    showDeleteModal.value = false;
                    itemToDelete.value = null;
                    deactivateMode.value = false;
                    activateMode.value = false;
                },
                onFinish: () => hideLoader(),
            });
        }
    }
};
const page = usePage();
function getStatutLabel(statut) {
    const labels = {
        actif: 'Actif',
        inactif: 'Inactif',
        en_cours: 'En cours',
        terminee: 'Terminée',
    };
    return labels[statut] || statut;
}
function getStatutBadgeClass(statut) {
    const classes = {
        actif: 'success',
        inactif: 'secondary',
        en_cours: 'primary',
        terminee: 'warning',
    };
    return classes[statut] || 'secondary';
}
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
                    <div class="dashboard-btn" v-if="can('annees_scolaires-create')">
                        <Link :href="route('parametrage.annees_scolaires.create')" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>
            <!-- Alert Message -->
            <AlertMessage />
            <div class="row m-0">
                <!-- Filtres de recherche -->
                <form @submit.prevent="search" style="display: flex; gap: 8px; align-items: flex-start; flex-wrap: wrap;">
                    <div style="width: 150px;">
                        <input
                            type="text"
                            v-model="searchFilters.libelle"
                            class="form-control form-control-sm"
                            :placeholder="t('fields.label') || 'Libellé'"
                            style="height: 32px; font-size: 13px; width: 100%;"
                        />
                    </div>
                    <div style="width: 150px;">
                        <SearchableSelect
                            v-model="searchFilters.statut"
                            :options="statutOptions"
                            optionValue="id"
                            optionLabel="libelle"
                            :placeholder="t('fields.status') || 'Statut'"
                            class="form-control-sm"
                            style="height: 32px; width: 100%;"
                        />
                    </div>
                    <div style="width: 150px;">
                        <SearchableSelect
                            v-model="searchFilters.etat"
                            :options="etatOptions"
                            optionValue="id"
                            optionLabel="libelle"
                            :placeholder="t('fields.state') || 'Etat'"
                            class="form-control-sm"
                            style="height: 32px; width: 100%;"
                        />
                    </div>
                    <div style="display: flex; gap: 4px;">
                        <button type="submit" class="btn btn-primary btn-sm" style="height: 32px; padding: 0 10px;">
                            <i class="fa fa-search"></i>
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" @click="resetFilters" style="height: 32px; padding: 0 10px;">
                            <i class="fa fa-redo"></i>
                        </button>
                    </div>
                </form>
                <!-- Tableau -->
                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>{{ t('common.nom') }}</th>
                                        <th>Dates</th>
                                        <th>Durée</th>
                                        <th>Pays</th>
                                        <th>État</th>
                                        <th>Courant</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="anneesScolaires?.data && anneesScolaires?.data.length > 0">
                                        <tr v-for="annee in anneesScolaires?.data" :key="annee.id">
                                            <td><small>{{ annee.code || '-' }}</small></td>
                                            <td>{{ annee.libelle || '-' }}</td>
                                            <td>
                                                <small v-if="annee.date_debut">
                                                    {{ new Date(annee.date_debut).toLocaleDateString('fr-FR') }} - {{ new Date(annee.date_fin).toLocaleDateString('fr-FR') }}
                                                </small>
                                                <small v-else>-</small>
                                            </td>
                                            <td><small>{{ annee.duree || '-' }} mois</small></td>
                                            <td><small>{{ annee.pays?.libelle || '-' }}</small></td>
                                            <td><span class="badge" :class="'bg-' + (annee.etat === 'actif' ? 'success' : 'secondary')">{{ annee.etat === 'actif' ? 'Actif' : 'Inactif' }}</span></td>
                                            <td><span v-if="annee.est_courante" class="badge bg-warning">Oui</span><span v-else>-</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('parametrage.annees_scolaires.show', annee?.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('parametrage.annees_scolaires.edit', annee?.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="confirmDelete(annee)" class="btn btn-danger" :title="t('actions.delete')">
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                    <button v-if="annee.etat === 'actif'" @click="confirmDeactivate(annee)" class="btn btn-danger" :title="t('actions.deactivate')">
                                                        <span class="fa fa-ban"></span>
                                                    </button>
                                                    <button v-else @click="confirmActivate(annee)" class="btn btn-success" :title="t('actions.activate')">
                                                        <span class="fa fa-check"></span>
                                                    </button>
                                                    <button v-if="!annee.est_courante" @click="confirmMakeCurrent(annee)" class="btn btn-warning" :title="'Définir comme courante'">
                                                        <span class="fa fa-star"></span>
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
                            <template v-if="anneesScolaires?.data && anneesScolaires?.data.length > 0">
                                <div v-for="annee in anneesScolaires?.data" :key="'m-' + annee.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Code</span>
                                            <span class="mobile-card-value"><small>{{ annee.code || '-' }}</small></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.nom') }}</span>
                                            <span class="mobile-card-value">{{ annee.libelle || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Dates</span>
                                            <span class="mobile-card-value">
                                                <small v-if="annee.date_debut">
                                                    {{ new Date(annee.date_debut).toLocaleDateString('fr-FR') }} - {{ new Date(annee.date_fin).toLocaleDateString('fr-FR') }}
                                                </small>
                                                <small v-else>-</small>
                                            </span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Durée</span>
                                            <span class="mobile-card-value"><small>{{ annee.duree || '-' }} mois</small></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Pays</span>
                                            <span class="mobile-card-value"><small>{{ annee.pays?.libelle || '-' }}</small></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">État</span>
                                            <span class="mobile-card-value"><span class="badge" :class="'bg-' + (annee.etat === 'actif' ? 'success' : 'secondary')">{{ annee.etat === 'actif' ? 'Actif' : 'Inactif' }}</span></span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">Courant</span>
                                            <span class="mobile-card-value"><span v-if="annee.est_courante" class="badge bg-warning">Oui</span><span v-else>-</span></span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('parametrage.annees_scolaires.show', annee?.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('parametrage.annees_scolaires.edit', annee?.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(annee)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                        <button v-if="annee.etat === 'actif'" @click="confirmDeactivate(annee)" class="btn btn-danger"><span class="fa fa-ban"></span></button>
                                        <button v-else @click="confirmActivate(annee)" class="btn btn-success"><span class="fa fa-check"></span></button>
                                        <button v-if="!annee.est_courante" @click="confirmMakeCurrent(annee)" class="btn btn-warning"><span class="fa fa-star"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>
                        <!-- Pagination -->
                        <Pagination :data="anneesScolaires" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal de confirmation -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="deleteMode ? t('messages.confirm.delete.title') : (deactivateMode ? 'Désactiver' : (activateMode ? 'Activer' : 'Définir comme courante'))"
            :message="deleteMode ? 'Êtes-vous sûr de vouloir supprimer cet élément?' : (deactivateMode ? 'Êtes-vous sûr de vouloir désactiver cet élément?' : (activateMode ? 'Êtes-vous sûr de vouloir activer cet élément?' : 'Cette année scolaire sera définie comme année courante. L\'année courante précédente sera remplacée.'))"
            :sub-message="deleteMode ? t('messages.confirm.delete.warning') : ''"
            @confirm="deleteMode ? deleteItem() : toggleStatus()"
            :confirm-text="deleteMode ? 'Supprimer' : (deactivateMode ? 'Désactiver' : (activateMode ? 'Activer' : 'Définir comme courante'))"
            :confirm-class="deleteMode ? 'btn-danger' : (deactivateMode ? 'btn-danger' : (activateMode ? 'btn-success' : 'btn-warning'))"
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
