<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '@/Composables/usePermissions';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import FilterBar from '@/Components/Common/FilterBar.vue';

defineOptions({ layout: DashboardLayout });

const page = usePage();
const { t } = useI18n();
const { can } = usePermissions();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, showActivateLoader, showDeactivateLoader, hideLoader } = useLoader();

const props = defineProps({
    structures: { type: Object, required: true },
    campuses: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const searchFilters = ref({
    search: props.filters?.search || '',
    campus_id: props.filters?.campus_id || '',
    statut_disponibilite: props.filters?.statut_disponibilite || '',
    etat: props.filters?.etat || '',
});

const dispoOptions = [
    { id: 'disponible', libelle: 'Disponible' },
    { id: 'indisponible', libelle: 'Indisponible' },
    { id: 'maintenance', libelle: 'En maintenance' },
];
const etatOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];
const campusesOptions = computed(() => props.campuses.map(c => ({ id: c.id, libelle: c.nom || c.libelle })));

const filterFields = computed(() => [
    { key: 'search', type: 'text', placeholder: 'Rechercher…', icon: 'fa-search', width: '220px' },
    { key: 'campus_id', type: 'select', placeholder: 'Campus', options: campusesOptions.value, optionValue: 'id', optionLabel: 'libelle', width: '180px' },
    { key: 'statut_disponibilite', type: 'select', placeholder: 'Disponibilité', options: dispoOptions, optionValue: 'id', optionLabel: 'libelle', width: '170px' },
    { key: 'etat', type: 'select', placeholder: 'Statut', options: etatOptions, optionValue: 'id', optionLabel: 'libelle', width: '150px' },
]);

const search = () => {
    router.get(route('academique.bibliotheque-structures.index'), searchFilters.value, { preserveState: true, preserveScroll: true });
};
const resetFilters = () => {
    Object.keys(searchFilters.value).forEach(k => { searchFilters.value[k] = ''; });
    router.get(route('academique.bibliotheque-structures.index'));
};

const showModal = ref(false);
const itemId = ref(null);
const modalMode = ref('delete');

const confirmDelete = (id) => { itemId.value = id; modalMode.value = 'delete'; showModal.value = true; };
const confirmToggle = (item) => { itemId.value = item.id; modalMode.value = item.etat === 'actif' ? 'deactivate' : 'activate'; showModal.value = true; };

const handleConfirm = () => {
    if (modalMode.value === 'delete') {
        showDeleteLoader();
        router.delete(route('academique.bibliotheque-structures.destroy', itemId.value), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
            onFinish: () => hideLoader(),
        });
    } else {
        modalMode.value === 'activate' ? showActivateLoader() : showDeactivateLoader();
        router.put(route('academique.bibliotheque-structures.statut', itemId.value), {}, {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
            onFinish: () => hideLoader(),
        });
    }
};

const dispoBadge = (s) => ({
    disponible: 'bg-success',
    indisponible: 'bg-danger',
    maintenance: 'bg-warning',
}[s] || 'bg-secondary');
</script>

<template>
    <Head :title="t('fields.libraries') || 'Bibliothèques'" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('fields.libraries') || 'Bibliothèques' }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('bibliotheque-structures-create')">
                        <Link :href="route('academique.bibliotheque-structures.create')" class="btn btn-primary">{{ t('actions.add') || 'Ajouter' }}</Link>
                    </div>
                </div>
            </div>

            <AlertMessage />

            <div class="row m-0">
                <FilterBar v-model="searchFilters" :fields="filterFields" @search="search" @reset="resetFilters" />

                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('fields.code') || 'Code' }}</th>
                                        <th>{{ t('fields.label') || 'Libellé' }}</th>
                                        <th>{{ t('fields.location') || 'Localisation' }}</th>
                                        <th>{{ t('fields.campus') || 'Campus' }}</th>
                                        <th>{{ t('fields.manager') || 'Responsable' }}</th>
                                        <th>{{ t('fields.availability_status') || 'Disponibilité' }}</th>
                                        <th>{{ t('fields.status') || 'État' }}</th>
                                        <th class="fit">{{ t('common.actions') || 'Actions' }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="structures?.data && structures.data.length > 0">
                                        <tr v-for="item in structures.data" :key="item.id">
                                            <td>{{ item.code || '-' }}</td>
                                            <td><strong>{{ item.libelle }}</strong></td>
                                            <td>{{ item.localisation || '-' }}</td>
                                            <td>{{ item.campus?.nom || '-' }}</td>
                                            <td>{{ item.responsable || '-' }}</td>
                                            <td><span class="badge" :class="dispoBadge(item.statut_disponibilite)">{{ item.statut_disponibilite }}</span></td>
                                            <td><span class="badge" :class="item.etat === 'actif' ? 'bg-success' : 'bg-danger'">{{ item.etat }}</span></td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('academique.bibliotheque-structures.show', item.id)" class="btn btn-secondary"><span class="fa fa-eye"></span></Link>
                                                    <Link :href="route('academique.bibliotheque-structures.edit', item.id)" class="btn btn-primary"><span class="fa fa-edit"></span></Link>
                                                    <button @click="confirmDelete(item.id)" class="btn btn-danger"><span class="fa fa-trash"></span></button>
                                                    <button v-if="item.etat === 'actif'" @click="confirmToggle(item)" class="btn btn-danger"><span class="fa fa-ban"></span></button>
                                                    <button v-else @click="confirmToggle(item)" class="btn btn-success"><span class="fa fa-check"></span></button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="8" class="text-center">{{ t('common.no_data') || 'Aucune donnée' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <Pagination :data="structures" :preserve-scroll="true" />
        </div>

        <ConfirmModal
            :show="showModal"
            :title="modalMode === 'delete' ? 'Supprimer' : (modalMode === 'deactivate' ? 'Désactiver' : 'Activer')"
            :message="'Confirmer cette action ?'"
            @confirm="handleConfirm"
            @update:show="showModal = $event"
            @close="showModal = false"
        />

        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
