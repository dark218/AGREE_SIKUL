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
    absences: Object,
    classes: Array,
    filters: Object,
});

const deleteMode = ref(false);
const deactivateMode = ref(false);
const activateMode = ref(false);

// Filtres de recherche
const searchFilters = ref({
    search: props.filters?.search || '',
    classe_id: props.filters?.classe_id || '',
    statut: props.filters?.statut || '',
});

const classOptions = (props.classes || []).map(c => ({
    id: c.id,
    libelle: c.nom || c.libelle
}));

const statutOptions = [
    { id: 'en_attente', libelle: t('common.en_attente') || 'En attente' },
    { id: 'justifiee', libelle: t('common.justifiee') || 'Justifiée' },
    { id: 'non_justifiee', libelle: t('common.non_justifiee') || 'Non justifiée' },
    { id: 'partiellement_justifiee', libelle: t('common.partiellement_justifiee') || 'Partiellement justifiée' },
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
    router.get(route('academique.absences.index'), searchFilters.value, {
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
    router.get(route('academique.absences.index'));
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('academique.absences.destroy', itemToDelete.value.id), {
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
        }
        router.visit(route('academique.absences.statut', itemToDelete.value.id), {
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

const page = usePage();
const absences = props.absences || page.props.absences;

const getStatusBadgeClass = (statut) => {
    switch (statut) {
        case 'justifiee': return 'bg-success';
        case 'en_attente': return 'bg-warning';
        case 'non_justifiee': return 'bg-danger';
        case 'partiellement_justifiee': return 'bg-info';
        default: return 'bg-secondary';
    }
};

const getStatusLabel = (statut) => {
    switch (statut) {
        case 'justifiee': return t('common.justifiee') || 'Justifiée';
        case 'en_attente': return t('common.en_attente') || 'En attente';
        case 'non_justifiee': return t('common.non_justifiee') || 'Non justifiée';
        case 'partiellement_justifiee': return t('common.partiellement_justifiee') || 'Partiellement justifiée';
        default: return statut;
    }
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
                    <div class="dashboard-btn" v-if="can('absences-create')">
                        <Link :href="route('academique.absences.create')" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Alert Message -->
            <AlertMessage />

            <div class="row m-0">
                <!-- Filtres de recherche -->
                <form @submit.prevent="search" style="display: flex; gap: 8px; align-items: flex-start; flex-wrap: wrap; width: 100%; margin-bottom: 15px;">
                    <div style="width: 200px;">
                        <input
                            type="text"
                            v-model="searchFilters.search"
                            class="form-control form-control-sm"
                            :placeholder="t('common.search') || 'Rechercher...'"
                            style="height: 32px; font-size: 13px; width: 100%;"
                        />
                    </div>
                    <div style="width: 180px;">
                        <SearchableSelect
                            v-model="searchFilters.classe_id"
                            :options="classOptions"
                            optionValue="id"
                            optionLabel="libelle"
                            :placeholder="t('fields.classe') || 'Classe'"
                            class="form-control-sm"
                            style="height: 32px; width: 100%;"
                        />
                    </div>
                    <div style="width: 180px;">
                        <SearchableSelect
                            v-model="searchFilters.statut"
                            :options="statutOptions"
                            optionValue="id"
                            optionLabel="libelle"
                            :placeholder="t('fields.statut') || 'Statut'"
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
                <div class="card-body" style="width: 100%; padding: 0;">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('fields.matricule') || 'Matricule' }}</th>
                                        <th>{{ t('fields.nom') || 'Nom' }}</th>
                                        <th>{{ t('fields.prenoms') || 'Prénoms' }}</th>
                                        <th>{{ t('fields.classe') || 'Classe' }}</th>
                                        <th>{{ t('fields.date_absence') || 'Date' }}</th>
                                        <th>{{ t('fields.week_number') || 'Semaine' }}</th>
                                        <th>{{ t('fields.day_of_week') || 'Jour' }}</th>
                                        <th>{{ t('fields.nombre_heures') || 'Heures' }}</th>
                                        <th>{{ t('fields.statut') || 'Statut' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="absences?.data && absences?.data.length > 0">
                                        <tr v-for="absence in absences?.data" :key="absence.id">
                                            <td>{{ absence.apprenant?.matricule || '-' }}</td>
                                            <td>{{ absence.apprenant?.nom || '-' }}</td>
                                            <td>{{ absence.apprenant?.prenoms || '-' }}</td>
                                            <td>{{ absence.classe?.nom || absence.classe?.libelle || '-' }}</td>
                                            <td>{{ absence.date_absence ? new Date(absence.date_absence).toLocaleDateString('fr-FR') : '-' }}</td>
                                            <td>{{ absence.week_number ? `Semaine ${absence.week_number}` : '-' }}</td>
                                            <td>{{ absence.day_of_week || '-' }}</td>
                                            <td>{{ absence.nombre_heures ? `${absence.nombre_heures}h` : '-' }}</td>
                                            <td><span class="badge" :class="getStatusBadgeClass(absence.statut)">{{ getStatusLabel(absence.statut) }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('academique.absences.show', absence.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('academique.absences.edit', absence.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="confirmDelete(absence)" class="btn btn-danger" :title="t('actions.delete')">
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                    <button v-if="absence.etat === 'actif'" @click="confirmDeactivate(absence)" class="btn btn-danger" :title="t('actions.deactivate')">
                                                        <span class="fa fa-ban"></span>
                                                    </button>
                                                    <button v-else @click="confirmActivate(absence)" class="btn btn-success" :title="t('actions.activate')">
                                                        <span class="fa fa-check"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="10" class="text-center">{{ t('common.emptyList') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card View -->
                        <div class="mobile-card-list">
                            <template v-if="absences?.data && absences?.data.length > 0">
                                <div v-for="absence in absences?.data" :key="'m-' + absence.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.matricule') || 'Matricule' }}</span>
                                            <span class="mobile-card-value">{{ absence.apprenant?.matricule || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.nom') || 'Nom' }}</span>
                                            <span class="mobile-card-value">{{ absence.apprenant?.nom || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.prenoms') || 'Prénoms' }}</span>
                                            <span class="mobile-card-value">{{ absence.apprenant?.prenoms || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.classe') || 'Classe' }}</span>
                                            <span class="mobile-card-value">{{ absence.classe?.nom || absence.classe?.libelle || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.date_absence') || 'Date' }}</span>
                                            <span class="mobile-card-value">{{ absence.date_absence ? new Date(absence.date_absence).toLocaleDateString('fr-FR') : '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.week_number') || 'Semaine' }}</span>
                                            <span class="mobile-card-value">{{ absence.week_number ? `Semaine ${absence.week_number}` : '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.day_of_week') || 'Jour' }}</span>
                                            <span class="mobile-card-value">{{ absence.day_of_week || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.nombre_heures') || 'Heures' }}</span>
                                            <span class="mobile-card-value">{{ absence.nombre_heures ? `${absence.nombre_heures}h` : '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.statut') || 'Statut' }}</span>
                                            <span class="mobile-card-value"><span class="badge" :class="getStatusBadgeClass(absence.statut)">{{ getStatusLabel(absence.statut) }}</span></span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('academique.absences.show', absence.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('academique.absences.edit', absence.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(absence)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                        <button v-if="absence.etat === 'actif'" @click="confirmDeactivate(absence)" class="btn btn-danger"><span class="fa fa-ban"></span></button>
                                        <button v-else @click="confirmActivate(absence)" class="btn btn-success"><span class="fa fa-check"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <Pagination :data="absences" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmation -->
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

        <!-- Loader pleine page -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
