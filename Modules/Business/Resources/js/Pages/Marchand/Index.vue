<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import RejectModal from '@/Components/Common/RejectModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
import { usePermissions } from '@/Composables/usePermissions';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showValidateLoader, showRejectLoader, showSuspendLoader, showBlockLoader, hideLoader } = useLoader();
const { can } = usePermissions();
const props = defineProps({
    marchands: Object,
    pays: Array,
    statuts: Array,
    kycStatuts: Array,
    filters: Object,
    paysCurrent: [Number, String, null],
    userStatuts: Object,
    kycStatutsConst: Object,
});
// Types de marchands
const marchandTypes = computed(() => [
    { value: 'informel', label: t('modules.business.marchands.types.informel') },
    { value: 'boutique', label: t('modules.business.marchands.types.boutique') },
    { value: 'grande_surface', label: t('modules.business.marchands.types.grande_surface') },
]);
const searchFilters = ref({
    pays_id: props.filters?.pays_id || props.paysCurrent || '',
    raison_sociale: props.filters?.raison_sociale || '',
    identifiant_fiscal: props.filters?.identifiant_fiscal || '',
    nom: props.filters?.nom || '',
    tel: props.filters?.tel || '',
    email: props.filters?.email || '',
    statut: props.filters?.statut || '',
    kyc_status: props.filters?.kyc_status || '',
    type: props.filters?.type || '',
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
const showRejectModal = ref(false);
const showValidateModal = ref(false);
const showSuspendreModal = ref(false);
const showBloquerModal = ref(false);
const marchandToDelete = ref(null);
const marchandToReject = ref(null);
const marchandToValidate = ref(null);
const marchandToSuspendre = ref(null);
const marchandToBloquer = ref(null);
const expandedMarchand = ref(null);
const search = () => {
    router.get(route('marchand.index'), searchFilters.value, {
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
  router.get(route('marchand.index'));
};
const confirmDelete = (marchand) => {
    marchandToDelete.value = marchand;
    showDeleteModal.value = true;
};
const deleteMarchand = () => {
    if (marchandToDelete.value) {
        showDeleteLoader();
        router.put(route('marchand.statut', marchandToDelete.value.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                marchandToDelete.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const openValidateModal = (marchand) => {
    marchandToValidate.value = marchand;
    showValidateModal.value = true;
};
const validerMarchand = () => {
    if (marchandToValidate.value) {
        showValidateLoader();
        router.post(route('marchand.validation', [marchandToValidate.value.id, 'valider']), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showValidateModal.value = false;
                marchandToValidate.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const openRejectModal = (marchand) => {
    marchandToReject.value = marchand;
    showRejectModal.value = true;
};
const rejeterMarchand = (motif) => {
    if (marchandToReject.value) {
        showRejectLoader();
        router.post(route('marchand.validation', [marchandToReject.value.id, 'rejeter']), { motif }, {
            preserveScroll: true,
            onSuccess: () => {
                showRejectModal.value = false;
                marchandToReject.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const openSuspendreModal = (marchand) => {
    marchandToSuspendre.value = marchand;
    showSuspendreModal.value = true;
};
const openBloquerModal = (marchand) => {
    marchandToBloquer.value = marchand;
    showBloquerModal.value = true;
};
const suspendreMarchand = (motif) => {
    if (marchandToSuspendre.value) {
        showSuspendLoader();
        router.post(route('marchand.suspendre', marchandToSuspendre.value.id), { motif }, {
            preserveScroll: true,
            onSuccess: () => {
                showSuspendreModal.value = false;
                marchandToSuspendre.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const bloquerMarchand = (motif) => {
    if (marchandToBloquer.value) {
        showBlockLoader();
        router.post(route('marchand.bloquer', marchandToBloquer.value.id), { motif }, {
            preserveScroll: true,
            onSuccess: () => {
                showBloquerModal.value = false;
                marchandToBloquer.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const toggleExpand = (marchand) => {
    if (expandedMarchand.value === marchand.id) {
        expandedMarchand.value = null;
    } else {
        expandedMarchand.value = marchand.id;
    }
};
const getStatutBadgeClass = (status) => {
    const statuts = props.userStatuts || {};
    const classes = {
        [statuts.non_actif]: 'bg-secondary',
        [statuts.actif]: 'bg-success',
        [statuts.suspendu]: 'bg-warning',
        [statuts.bloque]: 'bg-danger',
        [statuts.supprime]: 'bg-secondary',
    };
    return classes[status] || 'bg-secondary';
};
const getStatutLabel = (status) => {
    const statuts = props.userStatuts || {};
    const labels = {
        [statuts.non_actif]: t('statuts.non_actif'),
        [statuts.actif]: t('statuts.actif'),
        [statuts.suspendu]: t('statuts.suspendu'),
        [statuts.bloque]: t('statuts.bloque'),
        [statuts.supprime]: t('statuts.supprime'),
    };
    return labels[status] || status;
};
const getKycBadgeClass = (status) => {
    const kyc = props.kycStatutsConst || {};
    const classes = {
        [kyc.non_verifie]: 'bg-warning',
        [kyc.en_attente]: 'bg-info',
        [kyc.verifie]: 'bg-success',
        [kyc.rejete]: 'bg-danger',
    };
    return classes[status] || 'bg-secondary';
};
const getKycLabel = (status) => {
    const kyc = props.kycStatutsConst || {};
    const labels = {
        [kyc.non_verifie]: t('common.notVerified'),
        [kyc.en_attente]: t('common.pending'),
        [kyc.verifie]: t('common.verified'),
        [kyc.rejete]: t('common.rejected'),
    };
    return labels[status] || status;
};
const getTypeLabel = (type) => {
    const typeItem = marchandTypes.value.find(t => t.value === type);
    return typeItem ? typeItem.label : type || '-';
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
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('modules.business.marchands.title') }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('marchand-create')">
                        <Link :href="route('marchand.create')" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>
            <AlertMessage />
            <div class="row m-0 mb-3">
                <form class="row col-12" @submit.prevent="search">
                    <div class="col-2 p-1">
                        <StylishSelect v-model="searchFilters.pays_id" :options="pays" option-value="id"
                            option-label="libelle" :placeholder="t('fields.country')"
                            :disabled="paysCurrent !== null" />
                    </div>
                    <div class="col-2 p-1">
                        <input type="text" v-model="searchFilters.raison_sociale" class="form-control"
                            :placeholder="t('fields.businessName')" />
                    </div>
                    <!-- <div class="col-2 p-1">
                        <input
                            type="text"
                            v-model="searchFilters.identifiant_fiscal"
                            class="form-control"
                            :placeholder="t('fields.taxId')"
                        />
                    </div> -->
                    <!-- <div class="col-2 p-1">
                        <input type="text" v-model="searchFilters.tel" class="form-control"
                            :placeholder="t('fields.phone')" />
                    </div> -->
                    <div class="col-2 p-1">
                        <StylishSelect v-model="searchFilters.type" :options="marchandTypes" option-value="value"
                            option-label="label" placeholder="Type" />
                    </div>
                    <div class="col-2 p-1">
                        <StylishSelect v-model="searchFilters.kyc_status" :options="kycStatuts" option-value="value"
                            option-label="label" placeholder="KYC" />
                    </div>
                    <div class="col-2 p-1">
                        <StylishSelect v-model="searchFilters.statut" :options="statuts" option-value="value"
                            option-label="label" :placeholder="t('common.status')" />
                    </div>
                    <div class="col-2 p-1">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-search"></i>
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
                                    <th style="width: 40px;"></th>
                                    <th>{{ t('fields.country') }}</th>
                                    <th>{{ t('fields.businessName') }}</th>
                                    <th>{{ t('fields.taxId') }}</th>
                                    <th>Type</th>
                                    <th>{{ t('fields.name') }}</th>
                                    <th>{{ t('fields.firstName') }}</th>
                                    <th>{{ t('fields.email') }}</th>
                                    <th>{{ t('fields.phone') }}</th>
                                    <th>KYC</th>
                                    <th>{{ t('common.status') }}</th>
                                    <th class="fit">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="marchands.data && marchands.data.length > 0">
                                    <template v-for="marchand in marchands.data" :key="marchand.id">
                                        <tr>
                                            <td>
                                                <button v-if="marchand.points_vente_count > 0" type="button"
                                                    class="expand-toggle"
                                                    :class="expandedMarchand === marchand.id ? 'btn-danger' : 'btn-info'"
                                                    @click="toggleExpand(marchand)">
                                                    <i
                                                        :class="expandedMarchand === marchand.id ? 'fa fa-chevron-up' : 'fa fa-chevron-down'"></i>
                                                </button>
                                            </td>
                                            <td>{{ marchand.pays }}</td>
                                            <td>{{ marchand.raison_sociale }}</td>
                                            <td>{{ marchand.identifiant_fiscal }}</td>
                                            <td>{{ getTypeLabel(marchand.type) }}</td>
                                            <td>{{ marchand.nom }}</td>
                                            <td>{{ marchand.prenoms }}</td>
                                            <td>{{ marchand.email }}</td>
                                            <td>{{ marchand.telephone }}</td>
                                            <td>
                                                <span
                                                    :class="['badge text-dark rounded-pill', getKycBadgeClass(marchand.kyc_status)]">
                                                    {{ getKycLabel(marchand.kyc_status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span
                                                    :class="['badge text-dark rounded-pill', getStatutBadgeClass(marchand.statut)]">
                                                    {{ getStatutLabel(marchand.statut) }}
                                                </span>
                                            </td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('marchand.show', marchand.id)"
                                                        class="btn btn-secondary btn-sm" :title="t('actions.view')">
                                                        <i class="fa fa-eye"></i>
                                                    </Link>
                                                    <Link v-if="can('marchand-edit')"
                                                        :href="route('marchand.edit', marchand.id)"
                                                        class="btn btn-primary btn-sm" :title="t('actions.edit')">
                                                        <i class="fa fa-edit"></i>
                                                    </Link>
                                                    <button v-if="can('marchand-statut')" type="button"
                                                        class="btn btn-danger btn-sm" @click="confirmDelete(marchand)"
                                                        :title="t('actions.delete')">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                    <button
                                                        v-if="can('marchand-validate') && (marchand.statut === userStatuts?.non_actif || marchand.statut === userStatuts?.suspendu || marchand.statut === userStatuts?.bloque)"
                                                        type="button" class="btn btn-success btn-sm"
                                                        @click="openValidateModal(marchand)"
                                                        :title="t('actions.validate')">
                                                        <i class="fa fa-check"></i>
                                                    </button>
                                                    <button
                                                        v-if="can('marchand-validate') && (marchand.statut === userStatuts?.non_actif || marchand.statut === userStatuts?.suspendu || marchand.statut === userStatuts?.bloque)"
                                                        type="button" class="btn btn-warning btn-sm"
                                                        @click="openRejectModal(marchand)" :title="t('actions.reject')">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                    <button v-if="can('marchand-validate') && marchand.statut === userStatuts?.actif" type="button"
                                                        class="btn btn-warning btn-sm"
                                                        @click="openSuspendreModal(marchand)"
                                                        :title="t('actions.suspend')">
                                                        <i class="fa fa-pause"></i>
                                                    </button>
                                                    <button v-if="can('marchand-validate') &&marchand.statut === userStatuts?.actif" type="button"
                                                        class="btn btn-danger btn-sm"
                                                        @click="openBloquerModal(marchand)" :title="t('actions.block')">
                                                        <i class="fa fa-ban"></i>
                                                    </button>
                                                    <Link v-if="can('wallet-show-by-owner')"
                                                        :href="route('wallet.show-by-owner', { ownerType: 'marchand', ownerId: marchand.id })"
                                                        class="btn btn-info btn-sm" :title="t('modules.wallet.singular')">
                                                        <i class="fa fa-wallet"></i>
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Points de vente sous-tableau -->
                                        <tr
                                            v-if="expandedMarchand === marchand.id && marchand.points_vente && marchand.points_vente.length > 0">
                                            <td colspan="12" class="p-0">
                                                <div class="sub-table-wrapper"
                                                    style="background-color: #f8f9fa; padding: 15px; margin: 0;">
                                                    <h6 class="mb-2 text-start">{{ t('modules.business.pointsvente.title') }}</h6>
                                                    <table class="custom-table sub-table text-start">
                                                        <thead>
                                                            <tr style="background-color: #343a40; color: white;">
                                                                <th>{{ t('fields.zone') }}</th>
                                                                <th>{{ t('fields.name') }}</th>
                                                                <th>{{ t('fields.address') }}</th>
                                                                <th>{{ t('fields.phone') }}</th>
                                                                <th>{{ t('common.status') }}</th>
                                                                <th class="fit">{{ t('common.actions') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="pv in marchand.points_vente" :key="pv.id">
                                                                <td>{{ pv.zone }}</td>
                                                                <td>{{ pv.nom }}</td>
                                                                <td>{{ pv.adresse }}</td>
                                                                <td>{{ pv.telephone }}</td>
                                                                <td>
                                                                    <span
                                                                        :class="['badge text-dark rounded-pill', getStatutBadgeClass(pv.statut)]">
                                                                        {{ getStatutLabel(pv.statut) }}
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <Link :href="route('pointvente.show', pv.id)"
                                                                        class="btn btn-secondary btn-sm btn-sm">
                                                                        <i class="fa fa-eye"></i>
                                                                    </Link>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </template>
                                <tr v-else>
                                    <td colspan="12" class="text-center">{{ t('common.emptyList') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :data="marchands" />
                </div>
            </div>
        </div>
        <ConfirmModal :show="showDeleteModal" :title="t('messages.confirm.delete.title')"
            :message="marchandToDelete?.etat === 'actif' || marchandToDelete?.statut === 'actif' ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message')" :sub-message="t('messages.confirm.delete.warning')"
            @close="showDeleteModal = false" @confirm="deleteMarchand" confirm-text="Supprimer"
            confirm-class="btn-danger" />
        <RejectModal :show="showRejectModal" :title="t('messages.confirm.reject.title')"
            @close="showRejectModal = false" @confirm="rejeterMarchand" />
        <ConfirmModal :show="showValidateModal" :title="t('messages.confirm.validate.title')"
            :message="t('messages.confirm.validate.message')" @close="showValidateModal = false"
            @confirm="validerMarchand" :confirm-text="t('actions.validate')" confirm-class="btn-success"
            variant="success" />
        <RejectModal :show="showSuspendreModal" :title="t('actions.suspend')" :message="t('messages.confirm.suspend')"
            :motifLabel="t('fields.suspendReason')" :confirmText="t('actions.suspend')" confirmClass="btn-warning"
            @close="showSuspendreModal = false" @confirm="suspendreMarchand" />
        <RejectModal :show="showBloquerModal" :title="t('actions.block')" :message="t('messages.confirm.block')"
            :motifLabel="t('fields.blockReason')" :confirmText="t('actions.block')" confirmClass="btn-danger"
            @close="showBloquerModal = false" @confirm="bloquerMarchand" />
        <!-- Loader pleine page -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
