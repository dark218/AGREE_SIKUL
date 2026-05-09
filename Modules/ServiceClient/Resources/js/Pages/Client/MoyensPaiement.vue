<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import { useLoader } from '@/composables/useLoader';
import { usePermissions } from '@/Composables/usePermissions';
import { debounce } from 'lodash';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showLoader, hideLoader } = useLoader();
const { can } = usePermissions();
const props = defineProps({
    moyensPaiement: Object,
    client: Object,
    filters: Object,
    types: Array,
    statuts: Array,
    defautOptions: Array,
    fournisseurs: Array,
    stats: Object,
});
// État des filtres
const search = ref(props.filters?.search || '');
const selectedType = ref(props.filters?.type || '');
const selectedStatut = ref(props.filters?.statut || '');
const selectedDefaut = ref(props.filters?.is_defaut || '');
const selectedFournisseur = ref(props.filters?.fournisseur_id || '');
// Fonction de recherche avec debounce
const performSearch = debounce(() => {
    router.get(route('client.moyens-paiement', props.client.uuid), {
        search: search.value,
        type: selectedType.value,
        statut: selectedStatut.value,
        is_defaut: selectedDefaut.value,
        fournisseur_id: selectedFournisseur.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}, 300);
// Watchers pour les filtres
watch([search, selectedType, selectedStatut, selectedDefaut, selectedFournisseur], () => {
    performSearch();
    });
// Fonction pour réinitialiser les filtres
const resetFilters = () => {
    search.value = '';
    selectedType.value = '';
    selectedStatut.value = '';
    selectedDefaut.value = '';
    selectedFournisseur.value = '';
};
const toggleStatut = (moyenPaiement) => {
    showLoader('Modification du statut...', 'Veuillez patienter');
    router.post(route('client.moyens-paiement.toggle-statut', [props.client.uuid, moyenPaiement.id]), {}, {
        preserveScroll: true,
        onFinish: () => { hideLoader(); }
    });
};
const toggleDefaut = (moyenPaiement) => {
    showLoader('Définition du moyen par défaut...', 'Veuillez patienter');
    router.post(route('client.moyens-paiement.toggle-defaut', [props.client.uuid, moyenPaiement.id]), {}, {
        preserveScroll: true,
        onFinish: () => { hideLoader(); }
    });
};
const getStatutBadgeClass = (statut) => {
    return statut === 'actif' ? 'bg-success' : 'bg-secondary';
};
</script>
<template>
    <Head :title="'Moyens de paiement - ' + client.nom + ' ' + client.prenoms" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <Link :href="route('client.index')" class="text-decoration-none">
                            {{ t('modules.service_client.client.title') }}
                        </Link>
                    </li>
                    <li class="breadcrumb-item">
                        <Link :href="route('client.show', client.uuid)" class="text-decoration-none">
                            {{ client.nom }} {{ client.prenoms }}
                        </Link>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">{{ t('modules.service_client.moyen_paiement.title') }}</li>
                </ol>
            </nav>
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('modules.service_client.moyen_paiement.title') }} - {{ client.nom }} {{ client.prenoms }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div v-if="can('client-create')" class="dashboard-btn">
                        <Link :href="route('client.moyens-paiement.create', client.uuid)" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>
            <AlertMessage />
            <!-- Filtres et recherche -->
            <div class="d-flex gap-3 mb-4 align-items-center flex-wrap">
                <!-- Recherche -->
                <div style="width: 200px;">
                    <input
                        v-model="search"
                        type="text"
                        class="form-control"
                        :placeholder="t('modules.service_client.moyen_paiement.libelle')"
                    />
                </div>
                <!-- Filtre Type -->
                <div style="width: 200px;">
                    <StylishSelect
                        v-model="selectedType"
                        :options="types"
                        option-value="value"
                        option-label="label"
                        :placeholder="t('modules.service_client.moyen_paiement.type')"
                    />
                </div>
                <!-- Filtre Statut -->
                <div style="width: 180px;">
                    <StylishSelect
                        v-model="selectedStatut"
                        :options="statuts"
                        option-value="value"
                        option-label="label"
                        :placeholder="t('common.status')"
                    />
                </div>
                <!-- Filtre Défaut -->
                <div style="width: 150px;">
                    <StylishSelect
                        v-model="selectedDefaut"
                        :options="defautOptions"
                        option-value="value"
                        option-label="label"
                        :placeholder="t('common.default')"
                    />
                </div>
                <!-- Filtre Fournisseur -->
                <div style="width: 200px;">
                    <StylishSelect
                        v-model="selectedFournisseur"
                        :options="fournisseurs"
                        option-value="value"
                        option-label="label"
                        :placeholder="t('modules.service_client.moyen_paiement.fournisseur')"
                    />
                </div>
                <!-- Bouton Recherche -->
                <button
                    @click="performSearch"
                    class="btn btn-dark px-4"
                    style="height: 45px;">
                    <i class="fa fa-search"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="table-wrapper">
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>{{ t('modules.service_client.moyen_paiement.type') }}</th>
                                    <th>{{ t('modules.service_client.moyen_paiement.details_column') }}</th>
                                    <th>{{ t('common.status') }}</th>
                                    <th>{{ t('common.default') }}</th>
                                    <th class="fit">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="moyensPaiement.data && moyensPaiement.data.length > 0"
                                    v-for="moyen in moyensPaiement.data" :key="moyen.id">
                                    <td>{{ moyen.type_label }}</td>
                                    <td>
                                        <div>
                                            <strong>{{ t('modules.service_client.moyen_paiement.fournisseur') }}:</strong> {{ moyen.fournisseur?.nom || '-' }}<br>
                                            <strong>{{ t('modules.service_client.moyen_paiement.identifiant') }}:</strong> {{ moyen.identifiant_masque }}
                                            <div v-if="moyen.label" class="text-muted small mt-1">
                                                {{ moyen.label }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            
                                            <div v-if="can('client-edit')" class="form-check form-switch mb-0">
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    :id="'toggle-statut-' + moyen.id"
                                                    :checked="moyen.statut === 'actif'"
                                                    @change="toggleStatut(moyen)"
                                                    role="switch"
                                                    :title="moyen.statut === 'actif' ? t('actions.deactivate') : t('actions.activate')">
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span v-if="moyen.is_defaut" class="badge bg-primary rounded-pill">
                                            <i class="fa fa-star"></i> {{ t('common.default') }}
                                        </span>
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td class="fit">
                                        <div class="action-buttons">
                                            <Link :href="route('client.moyens-paiement.show', [client.uuid, moyen.id])"
                                                  class="btn btn-secondary btn-sm"
                                                  :title="t('actions.view')">
                                                <i class="fa fa-eye"></i>
                                            </Link>
                                            <Link v-if="can('client-edit')"
                                                  :href="route('client.moyens-paiement.edit', [client.uuid, moyen.id])"
                                                  class="btn btn-primary btn-sm"
                                                  :title="t('actions.edit')">
                                                <i class="fa fa-edit"></i>
                                            </Link>
                                            <button v-if="can('client-edit') && !moyen.is_defaut"
                                                    @click="toggleDefaut(moyen)"
                                                    class="btn btn-info btn-sm"
                                                    :title="t('actions.setAsDefault')">
                                                <i class="fa fa-star"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-else>
                                    <td colspan="5" class="text-center">{{ t('common.emptyList') }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <Pagination :data="moyensPaiement" :preserve-scroll="true" :preserve-state="true" />
                    </div>
                </div>
            </div>
        </div>
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
<style scoped>
.breadcrumb-item a:hover,
.breadcrumb-item a:active,
.breadcrumb-item a:focus {
    color: #0a58ca !important;
    text-decoration: underline;
}
</style>
