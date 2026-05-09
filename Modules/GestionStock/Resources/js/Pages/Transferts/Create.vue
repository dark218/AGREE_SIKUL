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
import { ref, computed, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import { useLoader } from '@/Composables/useLoader';
const { t } = useI18n();
const page = usePage();
const { showLoader, hideLoader } = useLoader();
const props = defineProps({
    pointsVenteSource: Array,
    pointsVenteDestination: Array,
    defaultSource: Number, // ID du PV source par défaut (pour manager)
    articles: Array,
    reference: String,
    useDashboardLayout: Boolean,
});
// Vérifier si l'utilisateur est manager (pour afficher le bouton retour)
const isManager = computed(() => {
    return !props.useDashboardLayout;
});
const form = ref({
    // Source = à sélectionner (celui qui ENVOIE le stock)
    emplacement_source_id: props.defaultSource || (props.pointsVenteSource?.length === 1 ? props.pointsVenteSource[0].value : ''),
    
    // Destination = fixe pour manager (celui qui REÇOIT le stock)
    emplacement_destination_id: props.pointsVenteDestination?.length === 1 ? props.pointsVenteDestination[0].value : '',
    commentaire: '',
    lignes: [],
});
const errors = ref({});
const selectedArticle = ref('');
const selectedQuantite = ref(1);
// Articles filtrés par point de vente source
const articlesDisponibles = computed(() => {
    if (!form.value.emplacement_destination_id) return [];
    // Utiliser == pour comparaison souple (string/number)
    return (props.articles || []).filter(a => a.point_vente_id == form.value.emplacement_destination_id);
});
// Ajouter un article à la liste
const ajouterArticle = () => {
    if (!selectedArticle.value || selectedQuantite.value < 1) return;
    const article = props.articles.find(a => a.value === selectedArticle.value);
    if (!article) return;
    // Vérifier si l'article n'est pas déjà dans la liste
    const existe = form.value.lignes.find(l => l.article_id === selectedArticle.value);
    if (existe) {
        existe.quantite_demandee += selectedQuantite.value;
    } else {
        form.value.lignes.push({
            article_id: article.value,
            article_nom: article.label,
            stock: article.stock,
            quantite_demandee: selectedQuantite.value,
        });
    }
    selectedArticle.value = '';
    selectedQuantite.value = 1;
};
// Supprimer un article de la liste
const supprimerArticle = (index) => {
    form.value.lignes.splice(index, 1);
};
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
    if (form.value.lignes.length === 0) {
        errors.value.lignes = [t('modules.business.transfertStock.messages.aucuneLigne')];
        return;
    }
    showLoader(t('modules.business.transfertStock.messages.creationEnCours'), t('modules.business.transfertStock.messages.veuilezPatienter'), 'primary');
    router.post(route('transfert-stock.store'), {
        emplacement_source_id: form.value.emplacement_source_id,
        emplacement_destination_id: form.value.emplacement_destination_id,
        commentaire: form.value.commentaire,
        lignes: form.value.lignes.map(l => ({
            article_id: l.article_id,
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
// Réinitialiser les lignes si le point de vente source change
watch(() => form.value.emplacement_source_id, () => {
    form.value.lignes = [];
    selectedArticle.value = '';
});
</script>
<template>
    <Head :title="t('modules.business.transfertStock.create')" />
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
                        <li class="breadcrumb-item active">{{ t('modules.business.transfertStock.create') }}</li>
                    </ol>
                </nav>
            </div>
            <div class="card-header">
                <h4 class="card-title mb-0">
                    {{ t('modules.business.transfertStock.demande') }}
                    <span class="text-primary fw-bold">{{ t('modules.business.transfertStock.singular') }}</span>
                </h4>
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
                                <!-- Point de vente SOURCE (celui qui ENVOIE - mon PV) -->
                                <!-- Afficher en premier car c'est le PV du demandeur -->
                                <div class="col-md-6" v-if="pointsVenteSource && pointsVenteSource.length === 1">
                                    <!-- Cas manager: source fixe (son PV) -->
                                    <div class="form-group mb-3">
                                        <label class="form-label">
                                            {{ t('modules.business.transfertStock.fields.emplacementSource') }}
                                            <small class="text-muted">({{ t('modules.business.transfertStock.labels.monPointVente') }})</small>
                                        </label>
                                        <div class="form-control bg-light">
                                            <i class="fa fa-store me-2 text-primary"></i>
                                            {{ pointsVenteSource[0].label }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6" v-else-if="pointsVenteSource && pointsVenteSource.length > 1">
                                    <!-- Cas marchand: destination sélectionnable -->
                                    <div class="form-group mb-3">
                                        <label class="form-label">
                                            {{ t('modules.business.transfertStock.fields.emplacementSource') }}
                                            <span class="text-danger">*</span>
                                        </label>
                                        <StylishSelect
                                            v-model="form.emplacement_source_id"
                                            :options="pointsVenteSource"
                                            option-value="value"
                                            option-label="label"
                                            :placeholder="t('modules.business.transfertStock.labels.selectSource')"
                                            required
                                        />
                                        <div v-if="errors.emplacement_source_id" class="text-danger small mt-1">
                                            {{ errors.emplacement_source_id }}
                                        </div>
                                    </div>
                                </div>
                                <!-- Point de vente DESTINATION (celui qui ENVOIE - à sélectionner) -->
                                <div class="col-md-6" v-if="pointsVenteDestination && pointsVenteDestination.length > 1">
                                    <div class="form-group mb-3">
                                        <label class="form-label">
                                            {{ t('modules.business.transfertStock.fields.emplacementDestination') }}
                                            <span class="text-danger">*</span>
                                            <small class="text-muted">({{ t('modules.business.transfertStock.labels.demanderDepuis') }})</small>
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
                                <!-- Afficher le point source si unique -->
                                <div class="col-md-6" v-else-if="pointsVenteDestination && pointsVenteDestination.length === 1">
                                    <div class="form-group mb-3">
                                        <label class="form-label">{{ t('modules.business.transfertStock.fields.emplacementDestination') }}</label>
                                        <div class="form-control bg-light">
                                            <i class="fa fa-store me-2 text-success"></i>
                                            {{ pointsVenteDestination[0].label }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Section Articles -->
                    <div class="card mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="fa fa-boxes me-2"></i>
                                {{ t('modules.business.transfertStock.labels.articlesTransfert') }}
                            </h6>
                        </div>
                        <div class="card-body">
                            <!-- Formulaire d'ajout d'article -->
                            <div class="row mb-3 align-items-end" v-if="form.emplacement_destination_id">
                                <div class="col-md-6">
                                    <label class="form-label">{{ t('modules.business.transfertStock.fields.article') }}</label>
                                    <StylishSelect
                                        v-model="selectedArticle"
                                        :options="articlesDisponibles"
                                        option-value="value"
                                        option-label="label"
                                        :placeholder="t('modules.business.transfertStock.placeholders.selectArticle')"
                                    />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ t('modules.business.transfertStock.fields.quantiteDemandee') }}</label>
                                    <input
                                        type="number"
                                        v-model.number="selectedQuantite"
                                        class="form-control"
                                        min="1"
                                        :placeholder="t('modules.business.transfertStock.placeholders.quantite')"
                                    />
                                </div>
                                <div class="col-md-3">
                                    <button
                                        type="button"
                                        class="btn btn-primary w-100"
                                        @click="ajouterArticle"
                                        :disabled="!selectedArticle || selectedQuantite < 1"
                                    >
                                        <i class="fa fa-plus me-1"></i>
                                        {{ t('modules.business.transfertStock.labels.ajouterArticle') }}
                                    </button>
                                </div>
                            </div>
                            <!-- Message si aucun point source sélectionné -->
                            <div v-else class="alert alert-info">
                                <i class="fa fa-info-circle me-2"></i>
                                {{ t('modules.business.transfertStock.labels.selectDestination') }}
                            </div>
                            <!-- Tableau des articles ajoutés -->
                            <div class="table-responsive" v-if="form.lignes.length > 0">
                                <table class="table table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>{{ t('modules.business.transfertStock.fields.reference') }}</th>
                                            <th>{{ t('modules.business.transfertStock.fields.article') }}</th>
                                            <th class="text-center">{{ t('modules.business.transfertStock.fields.stockDisponible') }}</th>
                                            <th class="text-center">{{ t('modules.business.transfertStock.fields.quantiteDemandee') }}</th>
                                            <th class="text-center">{{ t('common.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(ligne, index) in form.lignes" :key="index">
                                            <td>
                                                <span class="text-muted small">{{ ligne.article_nom.split('(')[1]?.replace(')', '') || '-' }}</span>
                                            </td>
                                            <td>{{ ligne.article_nom.split('(')[0] }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary">{{ ligne.stock }}</span>
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
                                            <td class="text-center">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-danger"
                                                    @click="supprimerArticle(index)"
                                                >
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Message si aucun article -->
                            <div v-else-if="form.emplacement_source_id" class="text-center py-4 text-muted">
                                <i class="fa fa-inbox fa-2x mb-2"></i>
                                <p class="mb-0">{{ t('modules.business.transfertStock.messages.aucuneLigne') }}</p>
                            </div>
                            <div v-if="errors.lignes" class="text-danger small mt-2">
                                {{ errors.lignes[0] }}
                            </div>
                        </div>
                    </div>
                    <!-- Note -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">
                                    {{ t('modules.business.transfertStock.labels.noteOptionnelle') }}
                                </label>
                                <textarea
                                    v-model="form.commentaire"
                                    class="form-control"
                                    rows="8"
                                    style="min-height: 150px;"
                                    :placeholder="t('modules.business.transfertStock.placeholders.commentaire')"
                                ></textarea>
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
                            :disabled="!form.emplacement_source_id || form.lignes.length === 0"
                        >
                            <i class="fa fa-paper-plane me-1"></i>
                            {{ t('modules.business.transfertStock.actions.envoyerDemande') }}
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
