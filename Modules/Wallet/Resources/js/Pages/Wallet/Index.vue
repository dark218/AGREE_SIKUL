<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
import { usePermissions } from '@/Composables/usePermissions';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, hideLoader } = useLoader();
const { can } = usePermissions();
const props = defineProps({
    wallets: Object,
    paysDevises: Array,
    statuts: Array,
    ownerTypes: Array,
    filters: Object,
    paysCurrent: [Number, String],
});
const statutOptions = computed(() => {
    return (props.statuts || []).map(item => ({
        id: item.value,
        label: t(`modules.wallet.statuts.${item.value}`)
    }));
});
const ownerTypeOptions = computed(() => {
    return (props.ownerTypes || []).map(item => ({
        id: item.value,
        label: t(`modules.wallet.ownerTypes.${item.value}`)
    }));
});
const paysDeviseOptions = computed(() => {
    return (props.paysDevises || []).map(item => ({
        id: item.id,
        label: `${item.pays?.libelle || ''} - ${item.devise?.code || ''}`
    }));
});
const searchFilters = ref({
    owner_type: props.filters?.owner_type || '',
    statut: props.filters?.statut || '',
    pays_devise_id: props.filters?.pays_devise_id || '',
    search: props.filters?.search || '',
});
function search() {
    router.get(route('wallet.index'), {
        owner_type: searchFilters.value.owner_type || undefined,
        statut: searchFilters.value.statut || undefined,
        pays_devise_id: searchFilters.value.pays_devise_id || undefined,
        search: searchFilters.value.search || undefined,
    }, { preserveState: true, preserveScroll: true });
}
function getStatutBadgeClass(status) {
    const classes = {
        'actif': 'bg-success',
        'suspendu': 'bg-warning',
        'ferme': 'bg-danger'
};
return classes[status] || 'bg-secondary';
}
function getStatutLabel(status) {
    return t(`modules.wallet.statuts.${status}`) || status;
}
function getOwnerTypeLabel(type) {
    return t(`modules.wallet.ownerTypes.${type}`) || type;
}
function getOwnerTypeBadgeClass(type) {
    const classes = {
        'client': 'bg-info',
        'marchand': 'bg-primary',
        'agent': 'bg-secondary',
        'plateforme': 'bg-dark'
};
return classes[type] || 'bg-secondary';
}
</script>
<template>
    <Head :title="t('modules.wallet.title')" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('modules.wallet.title') }}</h4>
            </div>
            <AlertMessage />
            <div class="row m-0 mb-3">
                <form class="row col-12" @submit.prevent="search">
                    <div class="col-2 p-1">
                        <input v-model="searchFilters.search" type="text" class="form-control search-slt" :placeholder="t('actions.search')">
                    </div>
                    <div class="col-2 p-1">
                        <StylishSelect v-model="searchFilters.owner_type" :options="ownerTypeOptions" option-value="id" option-label="label" :placeholder="t('modules.wallet.fields.ownerType')" :searchable="false" />
                    </div>
                    <div class="col-2 p-1">
                        <StylishSelect v-model="searchFilters.statut" :options="statutOptions" option-value="id" option-label="label" :placeholder="t('common.status')" :searchable="false" />
                    </div>
                    <div class="col-2 p-1">
                        <StylishSelect v-model="searchFilters.pays_devise_id" :options="paysDeviseOptions" option-value="id" option-label="label" :placeholder="t('fields.countryCurrency')" :searchable="true" />
                    </div>
                    <div class="col-2 p-1">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i></button>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div class="table-wrapper">
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>{{ t('modules.wallet.fields.ownerType') }}</th>
                                    <th>{{ t('fields.name') }}</th>
                                    <th>{{ t('fields.email') }}</th>
                                    <th>{{ t('fields.login') }}</th>
                                    <th>{{ t('fields.countryCurrency') }}</th>
                                    <th>{{ t('modules.wallet.fields.solde') }}</th>
                                    <th>{{ t('modules.wallet.fields.soldeBloque') }}</th>
                                    <th>{{ t('common.status') }}</th>
                                    <th>{{ t('fields.createdAt') }}</th>
                                    <th class="fit">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="wallets.data && wallets.data.length > 0" v-for="wallet in wallets.data" :key="wallet.id">
                                    <td>
                                        <span :class="['badge text-white rounded-pill', getOwnerTypeBadgeClass(wallet.owner_type)]">
                                            {{ getOwnerTypeLabel(wallet.owner_type) }}
                                        </span>
                                    </td>
                                    <td>{{ wallet.owner_nom }} {{ wallet.owner_prenoms }}</td>
                                    <td>{{ wallet.owner_email }}</td>
                                    <td>{{ wallet.owner_login }}</td>
                                    <td>{{ wallet.pays_devise }}</td>
                                    <td class="text-end fw-bold">{{ wallet.solde }}</td>
                                    <td class="text-end">{{ wallet.solde_bloque }}</td>
                                    <td>
                                        <span :class="['badge text-dark rounded-pill', getStatutBadgeClass(wallet.statut)]">
                                            {{ getStatutLabel(wallet.statut) }}
                                        </span>
                                    </td>
                                    <td>{{ wallet.created_at }}</td>
                                    <td class="fit">
                                        <div class="action-buttons">
                                            <Link :href="route('wallet.show', wallet.id)" class="btn btn-secondary btn-sm" :title="t('actions.view')">
                                                <i class="fa fa-eye"></i>
                                            </Link>
                                            <Link v-if="can('wallet-edit')" :href="route('wallet.edit', wallet.id)" class="btn btn-primary btn-sm" :title="t('actions.edit')">
                                                <i class="fa fa-edit"></i>
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-else>
                                    <td colspan="9" class="text-center">{{ t('common.emptyList') }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <Pagination :data="wallets" :preserve-scroll="true" :preserve-state="true" />
                    </div>
                </div>
            </div>
        </div>
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
