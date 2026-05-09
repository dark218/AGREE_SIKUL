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
import { useLoader } from '@/Composables/useLoader';
import { usePermissions } from '@/Composables/usePermissions';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showValidateLoader, showSuspendLoader, hideLoader } = useLoader();
const { can } = usePermissions();
const props = defineProps({
    terminaux: Object,
    pointsVente: Array,
    marchands: Array,
    typesTerminaux: Array,
    statuts: Array,
    filters: Object,
});
// Computed pour traduire les labels des types de terminaux
const translatedTypesTerminaux = computed(() => {
    return props.typesTerminaux.map(type => ({
        ...type,
        label: t(`modules.business.terminaux.types.${type.value}`)
    }));
});
// Computed pour traduire les labels des statuts
const translatedStatuts = computed(() => {
    return props.statuts.map(statut => ({
        ...statut,
        label: t(`modules.business.terminaux.statuts.${statut.value}`)
    }));
});
// Fonction pour traduire le type dans la table
const getTranslatedTypeLabel = (type) => {
    return t(`modules.business.terminaux.types.${type}`);
};
// Fonction pour traduire le statut dans la table
const getTranslatedStatutLabel = (statut) => {
    return t(`modules.business.terminaux.statuts.${statut}`);
};
const searchFilters = ref({
    type_terminal: props.filters?.type_terminal || '',
    statut: props.filters?.statut || '',
    points_vente_id: props.filters?.points_vente_id || '',
    raison_sociale: props.filters?.raison_sociale || '',
    numero_serie: props.filters?.numero_serie || '',
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
const showSuspendreModal = ref(false);
const showRetirerModal = ref(false);
const terminalToDelete = ref(null);
const terminalToValidate = ref(null);
const terminalToSuspendre = ref(null);
const terminalToRetirer = ref(null);
const search = () => {
    router.get(route('terminal.index'), searchFilters.value, {
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
  router.get(route('terminal.index'));
};
const confirmDelete = (terminal) => {
    terminalToDelete.value = terminal;
    showDeleteModal.value = true;
};
const deleteTerminal = () => {
    if (terminalToDelete.value) {
        showDeleteLoader();
        router.put(route('terminal.statut', terminalToDelete.value.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                terminalToDelete.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const openValidateModal = (terminal) => {
    terminalToValidate.value = terminal;
    showValidateModal.value = true;
};
const validerTerminal = () => {
    if (terminalToValidate.value) {
        showValidateLoader();
        router.post(route('terminal.valider', terminalToValidate.value.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showValidateModal.value = false;
                terminalToValidate.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const openSuspendreModal = (terminal) => {
    terminalToSuspendre.value = terminal;
    showSuspendreModal.value = true;
};
const suspendreTerminal = (motif) => {
    if (terminalToSuspendre.value) {
        showSuspendLoader();
        router.post(route('terminal.suspendre', terminalToSuspendre.value.id), { motif }, {
            preserveScroll: true,
            onSuccess: () => {
                showSuspendreModal.value = false;
                terminalToSuspendre.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const openRetirerModal = (terminal) => {
    terminalToRetirer.value = terminal;
    showRetirerModal.value = true;
};
const retirerTerminal = (motif) => {
    if (terminalToRetirer.value) {
        showDeleteLoader();
        router.post(route('terminal.retirer', terminalToRetirer.value.id), { motif }, {
            preserveScroll: true,
            onSuccess: () => {
                showRetirerModal.value = false;
                terminalToRetirer.value = null;
            },
            onFinish: () => {
                hideLoader();
            },
        });
    }
};
const getStatutBadgeClass = (status) => {
    const classes = {
        'deploye': 'bg-info',
        'actif': 'bg-success',
        'suspendu': 'bg-warning',
        'retire': 'bg-danger',
    };
    return classes[status] || 'bg-secondary';
};
const getTypeBadgeClass = (type) => {
    const classes = {
        'pos': 'bg-primary',
        'mobile': 'bg-info',
        'kiosk': 'bg-warning',
        'web': 'bg-success',
        'api': 'bg-secondary',
    };
    return classes[type] || 'bg-secondary';
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
    <Head :title="t('modules.business.terminaux.title')" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('modules.business.terminaux.title') }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('terminal-create')">
                        <Link :href="route('terminal.create')" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>
            <AlertMessage />
            <div class="row m-0 mb-3">
                <form class="row col-12" @submit.prevent="search">
                    <div class="col-2 p-1">
                        <input type="text" v-model="searchFilters.numero_serie" class="form-control"
                            :placeholder="t('fields.serialNumber')" />
                    </div>
                    <div class="col-2 p-1">
                        <input type="text" v-model="searchFilters.raison_sociale" class="form-control"
                            :placeholder="t('fields.businessName')" />
                    </div>
                    <div class="col-2 p-1">
                        <StylishSelect v-model="searchFilters.type_terminal" :options="translatedTypesTerminaux" option-value="value"
                            option-label="label" :placeholder="t('fields.terminalType')" />
                    </div>
                    <div class="col-2 p-1">
                        <StylishSelect v-model="searchFilters.statut" :options="translatedStatuts" option-value="value"
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
                                    <th>{{ t('fields.serialNumber') }}</th>
                                    <th>{{ t('fields.terminalType') }}</th>
                                    <th>{{ t('fields.manufacturer') }}</th>
                                    <th>{{ t('fields.model') }}</th>
                                    <th>{{ t('fields.pointOfSale') }}</th>
                                    <th>{{ t('fields.merchant') }}</th>
                                    <th>{{ t('common.status') }}</th>
                                    <th>{{ t('fields.lastCheckin') }}</th>
                                    <th class="fit">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="terminaux.data && terminaux.data.length > 0">
                                    <tr v-for="terminal in terminaux.data" :key="terminal.id">
                                        <td>{{ terminal.numero_serie }}</td>
                                        <td>
                                            <span>
                                                {{ getTranslatedTypeLabel(terminal.type_terminal) }}
                                            </span>
                                        </td>
                                        <td>{{ terminal.fabricant }}</td>
                                        <td>{{ terminal.modele }}</td>
                                        <td>{{ terminal.point_vente || '-' }}</td>
                                        <td>{{ terminal.marchand || '-' }}</td>
                                        <td>
                                            <span :class="['badge text-dark rounded-pill', getStatutBadgeClass(terminal.statut)]">
                                                {{ getTranslatedStatutLabel(terminal.statut) }}
                                            </span>
                                        </td>
                                        <td>{{ terminal.last_checkin || '-' }}</td>
                                        <td class="fit">
                                            <div class="action-buttons">
                                                <Link :href="route('terminal.show', terminal.id)"
                                                    class="btn btn-secondary btn-sm" :title="t('actions.view')">
                                                    <i class="fa fa-eye"></i>
                                                </Link>
                                                <Link v-if="can('terminal-edit')"
                                                    :href="route('terminal.edit', terminal.id)"
                                                    class="btn btn-primary btn-sm" :title="t('actions.edit')">
                                                    <i class="fa fa-edit"></i>
                                                </Link>
                                                <button v-if="can('terminal-statut')" type="button"
                                                    class="btn btn-danger btn-sm" @click="confirmDelete(terminal)"
                                                    :title="t('actions.delete')">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                                <button v-if="can('terminal-validate') && (terminal.statut === 'deploye' || terminal.statut === 'retire' || terminal.statut === 'suspendu')"
                                                    type="button" class="btn btn-success btn-sm"
                                                    @click="openValidateModal(terminal)" :title="t('actions.validate')">
                                                    <i class="fa fa-check"></i>
                                                </button>
                                                <button v-if="terminal.statut === 'actif'" type="button"
                                                    class="btn btn-warning btn-sm" @click="openSuspendreModal(terminal)"
                                                    :title="t('actions.suspend')">
                                                    <i class="fa fa-pause"></i>
                                                </button>
                                                <button v-if="terminal.statut === 'actif' || terminal.statut === 'suspendu'" type="button"
                                                    class="btn btn-danger btn-sm" @click="openRetirerModal(terminal)"
                                                    :title="t('actions.retire')">
                                                    <i class="fa fa-ban"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="9" class="text-center">{{ t('common.emptyList') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :data="terminaux" />
                </div>
            </div>
        </div>
        <ConfirmModal :show="showDeleteModal" :title="t('messages.confirm.delete.title')"
            :message="terminalToDelete?.etat === 'actif' || terminalToDelete?.statut === 'actif' ? t('messages.confirm.deactivate.message') : t('messages.confirm.activate.message')" :sub-message="t('messages.confirm.delete.warning')"
            @close="showDeleteModal = false" @confirm="deleteTerminal" confirm-text="Supprimer"
            confirm-class="btn-danger" />
        <ConfirmModal :show="showValidateModal" :title="t('messages.confirm.validate.title')"
            :message="t('messages.confirm.validate.message')" @close="showValidateModal = false"
            @confirm="validerTerminal" :confirm-text="t('actions.validate')" confirm-class="btn-success"
            variant="success" />
        <RejectModal :show="showSuspendreModal" :title="t('actions.suspend')" :message="t('messages.confirm.suspend')"
            :motifLabel="t('fields.suspendReason')" :confirmText="t('actions.suspend')" confirmClass="btn-warning"
            @close="showSuspendreModal = false" @confirm="suspendreTerminal" />
        <RejectModal :show="showRetirerModal" :title="t('actions.retire')" :message="t('messages.confirm.retire')"
            :motifLabel="t('fields.retireReason')" :confirmText="t('actions.retire')" confirmClass="btn-danger"
            @close="showRetirerModal = false" @confirm="retirerTerminal" />
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
