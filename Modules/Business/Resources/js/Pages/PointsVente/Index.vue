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
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showValidateLoader, showRejectLoader, showSuspendLoader, showBlockLoader, hideLoader } = useLoader();
const { can } = usePermissions();
const props = defineProps({
    pointventes: Object,
    pays: Array,
    zones: Array,
    statuts: Array,
    filters: Object,
    paysCurrent: [Number, String, null],
    userStatuts: Object,
});
const searchFilters = ref({
    pays_id: props.filters?.pays_id || props.paysCurrent || '',
    nom: props.filters?.nom || '',
    tel: props.filters?.tel || '',
    zone_id: props.filters?.zone_id || '',
    adresse: props.filters?.adresse || '',
    raison_sociale: props.filters?.raison_sociale || '',
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
const showRejectModal = ref(false);
const showValidateModal = ref(false);
const showSuspendreModal = ref(false);
const showBloquerModal = ref(false);
const pointventeToDelete = ref(null);
const pointventeToReject = ref(null);
const pointventeToValidate = ref(null);
const pointventeToSuspendre = ref(null);
const pointventeToBloquer = ref(null);
const expandedPointVente = ref(null);
const search = () => {
    router.get(route('pointvente.index'), searchFilters.value, {
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
  router.get(route('pointvente.index'));
};
const confirmDelete = (pointvente) => {
    pointventeToDelete.value = pointvente;
    showDeleteModal.value = true;
};
const deletePointVente = () => {
    if (pointventeToDelete.value) {
        showDeleteLoader();
        router.put(route('pointvente.statut', pointventeToDelete.value.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                pointventeToDelete.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const openValidateModal = (pointvente) => {
    pointventeToValidate.value = pointvente;
    showValidateModal.value = true;
};
const validerPointVente = () => {
    if (pointventeToValidate.value) {
        showValidateLoader();
        router.post(route('pointvente.validation', [pointventeToValidate.value.id, 'valider']), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showValidateModal.value = false;
                pointventeToValidate.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const openRejectModal = (pointvente) => {
    pointventeToReject.value = pointvente;
    showRejectModal.value = true;
};
const rejeterPointVente = (motif) => {
    if (pointventeToReject.value) {
        showRejectLoader();
        router.post(route('pointvente.validation', [pointventeToReject.value.id, 'rejeter']), { motif }, {
            preserveScroll: true,
            onSuccess: () => {
                showRejectModal.value = false;
                pointventeToReject.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const openSuspendreModal = (pv) => {
    pointventeToSuspendre.value = pv;
    showSuspendreModal.value = true;
};
const openBloquerModal = (pv) => {
    pointventeToBloquer.value = pv;
    showBloquerModal.value = true;
};
const suspendrePointVente = (motif) => {
    if (pointventeToSuspendre.value) {
        showSuspendLoader();
        router.post(route('pointvente.suspendre', pointventeToSuspendre.value.id), { motif }, {
            preserveScroll: true,
            onSuccess: () => {
                showSuspendreModal.value = false;
                pointventeToSuspendre.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const bloquerPointVente = (motif) => {
    if (pointventeToBloquer.value) {
        showBlockLoader();
        router.post(route('pointvente.bloquer', pointventeToBloquer.value.id), { motif }, {
            preserveScroll: true,
            onSuccess: () => {
                showBloquerModal.value = false;
                pointventeToBloquer.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const toggleExpand = (pv) => {
    if (expandedPointVente.value === pv.id) {
        expandedPointVente.value = null;
    } else {
        expandedPointVente.value = pv.id;
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
        [statuts.non_actif]: t('statuts.inactif'),
        [statuts.actif]: t('statuts.actif'),
        [statuts.suspendu]: t('statuts.suspendu'),
        [statuts.bloque]: t('statuts.bloque'),
        [statuts.supprime]: t('statuts.supprime'),
    };
    return labels[status] || status;
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
                <h4 class="title">{{ t('modules.business.pointsvente.title') }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('pointvente-create')">
                        <Link :href="route('pointvente.create')" class="btn btn-primary">
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
                        <StylishSelect v-model="searchFilters.zone_id" :options="zones" option-value="id"
                            option-label="libelle" :placeholder="t('fields.zone')" />
                    </div>
                    <div class="col-2 p-1">
                        <input type="text" v-model="searchFilters.raison_sociale" class="form-control"
                            :placeholder="t('fields.businessName')" />
                    </div>
                    <div class="col-2 p-1">
                        <input type="text" v-model="searchFilters.nom" class="form-control"
                            :placeholder="t('fields.name')" />
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
                                    <th>{{ t('fields.zone') }}</th>
                                    <th>{{ t('fields.merchant') }}</th>
                                    <th>{{ t('fields.name') }}</th>
                                    <th>{{ t('fields.address') }}</th>
                                    <th>{{ t('fields.phone') }}</th>
                                    <th>{{ t('common.status') }}</th>
                                    <th class="fit">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="pointventes.data && pointventes.data.length > 0">
                                    <template v-for="pv in pointventes.data" :key="pv.id">
                                        <tr>
                                            <td>
                                                <button v-if="pv.employes_count > 0" type="button" class="expand-toggle"
                                                    :class="expandedPointVente === pv.id ? 'btn-danger' : 'btn-info'"
                                                    @click="toggleExpand(pv)">
                                                    <i
                                                        :class="expandedPointVente === pv.id ? 'fa fa-chevron-up' : 'fa fa-chevron-down'"></i>
                                                </button>
                                            </td>
                                            <td>{{ pv.pays }}</td>
                                            <td>{{ pv.zone }}</td>
                                            <td>{{ pv.marchand }}</td>
                                            <td>{{ pv.nom }}</td>
                                            <td>{{ pv.adresse }}</td>
                                            <td>{{ pv.telephone }}</td>
                                            <td>
                                                <span
                                                    :class="['badge text-dark rounded-pill', getStatutBadgeClass(pv.statut)]">
                                                    {{ getStatutLabel(pv.statut) }}
                                                </span>
                                            </td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('pointvente.show', pv.id)"
                                                        class="btn btn-secondary btn-sm" :title="t('actions.view')">
                                                        <i class="fa fa-eye"></i>
                                                    </Link>
                                                    <Link v-if="can('pointvente-edit')"
                                                        :href="route('pointvente.edit', pv.id)"
                                                        class="btn btn-primary btn-sm" :title="t('actions.edit')">
                                                        <i class="fa fa-edit"></i>
                                                    </Link>
                                                    <button v-if="can('pointvente-statut')" type="button"
                                                        class="btn btn-danger btn-sm" @click="confirmDelete(pv)"
                                                        :title="t('actions.delete')">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                    <button
                                                        v-if="can('pointvente-validate') && (pv.statut === userStatuts?.non_actif || pv.statut === userStatuts?.bloque || pv.statut === userStatuts?.suspendu)"
                                                        type="button" class="btn btn-success btn-sm"
                                                        @click="openValidateModal(pv)" :title="t('actions.validate')">
                                                        <i class="fa fa-check"></i>
                                                    </button>
                                                    <button
                                                        v-if="can('pointvente-validate') && (pv.statut === userStatuts?.non_actif || pv.statut === userStatuts?.bloque || pv.statut === userStatuts?.suspendu)"
                                                        type="button" class="btn btn-warning btn-sm"
                                                        @click="openRejectModal(pv)" :title="t('actions.reject')">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                    <button v-if="can('pointvente-validate') && pv.statut === userStatuts?.actif" type="button"
                                                        class="btn btn-warning btn-sm" @click="openSuspendreModal(pv)"
                                                        :title="t('actions.suspend')">
                                                        <i class="fa fa-pause"></i>
                                                    </button>
                                                    <button v-if="can('pointvente-validate') && pv.statut === userStatuts?.actif" type="button"
                                                        class="btn btn-danger btn-sm" @click="openBloquerModal(pv)"
                                                        :title="t('actions.block')">
                                                        <i class="fa fa-ban"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Employés sous-tableau -->
                                        <tr
                                            v-if="expandedPointVente === pv.id && pv.employes && pv.employes.length > 0">
                                            <td colspan="9" class="p-0">
                                                <div class="sub-table-wrapper"
                                                    style="background-color: #f8f9fa; padding: 15px; margin: 0;">
                                                    <h6 class="mb-2 text-start">{{ t('modules.business.employes.title') }}</h6>
                                                    <table class="custom-table sub-table">
                                                        <thead>
                                                            <tr style="background-color: #343a40; color: white;">
                                                                <th>{{ t('fields.employeeCode') }}</th>
                                                                <th>{{ t('fields.name') }}</th>
                                                                <th>{{ t('fields.firstName') }}</th>
                                                                <th>{{ t('fields.phone') }}</th>
                                                                <th>{{ t('fields.employeeType') }}</th>
                                                                <th>{{ t('common.status') }}</th>
                                                                <th class="fit">{{ t('common.actions') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="emp in pv.employes" :key="emp.id">
                                                                <td>{{ emp.code_employe }}</td>
                                                                <td>{{ emp.nom }}</td>
                                                                <td>{{ emp.prenoms }}</td>
                                                                <td>{{ emp.telephone }}</td>
                                                                <td>{{ emp.type_employe }}</td>
                                                                <td>
                                                                    <span
                                                                        :class="['badge text-dark rounded-pill', getStatutBadgeClass(emp.statut)]">
                                                                        {{ getStatutLabel(emp.statut) }}
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <Link :href="route('employe.show', emp.id)"
                                                                        class="btn btn-secondary btn-sm">
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
                                    <td colspan="9" class="text-center">{{ t('common.emptyList') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :data="pointventes" />
                </div>
            </div>
        </div>
        <ConfirmModal :show="showDeleteModal" :title="t('messages.confirm.delete.title')"
            :message="pvToDelete?.etat === 'actif' || pvToDelete?.statut === 'actif' ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message')" :sub-message="t('messages.confirm.delete.warning')"
            @close="showDeleteModal = false" @confirm="deletePointVente" confirm-text="Supprimer"
            confirm-class="btn-danger" />
        <RejectModal :show="showRejectModal" :title="t('messages.confirm.reject.title')"
            @close="showRejectModal = false" @confirm="rejeterPointVente" />
        <ConfirmModal :show="showValidateModal" :title="t('messages.confirm.validate.title')"
            :message="t('messages.confirm.validate.message')" @close="showValidateModal = false"
            @confirm="validerPointVente" :confirm-text="t('actions.validate')" confirm-class="btn-success"
            variant="success" />
        <RejectModal :show="showSuspendreModal" :title="t('actions.suspend')" :message="t('messages.confirm.suspend')"
            :motifLabel="t('fields.suspendReason')" :confirmText="t('actions.suspend')" confirmClass="btn-warning"
            @close="showSuspendreModal = false" @confirm="suspendrePointVente" />
        <RejectModal :show="showBloquerModal" :title="t('actions.block')" :message="t('messages.confirm.block')"
            :motifLabel="t('fields.blockReason')" :confirmText="t('actions.block')" confirmClass="btn-danger"
            @close="showBloquerModal = false" @confirm="bloquerPointVente" />
        <!-- Loader pleine page -->
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
