<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '@/Composables/usePermissions';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import FilterBar from '@/Components/Common/FilterBar.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { can } = usePermissions();

const props = defineProps({
    classes: Object,
    allClasses: {
        type: Array,
        default: () => [],
    },
    filters: Object,
});

const handleDelete = (apprenantId) => {
    if (confirm(t('messages.confirm_delete') || 'Êtes-vous sûr de vouloir supprimer?')) {
        router.delete(route('academique.classes_apprenants.destroy', apprenantId), {
            onSuccess: () => {
                router.visit(route('academique.classes_apprenants.index'));
            },
        });
    }
};

// Filtres de recherche
const searchFilters = ref({
    search: props.filters?.search || '',
    classe_id: props.filters?.classe_id || '',
});

const filterFields = computed(() => [
    { key: 'search', type: 'text', placeholder: 'Rechercher...', icon: 'fa-search', width: '220px' },
    { key: 'classe_id', type: 'select', placeholder: 'Sélectionner une classe', options: props.allClasses, optionValue: 'id', optionLabel: 'libelle', width: '190px' },
]);

// Debounce timer for real-time search
let searchTimeout;

// Real-time search with debounce
const performSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        search();
    }, 500);
};

const search = () => {
    router.get(route('academique.classes_apprenants.index'), searchFilters.value, {
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
    router.get(route('academique.classes_apprenants.index'));
};

const page = usePage();

// Real-time search with debounce
watch(
    () => searchFilters.value,
    () => {
        performSearch();
    },
    { deep: true }
);
</script>

<template>
    <Head :title="t('modules.academique.classes_apprenants.index')" />
    <div class="body-wrapper">
        <div class="table-area">
            <!-- Header -->
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('modules.academique.classes_apprenants.index') || 'Classes des apprenants' }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('classes-apprenants-create')">
                        <Link :href="route('academique.classes_apprenants.create')" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Alert Message -->
            <AlertMessage />

            <div class="row m-0">
                <!-- Filtres de recherche -->
                <FilterBar v-model="searchFilters" :fields="filterFields" @search="search" @reset="resetFilters"></FilterBar>

                <!-- Tableau -->
                <div class="card-body">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>{{ t('fields.classe') || 'Classe' }}</th>
                                        <th>{{ t('fields.matricule') || 'Matricule' }}</th>
                                        <th>{{ t('fields.nom') || 'Nom' }}</th>
                                        <th>{{ t('fields.prenoms') || 'Prénoms' }}</th>
                                        <th>{{ t('fields.genre') || 'Genre' }}</th>
                                        <th>{{ t('fields.date_naissance') || 'Date de naissance' }}</th>
                                        <th>{{ t('fields.status') || 'État' }}</th>
                                        <th class="fit">{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="classes?.data && classes?.data.length > 0">
                                        <template v-for="classe in classes?.data" :key="classe.id">
                                            <template v-if="classe.apprenants && classe.apprenants.length > 0">
                                                <tr v-for="(apprenant, index) in classe.apprenants" :key="apprenant.id">
                                                    <td>
                                                        <span v-if="index === 0" class="badge bg-info">{{ classe.nom }}</span>
                                                    </td>
                                                    <td>{{ apprenant.matricule || '-' }}</td>
                                                    <td>{{ apprenant.nom || '-' }}</td>
                                                    <td>{{ apprenant.prenoms || '-' }}</td>
                                                    <td>
                                                        <span class="badge" :class="getGenreClass(apprenant.sexe)">
                                                            {{ getGenreLabel(apprenant.sexe) }}
                                                        </span>
                                                    </td>
                                                    <td>{{ formatDate(apprenant.date_naissance) }}</td>
                                                    <td>
                                                        <span class="badge" :class="apprenant.statut === 'actif' ? 'bg-success' : 'bg-danger'">
                                                            {{ t('common.' + apprenant.statut) }}
                                                        </span>
                                                    </td>
                                                    <td class="fit">
                                                        <div class="action-buttons">
                                                            <Link
                                                                :href="route('academique.classes_apprenants.show', apprenant.id)"
                                                                class="btn btn-secondary"
                                                                :title="t('actions.view')"
                                                            >
                                                                <span class="fa fa-eye"></span>
                                                            </Link>
                                                            <Link
                                                                :href="route('academique.classes_apprenants.edit', apprenant.id)"
                                                                class="btn btn-secondary"
                                                                :title="t('actions.edit')"
                                                            >
                                                                <span class="fa fa-edit"></span>
                                                            </Link>
                                                            <button
                                                                @click="handleDelete(apprenant.id)"
                                                                class="btn btn-danger"
                                                                :title="t('actions.delete')"
                                                            >
                                                                <span class="fa fa-trash"></span>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </template>
                                        </template>
                                    </template>
                                    <tr v-else>
                                        <td :colspan="8" class="text-center">{{ t('common.emptyList') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card View -->
                        <div class="mobile-card-list">
                            <template v-if="classes?.data && classes?.data.length > 0">
                                <template v-for="classe in classes?.data" :key="'m-' + classe.id">
                                    <template v-if="classe.apprenants && classe.apprenants.length > 0">
                                        <div v-for="apprenant in classe.apprenants" :key="'m-' + apprenant.id" class="mobile-card">
                                            <div class="mobile-card-body">
                                                <div class="mobile-card-row">
                                                    <span class="mobile-card-label">{{ t('fields.classe') || 'Classe' }}</span>
                                                    <span class="mobile-card-value badge bg-info">{{ classe.nom }}</span>
                                                </div>
                                                <div class="mobile-card-row">
                                                    <span class="mobile-card-label">{{ t('fields.matricule') || 'Matricule' }}</span>
                                                    <span class="mobile-card-value">{{ apprenant.matricule || '-' }}</span>
                                                </div>
                                                <div class="mobile-card-row">
                                                    <span class="mobile-card-label">{{ t('fields.nom') || 'Nom' }}</span>
                                                    <span class="mobile-card-value">{{ apprenant.nom || '-' }}</span>
                                                </div>
                                                <div class="mobile-card-row">
                                                    <span class="mobile-card-label">{{ t('fields.prenoms') || 'Prénoms' }}</span>
                                                    <span class="mobile-card-value">{{ apprenant.prenoms || '-' }}</span>
                                                </div>
                                                <div class="mobile-card-row">
                                                    <span class="mobile-card-label">{{ t('fields.genre') || 'Genre' }}</span>
                                                    <span class="mobile-card-value badge" :class="getGenreClass(apprenant.sexe)">
                                                        {{ getGenreLabel(apprenant.sexe) }}
                                                    </span>
                                                </div>
                                                <div class="mobile-card-row">
                                                    <span class="mobile-card-label">{{ t('fields.date_naissance') || 'Date de naissance' }}</span>
                                                    <span class="mobile-card-value">{{ formatDate(apprenant.date_naissance) }}</span>
                                                </div>
                                                <div class="mobile-card-row">
                                                    <span class="mobile-card-label">{{ t('fields.status') || 'État' }}</span>
                                                    <span class="mobile-card-value badge" :class="apprenant.statut === 'actif' ? 'bg-success' : 'bg-danger'">
                                                        {{ t('common.' + apprenant.statut) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="mobile-card-actions">
                                                <Link
                                                    :href="route('academique.classes_apprenants.show', apprenant.id)"
                                                    class="btn btn-secondary"
                                                    :title="t('actions.view')"
                                                >
                                                    <span class="fa fa-eye"></span>
                                                </Link>
                                                <Link
                                                    :href="route('academique.classes_apprenants.edit', apprenant.id)"
                                                    class="btn btn-secondary"
                                                    :title="t('actions.edit')"
                                                >
                                                    <span class="fa fa-edit"></span>
                                                </Link>
                                                <button
                                                    @click="handleDelete(apprenant.id)"
                                                    class="btn btn-danger"
                                                    :title="t('actions.delete')"
                                                >
                                                    <span class="fa fa-trash"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </template>
                            </template>
                            <div v-else class="mobile-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>{{ t('common.emptyList') }}</p>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <Pagination :data="classes" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    methods: {
        formatDate(date) {
            if (!date) return '-';
            return new Date(date).toLocaleDateString('fr-FR');
        },
        getGenreClass(genre) {
            if (genre === 'M' || genre === 'Masculin') return 'bg-info';
            if (genre === 'F' || genre === 'Féminin') return 'bg-warning';
            return 'bg-secondary';
        },
        getGenreLabel(genre) {
            if (genre === 'M' || genre === 'Masculin') return 'M';
            if (genre === 'F' || genre === 'Féminin') return 'F';
            return genre || '-';
        },
    },
};
</script>

<style scoped>
/* Layout */
.body-wrapper {
    padding: 20px;
    background: #f5f5f5;
}

.table-area {
    width: 100%;
}

.dashboard-header-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.title {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
    color: #2c3e50;
}

.dashboard-btn-wrapper {
    display: flex;
    gap: 10px;
}

.dashboard-btn {
    display: flex;
}

.row {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

form {
    width: 100%;
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card-body {
    width: 100%;
    background: white;
    padding: 0;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

/* Table Styles */
.table-wrapper {
    width: 100%;
}

.table-responsive {
    overflow-x: auto;
}

.custom-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.custom-table thead {
    background-color: #2c3e50;
    color: white;
}

.custom-table th {
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
    border: none;
}

.custom-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #e3e6f0;
}

.custom-table tbody tr:hover {
    background-color: #f8f9fa;
}

.custom-table th.fit,
.custom-table td.fit {
    width: 100px;
    text-align: center;
}

/* Badges */
.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.bg-success {
    background-color: #198754;
    color: white;
}

.bg-danger {
    background-color: #dc3545;
    color: white;
}

.bg-info {
    background-color: #0dcaf0;
    color: #000;
}

.bg-warning {
    background-color: #ffc107;
    color: #000;
}

.bg-secondary {
    background-color: #6c757d;
    color: white;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 4px;
    justify-content: center;
}

.btn {
    padding: 6px 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.2s ease;
}

.btn-primary {
    background-color: #0d6efd;
    color: white;
}

.btn-primary:hover {
    background-color: #0b5ed7;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #5c636a;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #bb2d3b;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 11px;
}

/* Mobile Styles */
.table-responsive {
    display: table;
    width: 100%;
}

.mobile-card-list {
    display: none;
}

@media (max-width: 768px) {
    .table-responsive {
        display: none;
    }

    .mobile-card-list {
        display: block;
    }

    .mobile-card {
        background: white;
        border: 1px solid #e3e6f0;
        border-radius: 8px;
        margin-bottom: 12px;
        overflow: hidden;
    }

    .mobile-card-body {
        padding: 12px;
    }

    .mobile-card-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .mobile-card-row:last-child {
        border-bottom: none;
    }

    .mobile-card-label {
        font-weight: 600;
        color: #495057;
        font-size: 12px;
    }

    .mobile-card-value {
        text-align: right;
        color: #2c3e50;
        font-size: 12px;
    }

    .mobile-card-actions {
        padding: 12px;
        border-top: 1px solid #e3e6f0;
        display: flex;
        gap: 4px;
        justify-content: center;
    }

    .mobile-empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }

    .mobile-empty-state i {
        font-size: 32px;
        margin-bottom: 10px;
        display: block;
    }
}

.text-center {
    text-align: center;
}

.m-0 {
    margin: 0;
}
</style>
