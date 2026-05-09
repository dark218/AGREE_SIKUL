<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import TerminalForm from './TerminalForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const props = defineProps({
    terminal: Object,
});
const form = {
    type_terminal: props.terminal.type_terminal || '',
    fabricant: props.terminal.fabricant || '',
    modele: props.terminal.modele || '',
    numero_serie: props.terminal.numero_serie || '',
    status: props.terminal.status || '',
    points_vente_id: props.terminal.point_vente?.id || '',
    marchand_id: props.terminal.marchand?.id || '',
    version_firmware: props.terminal.version_firmware || '',
    pki_cert_id: props.terminal.pki_cert_id || '',
    metadata: props.terminal.metadata || null,
    errors: {},
};
const pointsVente = props.terminal.point_vente ? [{ id: props.terminal.point_vente.id, label: props.terminal.point_vente.nom }] : [];
const marchands = props.terminal.marchand ? [{ id: props.terminal.marchand.id, label: props.terminal.marchand.raison_sociale }] : [];
const typesTerminaux = [
    { value: 'pos', label: 'Terminal de paiement' },
    { value: 'mobile', label: 'Application mobile' },
    { value: 'kiosk', label: 'Kiosque' },
    { value: 'web', label: 'Interface web' },
    { value: 'api', label: 'API' },
];
const statuts = [
    { value: 'deploye', label: 'Déployé' },
    { value: 'actif', label: 'Actif' },
    { value: 'suspendu', label: 'Suspendu' },
    { value: 'retire', label: 'Retiré' },
];
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const getStatutBadgeClass = (status) => {
    const classes = {
        'deploye': 'bg-info',
        'actif': 'bg-success',
        'suspendu': 'bg-warning',
        'retire': 'bg-danger',
    };
    return classes[status] || 'bg-secondary';
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
                                <h5 class="title mb-0">{{ t('modules.business.terminaux.show') }}</h5>
                            </div>
                            <div class="d-flex flex-grow-1 align-items-center justify-content-end">
                                <div class="d-flex align-items-center gap-1">
                                    <small class="text-muted">{{ t('common.status') }}:</small>
                                    <span :class="['badge', getStatutBadgeClass(terminal.status)]">
                                        {{ terminal.status_label }}
                                    </span>
                                </div>
                                <button type="button" class="collapse-toggle ms-3" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                    <i class="fa fa-chevron-up"></i>
                                </button>
                            </div>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <TerminalForm
                                :form="form"
                                :pointsVente="pointsVente"
                                :marchands="marchands"
                                :typesTerminaux="typesTerminaux"
                                :statuts="statuts"
                                mode="show"
                            />
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label>UUID</label>
                                        <input type="text" :value="terminal.uuid" class="form-control" disabled />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label>{{ t('fields.lastCheckin') }}</label>
                                        <input type="text" :value="terminal.last_checkin" class="form-control" disabled />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label>{{ t('fields.createdAt') }}</label>
                                        <input type="text" :value="terminal.created_at" class="form-control" disabled />
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('terminal.index')" class="btn btn-danger">
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
