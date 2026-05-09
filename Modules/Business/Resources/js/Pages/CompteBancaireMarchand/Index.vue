<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
import { usePermissions } from '@/Composables/usePermissions';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, hideLoader } = useLoader();
const { can } = usePermissions();
const props = defineProps({
    comptes: Object,
    filters: Object,
    marchands: Array,
    banques: Array,
    is_marchand: Boolean,
    marchand_current: Object,
});
const page = usePage();
// Filtres de recherche
const searchFilters = ref({
    nom_compte: props.filters?.nom_compte || '',
    numero_compte: props.filters?.numero_compte || '',
    marchand_id: props.filters?.marchand_id || '',
    banque_id: props.filters?.banque_id || '',
});
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
const compteToDelete = ref(null);
// Modal de toggle active
const showToggleModal = ref(false);
const compteToToggle = ref(null);
const search = () => {
    router.get(route('compte-bancaire-marchand.index'), searchFilters.value, {
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
  router.get(route('compte-bancaire-marchand.index'));
};
const confirmDelete = (compte) => {
    compteToDelete.value = compte;
    showDeleteModal.value = true;
};
const deleteCompte = () => {
    if (compteToDelete.value) {
        showDeleteLoader();
        router.put(route('compte-bancaire-marchand.statut', compteToDelete.value.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                compteToDelete.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const confirmToggleActive = (compte) => {
    compteToToggle.value = compte;
    showToggleModal.value = true;
};
const toggleActive = () => {
    if (compteToToggle.value) {
        router.put(route('compte-bancaire-marchand.toggle-active', compteToToggle.value.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showToggleModal.value = false;
                compteToToggle.value = null;
            },
        });
    }
};
// Real-time search with debounce
watch(
  () => searchFilters.value,
  () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      search();
    }, 500); // 500ms debounce
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
                <h4 class="title">{{ t('modules.business.comptesBancaires.title') }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('compte-bancaire-marchand-create')">
                        <Link :href="route('compte-bancaire-marchand.create')" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>
            <!-- Alert Message -->
            <AlertMessage />
            <div class="row m-0">
                <!-- Filtres de recherche -->
                <form class="row col-12 text-center" @submit.prevent="search">
                    <div class="col-3 p-1">
                        <input
                            type="text"
                            v-model="searchFilters.nom_compte"
                            class="form-control search-input"
                            :placeholder="t('fields.accountName')"
                        />
                    </div>
                    <div class="col-2 p-1">
                        <input
                            type="text"
                            v-model="searchFilters.numero_compte"
                            class="form-control search-input"
                            :placeholder="t('fields.accountNumber')"
                        />
                    </div>
                    <div class="col-2 p-1" v-if="!is_marchand">
                        <StylishSelect
                            v-model="searchFilters.marchand_id"
                            :options="marchands"
                            option-value="id"
                            option-label="raison_sociale"
                            :placeholder="t('fields.marchand')"
                        />
                    </div>
                    <div class="col-2 p-1">
                        <StylishSelect
                            v-model="searchFilters.banque_id"
                            :options="banques"
                            option-value="id"
                            option-label="nom"
                            :placeholder="t('fields.bank')"
                        />
                    </div>
                    <div class="col-2 p-0">
                        <button type="submit" class="btn btn-primary wrn-btn radius-0">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </form>
                <br>
                <br>
                <br>
                <!-- Tableau -->
                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th v-if="!is_marchand">{{ t('fields.marchand') }}</th>
                                        <th>{{ t('fields.bank') }}</th>
                                        <th>{{ t('fields.accountName') }}</th>
                                        <th>{{ t('fields.accountNumber') }}</th>
                                        <th>{{ t('fields.iban') }}</th>
                                        <th>{{ t('fields.principal') }}</th>
                                        <th>{{ t('fields.status') }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="comptes.data && comptes.data.length > 0">
                                        <tr v-for="compte in comptes.data" :key="compte.id">
                                            <td v-if="!is_marchand">{{ compte.marchand?.raison_sociale || '-' }}</td>
                                            <td>{{ compte.banque?.nom || '-' }}</td>
                                            <td>{{ compte.nom_compte || '' }}</td>
                                            <td>{{ compte.numero_compte || '' }}</td>
                                            <td>{{ compte.iban || '-' }}</td>
                                            <td>
                                                <span :class="['badge rounded-pill', compte.is_principal ? 'bg-success' : 'bg-secondary']">
                                                    {{ compte.is_principal ? t('common.yes') : t('common.no') }}
                                                </span>
                                            </td>
                                            <td>
                                                <div v-if="can('compte-bancaire-marchand-statut')" class="form-check form-switch d-flex justify-content-center">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        role="switch"
                                                        :id="'switch-' + compte.id"
                                                        :checked="compte.is_active"
                                                        @change="confirmToggleActive(compte)"
                                                        style="cursor: pointer; width: 50px; height: 25px;"
                                                    />
                                                </div>
                                                <span
                                                    v-else
                                                    :class="['badge rounded-pill', compte.is_active ? 'bg-success' : 'bg-secondary']"
                                                >
                                                    {{ compte.is_active ? t('common.active') : t('common.inactive') }}
                                                </span>
                                            </td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link
                                                        :href="route('compte-bancaire-marchand.show', compte.id)"
                                                        class="btn btn-secondary"
                                                        :title="t('actions.view')"
                                                    >
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link
                                                        v-if="can('compte-bancaire-marchand-edit')"
                                                        :href="route('compte-bancaire-marchand.edit', compte.id)"
                                                        class="btn btn-primary"
                                                        :title="t('actions.edit')"
                                                    >
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button
                                                        v-if="can('compte-bancaire-marchand-statut')"
                                                        type="button"
                                                        class="btn btn-danger"
                                                        @click="confirmDelete(compte)"
                                                        :title="t('actions.delete')"
                                                    >
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td :colspan="is_marchand ? 7 : 8" class="text-center">{{ t('common.emptyList') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Mobile Card View -->
                        <div class="mobile-card-list">
                            <template v-if="comptes.data && comptes.data.length > 0">
                                <div v-for="compte in comptes.data" :key="'m-' + compte.id" class="mobile-card">
                                    <div class="mobile-card-body">
                                        <div class="mobile-card-row" v-if="!is_marchand">
                                            <span class="mobile-card-label">{{ t('fields.marchand') }}</span>
                                            <span class="mobile-card-value">{{ compte.marchand?.raison_sociale || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.bank') }}</span>
                                            <span class="mobile-card-value">{{ compte.banque?.nom || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.accountName') }}</span>
                                            <span class="mobile-card-value">{{ compte.nom_compte || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.accountNumber') }}</span>
                                            <span class="mobile-card-value">{{ compte.numero_compte || '-' }}</span>
                                        </div>
                                        <div class="mobile-card-row">
                                            <span class="mobile-card-label">{{ t('fields.status') }}</span>
                                            <span :class="['badge rounded-pill', compte.is_active ? 'bg-success' : 'bg-secondary']">
                                                {{ compte.is_active ? t('common.active') : t('common.inactive') }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                        <Link :href="route('compte-bancaire-marchand.show', compte.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                        <Link v-if="can('compte-bancaire-marchand-edit')" :href="route('compte-bancaire-marchand.edit', compte.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                        <button v-if="can('compte-bancaire-marchand-statut')" type="button" class="btn btn-danger" @click="confirmDelete(compte)"><span class="fa fa-trash"></span></button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>
                        <!-- Pagination -->
                        <Pagination :data="comptes" />
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal de confirmation de suppression -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="t('messages.confirm.delete.title')"
            :message="t('messages.confirm.delete.message')"
            :sub-message="t('messages.confirm.delete.warning')"
            @close="showDeleteModal = false"
            @confirm="deleteCompte"
            confirm-text="Supprimer"
            confirm-class="btn-danger"
        />
        <!-- Modal de confirmation de toggle -->
        <ConfirmModal
            :show="showToggleModal"
            :title="compteToToggle?.is_active ? t('messages.confirm.deactivate.title') : t('messages.confirm.activate.title')"
            :message="compteToToggle?.is_active ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message')"
            @close="showToggleModal = false"
            @confirm="toggleActive"
            :confirm-text="compteToToggle?.is_active ? t('actions.deactivate') : t('actions.activate')"
            :confirm-class="compteToToggle?.is_active ? 'btn-warning' : 'btn-success'"
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
