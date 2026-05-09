<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import { usePermissions } from '@/Composables/usePermissions';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { can } = usePermissions();
const props = defineProps({
    mouvements: Object,
    articles: Array,
    typesMouvement: Array,
    pointsVente: Array,
    filters: Object,
});
const searchFilters = ref({
    article_id: props.filters?.article_id || '',
    type_mouvement: props.filters?.type_mouvement || '',
    date_debut: props.filters?.date_debut || '',
    date_fin: props.filters?.date_fin || '',
    emplacement_id: props.filters?.emplacement_id || '',
});
const search = () => {
    router.get(route('mouvement-stock.index'), searchFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};
const resetFilters = () => {
    searchFilters.value = {
        article_id: '',
        type_mouvement: '',
        date_debut: '',
        date_fin: '',
        emplacement_id: '',
    };
    search();
};
const getTypeMouvementLabel = (type) => {
    return t(`modules.business.mouvementStock.types.${type}`) || type;
};
const getQuantiteBadgeClass = (quantite) => {
    if (quantite > 0) {
        return 'bg-success';
    } else if (quantite < 0) {
        return 'bg-danger';
    }
    return 'bg-secondary';
};
const getQuantiteLabel = (quantite) => {
    if (quantite > 0) {
        return `+${quantite}`;
    }
    return quantite.toString();
};
const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>
<template>
    <Head :title="page.props.title" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('modules.business.mouvementStock.title') }}</h4>
                <div class="dashboard-btn-wrapper">
                    <div class="dashboard-btn" v-if="can('mouvement-stock-create')">
                        <Link :href="route('mouvement-stock.create')" class="btn btn-primary">
                            <i class="fa fa-plus"></i> {{ t('actions.add') }}
                        </Link>
                    </div>
                </div>
            </div>
            <AlertMessage />
            <!-- Filtres -->
            <div class="row m-0 mb-3">
                <form class="row col-12" @submit.prevent="search">
                    <div class="col-md-2 p-1">
                        <StylishSelect
                            v-model="searchFilters.article_id"
                            :options="articles"
                            option-value="value"
                            option-label="label"
                            :placeholder="t('modules.business.mouvementStock.labels.selectArticle')"
                        />
                    </div>
                    <div class="col-md-2 p-1">
                        <StylishSelect
                            v-model="searchFilters.type_mouvement"
                            :options="typesMouvement"
                            option-value="value"
                            option-label="label"
                            :placeholder="t('modules.business.mouvementStock.labels.selectType')"
                        />
                    </div>
                    <div class="col-md-2 p-1">
                        <StylishSelect
                            v-model="searchFilters.emplacement_id"
                            :options="pointsVente"
                            option-value="value"
                            option-label="label"
                            :placeholder="t('modules.business.mouvementStock.labels.selectEmplacement')"
                        />
                    </div>
                    <div class="col-md-2 p-1">
                        <input
                            type="date"
                            v-model="searchFilters.date_debut"
                            class="form-control"
                            :placeholder="t('modules.business.mouvementStock.filters.dateDebut')"
                        />
                    </div>
                    <div class="col-md-2 p-1">
                        <input
                            type="date"
                            v-model="searchFilters.date_fin"
                            class="form-control"
                            :placeholder="t('modules.business.mouvementStock.filters.dateFin')"
                        />
                    </div>
                    <div class="col-md-2 p-1 d-flex gap-1">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-search"></i>
                        </button>
                        <button type="button" class="btn btn-secondary" @click="resetFilters">
                            <i class="fa fa-times"></i>
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
                                    <th>{{ t('modules.business.mouvementStock.fields.date') }}</th>
                                    <th>{{ t('modules.business.mouvementStock.fields.reference') }}</th>
                                    <th>{{ t('modules.business.mouvementStock.fields.article') }}</th>
                                    <th>{{ t('modules.business.mouvementStock.fields.typeMouvement') }}</th>
                                    <th>{{ t('modules.business.mouvementStock.fields.emplacementSource') }}</th>
                                    <th>{{ t('modules.business.mouvementStock.fields.emplacementDestination') }}</th>
                                    <th>{{ t('modules.business.mouvementStock.fields.quantite') }}</th>
                                    <th>{{ t('modules.business.mouvementStock.fields.quantiteStockAvant') }}</th>
                                    <th>{{ t('modules.business.mouvementStock.fields.quantiteStockApres') }}</th>
                                    <th>{{ t('modules.business.mouvementStock.fields.employe') }}</th>
                                    <th class="fit">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="mouvements.data && mouvements.data.length > 0">
                                    <tr v-for="mouvement in mouvements.data" :key="mouvement.id">
                                        <td>{{ formatDate(mouvement.created_at) }}</td>
                                        <td>{{ mouvement.reference || '-' }}</td>
                                        <td>
                                            <span v-if="mouvement.article">
                                                {{ mouvement.article.nom }}
                                                <span v-if="mouvement.article.marque" class="text-muted">({{ mouvement.article.marque }})</span>
                                                <small class="text-muted d-block">{{ mouvement.article.sku }}</small>
                                            </span>
                                            <span v-else>-</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-dark">
                                                {{ getTypeMouvementLabel(mouvement.type_mouvement) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span v-if="mouvement.emplacement_source" class="badge bg-warning text-dark">
                                                {{ mouvement.emplacement_source.nom }}
                                            </span>
                                            <span v-else>-</span>
                                        </td>
                                        <td>
                                            <span v-if="mouvement.emplacement_destination" class="badge bg-success">
                                                {{ mouvement.emplacement_destination.nom }}
                                            </span>
                                            <span v-else>-</span>
                                        </td>
                                        <td>
                                            <span
                                                :class="['badge', getQuantiteBadgeClass(mouvement.quantite)]"
                                            >
                                                {{ getQuantiteLabel(mouvement.quantite) }}
                                            </span>
                                        </td>
                                        <td>{{ mouvement.quantite_stock_avant }}</td>
                                        <td>{{ mouvement.quantite_stock_apres }}</td>
                                        <td>
                                            <span v-if="mouvement.employe && mouvement.employe.user">
                                                {{ mouvement.employe.user.nom }} {{ mouvement.employe.user.prenoms }}
                                            </span>
                                            <span v-else>-</span>
                                        </td>
                                        <td class="fit">
                                            <div class="action-buttons">
                                                <Link
                                                    :href="route('mouvement-stock.show', mouvement.id)"
                                                    class="btn btn-secondary btn-sm"
                                                    :title="t('actions.view')"
                                                >
                                                    <i class="fa fa-eye"></i>
                                                </Link>
                                                <Link
                                                    v-if="can('mouvement-stock-edit')"
                                                    :href="route('mouvement-stock.edit', mouvement.id)"
                                                    class="btn btn-primary btn-sm"
                                                    :title="t('actions.edit')"
                                                >
                                                    <i class="fa fa-edit"></i>
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="11" class="text-center">{{ t('common.emptyList') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :data="mouvements" />
                </div>
            </div>
        </div>
    </div>
</template>
