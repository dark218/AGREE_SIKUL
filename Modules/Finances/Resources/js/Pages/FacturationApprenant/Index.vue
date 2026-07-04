<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import FilterBar from '@/Components/Common/FilterBar.vue';

defineOptions({
    layout: DashboardLayout,
});

const { t } = useI18n();
const { isLoading, loaderMessage, showDeleteLoader, showDeactivateLoader, showActivateLoader, hideLoader } = useLoader();

const can = (permission) => {
    const userPerms = page.props.auth?.permissions || page.props.userPermissions || [];
    return userPerms.some(p => p.name === permission) || false;
};

const props = defineProps({
    facturations: Object,
    anneesScolaires: Array,
    ecoles: Array,
    filters: Object,
});

const searchFilters = ref({
    code: props.filters?.code || '',
    libelle: props.filters?.libelle || '',
    annee_scolaire_id: props.filters?.annee_scolaire_id || '',
    ecole_id: props.filters?.ecole_id || '',
    etat: props.filters?.etat || '',
});

let searchTimeout;

const showConfirmModal = ref(false);
const itemToAction = ref(null);
const deleteMode = ref(false);
const deactivateMode = ref(false);
const activateMode = ref(false);

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const filterFields = computed(() => [
    { key: 'code', type: 'text', placeholder: 'Code', icon: 'fa-search', width: '220px' },
    { key: 'libelle', type: 'text', placeholder: 'Libellé', width: '220px' },
    { key: 'ecole_id', type: 'select', placeholder: 'École', options: props.ecoles, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
    { key: 'annee_scolaire_id', type: 'select', placeholder: 'Année Scolaire', options: props.anneesScolaires, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
    { key: 'etat', type: 'select', placeholder: 'Tous', options: statusOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
]);

function search() {
    router.get(route('finances.facturation-apprenants.index'), searchFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
}

function resetFilters() {
    searchFilters.value = {
        code: '',
        libelle: '',
        annee_scolaire_id: '',
        ecole_id: '',
        etat: '',
    };
    router.get(route('finances.facturation-apprenants.index'));
}

function confirmDelete(item) {
    itemToAction.value = item;
    deleteMode.value = true;
    deactivateMode.value = false;
    activateMode.value = false;
    showConfirmModal.value = true;
}

function confirmDeactivate(item) {
    itemToAction.value = item;
    deactivateMode.value = true;
    deleteMode.value = false;
    activateMode.value = false;
    showConfirmModal.value = true;
}

function confirmActivate(item) {
    itemToAction.value = item;
    activateMode.value = true;
    deleteMode.value = false;
    deactivateMode.value = false;
    showConfirmModal.value = true;
}

function performAction() {
    if (deleteMode.value) {
        showDeleteLoader();
        router.delete(route('finances.facturation-apprenants.destroy', itemToAction.value.id), {
            onSuccess: () => {
                showConfirmModal.value = false;
                deleteMode.value = false;
                hideLoader();
            },
            onError: () => hideLoader(),
        });
    } else if (deactivateMode.value || activateMode.value) {
        if (deactivateMode.value) showDeactivateLoader();
        else showActivateLoader();
        router.put(route('finances.facturation-apprenants.statut', itemToAction.value.id), {}, {
            onSuccess: () => {
                showConfirmModal.value = false;
                deactivateMode.value = false;
                activateMode.value = false;
                hideLoader();
            },
            onError: () => hideLoader(),
        });
    }
}

function getStatutBadgeClass(statut) {
    return statut === 'actif' ? 'bg-success' : 'bg-danger';
}

function getStatutLabel(statut) {
    return t('common.' + statut) || statut;
}

function getEcoleLabel(facturation) {
    return facturation.ecole?.nom || '-';
}

function getAnneeLabel(facturation) {
    return facturation.annee_scolaire?.libelle || '-';
}

function formatCurrency(value) {
    if (!value) return '0.00';
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(value);
}

function closeModal() {
    showConfirmModal.value = false;
    itemToAction.value = null;
    deleteMode.value = false;
    deactivateMode.value = false;
    activateMode.value = false;
}

const page = usePage();

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
    <Head :title="t('common.facturations_apprenants')" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('common.facturations_apprenants') }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('finances-facturation-apprenant-create')">
                        <Link :href="route('finances.facturation-apprenants.create')" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Alert Message -->
            <AlertMessage />

            <div class="row m-0">
                <!-- Filters -->
                <FilterBar v-model="searchFilters" :fields="filterFields" @search="search" @reset="resetFilters"></FilterBar>

                <!-- Table -->
                <div class="card-body" style="width: 100%;">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('fields.code') || 'Code' }}</th>
                                        <th>{{ t('fields.libelle') || 'Libellé' }}</th>
                                        <th>{{ t('fields.ecole') || 'École' }}</th>
                                        <th>{{ t('fields.annee_scolaire') || 'Année scolaire' }}</th>
                                        <th>{{ t('fields.montant') || 'Montant' }}</th>
                                        <th>{{ t('fields.etat') || 'État' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="facturations?.data && facturations?.data.length > 0">
                                        <tr v-for="facturation in facturations?.data" :key="facturation.id">
                                            <td>{{ facturation.code || '-' }}</td>
                                            <td>{{ facturation.libelle || '-' }}</td>
                                            <td>{{ getEcoleLabel(facturation) }}</td>
                                            <td>{{ getAnneeLabel(facturation) }}</td>
                                            <td>{{ formatCurrency(facturation.montant) }}</td>
                                            <td>
                                                <span class="badge" :class="getStatutBadgeClass(facturation.etat)">
                                                    {{ getStatutLabel(facturation.etat) }}
                                                </span>
                                            </td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link v-if="can('finances-facturation-apprenant-list')" :href="route('finances.facturation-apprenants.show', facturation.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link v-if="can('finances-facturation-apprenant-edit')" :href="route('finances.facturation-apprenants.edit', facturation.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button v-if="can('finances-facturation-apprenant-delete')" @click="confirmDelete(facturation)" class="btn btn-danger" :title="t('actions.delete')">
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                    <button v-if="can('finances-facturation-apprenant-activate') && facturation.etat === 'actif'" @click="confirmDeactivate(facturation)" class="btn btn-danger" :title="t('actions.deactivate')">
                                                        <span class="fa fa-ban"></span>
                                                    </button>
                                                    <button v-else-if="can('finances-facturation-apprenant-activate')" @click="confirmActivate(facturation)" class="btn btn-success" :title="t('actions.activate')">
                                                        <span class="fa fa-check"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            {{ t('common.no_records') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <Pagination :data="facturations" />
                </div>
            </div>
        </div>

        <!-- Confirm Modal -->
        <ConfirmModal
            :show="showConfirmModal"
            :title="deleteMode ? t('common.confirm_delete') : (deactivateMode ? t('common.confirm_deactivate') : t('common.confirm_activate'))"
            :message="deleteMode ? t('common.delete_confirmation_message') : (deactivateMode ? t('common.deactivate_confirmation_message') : t('common.activate_confirmation_message'))"
            @close="closeModal"
            @confirm="performAction"
            :confirm-text="deleteMode ? t('actions.delete') : (deactivateMode ? t('actions.deactivate') : t('actions.activate'))"
            :confirm-class="deleteMode ? 'btn-danger' : (deactivateMode ? 'btn-danger' : 'btn-success')"
        />

        <!-- Full Page Loader -->
        <FullPageLoader :show="isLoading" :message="loaderMessage" />
    </div>
</template>
