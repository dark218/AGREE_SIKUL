<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
import { usePermissions } from '@/Composables/usePermissions';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showLoader, hideLoader } = useLoader();
const { can } = usePermissions();
const props = defineProps({
    moyenPaiement: Object,
    client: Object,
});
const toggleStatut = () => {
    showLoader('Modification du statut...', 'Veuillez patienter');
    router.post(route('client.moyens-paiement.toggle-statut', [props.client.uuid, props.moyenPaiement.id]), {}, {
        preserveScroll: true,
        onFinish: () => { hideLoader(); }
    });
};
const toggleDefaut = () => {
    showLoader('Définition du moyen par défaut...', 'Veuillez patienter');
    router.post(route('client.moyens-paiement.toggle-defaut', [props.client.uuid, props.moyenPaiement.id]), {}, {
        preserveScroll: true,
        onFinish: () => { hideLoader(); }
    });
};
const getStatutBadgeClass = () => {
    return props.moyenPaiement.statut === 'actif' ? 'bg-success' : 'bg-secondary';
};
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
// Toggle pour afficher/masquer l'identifiant
const showIdentifiant = ref(false);
const toggleIdentifiant = () => {
    showIdentifiant.value = !showIdentifiant.value;
    };
</script>
<template>
    <Head :title="'Moyen de paiement - ' + client.nom + ' ' + client.prenoms" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <Link :href="route('client.index')" class="text-decoration-none">
                                {{ t('modules.service_client.client.title') }}
                            </Link>
                        </li>
                        <li class="breadcrumb-item">
                            <Link :href="route('client.show', client.uuid)" class="text-decoration-none">
                                {{ client.nom }} {{ client.prenoms }}
                            </Link>
                        </li>
                        <li class="breadcrumb-item">
                            <Link :href="route('client.moyens-paiement', client.uuid)" class="text-decoration-none">
                                {{ t('modules.service_client.moyen_paiement.title') }}
                            </Link>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">{{ t('common.details') }}</li>
                    </ol>
                </nav>
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center"
                             @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('modules.service_client.moyen_paiement.details') }}</h5>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <!-- Boutons Toggle -->
                                <div v-if="can('client-edit')" class="d-flex align-items-center gap-3" @click.stop>
                                    <div class="d-flex align-items-center gap-2">
                                        <label class="mb-0 small text-muted">{{ t('common.status') }}:</label>
                                        <div class="form-check form-switch mb-0 mt-2">
                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                id="toggle-statut"
                                                :checked="moyenPaiement.statut === 'actif'"
                                                @change="toggleStatut"
                                                role="switch"
                                                :title="moyenPaiement.statut === 'actif' ? 'Désactiver' : 'Activer'">
                                        </div>
                                    </div>
                                    <button v-if="!moyenPaiement.is_defaut"
                                            @click="toggleDefaut"
                                            class="btn btn-sm btn-info">
                                        <i class="fa fa-star"></i> {{ t('actions.setAsDefault') }}
                                    </button>
                                </div>
                                <!-- Statut badges -->
                                <div class="d-flex align-items-center gap-2">
                                   
                                    <span v-if="moyenPaiement.is_defaut" class="badge bg-primary">
                                        <i class="fa fa-star"></i> Défaut
                                    </span>
                                </div>
                                <button type="button"
                                        class="collapse-toggle"
                                        :class="{ collapsed: isCollapsed }"
                                        @click.stop="toggleCollapse">
                                    <i class="fa fa-chevron-up"></i>
                                </button>
                            </div>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <div class="custom-input">
                                <div class="row g-3">
                                    <!-- Type -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label><strong>{{ t('modules.service_client.moyen_paiement.type') }}</strong></label>
                                            <p class="form-control-plaintext">{{ moyenPaiement.type_label }}</p>
                                        </div>
                                    </div>
                                    <!-- Fournisseur -->
                                    <div class="col-md-6" v-if="moyenPaiement.fournisseur">
                                        <div class="mb-3">
                                            <label><strong>{{ t('modules.service_client.moyen_paiement.fournisseur') }}</strong></label>
                                            <p class="form-control-plaintext">{{ moyenPaiement.fournisseur.nom }}</p>
                                        </div>
                                    </div>
                                    <!-- Identifiant masqué -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label><strong>{{ t('modules.service_client.moyen_paiement.identifiant') }}</strong></label>
                                            <div class="d-flex align-items-center gap-2">
                                                <p class="form-control-plaintext mb-0">
                                                    {{ showIdentifiant ? moyenPaiement.identifiant_chiffre : moyenPaiement.identifiant_masque }}
                                                </p>
                                                <button
                                                    @click="toggleIdentifiant"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    type="button"
                                                    :title="showIdentifiant ? 'Masquer' : 'Afficher'">
                                                    <i :class="showIdentifiant ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Label -->
                                    <div class="col-md-6" v-if="moyenPaiement.label">
                                        <div class="mb-3">
                                            <label><strong>{{ t('modules.service_client.moyen_paiement.libelle') }}</strong></label>
                                            <p class="form-control-plaintext">{{ moyenPaiement.label }}</p>
                                        </div>
                                    </div>
                                    <!-- Libellé complet -->
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label><strong>{{ t('modules.service_client.moyen_paiement.libelle_complet') }}</strong></label>
                                            <p class="form-control-plaintext">{{ moyenPaiement.libelle_complet }}</p>
                                        </div>
                                    </div>
                           
                                    <!-- Utilisable -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label><strong>{{ t('modules.service_client.moyen_paiement.utilisable') }}</strong></label>
                                            <p class="form-control-plaintext">
                                                <span v-if="moyenPaiement.is_utilisable" class="badge bg-success">{{ t('Oui') }}</span>
                                                <span v-else class="badge bg-danger">{{ t('Non') }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('client.moyens-paiement', client.uuid)"
                                              class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                        </Link>
                                        <Link v-if="can('client-edit')"
                                              :href="route('client.moyens-paiement.edit', [client.uuid, moyenPaiement.id])"
                                              class="btn btn-primary">
                                            <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
<style scoped>
.breadcrumb-item a:hover,
.breadcrumb-item a:active,
.breadcrumb-item a:focus {
    color: #0a58ca !important;
    text-decoration: underline;
}
</style>
