<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InstitutionForm from './InstitutionForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    title: String,
    institution: Object,
    paysList: {
        type: Array,
        default: () => [],
    },
    directeurs: {
        type: Array,
        default: () => [],
    },
});
const form = useForm({
    code: page.props.institution?.code || '',
    nom: page.props.institution?.nom || '',
    sigle: page.props.institution?.sigle || '',
    type: page.props.institution?.type || '',
    statut_juridique: page.props.institution?.statut_juridique || '',
    numero_autorisation: page.props.institution?.numero_autorisation || '',
    date_creation: page.props.institution?.date_creation || '',
    directeur_general_id: page.props.institution?.directeur_general_id || null,
    email_principal: page.props.institution?.email_principal || '',
    telephone_principal: page.props.institution?.telephone_principal || '',
    site_web: page.props.institution?.site_web || '',
    adresse_siege: page.props.institution?.adresse_siege || '',
    code_postal: page.props.institution?.code_postal || '',
    boite_postale: page.props.institution?.boite_postale || '',
    quartier: page.props.institution?.quartier || '',
    commune: page.props.institution?.commune || '',
    ville: page.props.institution?.ville || '',
    departement: page.props.institution?.departement || '',
    region: page.props.institution?.region || '',
    pays_id: page.props.institution?.pays_id || null,
    devise_principale: page.props.institution?.devise_principale || '',
    ministere_tutelle_1: page.props.institution?.ministere_tutelle_1 || '',
    ministere_tutelle_2: page.props.institution?.ministere_tutelle_2 || '',
    ministere_tutelle_3: page.props.institution?.ministere_tutelle_3 || '',
    ministere_tutelle_4: page.props.institution?.ministere_tutelle_4 || '',
    telephone_1: page.props.institution?.telephone_1 || '',
    telephone_2: page.props.institution?.telephone_2 || '',
    telephone_3: page.props.institution?.telephone_3 || '',
    whatsapp_1: page.props.institution?.whatsapp_1 || '',
    whatsapp_2: page.props.institution?.whatsapp_2 || '',
    fax: page.props.institution?.fax || '',
    email_1: page.props.institution?.email_1 || '',
    email_2: page.props.institution?.email_2 || '',
    facebook: page.props.institution?.facebook || '',
    linkedin: page.props.institution?.linkedin || '',
    twitter: page.props.institution?.twitter || '',
    fuseau_horaire: page.props.institution?.fuseau_horaire || '',
    langue_principale: page.props.institution?.langue_principale || 'fr',
    description: page.props.institution?.description || '',
    vision: page.props.institution?.vision || '',
    mission: page.props.institution?.mission || '',
    statut: page.props.institution?.statut || 'actif',
});
</script>
<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">
                                    <i class="fa fa-eye"></i>
                                </span>
                                <h5 class="title mb-0">{{ t('common.institution') }} - {{ page.props.institution?.nom }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <InstitutionForm :form="form" mode="show" :paysList="props.paysList" :directeurs="props.directeurs" />
                            <!-- Bouton Retour -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('parametrage.institution.index')" class="btn btn-danger">
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
