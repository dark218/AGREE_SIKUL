<script setup>
import { computed, ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '@/Composables/usePermissions';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { can } = usePermissions();
const page = usePage();

const props = defineProps({
    listes: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const showConfirmModal = ref(false);
const confirmAction = ref(null);
const confirmMessage = ref('');
const selectedId = ref(null);

const filteredListes = computed(() => props.listes.data || []);

const etatOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

// Debounced search
let searchTimeout;
const onSearch = (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('academique.listes-manuels.index'), {
            search: value,
            section_id: props.filters.section_id || '',
            etat: props.filters.etat || '',
            annee_scolaire_id: props.filters.annee_scolaire_id || '',
        }, { preserveState: true, replace: true });
    }, 500);
};

const filterByEtat = (etat) => {
    router.get(route('academique.listes-manuels.index'), {
        search: props.filters.search || '',
        section_id: props.filters.section_id || '',
        etat: etat || '',
        annee_scolaire_id: props.filters.annee_scolaire_id || '',
    }, { preserveState: true, replace: true });
};

const viewItem = (id) => {
    router.visit(route('academique.listes-manuels.show', id));
};

const editItem = (id) => {
    router.visit(route('academique.listes-manuels.edit', id));
};

const toggleStatus = (id, etat) => {
    selectedId.value = id;
    confirmAction.value = 'statut';
    confirmMessage.value = `Êtes-vous sûr de vouloir ${etat === 'actif' ? 'désactiver' : 'activer'} ce manuel?`;
    showConfirmModal.value = true;
};

const deleteItem = (id) => {
    selectedId.value = id;
    confirmAction.value = 'delete';
    confirmMessage.value = 'Êtes-vous sûr de vouloir supprimer ce manuel? Cette action ne peut pas être annulée.';
    showConfirmModal.value = true;
};

const handleConfirm = () => {
    if (confirmAction.value === 'statut') {
        router.put(route('academique.listes-manuels.statut', selectedId.value), {}, {
            onSuccess: () => {
                showConfirmModal.value = false;
            },
        });
    } else if (confirmAction.value === 'delete') {
        router.delete(route('academique.listes-manuels.destroy', selectedId.value), {
            onSuccess: () => {
                showConfirmModal.value = false;
            },
        });
    }
};
</script>

<template>
    <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-area d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="page-title">{{ t('modules.academique.listes_manuels') || 'Listes des manuels' }}</h3>
                        </div>
                        <div v-if="can('listes-manuels-create')">
                            <router-link :href="route('academique.listes-manuels.create')" class="btn btn-primary">
                                <i class="fa fa-plus"></i> {{ t('actions.add') || 'Ajouter' }}
                            </router-link>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <input
                                        type="text"
                                        class="form-control"
                                        :placeholder="t('actions.search') || 'Rechercher...'"
                                        :value="filters.search || ''"
                                        @input="onSearch($event.target.value)"
                                    >
                                </div>
                                <div class="col-md-6">
                                    <select class="form-control" @change="filterByEtat($event.target.value)">
                                        <option value="">{{ t('actions.select') || '-- Sélectionner --' }}</option>
                                        <option v-for="option in etatOptions" :key="option.id" :value="option.id" :selected="filters.etat === option.id">
                                            {{ option.libelle }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop View - Table -->
                    <div class="d-none d-lg-block">
                        <div v-if="filteredListes.length > 0" class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ t('fields.titre_manuel') || 'Titre' }}</th>
                                        <th>{{ t('fields.auteurs') || 'Auteur(s)' }}</th>
                                        <th>{{ t('fields.editeur') || 'Éditeur' }}</th>
                                        <th>{{ t('fields.section') || 'Section' }}</th>
                                        <th>{{ t('fields.annee_scolaire') || 'Année scolaire' }}</th>
                                        <th>{{ t('common.status') || 'État' }}</th>
                                        <th class="text-center">{{ t('actions.actions') || 'Actions' }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in filteredListes" :key="item.id">
                                        <td>
                                            <strong>{{ item.titre_manuel }}</strong>
                                        </td>
                                        <td>{{ item.auteurs || '-' }}</td>
                                        <td>{{ item.editeur || '-' }}</td>
                                        <td>{{ item.section?.libelle || '-' }}</td>
                                        <td>{{ item.annee_scolaire?.libelle || '-' }}</td>
                                        <td>
                                            <span :class="['badge', item.etat === 'actif' ? 'bg-success' : 'bg-danger']">
                                                {{ item.etat === 'actif' ? 'Actif' : 'Inactif' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-secondary" @click="viewItem(item.id)" title="Voir">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary" @click="editItem(item.id)" title="Éditer">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button
                                                type="button"
                                                :class="['btn', 'btn-sm', item.etat === 'actif' ? 'btn-warning' : 'btn-success']"
                                                @click="toggleStatus(item.id, item.etat)"
                                                :title="item.etat === 'actif' ? 'Désactiver' : 'Activer'"
                                            >
                                                <i :class="['fa', item.etat === 'actif' ? 'fa-ban' : 'fa-check']"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" @click="deleteItem(item.id)" title="Supprimer">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="alert alert-info">
                            {{ t('messages.no_data') || 'Aucune donnée' }}
                        </div>
                    </div>

                    <!-- Mobile View - Cards -->
                    <div class="d-lg-none">
                        <div v-if="filteredListes.length > 0" class="row g-3">
                            <div v-for="item in filteredListes" :key="item.id" class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h6 class="card-title">{{ item.titre_manuel }}</h6>
                                            <span :class="['badge', item.etat === 'actif' ? 'bg-success' : 'bg-danger']">
                                                {{ item.etat === 'actif' ? 'Actif' : 'Inactif' }}
                                            </span>
                                        </div>
                                        <p class="mb-2"><strong>Auteur(s):</strong> {{ item.auteurs || '-' }}</p>
                                        <p class="mb-2"><strong>Éditeur:</strong> {{ item.editeur || '-' }}</p>
                                        <p class="mb-2"><strong>Section:</strong> {{ item.section?.libelle || '-' }}</p>
                                        <p class="mb-3"><strong>Année scolaire:</strong> {{ item.annee_scolaire?.libelle || '-' }}</p>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-secondary flex-grow-1" @click="viewItem(item.id)">
                                                <i class="fa fa-eye"></i> Voir
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary flex-grow-1" @click="editItem(item.id)">
                                                <i class="fa fa-edit"></i> Éditer
                                            </button>
                                            <button
                                                type="button"
                                                :class="['btn', 'btn-sm', 'flex-grow-1', item.etat === 'actif' ? 'btn-warning' : 'btn-success']"
                                                @click="toggleStatus(item.id, item.etat)"
                                            >
                                                <i :class="['fa', item.etat === 'actif' ? 'fa-ban' : 'fa-check']"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger flex-grow-1" @click="deleteItem(item.id)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="alert alert-info">
                            {{ t('messages.no_data') || 'Aucune donnée' }}
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="listes.links" class="d-flex justify-content-center mt-4">
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <li v-for="link in listes.links" :key="link.url" :class="['page-item', { active: link.active, disabled: !link.url }]">
                                    <a v-if="link.url" :href="link.url" class="page-link" v-html="link.label"></a>
                                    <span v-else class="page-link" v-html="link.label"></span>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm Modal -->
        <ConfirmModal
            v-if="showConfirmModal"
            :title="confirmAction === 'delete' ? 'Supprimer' : 'Modifier le statut'"
            :message="confirmMessage"
            @confirm="handleConfirm"
            @close="showConfirmModal = false"
        />
</template>

<style scoped>
.page-title-area {
    margin-bottom: 2rem;
}

.page-title {
    color: #333;
    font-weight: 600;
}

.table thead {
    background-color: #f8f9fa;
}

.btn-sm {
    margin: 0 2px;
}
</style>
