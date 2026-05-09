<script>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PosLayout from '@/Layouts/PosLayout.vue';
// Layout dynamique basé sur useDashboardLayout
export default {
    layout: (h, page) => {
        const Layout = page.props.useDashboardLayout ? DashboardLayout : PosLayout;
        return h(Layout, () => page);
    }
};
</script>
<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import { useLoader } from '@/Composables/useLoader';
const { t } = useI18n();
const page = usePage();
const { showLoader, hideLoader } = useLoader();
const props = defineProps({
    transfert: Object,
    lignes: Array,
    pointsVenteDestination: Array,
    useDashboardLayout: Boolean,
});
// Vérifier si l'utilisateur est manager (pour afficher le bouton retour)
const isManager = computed(() => {
    return !props.useDashboardLayout;
});
const form = ref({
    emplacement_destination_id: props.transfert?.emplacement_destination_id || '',
    lignes: (props.lignes || []).map(l => ({
        id: l.id,
        article_id: l.article_id,
        article_nom: l.article_nom,
        sku: l.sku,
        stock_disponible: l.stock_disponible || 0,
        quantite_demandee: l.quantite_demandee,
    })),
});
const errors = ref({});
// Modifier la quantité
const modifierQuantite = (index, delta) => {
    const ligne = form.value.lignes[index];
    const nouvelleQte = ligne.quantite_demandee + delta;
    if (nouvelleQte >= 1) {
        ligne.quantite_demandee = nouvelleQte;
    }
};
// Soumettre le formulaire
const submit = () => {
    showLoader(t('modules.business.transfertStock.messages.validationEnCours'), t('modules.business.transfertStock.messages.veuilezPatienter'), 'primary');
    router.post(route('transfert-stock.update', props.transfert.id), {
        _method: 'PUT',
        emplacement_destination_id: form.value.emplacement_destination_id,
        lignes: form.value.lignes.map(l => ({
            id: l.id,
            quantite_demandee: l.quantite_demandee,
        })),
    }, {
        onError: (e) => {
            errors.value = e;
        },
        onFinish: () => {
            hideLoader();
        },
    });
};
const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};
</script>
<template>
    <Head :title="t('modules.business.transfertStock.edit')" />
    <div class="body-wrapper">
        <div class="card">
            <!-- Breadcrumb -->
            <div class="px-4 pt-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item" v-if="isManager">
                            <a :href="route('session-caisse-manager.index')">
                                {{ t('pos.manager.title') }}
                            </a>
                        </li>
                        <li class="breadcrumb-item">
                            <Link :href="route('transfert-stock.index')">
                                {{ t('modules.business.transfertStock.title') }}
                            </Link>
                        </li>
                        <li class="breadcrumb-item">
                            <Link :href="route('transfert-stock.show', transfert.id)">
                                {{ transfert.reference }}
                            </Link>
                        </li>
                        <li class="breadcrumb-item active"><i class="fa fa-pencil"></i> {{ t('actions.edit') }}</li>
                    </ol>
                </nav>
            </div>
            <div class="card-header">
                <h4 class="card-title mb-0">
                    {{ t('modules.business.transfertStock.edit') }}
                </h4>
                <small class="text-muted">{{ transfert.reference }} - {{ formatDate(transfert.date_demande) }}</small>
            </div>
            <div class="card-body">
                <form @submit.prevent="submit">
                    <!-- Section Points de vente -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="fa fa-map-marker-alt me-2"></i>
                                {{ t('modules.business.transfertStock.sections.pointsVente') }}
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Point de vente source (lecture seule) -->
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">{{ t('modules.business.transfertStock.fields.emplacementSource') }}</label>
                                        <div class="form-control bg-light">
                                            <i class="fa fa-store me-2 text-success"></i>
                                            {{ transfert.emplacement_source?.nom }}
                                        </div>
                                    </div>
                                </div>
                                <!-- Point de vente destination -->
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label">
                                            {{ t('modules.business.transfertStock.fields.emplacementDestination') }}
                                            <span class="text-danger">*</span>
                                        </label>
                                        <StylishSelect
                                            v-model="form.emplacement_destination_id"
                                            :options="pointsVenteDestination"
                                            option-value="value"
                                            option-label="label"
                                            :placeholder="t('modules.business.transfertStock.labels.selectDestination')"
                                            required
                                        />
                                        <div v-if="errors.emplacement_destination_id" class="text-danger small mt-1">
                                            {{ errors.emplacement_destination_id }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Section Articles -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="fa fa-boxes me-2"></i>
                                {{ t('modules.business.transfertStock.labels.articlesTransfert') }}
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>{{ t('modules.business.transfertStock.fields.reference') }}</th>
                                            <th>{{ t('modules.business.transfertStock.fields.article') }}</th>
                                            <th class="text-center">{{ t('modules.business.transfertStock.fields.stockDisponible') }}</th>
                                            <th class="text-center">{{ t('modules.business.transfertStock.fields.quantiteDemandee') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(ligne, index) in form.lignes" :key="ligne.id">
                                            <td>
                                                <span class="text-muted small">{{ ligne.sku }}</span>
                                            </td>
                                            <td>{{ ligne.article_nom }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-info">{{ ligne.stock_disponible }}</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center align-items-center gap-2">
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        @click="modifierQuantite(index, -1)"
                                                        :disabled="ligne.quantite_demandee <= 1"
                                                    >
                                                        <i class="fa fa-minus"></i>
                                                    </button>
                                                    <input
                                                        type="number"
                                                        v-model.number="ligne.quantite_demandee"
                                                        class="form-control form-control-sm text-center"
                                                        style="width: 70px;"
                                                        min="1"
                                                    />
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        @click="modifierQuantite(index, 1)"
                                                    >
                                                        <i class="fa fa-plus"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Boutons d'action -->
                    <div class="d-flex justify-content-center gap-3">
                        <Link :href="route('transfert-stock.index')" class="btn btn-secondary">
                            <i class="fa fa-times me-1"></i>
                            {{ t('actions.cancel') }}
                        </Link>
                        <button
                            type="submit"
                            class="btn btn-primary"
                            :disabled="!form.emplacement_destination_id"
                        >
                            <i class="fa fa-save me-1"></i>
                            {{ t('actions.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
<style scoped>
.card-header.bg-light {
    background-color: #f8f9fa !important;
}
/* Fix breadcrumb link colors for PosLayout */
.breadcrumb-item a {
    color: #0d6efd !important;
    text-decoration: none;
}
.breadcrumb-item a:hover {
    color: #0a58ca !important;
    text-decoration: underline;
}
/* Fix table header text visibility */
.table-dark th {
    color: #fff !important;
}
</style>
