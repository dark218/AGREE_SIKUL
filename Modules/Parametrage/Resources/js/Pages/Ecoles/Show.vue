<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import EcoleForm from './EcoleForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    title: String,
    ecole: Object,
    campuses: {
        type: Array,
        default: () => [],
    },
    typeEtablissements: {
        type: Array,
        default: () => [],
    },
    directeurs: {
        type: Array,
        default: () => [],
    },
    paysList: {
        type: Array,
        default: () => [],
    },
});
const form = useForm({
    code: page.props.ecole?.code || '',
    nom: page.props.ecole?.nom || '',
    campus_id: page.props.ecole?.campus_id || null,
    type_enseignement: page.props.ecole?.type_enseignement || null,
    directeur_id: page.props.ecole?.directeur_id || null,
    capacite_totale: page.props.ecole?.capacite_totale || null,
    statut: page.props.ecole?.statut || 'actif',
    // Adresse et localisation
    adresse_siege: page.props.ecole?.adresse_siege || '',
    code_postal: page.props.ecole?.code_postal || '',
    boite_postale: page.props.ecole?.boite_postale || '',
    ville: page.props.ecole?.ville || '',
    quartier: page.props.ecole?.quartier || '',
    commune: page.props.ecole?.commune || '',
    departement: page.props.ecole?.departement || '',
    region: page.props.ecole?.region || '',
    pays_id: page.props.ecole?.pays_id || null,
    // Contacts - Téléphones
    telephone_principal: page.props.ecole?.telephone_principal || '',
    telephone_2: page.props.ecole?.telephone_2 || '',
    telephone_3: page.props.ecole?.telephone_3 || '',
    // Contacts - WhatsApp
    whatsapp_1: page.props.ecole?.whatsapp_1 || '',
    whatsapp_2: page.props.ecole?.whatsapp_2 || '',
    // Contacts - Autres
    fax: page.props.ecole?.fax || '',
    email_principal: page.props.ecole?.email_principal || '',
    email_1: page.props.ecole?.email_1 || '',
    site_web: page.props.ecole?.site_web || '',
    facebook: page.props.ecole?.facebook || '',
    linkedin: page.props.ecole?.linkedin || '',
    twitter: page.props.ecole?.twitter || '',
    // Description
    description: page.props.ecole?.description || '',
    vision: page.props.ecole?.vision || '',
    mission: page.props.ecole?.mission || '',
    // Dirigeants
    dirigeants: page.props.ecole?.dirigeants || [],
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
                                <h5 class="title mb-0">{{ t('common.ecole') }} - {{ page.props.ecole?.nom }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <EcoleForm
                                :form="form"
                                :campuses="campuses"
                                :typeEtablissements="typeEtablissements"
                                :directeurs="directeurs"
                                :paysList="paysList"
                                mode="show"
                            />
                            <!-- Bouton Retour -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('parametrage.ecoles.index')" class="btn btn-danger">
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
