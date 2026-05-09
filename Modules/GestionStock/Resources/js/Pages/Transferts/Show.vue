<script>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PosLayout from '@/Layouts/PosLayout.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
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
import { useLoader } from '@/Composables/useLoader';
import { usePermissions } from '@/Composables/usePermissions';
const { t } = useI18n();
const page = usePage();
const { showLoader, hideLoader } = useLoader();
const { can } = usePermissions();
const props = defineProps({
    transfert: Object,
    canValidate: Boolean,
    statuts: Array,
    useDashboardLayout: Boolean,
});
// Vérifier si l'utilisateur est manager (pour afficher le bouton retour)
const isManager = computed(() => {
    return !props.useDashboardLayout;
});
// Formulaire de validation
const validationForm = ref({
    lignes: (props.transfert?.lignes || []).map(l => ({
        ligne_id: l.id,
        quantite_approuvee: l.quantite_demandee,
        statut: 'ok', // ok, partiel, refuse
        commentaire: '', // Commentaire par ligne
    })),
    commentaire: '',
});
const showValidationModal = ref(false);
const errors = ref({});
const getStatutLabel = (statut) => {
    return t(`modules.business.transfertStock.statuts.${statut}`) || statut;
};
const getStatutBadgeClass = (statut) => {
    const classes = {
        'en_cours': 'bg-warning text-dark',
        'partiel': 'bg-info text-white',
        'confirme': 'bg-success text-white',
        'annule': 'bg-danger text-white',
        'receptionne': 'bg-success text-white',
    };
    return classes[statut] || 'bg-secondary';
};
const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
};
// Obtenir le statut d'une ligne
const getLigneStatut = (ligne) => {
    if (ligne.quantite_approuvee === 0) return 'refuse';
    if (ligne.quantite_approuvee < ligne.quantite_demandee) return 'partiel';
    return 'ok';
};
const getLigneStatutBadge = (ligne) => {
    const statut = getLigneStatut(ligne);
    const badges = {
        'ok': { class: 'bg-success', label: t('modules.business.transfertStock.ligneStatuts.ok'), icon: 'fa-check' },
        'partiel': { class: 'bg-warning text-dark', label: t('modules.business.transfertStock.ligneStatuts.partiel'), icon: 'fa-exclamation' },
        'refuse': { class: 'bg-danger', label: t('modules.business.transfertStock.ligneStatuts.refuse'), icon: 'fa-times' },
};
    return badges[statut] || badges['ok'];
};
// Mettre à jour une ligne de validation
const setLigneStatut = (index, statut) => {
    const ligne = validationForm.value.lignes[index];
    const original = props.transfert.lignes[index];
    if (statut === 'ok') {
        ligne.quantite_approuvee = original.quantite_demandee;
        ligne.statut = 'ok';
    } else if (statut === 'refuse') {
        ligne.quantite_approuvee = 0;
        ligne.statut = 'refuse';
    } else {
        ligne.statut = 'partiel';
    }
};
// Incrémenter la quantité approuvée
const incrementQuantite = (index) => {
    const ligne = validationForm.value.lignes[index];
    const original = props.transfert.lignes[index];
    if (ligne.quantite_approuvee < original.quantite_demandee) {
        ligne.quantite_approuvee++;
        updateLigneStatut(index);
    }
};
// Décrémenter la quantité approuvée
const decrementQuantite = (index) => {
    const ligne = validationForm.value.lignes[index];
    if (ligne.quantite_approuvee > 0) {
        ligne.quantite_approuvee--;
        updateLigneStatut(index);
    }
};
// Mettre à jour le statut en fonction de la quantité
const updateLigneStatut = (index) => {
    const ligne = validationForm.value.lignes[index];
    const original = props.transfert.lignes[index];
    if (ligne.quantite_approuvee === 0) {
        ligne.statut = 'refuse';
    } else if (ligne.quantite_approuvee < original.quantite_demandee) {
        ligne.statut = 'partiel';
    } else {
        ligne.statut = 'ok';
    }
};
// Obtenir le badge pour le formulaire de validation
const getValidationStatutBadge = (ligneForm) => {
    const badges = {
        'ok': { class: 'bg-success', label: t('modules.business.transfertStock.ligneStatuts.ok'), icon: 'fa-check' },
        'partiel': { class: 'bg-warning text-dark', label: t('modules.business.transfertStock.ligneStatuts.partiel'), icon: 'fa-exclamation' },
        'refuse': { class: 'bg-danger', label: t('modules.business.transfertStock.ligneStatuts.refuse'), icon: 'fa-times' },
};
    return badges[ligneForm.statut] || badges['ok'];
};
// Soumettre la validation
const submitValidation = () => {
    showLoader(t('modules.business.transfertStock.messages.validationEnCours'), t('modules.business.transfertStock.messages.veuilezPatienter'), 'primary');
    router.put(route('transfert-stock.validate', props.transfert.id), {
        lignes: validationForm.value.lignes.map(l => ({
            ligne_id: l.ligne_id,
            quantite_approuvee: l.quantite_approuvee,
        })),
        commentaire: validationForm.value.commentaire,
    }, {
        onError: (e) => {
            errors.value = e;
        },
        onFinish: () => {
            hideLoader();
            showValidationModal.value = false;
        },
    });
};
// Refuser tout le transfert
const refuserTransfert = () => {
    validationForm.value.lignes.forEach(l => {
        l.quantite_approuvee = 0;
        l.statut = 'refuse';
    });
    submitValidation();
};
// Peut-on valider ? (seulement si en_cours - une fois validé, c'est fini côté source)
const canValidateTransfert = computed(() => {
    return props.canValidate && props.transfert.statut === 'en_cours';
});
// Vérifier les rôles de l'utilisateur
const userRoles = computed(() => page.props.auth?.user?.roles || []);
const currentUserId = computed(() => page.props.auth?.user?.id);
// Vérifier si l'utilisateur est superadmin ou marchand
const isSuperAdminOrMarchand = computed(() => {
    return userRoles.value.some(role => ['Super Admin', 'Marchand'].includes(role.name));
});
// Vérifier si l'utilisateur est l'initiateur du transfert (via la relation demande_par -> user)
const isInitiateur = computed(() => {
    return props.transfert.demande_par_user_id === currentUserId.value;
});
// Est-ce que le transfert est modifiable ?
// Modifiable si: (statut en_cours ET initiateur) OU (superadmin/marchand)
const isEditable = computed(() => {
    if (isSuperAdminOrMarchand.value) {
        return props.transfert.statut === 'en_cours';
    }
    return props.transfert.statut === 'en_cours' && isInitiateur.value;
});
// Peut-on réceptionner ? (seulement si confirme ou partiel ET initiateur de la demande)
const canReceiveTransfert = computed(() => {
    const validStatuts = ['confirme', 'partiel'];
    return validStatuts.includes(props.transfert.statut) && isInitiateur.value;
});
// Modal de confirmation réception
const showReceptionModal = ref(false);
const openReceptionModal = () => {
    showReceptionModal.value = true;
};
const closeReceptionModal = () => {
    showReceptionModal.value = false;
};
// Soumettre la réception du transfert
const submitReception = () => {
    closeReceptionModal();
    showLoader(t('modules.business.transfertStock.messages.receptionEnCours'), t('modules.business.transfertStock.messages.veuilezPatienter'), 'primary');
    router.put(route('transfert-stock.reception', props.transfert.id), {}, {
        onError: (e) => {
            errors.value = e;
        },
        onFinish: () => {
            hideLoader();
        },
    });
};
</script>
<template>
    <Head :title="t('modules.business.transfertStock.show')" />
    <div class="body-wrapper">
        <!-- Breadcrumb -->
        <div class="mb-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
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
                    <li class="breadcrumb-item active">{{ transfert.reference }}</li>
                </ol>
            </nav>
        </div>
        <!-- Header -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h4 class="mb-1">
                            {{ t('modules.business.transfertStock.validation') }}
                            <span class="text-primary fw-bold">{{ t('modules.business.transfertStock.singular') }}</span>
                        </h4>
                        <p class="text-muted mb-0">{{ transfert.reference }}</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted">{{ transfert.reference }}</span>
                        <span :class="['badge', getStatutBadgeClass(transfert.statut)]">
                            {{ getStatutLabel(transfert.statut) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <!-- Informations du transfert -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <!-- Source -->
                    <div class="col-md-4 text-center">
                        <div class="mb-2">
                            <small class="text-muted">
                                <i class="fa fa-play text-success me-1"></i>
                                {{ t('modules.business.transfertStock.labels.de') }}
                            </small>
                        </div>
                        <div class="d-flex align-items-center justify-content-center">
                            <i class="fa fa-store fa-2x text-success me-2"></i>
                            <h5 class="mb-0">{{ transfert.emplacement_source?.nom }}</h5>
                        </div>
                    </div>
                    <!-- Flèche -->
                    <div class="col-md-1 d-flex align-items-center justify-content-center">
                        <i class="fa fa-arrow-right fa-2x text-primary"></i>
                    </div>
                    <!-- Destination -->
                    <div class="col-md-4 text-center">
                        <div class="mb-2">
                            <small class="text-muted">
                                <i class="fa fa-map-marker-alt text-primary me-1"></i>
                                {{ t('modules.business.transfertStock.labels.vers') }}
                            </small>
                        </div>
                        <div class="d-flex align-items-center justify-content-center">
                            <i class="fa fa-building fa-2x text-primary me-2"></i>
                            <h5 class="mb-0">{{ transfert.emplacement_destination?.nom }}</h5>
                        </div>
                    </div>
                    <!-- Infos demande -->
                    <div class="col-md-3">
                        <div class="border-start ps-3">
                            <div class="mb-2">
                                <small class="text-muted d-block">{{ t('modules.business.transfertStock.fields.demandePar') }}</small>
                                <span class="fw-bold">{{ transfert.demande_par || '-' }}</span>
                            </div>
                            <div>
                                <small class="text-muted d-block">{{ t('modules.business.transfertStock.fields.dateDemande') }}</small>
                                <span>{{ formatDate(transfert.date_demande) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Tableau des articles -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fa fa-boxes me-2"></i>
                    {{ t('modules.business.transfertStock.sections.lignes') }}
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
                                <th class="text-center" v-if="transfert.statut !== 'en_cours'">
                                    {{ t('modules.business.transfertStock.fields.quantiteApprouvee') }}
                                </th>
                                <th class="text-center" v-if="transfert.statut !== 'en_cours'">
                                    {{ t('modules.business.transfertStock.fields.statut') }}
                                </th>
                                <template v-if="canValidateTransfert">
                                    <th class="text-center">{{ t('modules.business.transfertStock.fields.quantiteApprouvee') }}</th>
                                    <th class="text-center">{{ t('modules.business.transfertStock.fields.statut') }}</th>
                                    <th>{{ t('modules.business.transfertStock.fields.commentaire') }}</th>
                                    <th class="text-center">{{ t('common.actions') }}</th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(ligne, index) in transfert.lignes" :key="ligne.id">
                                <td>
                                    <span class="text-muted small">{{ ligne.article?.sku }}</span>
                                </td>
                                <td>{{ ligne.article?.nom }}</td>
                                <td class="text-center">
                                    <span class="badge bg-info">{{ ligne.stock_disponible || 0 }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ ligne.quantite_demandee }}</span>
                                </td>
                                <td class="text-center" v-if="transfert.statut !== 'en_cours'">
                                    <span :class="['badge', ligne.quantite_approuvee > 0 ? 'bg-success' : 'bg-danger']">
                                        {{ ligne.quantite_approuvee }}
                                    </span>
                                </td>
                                <td class="text-center" v-if="transfert.statut !== 'en_cours'">
                                    <span :class="['badge', getLigneStatutBadge(ligne).class]">
                                        <i :class="['fa', getLigneStatutBadge(ligne).icon, 'me-1']"></i>
                                        {{ getLigneStatutBadge(ligne).label }}
                                    </span>
                                </td>
                                <template v-if="canValidateTransfert">
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center align-items-center gap-2">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                @click="decrementQuantite(index)"
                                                :disabled="validationForm.lignes[index].quantite_approuvee <= 0"
                                            >
                                                <i class="fa fa-minus"></i>
                                            </button>
                                            <input
                                                type="number"
                                                v-model.number="validationForm.lignes[index].quantite_approuvee"
                                                class="form-control form-control-sm text-center"
                                                style="width: 70px;"
                                                min="0"
                                                :max="ligne.quantite_demandee"
                                                @input="updateLigneStatut(index)"
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                @click="incrementQuantite(index)"
                                                :disabled="validationForm.lignes[index].quantite_approuvee >= ligne.quantite_demandee"
                                            >
                                                <i class="fa fa-plus"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span :class="['badge', getValidationStatutBadge(validationForm.lignes[index]).class]">
                                            <i :class="['fa', getValidationStatutBadge(validationForm.lignes[index]).icon, 'me-1']"></i>
                                            {{ getValidationStatutBadge(validationForm.lignes[index]).label }}
                                        </span>
                                    </td>
                                    <td>
                                        <input
                                            type="text"
                                            v-model="validationForm.lignes[index].commentaire"
                                            class="form-control form-control-sm"
                                            :placeholder="t('modules.business.transfertStock.placeholders.commentaireLigne')"
                                            style="min-width: 150px;"
                                        />
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button
                                                type="button"
                                                :class="['btn btn-sm', validationForm.lignes[index].statut === 'ok' ? 'btn-success' : 'btn-outline-success']"
                                                @click="setLigneStatut(index, 'ok')"
                                                :title="t('modules.business.transfertStock.labels.mettreOk')"
                                            >
                                                <i class="fa fa-check me-1"></i>
                                                {{ t('modules.business.transfertStock.ligneStatuts.ok') }}
                                            </button>
                                            <button
                                                type="button"
                                                :class="['btn btn-sm', validationForm.lignes[index].statut === 'partiel' ? 'btn-warning text-dark' : 'btn-outline-warning']"
                                                @click="setLigneStatut(index, 'partiel')"
                                                :title="t('modules.business.transfertStock.labels.accepterPartiel')"
                                            >
                                                <i class="fa fa-exclamation me-1"></i>
                                                {{ t('modules.business.transfertStock.ligneStatuts.partiel') }}
                                            </button>
                                            <button
                                                type="button"
                                                :class="['btn btn-sm', validationForm.lignes[index].statut === 'refuse' ? 'btn-danger' : 'btn-outline-danger']"
                                                @click="setLigneStatut(index, 'refuse')"
                                                :title="t('modules.business.transfertStock.labels.refuser')"
                                            >
                                                <i class="fa fa-times me-1"></i>
                                                {{ t('modules.business.transfertStock.ligneStatuts.refuse') }}
                                            </button>
                                        </div>
                                    </td>
                                </template>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Note de validation (si peut valider) -->
        <div class="card mb-4" v-if="canValidateTransfert">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">{{ t('modules.business.transfertStock.labels.noteOptionnelle') }}</label>
                    <textarea
                        v-model="validationForm.commentaire"
                        class="form-control"
                        rows="2"
                        :placeholder="t('modules.business.transfertStock.placeholders.commentaire')"
                    ></textarea>
                </div>
            </div>
        </div>
        <!-- Commentaires existants -->
        <div class="card mb-4" v-if="transfert.commentaire || transfert.commentaire_confirmation">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fa fa-comments me-2"></i>
                    {{ t('modules.business.transfertStock.fields.commentaire') }}
                </h6>
            </div>
            <div class="card-body">
                <div v-if="transfert.commentaire" class="mb-3">
                    <small class="text-muted d-block">{{ t('modules.business.transfertStock.demande') }}:</small>
                    <p class="mb-0">{{ transfert.commentaire }}</p>
                </div>
                <div v-if="transfert.commentaire_confirmation">
                    <small class="text-muted d-block">{{ t('modules.business.transfertStock.fields.commentaireConfirmation') }}:</small>
                    <p class="mb-0">{{ transfert.commentaire_confirmation }}</p>
                </div>
            </div>
        </div>
        <!-- Informations de confirmation -->
        <div class="card mb-4" v-if="transfert.confirme_par || transfert.date_confirmation">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6" v-if="transfert.confirme_par">
                        <small class="text-muted d-block">{{ t('modules.business.transfertStock.fields.confirmePar') }}</small>
                        <span class="fw-bold">{{ transfert.confirme_par }}</span>
                    </div>
                    <div class="col-md-6" v-if="transfert.date_confirmation">
                        <small class="text-muted d-block">{{ t('modules.business.transfertStock.fields.dateConfirmation') }}</small>
                        <span>{{ formatDate(transfert.date_confirmation) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- Boutons d'action -->
        <div class="d-flex justify-content-center gap-3">
            <Link :href="route('transfert-stock.index')" class="btn btn-secondary">
                <i class="fa fa-arrow-left me-1"></i>
                <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
            </Link>
            <template v-if="canValidateTransfert">
                <button
                    type="button"
                    class="btn btn-danger"
                    @click="refuserTransfert"
                >
                    <i class="fa fa-times me-1"></i>
                    {{ t('modules.business.transfertStock.actions.refuser') }}
                </button>
                <button
                    type="button"
                    class="btn btn-success"
                    @click="submitValidation"
                >
                    <i class="fa fa-check me-1"></i>
                    {{ t('modules.business.transfertStock.actions.confirmerTransfert') }}
                </button>
            </template>
            <Link
                v-if="(can('transfert-stock-edit') || isSuperAdminOrMarchand) && isEditable"
                :href="route('transfert-stock.edit', transfert.id)"
                class="btn btn-primary"
            >
                <i class="fa fa-edit me-1"></i>
                <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
            </Link>
            <button
                v-if="canReceiveTransfert"
                type="button"
                class="btn btn-success"
                @click="openReceptionModal"
            >
                <i class="fa fa-check-double me-1"></i>
                {{ t('modules.business.transfertStock.actions.confirmerReception') }}
            </button>
        </div>
       
          <ConfirmModal :show="showReceptionModal" :title="t('messages.confirm.validate.title')"
            :message="t('messages.confirm.validate.message')" @close="showReceptionModal = false"
            @confirm="submitReception" :confirm-text="t('actions.validate')" confirm-class="btn-success"
            variant="success" />
    </div>
</template>
<style scoped>
.card-header {
    background-color: #f8f9fa;
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
/* Modal styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1050;
}
.modal-content {
    background: white;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #dee2e6;
}
.modal-title {
    margin: 0;
    font-size: 1.25rem;
}
.modal-body {
    padding: 1.5rem;
}
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid #dee2e6;
}
</style>
