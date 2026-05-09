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
import { useLoader } from '@/composables/useLoader';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { can } = usePermissions();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showDeactivateLoader, showActivateLoader, hideLoader } = useLoader();

const props = defineProps({
    title: String,
    examens: Object,
    matieres: Array,
    filters: Object,
});

const deleteMode = ref(false);
const deactivateMode = ref(false);
const activateMode = ref(false);

const searchFilters = ref({
    titre: props.filters?.titre || '',
    matiere_id: props.filters?.matiere_id || '',
    etat: props.filters?.etat || '',
});

const statusOptions = [
    { id: 'brouillon', libelle: 'Brouillon' },
    { id: 'publie', libelle: 'Publié' },
    { id: 'en_cours', libelle: 'En cours' },
    { id: 'termine', libelle: 'Terminé' },
    { id: 'corrige', libelle: 'Corrigé' },
];

const statutBadgeClass = (etat) => {
    const map = {
        brouillon: 'bg-secondary',
        publie: 'bg-info',
        en_cours: 'bg-warning text-dark',
        termine: 'bg-primary',
        corrige: 'bg-success',
    };
    return map[etat] || 'bg-secondary';
};

const statutLabel = (etat) => {
    const map = {
        brouillon: 'Brouillon',
        publie: 'Publié',
        en_cours: 'En cours',
        termine: 'Terminé',
        corrige: 'Corrigé',
        actif: 'Actif',
        inactif: 'Inactif',
    };
    return map[etat] || etat;
};

let searchTimeout;

const search = () => {
    router.get(route('academique.examens-en-ligne.index'), searchFilters.value, {
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
  router.get(route('academique.examens-en-ligne.index'));
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('academique.examens-en-ligne.destroy', itemToDelete.value?.id), {
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

const toggleStatus = () => {
    if (itemToDelete.value) {
        if (deactivateMode.value) {
            showDeactivateLoader();
        } else if (activateMode.value) {
            showActivateLoader();
        } else {
            showDeleteLoader();
        }
        router.visit(route('academique.examens-en-ligne.statut', itemToDelete.value?.id), {
            method: 'put',
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
};

const closeModal = () => {
    showDeleteModal.value = false;
    itemToDelete.value = null;
    deleteMode.value = false;
    deactivateMode.value = false;
    activateMode.value = false;
};

const showDeleteModal = ref(false);
const itemToDelete = ref(null);

const page = usePage();
const examens = props.examens || page.props.examens;

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
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('examens-en-ligne-create')">
                        <Link :href="route('academique.examens-en-ligne.create')" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>
            <AlertMessage />
            <div class="row m-0">
                <form @submit.prevent="search" style="display: flex; gap: 8px; align-items: flex-start; flex-wrap: wrap;">
                    <div style="width: 200px;">
                        <input
                            type="text"
                            v-model="searchFilters.titre"
                            class="form-control form-control-sm"
                            :placeholder="t('fields.titre')"
                            style="height: 32px; font-size: 13px; width: 100%;"
                        />
                    </div>
                    <div style="width: 150px;">
                        <SearchableSelect
                            v-model="searchFilters.matiere_id"
                            :options="matieres"
                            optionValue="id"
                            optionLabel="libelle"
                            :placeholder="t('common.matiere') || 'Matière'"
                            class="form-control-sm"
                            style="height: 32px; width: 100%;"
                        />
                    </div>
                    <div style="width: 150px;">
                        <SearchableSelect
                            v-model="searchFilters.etat"
                            :options="statusOptions"
                            optionValue="id"
                            optionLabel="libelle"
                            :placeholder="t('fields.status') || 'Statut'"
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
                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('fields.titre') || 'Titre' }}</th>
                                        <th>{{ t('common.matiere') || 'Matière' }}</th>
                                        <th>{{ t('fields.date_debut') || 'Date Début' }}</th>
                                        <th>{{ t('fields.date_fin') || 'Date Fin' }}</th>
                                        <th>Questions</th>
                                        <th>{{ t('fields.status') || 'Statut' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="examens?.data && examens?.data.length > 0">
                                        <tr v-for="item in examens?.data" :key="item.id">
                                            <td>{{ item.titre || '' }}</td>
                                            <td>{{ item.matiere || '' }}</td>
                                            <td>{{ item.date_debut || '' }}</td>
                                            <td>{{ item.date_fin || '' }}</td>
                                            <td><span class="badge bg-dark">{{ item.nombre_questions || 0 }}</span></td>
                                            <td><span class="badge" :class="statutBadgeClass(item.etat)">{{ statutLabel(item.etat) }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('academique.examens-en-ligne.show', item?.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('academique.examens-en-ligne.edit', item?.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <Link :href="route('academique.examens-en-ligne.resultats', item?.id)" class="btn btn-info" title="Résultats & Notes">
                                                        <span class="fa fa-chart-bar"></span>
                                                    </Link>
                                                    <button @click="confirmDelete(item)" class="btn btn-danger" :title="t('actions.delete')">
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                    <button v-if="item.etat === 'actif'" @click="confirmDeactivate(item)" class="btn btn-danger" :title="t('actions.deactivate')">
                                                        <span class="fa fa-ban"></span>
                                                    </button>
                                                    <button v-else @click="confirmActivate(item)" class="btn btn-success" :title="t('actions.activate')">
                                                        <span class="fa fa-check"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="7" class="text-center">{{ t('common.emptyList') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mobile-card-list">
                            <template v-if="examens?.data && examens?.data.length > 0">
                                <div v-for="item in examens?.data" :key="'m-' + item.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.titre') }}</span>
                                            <span class="mobile-card-value">{{ item.titre || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.matiere') }}</span>
                                            <span class="mobile-card-value">{{ item.matiere || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.date_debut') }}</span>
                                            <span class="mobile-card-value">{{ item.date_debut || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.date_fin') }}</span>
                                            <span class="mobile-card-value">{{ item.date_fin || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.status') }}</span>
                                            <span class="mobile-card-value"><span class="badge" :class="statutBadgeClass(item.etat)">{{ statutLabel(item.etat) }}</span></span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('academique.examens-en-ligne.show', item?.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('academique.examens-en-ligne.edit', item?.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(item)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                        <button v-if="item.etat === 'actif'" @click="confirmDeactivate(item)" class="btn btn-danger"><span class="fa fa-ban"></span></button>
                                        <button v-else @click="confirmActivate(item)" class="btn btn-success"><span class="fa fa-check"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>
                        <Pagination :data="examens" />
                    </div>
                </div>
            </div>
        </div>
        <ConfirmModal
            :show="showDeleteModal"
            :title="deleteMode ? t('messages.confirm.delete.title') : (deactivateMode ? t('messages.confirm.deactivate.title') : t('messages.confirm.activate.title'))"
            :message="deleteMode ? t('messages.confirm.delete.message') : (deactivateMode ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message'))"
            :sub-message="deleteMode ? t('messages.confirm.delete.warning') : ''"
            @close="closeModal"
            @confirm="deleteMode ? deleteItem() : toggleStatus()"
            :confirm-text="deleteMode ? t('actions.delete') : (deactivateMode ? t('actions.deactivate') : t('actions.activate'))"
            :confirm-class="deleteMode ? 'btn-danger' : (deactivateMode ? 'btn-danger' : 'btn-success')"
        />
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
