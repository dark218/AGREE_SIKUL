<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import { usePermissions } from '@/Composables/usePermissions';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { can } = usePermissions();
const props = defineProps({
    mouvement: Object,
});
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
        minute: '2-digit',
        second: '2-digit'
    });
};
</script>
<template>
    <Head :title="page.props.title" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('modules.business.mouvementStock.show') }}</h4>
                <div class="dashboard-btn-wrapper">
                    <Link
                        v-if="can('mouvement-stock-edit')"
                        :href="route('mouvement-stock.edit', mouvement.id)"
                        class="btn btn-primary me-2"
                    >
                        <i class="fa fa-edit me-1"></i>
                        <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                    </Link>
                    <Link :href="route('mouvement-stock.index')" class="btn btn-secondary">
                        <i class="fa fa-arrow-left me-1"></i>
                        <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                    </Link>
                </div>
            </div>
            <AlertMessage />
            <div class="row">
                <!-- Informations generales -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fa fa-info-circle me-2"></i>
                                {{ t('modules.business.mouvementStock.show') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tbody>
                                    <tr>
                                        <th class="text-muted" style="width: 40%">
                                            {{ t('modules.business.mouvementStock.fields.reference') }}
                                        </th>
                                        <td>
                                            <strong>{{ mouvement.reference || '-' }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">
                                            {{ t('modules.business.mouvementStock.fields.date') }}
                                        </th>
                                        <td>{{ formatDate(mouvement.created_at) }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">
                                            {{ t('modules.business.mouvementStock.fields.typeMouvement') }}
                                        </th>
                                        <td>
                                            <span class="badge bg-info text-dark">
                                                {{ getTypeMouvementLabel(mouvement.type_mouvement) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">
                                            {{ t('modules.business.mouvementStock.fields.employe') }}
                                        </th>
                                        <td>
                                            <span v-if="mouvement.employe && mouvement.employe.user">
                                                {{ mouvement.employe.user.nom }} {{ mouvement.employe.user.prenoms }}
                                            </span>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Informations Article -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fa fa-box me-2"></i>
                                {{ t('modules.business.mouvementStock.fields.article') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tbody>
                                    <tr v-if="mouvement.article">
                                        <th class="text-muted" style="width: 40%">
                                            {{ t('modules.business.articles.fields.nom') }}
                                        </th>
                                        <td>
                                            <strong>{{ mouvement.article.nom }}</strong>
                                            <span v-if="mouvement.article.marque" class="text-muted">({{ mouvement.article.marque }})</span>
                                        </td>
                                    </tr>
                                    <tr v-if="mouvement.article">
                                        <th class="text-muted">
                                            {{ t('modules.business.articles.sku') }}
                                        </th>
                                        <td>{{ mouvement.article.sku }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">
                                            {{ t('modules.business.mouvementStock.fields.quantite') }}
                                        </th>
                                        <td>
                                            <span
                                                :class="['badge fs-6', getQuantiteBadgeClass(mouvement.quantite)]"
                                            >
                                                {{ getQuantiteLabel(mouvement.quantite) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">
                                            {{ t('modules.business.mouvementStock.fields.quantiteStockAvant') }}
                                        </th>
                                        <td>{{ mouvement.quantite_stock_avant }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">
                                            {{ t('modules.business.mouvementStock.fields.quantiteStockApres') }}
                                        </th>
                                        <td>
                                            <strong>{{ mouvement.quantite_stock_apres }}</strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Emplacements -->
                <div class="col-md-6" v-if="mouvement.emplacement_source || mouvement.emplacement_destination">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fa fa-map-marker-alt me-2"></i>
                                {{ t('modules.business.pointsvente.title') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tbody>
                                    <tr v-if="mouvement.emplacement_source">
                                        <th class="text-muted" style="width: 40%">
                                            {{ t('modules.business.mouvementStock.fields.emplacementSource') }}
                                        </th>
                                        <td>
                                            <span class="badge bg-warning text-dark">
                                                {{ mouvement.emplacement_source.nom }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="mouvement.emplacement_destination">
                                        <th class="text-muted">
                                            {{ t('modules.business.mouvementStock.fields.emplacementDestination') }}
                                        </th>
                                        <td>
                                            <span class="badge bg-success">
                                                {{ mouvement.emplacement_destination.nom }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Commentaire -->
                <div class="col-md-6" v-if="mouvement.commentaire">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fa fa-comment me-2"></i>
                                {{ t('modules.business.mouvementStock.fields.commentaire') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">{{ mouvement.commentaire }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
