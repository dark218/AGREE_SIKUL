<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '@/Composables/usePermissions';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import FilterBar from '@/Components/Common/FilterBar.vue';

defineOptions({ layout: DashboardLayout });
const page = usePage();
const { t } = useI18n();
const { can } = usePermissions();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showDeleteLoader, hideLoader } = useLoader();

const props = defineProps({
    title: String,
    genres: Object,
    filters: Object,
});

const searchFilters = ref({
    search: props.filters?.search || '',
    etat: props.filters?.etat || '',
});

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const filterFields = [
    { key: 'search', type: 'text', placeholder: 'Rechercher (code, libellé)…', icon: 'fa-search', width: '220px' },
    { key: 'etat', type: 'select', placeholder: 'Statut', options: statusOptions, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
];

let searchTimeout;
const showDeleteModal = ref(false);
const itemToDelete = ref(null);

const search = () => {
    router.get(route('parametrage.genres.index'), searchFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    searchFilters.value = { search: '', etat: '' };
    router.get(route('parametrage.genres.index'));
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const deleteItem = () => {
    if (!itemToDelete.value) return;
    showDeleteLoader();
    router.visit(route('parametrage.genres.destroy', itemToDelete.value.id), {
        method: 'delete',
        preserveScroll: true,
        onSuccess: () => { showDeleteModal.value = false; itemToDelete.value = null; },
        onFinish: () => hideLoader(),
    });
};

const toggleStatut = (item) => {
    router.visit(route('parametrage.genres.statut', item.id), { method: 'put', preserveScroll: true });
};

watch(() => searchFilters.value, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => search(), 400);
}, { deep: true });
</script>

<template>
    <Head :title="page.props.title" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('genres-create')">
                        <Link :href="route('parametrage.genres.create')" class="btn btn-primary">
                            {{ t('actions.add') || 'Ajouter' }}
                        </Link>
                    </div>
                </div>
            </div>

            <AlertMessage />

            <div class="row m-0">
                <FilterBar v-model="searchFilters" :fields="filterFields" @search="search" @reset="resetFilters">
                </FilterBar>

                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Libellé</th>
                                        <th>Symbole</th>
                                        <th>Couleur</th>
                                        <th>Ordre</th>
                                        <th>Statut</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="genres?.data && genres?.data.length > 0">
                                        <tr v-for="item in genres.data" :key="item.id">
                                            <td><strong>{{ item.code }}</strong></td>
                                            <td>{{ item.libelle }}</td>
                                            <td>{{ item.symbole || '-' }}</td>
                                            <td>
                                                <span v-if="item.couleur" :style="{ background: item.couleur, display: 'inline-block', width: '24px', height: '24px', borderRadius: '6px', border: '1px solid #cbd5e1' }"></span>
                                                <small class="ms-2 text-muted">{{ item.couleur || '-' }}</small>
                                            </td>
                                            <td>{{ item.ordre }}</td>
                                            <td>
                                                <span class="badge" :class="item.etat === 'actif' ? 'bg-success' : 'bg-danger'">
                                                    {{ item.etat === 'actif' ? 'Actif' : 'Inactif' }}
                                                </span>
                                            </td>
                                            <td class="fit">
                                                <div class="action-buttons">
                                                    <Link :href="route('parametrage.genres.show', item.id)" class="btn btn-secondary" title="Voir">
                                                        <span class="fa fa-eye"></span>
                                                    </Link>
                                                    <Link :href="route('parametrage.genres.edit', item.id)" class="btn btn-primary" title="Modifier">
                                                        <span class="fa fa-edit"></span>
                                                    </Link>
                                                    <button @click="toggleStatut(item)" class="btn" :class="item.etat === 'actif' ? 'btn-warning' : 'btn-success'" :title="item.etat === 'actif' ? 'Désactiver' : 'Activer'">
                                                        <span class="fa" :class="item.etat === 'actif' ? 'fa-ban' : 'fa-check'"></span>
                                                    </button>
                                                    <button @click="confirmDelete(item)" class="btn btn-danger" title="Supprimer">
                                                        <span class="fa fa-trash"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-else>
                                        <td colspan="7" class="text-center">Aucun genre trouvé</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :data="genres" />
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="showDeleteModal"
            @update:show="showDeleteModal = $event"
            title="Confirmer la suppression"
            message="Êtes-vous sûr de vouloir supprimer ce genre ? Impossible si des personnes l'utilisent."
            confirm-text="Supprimer"
            confirm-class="btn-danger"
            @confirm="deleteItem"
        />

        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
