<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import PointVenteForm from './PointVenteForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const props = defineProps({
    pointvente: Object,
    userStatuts: Object,
});
const form = {
    marchand_id: props.pointvente.marchand_id || '',
    zone_id: props.pointvente.zone_id || '',
    nom: props.pointvente.nom || '',
    adresse: props.pointvente.adresse || '',
    telephone: props.pointvente.telephone || '',
    longitude: props.pointvente.longitude || null,
    latitude: props.pointvente.latitude || null,
    errors: {},
};
const marchands = [{ id: props.pointvente.marchand_id, label: props.pointvente.marchand }];
const zones = [{ id: props.pointvente.zone_id, libelle: props.pointvente.zone }];
const existingFiles = {
    photo: props.pointvente.photo,
};
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const getStatutBadgeClass = (status) => {
    const statuts = props.userStatuts || {};
    const classes = {
        [statuts.non_actif]: 'bg-secondary',
        [statuts.actif]: 'bg-success',
        [statuts.suspendu]: 'bg-warning',
        [statuts.bloque]: 'bg-danger',
        [statuts.supprime]: 'bg-secondary',
    };
    return classes[status] || 'bg-secondary';
};
const getStatutLabel = (status) => {
    const statuts = props.userStatuts || {};
    const labels = {
        [statuts.non_actif]: t('statuts.inactif'),
        [statuts.actif]: t('statuts.actif'),
        [statuts.suspendu]: t('statuts.suspendu'),
        [statuts.bloque]: t('statuts.bloque'),
        [statuts.supprime]: t('statuts.supprime'),
    };
    return labels[status] || status;
};
</script>
<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('modules.business.pointsvente.show') }}</h5>
                            </div>
                            <div class="d-flex flex-grow-1 align-items-center justify-content-end">
                                <!-- Statut à droite -->
                                <div class="d-flex align-items-center gap-1">
                                    <small class="text-muted">{{ t('common.status') }}:</small>
                                    <span :class="['badge', getStatutBadgeClass(pointvente.statut)]">
                                        {{ getStatutLabel(pointvente.statut) }}
                                    </span>
                                </div>
                                <!-- Bouton de réduction -->
                                <button type="button" class="collapse-toggle ms-3" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                    <i class="fa fa-chevron-up"></i>
                                </button>
                            </div>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <PointVenteForm
                                :form="form"
                                :zones="zones"
                                :marchands="marchands"
                                :existingFiles="existingFiles"
                                mode="show"
                            />
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('pointvente.index')" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                        </Link>
                                        <!-- <Link :href="route('pointvente.edit', pointvente.id)" class="btn btn-primary">
                                            <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                                        </Link> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
