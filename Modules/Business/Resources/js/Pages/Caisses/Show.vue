<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import CaisseForm from './CaisseForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const props = defineProps({
    caisse: Object,
    types: Array,
    statuts: Array,
});
const form = {
    points_vente_id: props.caisse.point_vente?.id || '',
    code: props.caisse.code || '',
    nom: props.caisse.nom || '',
    type: props.caisse.type || '',
    statut: props.caisse.statut || '',
    parametres_json: props.caisse.parametres_json || null,
    errors: {},
};
const pointsVente = props.caisse.point_vente ? [{ id: props.caisse.point_vente.id, label: props.caisse.point_vente.nom }] : [];
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const getStatutBadgeClass = (statut) => {
    const classes = {
        'ouverte': 'bg-success',
        'fermee': 'bg-secondary',
        'bloquee': 'bg-danger',
    };
    return classes[statut] || 'bg-secondary';
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
                                <h5 class="title mb-0">{{ t('modules.business.caisses.show') }}</h5>
                            </div>
                            <div class="d-flex flex-grow-1 align-items-center justify-content-end">
                                <div class="d-flex align-items-center gap-1">
                                    <small class="text-muted">{{ t('common.status') }}:</small>
                                    <span :class="['badge', getStatutBadgeClass(caisse.statut)]">
                                        {{ caisse.statut_label }}
                                    </span>
                                </div>
                                <button type="button" class="collapse-toggle ms-3" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                    <i class="fa fa-chevron-up"></i>
                                </button>
                            </div>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <CaisseForm
                                :form="form"
                                :pointsVente="pointsVente"
                                :types="types"
                                :statuts="statuts"
                                mode="show"
                            />
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label>{{ t('fields.createdAt') }}</label>
                                        <input type="text" :value="caisse.created_at" class="form-control" disabled />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label>{{ t('fields.updatedAt') }}</label>
                                        <input type="text" :value="caisse.updated_at" class="form-control" disabled />
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('caisse.index')" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                        </Link>
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
