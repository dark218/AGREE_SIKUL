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
import FilterBar from '@/Components/Common/FilterBar.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { can } = usePermissions();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showDeactivateLoader, hideLoader } = useLoader();
const page = usePage();
const props = defineProps({
    absencesApprenants: Object,
    filters: Object,
});
const deleteMode = ref(false);
const deactivateMode = ref(false);
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
// Filtres de recherche
const searchFilters = ref({
    search: props.filters?.search || '',
});
const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Apprenant', icon: 'fa-search', width: '220px' },
];
// Debounce timer for real-time search
let searchTimeout;
const search = () => {
    router.get(route('academique.absences_apprenants.index'), searchFilters.value, {
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
    router.get(route('academique.absences_apprenants.index'));
};
const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteMode.value = true;
    deactivateMode.value = false;
    showDeleteModal.value = true;
};
const confirmDeactivate = (item) => {
    itemToDelete.value = item;
    deactivateMode.value = true;
    deleteMode.value = false;
    showDeleteModal.value = true;
};
const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('academique.absences_apprenants.destroy', itemToDelete.value.id), {
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
        if (deactivateMode.value) {
            showDeactivateLoader();
        }
        router.visit(route('academique.absences_apprenants.statut', itemToDelete.value.id), {
            method: 'put',
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                itemToDelete.value = null;
                deleteMode.value = false;
                deactivateMode.value = false;
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
};

// Format date for display (2026-04-04T16:00:29.000000Z => 04/04/2026 16:00)
const formatDate = (date) => {
    if (!date) return '-';
    try {
        const d = new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        // Format: DD/MM/YYYY HH:mm for better readability (French format with time)
        return `${day}/${month}/${year} ${hours}:${minutes}`;
    } catch {
        return date;
    }
};

// Watch filters for real-time search
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
                    <div class="dashboard-btn" v-if="can('absences_apprenants-create')">
                        <Link :href="route('academique.absences_apprenants.create')" class="btn btn-primary">
                            {{ t('actions.add') || 'Ajouter' }}
                        </Link>
                    </div>
                </div>
            </div>
            <!-- Alert Message -->
            <AlertMessage />
            <div class="row m-0">
                <!-- Filtres de recherche -->
                <FilterBar v-model="searchFilters" :fields="filterFields" @search="search" @reset="resetFilters">
                    <template #actions>
                        <a :href="route('academique.pdf.absences-apprenants')" class="fb-btn-pdf" target="_blank" title="PDF"><i class="fa fa-file-pdf"></i> PDF</a>
                    </template>
                </FilterBar>
                <!-- Tableau -->
                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('fields.apprenants') || 'Apprenant' }}</th>
                                        <th>{{ t('fields.date_debut') || 'Date de début' }}</th>
                                        <th>{{ t('fields.date_fin') || 'Date de fin' }}</th>
                                        <th>{{ t('fields.nombre_heures') || 'Heures' }}</th>
                                        <th>{{ t('fields.motif') || 'Motif' }}</th>
                                        <th>{{ t('fields.statut') || 'Statut' }}</th>
                                        <th class="fit">{{ t('common.actions') || 'Actions' }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="absencesApprenants?.data && absencesApprenants?.data.length > 0">
                                        <tr v-for="absence in absencesApprenants?.data" :key="absence.id">
                                            <td>{{ (absence.apprenant?.nom || absence.apprenant?.user?.nom || 'N/A') }} {{ (absence.apprenant?.prenoms || absence.apprenant?.user?.prenoms || '') }}</td>
                                            <td style="font-size: 13px; font-family: monospace; white-space: nowrap; letter-spacing: 0.5px;">
                                                <span style="font-weight: 600; color: #495057;">{{ formatDate(absence.date_debut).split(' ')[0] }}</span>
                                                <span style="color: #0078d4; font-weight: 700; margin-left: 6px;">{{ formatDate(absence.date_debut).split(' ')[1] }}</span>
                                            </td>
                                            <td style="font-size: 13px; font-family: monospace; white-space: nowrap; letter-spacing: 0.5px;">
                                                <span style="font-weight: 600; color: #495057;">{{ formatDate(absence.date_fin).split(' ')[0] }}</span>
                                                <span style="color: #0078d4; font-weight: 700; margin-left: 6px;">{{ formatDate(absence.date_fin).split(' ')[1] }}</span>
                                            </td>
                                            <td style="text-align: center; font-weight: 600;">{{ absence.nombre_heures || '-' }}</td>
                                            <td>{{ absence.motif || '-' }}</td>
                                            <td>
                                                <span class="badge" :class="{
                                                    'bg-danger': absence.statut === 'non_justifiee',
                                                    'bg-success': absence.statut === 'justifiee',
                                                    'bg-warning': absence.statut === 'en_attente'
                                                }">
                                                    {{ absence.statut === 'non_justifiee' ? 'Non justifiée' : (absence.statut === 'justifiee' ? 'Justifiée' : 'En attente') }}
                                                </span>
                                            </td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('academique.absences_apprenants.show', absence.id)" class="btn btn-secondary" :title="t('actions.view') || 'Voir'">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('academique.absences_apprenants.edit', absence.id)" class="btn btn-primary" :title="t('actions.edit') || 'Modifier'">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="confirmDeactivate(absence)" class="btn btn-warning" :title="t('actions.deactivate') || 'Désactiver'">
                                                        <span class="fa fa-ban"></span>
                                                    </button>
                                                    <button @click="confirmDelete(absence)" class="btn btn-danger" :title="t('actions.delete') || 'Supprimer'">
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="6" class="text-center">{{ t('common.emptyList') || 'Aucune donnée' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Mobile Card View -->
                        <div class="mobile-card-list">
                            <template v-if="absencesApprenants?.data && absencesApprenants?.data.length > 0">
                                <div v-for="absence in absencesApprenants?.data" :key="'m-' + absence.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.apprenants') || 'Apprenant' }}</span>
                                            <span class="mobile-card-value">{{ (absence.apprenant?.nom || absence.apprenant?.user?.nom || 'N/A') }} {{ (absence.apprenant?.prenoms || absence.apprenant?.user?.prenoms || '') }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.date_debut') || 'Date de début' }}</span>
                                            <span class="mobile-card-value">
                                                <span style="font-weight: 600; color: #495057;">{{ formatDate(absence.date_debut).split(' ')[0] }}</span>
                                                <span style="color: #0078d4; font-weight: 700; margin-left: 4px;">{{ formatDate(absence.date_debut).split(' ')[1] }}</span>
                                            </span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.date_fin') || 'Date de fin' }}</span>
                                            <span class="mobile-card-value">
                                                <span style="font-weight: 600; color: #495057;">{{ formatDate(absence.date_fin).split(' ')[0] }}</span>
                                                <span style="color: #0078d4; font-weight: 700; margin-left: 4px;">{{ formatDate(absence.date_fin).split(' ')[1] }}</span>
                                            </span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.nombre_heures') || 'Heures' }}</span>
                                            <span class="mobile-card-value">{{ absence.nombre_heures }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.motif') || 'Motif' }}</span>
                                            <span class="mobile-card-value">{{ absence.motif || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.statut') || 'Statut' }}</span>
                                            <span class="mobile-card-value"><span class="badge" :class="{
                                                'bg-danger': absence.statut === 'non_justifiee',
                                                'bg-success': absence.statut === 'justifiee',
                                                'bg-warning': absence.statut === 'en_attente'
                                            }">
                                                {{ absence.statut === 'non_justifiee' ? 'Non justifiée' : (absence.statut === 'justifiee' ? 'Justifiée' : 'En attente') }}
                                            </span></span>
                                        </div>
                                        <div class="mobile-card-actions">
                                            <Link :href="route('academique.absences_apprenants.show', absence.id)" class="btn btn-secondary btn-sm" :title="t('actions.view') || 'Voir'">
                                                <span class="fa fa-eye"></span>
                                            </Link>
                                            <Link :href="route('academique.absences_apprenants.edit', absence.id)" class="btn btn-primary btn-sm" :title="t('actions.edit') || 'Modifier'">
                                                <span class="fa fa-edit"></span>
                                            </Link>
                                            <button @click="confirmDeactivate(absence)" class="btn btn-warning btn-sm" :title="t('actions.deactivate') || 'Désactiver'">
                                                <span class="fa fa-ban"></span>
                                            </button>
                                            <button @click="confirmDelete(absence)" class="btn btn-danger btn-sm" :title="t('actions.delete') || 'Supprimer'">
                                                <span class="fa fa-trash"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Pagination -->
            <Pagination :data="absencesApprenants" :preserve-scroll="true" />
        </div>
        <!-- Modal de confirmation -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="deleteMode ? (t('messages.confirm.delete.title') || 'Confirmer la suppression') : (t('messages.confirm.deactivate.title') || 'Confirmer la désactivation')"
            :message="deleteMode ? (t('messages.confirm.delete.message') || 'Êtes-vous sûr de vouloir supprimer?') : (t('messages.confirm.deactivate.message') || 'Êtes-vous sûr de vouloir désactiver?')"
            :sub-message="deleteMode ? (t('messages.confirm.delete.warning') || 'Cette action est irréversible.') : ''"
            @close="closeModal"
            @confirm="deleteMode ? deleteItem() : toggleStatus()"
            :confirm-text="deleteMode ? (t('actions.delete') || 'Supprimer') : (t('actions.deactivate') || 'Désactiver')"
            :confirm-class="deleteMode ? 'btn-danger' : 'btn-warning'"
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
