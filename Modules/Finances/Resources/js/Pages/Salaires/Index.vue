<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

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
    salaires: Object,
    anneesScolaires: Array,
    ecoles: Array,
    filters: Object,
});

const searchFilters = ref({
    nom: props.filters?.nom || '',
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

function search() {
    router.get(route('finances.salaires.index'), searchFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
}

function resetFilters() {
    searchFilters.value = {
        nom: '',
        annee_scolaire_id: '',
        ecole_id: '',
        etat: '',
    };
    router.get(route('finances.salaires.index'));
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
        router.delete(route('finances.salaires.destroy', itemToAction.value.id), {
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
        router.put(route('finances.salaires.statut', itemToAction.value.id), {}, {
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

function getEcoleLabel(salaire) {
    return salaire.ecole?.nom || '-';
}

function getAnneeLabel(salaire) {
    return salaire.annee_scolaire?.libelle || '-';
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
    <Head :title="t('common.salaires')" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('common.salaires') }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('finances-salaire-create')">
                        <Link :href="route('finances.salaires.create')" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Alert Message -->
            <AlertMessage />

            <div class="row m-0">
                <!-- Filters -->
                <form @submit.prevent="search" style="display: flex; gap: 8px; align-items: flex-start; flex-wrap: wrap; width: 100%; margin-bottom: 15px;">
                    <div style="width: 180px;">
                        <input
                            type="text"
                            v-model="searchFilters.nom"
                            class="form-control form-control-sm"
                            :placeholder="t('fields.nom')"
                            style="height: 32px; font-size: 13px;"
                        />
                    </div>
                    <div style="width: 150px;">
                        <SearchableSelect
                            v-model="searchFilters.ecole_id"
                            :options="ecoles"
                            optionValue="id"
                            optionLabel="libelle"
                            :placeholder="t('fields.ecole') || 'École'"
                            style="height: 32px;"
                        />
                    </div>
                    <div style="width: 150px;">
                        <SearchableSelect
                            v-model="searchFilters.annee_scolaire_id"
                            :options="anneesScolaires"
                            optionValue="id"
                            optionLabel="libelle"
                            :placeholder="t('fields.annee_scolaire') || 'Année Scolaire'"
                            style="height: 32px;"
                        />
                    </div>
                    <div style="width: 150px;">
                        <SearchableSelect
                            v-model="searchFilters.etat"
                            :options="[
                                { id: '', libelle: t('actions.all') || 'Tous' },
                                { id: 'actif', libelle: 'Actif' },
                                { id: 'inactif', libelle: 'Inactif' },
                            ]"
                            optionValue="id"
                            optionLabel="libelle"
                            style="height: 32px;"
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

                <!-- Table -->
                <div class="card-body" style="width: 100%;">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('fields.nom') || 'Nom' }}</th>
                                        <th>{{ t('fields.mois_paie') || 'Mois de paie' }}</th>
                                        <th>{{ t('fields.ecole') || 'École' }}</th>
                                        <th>{{ t('fields.annee_scolaire') || 'Année scolaire' }}</th>
                                        <th>{{ t('fields.salaire_net') || 'Salaire net' }}</th>
                                        <th>{{ t('fields.total_paye') || 'Total payé' }}</th>
                                        <th>{{ t('fields.restant_a_payer') || 'Restant à payer' }}</th>
                                        <th>{{ t('fields.etat') || 'État' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="salaires?.data && salaires?.data.length > 0">
                                        <tr v-for="salaire in salaires?.data" :key="salaire.id">
                                            <td>{{ salaire.nom || '-' }}</td>
                                            <td>{{ salaire.mois_paie || '-' }}</td>
                                            <td>{{ getEcoleLabel(salaire) }}</td>
                                            <td>{{ getAnneeLabel(salaire) }}</td>
                                            <td>{{ formatCurrency(salaire.salaire_net) }}</td>
                                            <td>{{ formatCurrency(salaire.total_paye) }}</td>
                                            <td>{{ formatCurrency(salaire.restant_a_payer) }}</td>
                                            <td>
                                                <span class="badge" :class="getStatutBadgeClass(salaire.etat)">
                                                    {{ getStatutLabel(salaire.etat) }}
                                                </span>
                                            </td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link v-if="can('finances-salaire-list')" :href="route('finances.salaires.show', salaire.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link v-if="can('finances-salaire-edit')" :href="route('finances.salaires.edit', salaire.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button v-if="can('finances-salaire-delete')" @click="confirmDelete(salaire)" class="btn btn-danger" :title="t('actions.delete')">
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                    <button v-if="can('finances-salaire-activate') && salaire.etat === 'actif'" @click="confirmDeactivate(salaire)" class="btn btn-danger" :title="t('actions.deactivate')">
                                                        <span class="fa fa-ban"></span>
                                                    </button>
                                                    <button v-else-if="can('finances-salaire-activate')" @click="confirmActivate(salaire)" class="btn btn-success" :title="t('actions.activate')">
                                                        <span class="fa fa-check"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            {{ t('common.no_records') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <Pagination :data="salaires" />
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
