<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, showDeleteLoader, hideLoader } = useLoader();

const props = defineProps({
    inscriptionsCantines: Object,
    filters: Object,
});

const statusOptions = [
    { id: 'active', libelle: 'Actif' },
    { id: 'suspendue', libelle: 'Suspendue' },
    { id: 'terminee', libelle: 'Terminée' },
    { id: 'annulee', libelle: 'Annulée' },
];

const searchFilters = ref({
    search: props.filters?.search || '',
    statut: props.filters?.statut || '',
});

let searchTimeout;

const performSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        search();
    }, 300);
};

const showDeleteModal = ref(false);
const itemToDelete = ref(null);

const search = () => {
    router.get(route('inscriptions-cantine.index'), searchFilters.value, {
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
    router.get(route('inscriptions-cantine.index'));
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.visit(route('inscriptions-cantine.destroy', itemToDelete.value.id), {
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

const closeModal = () => {
    showDeleteModal.value = false;
    itemToDelete.value = null;
};

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
    <Head :title="t('common.inscription-cantine')" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('common.inscription-cantine') || 'Inscriptions Cantine' }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn">
                        <Link :href="route('inscriptions-cantine.create')" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Alert Message -->
            <AlertMessage />

            <div class="row m-0">
                <!-- Filtres de recherche -->
                <form @submit.prevent="search" style="display: flex; gap: 8px; align-items: flex-start; flex-wrap: wrap; width: 100%; margin-bottom: 20px;">
                    <div style="width: 200px;">
                        <input
                            v-model="searchFilters.search"
                            type="text"
                            class="form-control"
                            :placeholder="t('common.search') || 'Rechercher...'"
                            @input="performSearch"
                        />
                    </div>
                    <div style="width: 150px;">
                        <SearchableSelect
                            v-model="searchFilters.statut"
                            :options="statusOptions"
                            optionValue="id"
                            optionLabel="libelle"
                            :placeholder="t('common.statut') || 'État'"
                            class="form-control-sm"
                            style="height: 45px; width: 100%;"
                        />
                    </div>
                    <div style="display: flex; gap: 4px;">
                        <button type="submit" class="btn btn-primary btn-sm" style="height: 45px; padding: 0 10px;">
                            <i class="fa fa-search"></i>
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" @click="resetFilters" style="height: 45px; padding: 0 10px;">
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
                                        <th>{{ t('common.apprenant') || 'Apprenant' }}</th>
                                        <th>{{ t('common.service-cantine') || 'Service Cantine' }}</th>
                                        <th>{{ t('common.statut') || 'État' }}</th>
                                        <th class="fit">{{ t('common.actions') || 'Actions' }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="inscriptionsCantines?.data && inscriptionsCantines?.data.length > 0">
                                        <tr v-for="inscription in inscriptionsCantines?.data" :key="inscription.id">
                                            <td>{{ inscription.apprenant?.nom || '-' }} {{ inscription.apprenant?.prenoms || '' }}</td>
                                            <td>{{ inscription.serviceCantine?.nom || '-' }}</td>
                                            <td>
                                                <span class="badge" :class="
                                                    inscription.statut === 'active' ? 'badge-success' :
                                                    inscription.statut === 'suspendue' ? 'badge-warning' :
                                                    inscription.statut === 'terminee' ? 'badge-secondary' :
                                                    'badge-danger'
                                                ">
                                                    {{ inscription.statut === 'active' ? 'Actif' :
                                                       inscription.statut === 'suspendue' ? 'Suspendue' :
                                                       inscription.statut === 'terminee' ? 'Terminée' :
                                                       'Annulée' }}
                                                </span>
                                            </td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('inscriptions-cantine.show', inscription.id)" class="btn btn-secondary" :title="t('actions.view')">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('inscriptions-cantine.edit', inscription.id)" class="btn btn-primary" :title="t('actions.edit')">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="confirmDelete(inscription)" class="btn btn-danger" :title="t('actions.delete')">
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="4" class="text-center">{{ t('common.emptyList') || 'Aucune donnée' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card View -->
                        <div class="mobile-card-list">
                            <template v-if="inscriptionsCantines?.data && inscriptionsCantines?.data.length > 0">
                                <div v-for="inscription in inscriptionsCantines?.data" :key="'m-' + inscription.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.apprenant') || 'Apprenant' }}</span>
                                            <span class="mobile-card-value">{{ inscription.apprenant?.nom }} {{ inscription.apprenant?.prenoms }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.service-cantine') || 'Service Cantine' }}</span>
                                            <span class="mobile-card-value">{{ inscription.serviceCantine?.nom }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('common.statut') || 'État' }}</span>
                                            <span class="mobile-card-value">
                                                <span class="badge" :class="
                                                    inscription.statut === 'active' ? 'badge-success' :
                                                    inscription.statut === 'suspendue' ? 'badge-warning' :
                                                    inscription.statut === 'terminee' ? 'badge-secondary' :
                                                    'badge-danger'
                                                ">
                                                    {{ inscription.statut === 'active' ? 'Actif' :
                                                       inscription.statut === 'suspendue' ? 'Suspendue' :
                                                       inscription.statut === 'terminee' ? 'Terminée' :
                                                       'Annulée' }}
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('inscriptions-cantine.show', inscription.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link :href="route('inscriptions-cantine.edit', inscription.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button @click="confirmDelete(inscription)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') || 'Aucune donnée' }}</p>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <Pagination :data="inscriptionsCantines" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmation -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="t('messages.confirm.delete.title')"
            :message="t('messages.confirm.delete.message')"
            :sub-message="t('messages.confirm.delete.warning')"
            @close="closeModal"
            @confirm="deleteItem()"
            :confirm-text="t('actions.delete')"
            confirm-class="btn-danger"
        />

        <!-- Loader pleine page -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
        />
    </div>
</template>
