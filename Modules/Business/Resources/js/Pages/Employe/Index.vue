<script setup>
import { ref, watch } from 'vue';
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
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showValidateLoader, showRejectLoader, showSuspendLoader, showBlockLoader, hideLoader } = useLoader();
const { can } = usePermissions();
const page = usePage();
const props = defineProps({
    employes: Object,
    pays: Array,
    statuts: Array,
    kycStatuts: Array,
    typeEmployes: Array,
    filters: Object,
    paysCurrent: [Number, String, null],
    userStatuts: Object,
    kycStatutsConst: Object,
});
const searchFilters = ref({
    pays_id: props.filters?.pays_id || props.paysCurrent || '',
    nom: props.filters?.nom || '',
    tel: props.filters?.tel || '',
    raison_sociale: props.filters?.raison_sociale || '',
    code_employe: props.filters?.code_employe || '',
    statut: props.filters?.statut || '',
    kyc_status: props.filters?.kyc_status || '',
    type_employe: props.filters?.type_employe || '',
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
const employeToDelete = ref(null);
const employeToReject = ref(null);
const employeToValidate = ref(null);
const employeToSuspendre = ref(null);
const employeToBloquer = ref(null);
const search = () => {
    router.get(route('employe.index'), searchFilters.value, {
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
  router.get(route('employe.index'));
};
const confirmDelete = (employe) => {
    employeToDelete.value = employe;
    showDeleteModal.value = true;
};
const deleteEmploye = () => {
    if (employeToDelete.value) {
        showDeleteLoader();
        router.put(route('employe.statut', employeToDelete.value.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                employeToDelete.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const openValidateModal = (employe) => {
    employeToValidate.value = employe;
    showValidateModal.value = true;
};
const validerEmploye = () => {
    if (employeToValidate.value) {
        showValidateLoader();
        router.post(route('employe.validation', [employeToValidate.value.id, 'valider']), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showValidateModal.value = false;
                employeToValidate.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const openRejectModal = (employe) => {
    employeToReject.value = employe;
    showRejectModal.value = true;
};
const rejeterEmploye = (motif) => {
    if (employeToReject.value) {
        showRejectLoader();
        router.post(route('employe.validation', [employeToReject.value.id, 'rejeter']), { motif }, {
            preserveScroll: true,
            onSuccess: () => {
                showRejectModal.value = false;
                employeToReject.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const openSuspendreModal = (employe) => {
    employeToSuspendre.value = employe;
    showSuspendreModal.value = true;
};
const openBloquerModal = (employe) => {
    employeToBloquer.value = employe;
    showBloquerModal.value = true;
};
const suspendreEmploye = (motif) => {
    if (employeToSuspendre.value) {
        showSuspendLoader();
        router.post(route('employe.suspendre', employeToSuspendre.value.id), { motif }, {
            preserveScroll: true,
            onSuccess: () => {
                showSuspendreModal.value = false;
                employeToSuspendre.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const bloquerEmploye = (motif) => {
    if (employeToBloquer.value) {
        showBlockLoader();
        router.post(route('employe.bloquer', employeToBloquer.value.id), { motif }, {
            preserveScroll: true,
            onSuccess: () => {
                showBloquerModal.value = false;
                employeToBloquer.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const getStatutBadgeClass = (status) => {
    const classes = {
        'non_actif': 'bg-secondary',
        'actif': 'bg-success',
        'suspendu': 'bg-warning',
        'bloque': 'bg-danger',
        'supprime': 'bg-secondary',
    };
    return classes[status] || 'bg-secondary';
};
const getStatutLabel = (status) => {
    const labels = {
        'non_actif': t('statuts.inactif'),
        'actif': t('statuts.actif'),
        'suspendu': t('statuts.suspendu'),
        'bloque': t('statuts.bloque'),
        'supprime': t('statuts.supprime'),
    };
    return labels[status] || status;
};
const getKycBadgeClass = (status) => {
    const classes = {
        'non_verifie': 'bg-warning',
        'en_attente': 'bg-info',
        'verifie': 'bg-success',
        'rejete': 'bg-danger',
    };
    return classes[status] || 'bg-secondary';
};
function getKycLabel(status) {
    const labels = {
        'non_verifie': t('kyc.non_verifie'),
        'en_attente': t('kyc.en_attente'),
        'verifie': t('kyc.verifie'),
        'rejete': t('kyc.rejete'),
    };
    return labels[status] || status;
}
function getTypeEmployeLabel(type) {
    const labels = {
        'caissier': t('modules.business.employes.types.caissier'),
        'manager': t('modules.business.employes.types.manager'),
    };
    return labels[type] || type;
}
function getTypeEmployeBadgeClass(type) {
    const classes = {
        'caissier': 'bg-info',
        'manager': 'bg-primary',
    };
    return classes[type] || 'bg-secondary';
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
    <Head :title="page.props.title" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('modules.business.employes.title') }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('employe-create')">
                        <Link :href="route('employe.create')" class="btn btn-primary">
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
                    <!-- <div class="col-2 p-1">
                        <input type="text" v-model="searchFilters.raison_sociale" class="form-control"
                            :placeholder="t('fields.businessName')" />
                    </div> -->
                    <!-- <div class="col-2 p-1">
                        <input type="text" v-model="searchFilters.tel" class="form-control"
                            :placeholder="t('fields.phone')" />
                    </div> -->
                    <div class="col-2 p-1">
                        <input type="text" v-model="searchFilters.code_employe" class="form-control"
                            :placeholder="t('fields.employeeCode')" />
                    </div>
                    <div class="col-2 p-1">
                        <StylishSelect v-model="searchFilters.type_employe" :options="typeEmployes" option-value="value"
                            option-label="label" :placeholder="t('fields.employeeType')" />
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
                        <button type="button" @click="search" class="btn btn-primary">
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
                                    <th>{{ t('fields.employeeType') }}</th>
                                    <th>{{ t('fields.country') }}</th>
                                    <th>{{ t('fields.businessName') }}</th>
                                    <th>{{ t('fields.name') }}</th>
                                    <th>{{ t('fields.firstName') }}</th>
                                    <th>{{ t('fields.email') }}</th>
                                    <th>{{ t('fields.phone') }}</th>
                                    <th>{{ t('fields.employeeCode') }}</th>
                                    <th>KYC</th>
                                    <th>{{ t('common.status') }}</th>
                                    <th class="fit">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="employes.data && employes.data.length > 0">
                                    <tr v-for="employe in employes.data" :key="employe.id">
                                        <td>
                                            <span :class="['badge text-white rounded-pill', getTypeEmployeBadgeClass(employe.type_employe)]">
                                                {{ getTypeEmployeLabel(employe.type_employe) }}
                                            </span>
                                        </td>
                                        <td>{{ employe.pays }}</td>
                                        <td>{{ employe.raison_sociale }}</td>
                                        <td>{{ employe.nom }}</td>
                                        <td>{{ employe.prenoms }}</td>
                                        <td>{{ employe.email }}</td>
                                        <td>{{ employe.telephone }}</td>
                                        <td>{{ employe.code_employe }}</td>
                                        <td>
                                            <span
                                                :class="['badge text-dark rounded-pill', getKycBadgeClass(employe.kyc_status)]">
                                                {{ getKycLabel(employe.kyc_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                :class="['badge text-dark rounded-pill', getStatutBadgeClass(employe.statut)]">
                                                {{ getStatutLabel(employe.statut) }}
                                            </span>
                                        </td>
                                        <td class="fit">
                                            <div class="action-buttons">
                                                <Link :href="route('employe.show', employe.id)"
                                                    class="btn btn-secondary btn-sm" :title="t('actions.view')">
                                                    <i class="fa fa-eye"></i>
                                                </Link>
                                                <Link v-if="can('employe-edit')"
                                                    :href="route('employe.edit', employe.id)"
                                                    class="btn btn-primary btn-sm" :title="t('actions.edit')">
                                                    <i class="fa fa-edit"></i>
                                                </Link>
                                                <button v-if="can('employe-statut')" type="button"
                                                    class="btn btn-danger btn-sm" @click="confirmDelete(employe)"
                                                    :title="t('actions.delete')">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                                <button
                                                    v-if="can('employe-validate') && [userStatuts?.non_actif, userStatuts?.bloque, userStatuts?.suspendu].includes(employe.statut)"
                                                    type="button" class="btn btn-success btn-sm"
                                                    @click="openValidateModal(employe)" :title="t('actions.validate')">
                                                    <i class="fa fa-check"></i>
                                                </button>
                                                <button
                                                    v-if="can('employe-validate') && [userStatuts?.non_actif, userStatuts?.bloque, userStatuts?.suspendu].includes(employe.statut)"
                                                    type="button" class="btn btn-warning btn-sm"
                                                    @click="openRejectModal(employe)" :title="t('actions.reject')">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                                <button v-if="employe.statut === userStatuts?.actif" type="button"
                                                    class="btn btn-warning btn-sm" @click="openSuspendreModal(employe)"
                                                    :title="t('actions.suspend')">
                                                    <i class="fa fa-pause"></i>
                                                </button>
                                                <button v-if="employe.statut === userStatuts?.actif" type="button"
                                                    class="btn btn-danger btn-sm" @click="openBloquerModal(employe)"
                                                    :title="t('actions.block')">
                                                    <i class="fa fa-ban"></i>
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
                    <Pagination :data="employes" />
                </div>
            </div>
        </div>
        <ConfirmModal :show="showDeleteModal" :title="t('messages.confirm.delete.title')"
            :message="employeToDelete?.etat === 'actif' || employeToDelete?.statut === 'actif' ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message')" :sub-message="t('messages.confirm.delete.warning')"
            @close="showDeleteModal = false" @confirm="deleteEmploye" confirm-text="Supprimer"
            confirm-class="btn-danger" />
        <RejectModal :show="showRejectModal" :title="t('messages.confirm.reject.title')"
            @close="showRejectModal = false" @confirm="rejeterEmploye" />
        <ConfirmModal :show="showValidateModal" :title="t('messages.confirm.validate.title')"
            :message="t('messages.confirm.validate.message')" @close="showValidateModal = false"
            @confirm="validerEmploye" :confirm-text="t('actions.validate')" confirm-class="btn-success"
            variant="success" />
        <RejectModal :show="showSuspendreModal" :title="t('actions.suspend')" :message="t('messages.confirm.suspend')"
            :motifLabel="t('fields.suspendReason')" :confirmText="t('actions.suspend')" confirmClass="btn-warning"
            @close="showSuspendreModal = false" @confirm="suspendreEmploye" />
        <RejectModal :show="showBloquerModal" :title="t('actions.block')" :message="t('messages.confirm.block')"
            :motifLabel="t('fields.blockReason')" :confirmText="t('actions.block')" confirmClass="btn-danger"
            @close="showBloquerModal = false" @confirm="bloquerEmploye" />
        <!-- Loader pleine page -->
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage"
            :variant="loaderVariant" />
    </div>
</template>
