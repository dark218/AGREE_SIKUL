<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import RejectModal from '@/Components/Common/RejectModal.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
import { usePermissions } from '@/Composables/usePermissions';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showValidateLoader, showRejectLoader, showSuspendLoader, showBlockLoader, hideLoader } = useLoader();
const { can } = usePermissions();
const page = usePage();
const props = defineProps({
    serviceClient: Object,
    pays: Array,
    filters: Object,
    statuts: Array,
    kycStatuts: Array,
    paysCurrent: [Number, String],
    userStatuts: Object,
    kycStatutsConst: Object,
});
const kycStatusOptions = computed(() => {
    return (props.kycStatuts || []).map(item => ({ id: item.value, label: item.label }));
});
const statutOptions = computed(() => {
    return (props.statuts || []).map(item => ({ id: item.value, label: item.label }));
});
const searchFilters = ref({
    nom: props.filters?.nom || '',
    prenoms: props.filters?.prenoms || '',
    login: props.filters?.login || '',
    email: props.filters?.email || '',
    pays_id: props.filters?.pays_id || '',
    kyc_status: props.filters?.kyc_status || '',
    statut: props.filters?.statut || '',
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
const showDeleteModal = ref(false);
const showValidateModal = ref(false);
const showRejectModal = ref(false);
const showSuspendreModal = ref(false);
const showBloquerModal = ref(false);
const itemToDelete = ref(null);
const itemToValidate = ref(null);
const itemToReject = ref(null);
const itemToSuspendre = ref(null);
const itemToBloquer = ref(null);
function search() {
    router.get(route('service_client.index'), {
        nom: searchFilters.value.nom || undefined,
        prenoms: searchFilters.value.prenoms || undefined,
        login: searchFilters.value.login || undefined,
        email: searchFilters.value.email || undefined,
        pays_id: searchFilters.value.pays_id || undefined,
        kyc_status: searchFilters.value.kyc_status || undefined,
        statut: searchFilters.value.statut || undefined,
    }, { preserveState: true, preserveScroll: true });
}
function confirmDelete(item) {
    itemToDelete.value = item;
    showDeleteModal.value = true;
}
function deleteItem() {
    if (itemToDelete.value) {
        showDeleteLoader();
        router.put(route('service_client.statut', itemToDelete.value.uuid), {}, {
            onSuccess: () => { showDeleteModal.value = false; itemToDelete.value = null; },
            onFinish: () => { hideLoader(); }
    });
    }
}
function closeDeleteModal() {
    showDeleteModal.value = false;
    itemToDelete.value = null;
}
function openValidateModal(item) {
    itemToValidate.value = item;
    showValidateModal.value = true;
}
function validerItem() {
    if (itemToValidate.value) {
        showValidateLoader();
        router.post(route('service_client.validation', [itemToValidate.value.uuid, 'valider']), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showValidateModal.value = false;
                itemToValidate.value = null;
            },
            onFinish: () => { hideLoader(); },
        });
    }
}
function openRejectModal(item) {
    itemToReject.value = item;
    showRejectModal.value = true;
}
function rejeterItem(motif) {
    if (itemToReject.value) {
        showRejectLoader();
        router.post(route('service_client.validation', [itemToReject.value.uuid, 'rejeter']), { motif }, {
            preserveScroll: true,
            onSuccess: () => {
                showRejectModal.value = false;
                itemToReject.value = null;
            },
            onFinish: () => { hideLoader(); },
        });
    }
}
function openSuspendreModal(item) {
    itemToSuspendre.value = item;
    showSuspendreModal.value = true;
}
function suspendreItem(motif) {
    if (itemToSuspendre.value) {
        showSuspendLoader();
        router.post(route('service_client.suspendre', itemToSuspendre.value.uuid), { motif }, {
            preserveScroll: true,
            onSuccess: () => {
                showSuspendreModal.value = false;
                itemToSuspendre.value = null;
            },
            onFinish: () => { hideLoader(); },
        });
    }
}
function openBloquerModal(item) {
    itemToBloquer.value = item;
    showBloquerModal.value = true;
}
function bloquerItem(motif) {
    if (itemToBloquer.value) {
        showBlockLoader();
        router.post(route('service_client.bloquer', itemToBloquer.value.uuid), { motif }, {
            preserveScroll: true,
            onSuccess: () => {
                showBloquerModal.value = false;
                itemToBloquer.value = null;
            },
            onFinish: () => { hideLoader(); },
        });
    }
}
function getKycBadgeClass(status) {
    const classes = { 'non_verifie': 'bg-warning', 'en_attente': 'bg-info', 'verifie': 'bg-success', 'rejete': 'bg-danger' };
    return classes[status] || 'bg-secondary';
}
function getKycLabel(status) {
    const labels = { 'non_verifie': t('kyc.non_verifie'), 'en_attente': t('kyc.en_attente'), 'verifie': t('kyc.verifie'), 'rejete': t('kyc.rejete') };
    return labels[status] || status;
}
function getStatutBadgeClass(status) {
    const classes = { 'non_actif': 'bg-secondary', 'actif': 'bg-success', 'suspendu': 'bg-warning', 'bloque': 'bg-danger', 'supprime': 'bg-secondary' };
    return classes[status] || 'bg-secondary';
}
function getStatutLabel(status) {
    const labels = { 'non_actif': t('statuts.non_actif'), 'actif': t('statuts.actif'), 'suspendu': t('statuts.suspendu'), 'bloque': t('statuts.bloque'), 'supprime': t('statuts.supprime') };
    return labels[status] || status;
}
function canValidateStatut(item) {
    return item.statut === 'non_actif' || item.statut === 'suspendu' || item.statut === 'bloque';
}
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
    <Head :title="t('modules.personnel.service_client.title')" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('modules.personnel.service_client.title') }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div v-if="can('service_client-create')" class="dashboard-btn">
                        <Link :href="route('service_client.create')" class="btn btn-primary"><i class="fa fa-plus"></i> {{ t('actions.add') }}</Link>
                    </div>
                </div>
            </div>
            <AlertMessage />
            <div class="row m-0 mb-3">
                <form class="row col-12" @submit.prevent="search">
                    <div class="col-2 p-1">
                        <input v-model="searchFilters.nom" type="text" class="form-control search-slt" :placeholder="t('fields.name')">
                    </div>
                    <div class="col-2 p-1">
                        <input v-model="searchFilters.login" type="text" class="form-control search-slt" :placeholder="t('fields.login')">
                    </div>
                    <div class="col-2 p-1">
                        <StylishSelect v-model="searchFilters.kyc_status" :options="kycStatusOptions" option-value="id" option-label="label" placeholder="KYC" :searchable="false" />
                    </div>
                    <div class="col-2 p-1">
                        <StylishSelect v-model="searchFilters.statut" :options="statutOptions" option-value="id" option-label="label" :placeholder="t('common.status')" :searchable="false" />
                    </div>
                    <div class="col-2 p-1">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i></button>
                    <button type="button" @click="resetFilters" class="btn btn-secondary wrn-btn radius-0">
                        <i class="fa fa-redo"></i> <i class="fa fa-sync"></i> {{ t('actions.reset') }}
                    </button>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div class="table-wrapper">
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>{{ t('fields.name') }}</th>
                                    <th>{{ t('fields.firstName') }}</th>
                                    <th>{{ t('fields.login') }}</th>
                                    <th>{{ t('fields.country') }}</th>
                                    <th>KYC</th>
                                    <th>{{ t('common.status') }}</th>
                                    <th class="fit">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="serviceClient?.data && serviceClient.data.length > 0" v-for="item in serviceClient.data" :key="item.id">
                                    <td>{{ item.nom }}</td>
                                    <td>{{ item.prenoms }}</td>
                                    <td>{{ item.login }}</td>
                                    <td>{{ item.pays }}</td>
                                    <td><span :class="['badge text-dark rounded-pill', getKycBadgeClass(item.kyc_status)]">{{ getKycLabel(item.kyc_status) }}</span></td>
                                    <td><span :class="['badge text-dark rounded-pill', getStatutBadgeClass(item.statut)]">{{ getStatutLabel(item.statut) }}</span></td>
                                    <td class="fit">
                                        <div class="action-buttons">
                                            <Link :href="route('service_client.show', item.uuid)" class="btn btn-secondary btn-sm" :title="t('actions.view')"><i class="fa fa-eye"></i></Link>
                                            <Link v-if="can('service_client-edit')" :href="route('service_client.edit', item.uuid)" class="btn btn-primary btn-sm" :title="t('actions.edit')"><i class="fa fa-edit"></i></Link>
                                            <button v-if="can('service_client-statut')" @click="confirmDelete(item)" class="btn btn-danger btn-sm" :title="t('actions.delete')"><i class="fa fa-trash"></i></button>
                                            <button v-if="can('service_client-statut') && canValidateStatut(item)" type="button" class="btn btn-success btn-sm" @click="openValidateModal(item)" :title="t('actions.validate')"><i class="fa fa-check"></i></button>
                                            <button v-if="can('service_client-statut') && canValidateStatut(item)" type="button" class="btn btn-warning btn-sm" @click="openRejectModal(item)" :title="t('actions.reject')"><i class="fa fa-times"></i></button>
                                            <button v-if="item.statut === 'actif'" type="button" class="btn btn-warning btn-sm" @click="openSuspendreModal(item)" :title="t('actions.suspend')"><i class="fa fa-pause"></i></button>
                                            <button v-if="item.statut === 'actif'" type="button" class="btn btn-danger btn-sm" @click="openBloquerModal(item)" :title="t('actions.block')"><i class="fa fa-ban"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-else><td colspan="7" class="text-center">{{ t('common.emptyList') }}</td></tr>
                            </tbody>
                        </table>
                        <Pagination :data="serviceClient" :preserve-scroll="true" :preserve-state="true" />
                    </div>
                </div>
            </div>
        </div>
        <ConfirmModal :show="showDeleteModal" :title="t('messages.confirm.delete.title')" :message="itemToDelete?.etat === 'actif' || itemToDelete?.statut === 'actif' ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message')" :sub-message="t('messages.confirm.delete.warning')" confirm-text="Supprimer" confirm-class="btn-danger" @confirm="deleteItem" @close="closeDeleteModal" />
        <ConfirmModal :show="showValidateModal" :title="t('messages.confirm.validate.title')" :message="t('messages.confirm.validate.message')" @close="showValidateModal = false" @confirm="validerItem" :confirm-text="t('actions.validate')" confirm-class="btn-success" variant="success" />
        <RejectModal :show="showRejectModal" :title="t('messages.confirm.reject.title')" @close="showRejectModal = false" @confirm="rejeterItem" />
        <RejectModal :show="showSuspendreModal" :title="t('actions.suspend')" :message="t('messages.confirm.suspend')" :motifLabel="t('fields.suspendReason')" :confirmText="t('actions.suspend')" confirmClass="btn-warning" @close="showSuspendreModal = false" @confirm="suspendreItem" />
        <RejectModal :show="showBloquerModal" :title="t('actions.block')" :message="t('messages.confirm.block')" :motifLabel="t('fields.blockReason')" :confirmText="t('actions.block')" confirmClass="btn-danger" @close="showBloquerModal = false" @confirm="bloquerItem" />
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
