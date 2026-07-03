<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AccompagnateurForm from './AccompagnateurForm.vue';
import ApprenantsBadges from '@/Components/Common/ApprenantsBadges.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const page = usePage();

const isCollapsed = ref(false);

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    accompagnateur: {
        type: Object,
        required: true,
    },
    ecoles: {
        type: Array,
        default: () => [],
    },
    institutions: {
        type: Array,
        default: () => [],
    },
    campuses: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    // School Information
    ecole_id: page.props.accompagnateur?.ecole_id || '',
    institution_id: page.props.accompagnateur?.institution_id || '',
    campus_id: page.props.accompagnateur?.campus_id || '',
    // Accompagnant 1
    accompagnant1_civilite: page.props.accompagnateur?.accompagnant1_civilite || '',
    accompagnant1_nom: page.props.accompagnateur?.accompagnant1_nom || '',
    accompagnant1_prenoms: page.props.accompagnateur?.accompagnant1_prenoms || '',
    accompagnant1_nom_complet: page.props.accompagnateur?.accompagnant1_nom_complet || '',
    accompagnant1_lien: page.props.accompagnateur?.accompagnant1_lien || '',
    accompagnant1_photo_id: page.props.accompagnateur?.accompagnant1_photo_id || '',
    accompagnant1_photo_url: page.props.accompagnateur?.accompagnant1_photo_url || null,
    // Accompagnant 2
    accompagnant2_civilite: page.props.accompagnateur?.accompagnant2_civilite || '',
    accompagnant2_nom: page.props.accompagnateur?.accompagnant2_nom || '',
    accompagnant2_prenoms: page.props.accompagnateur?.accompagnant2_prenoms || '',
    accompagnant2_nom_complet: page.props.accompagnateur?.accompagnant2_nom_complet || '',
    accompagnant2_lien: page.props.accompagnateur?.accompagnant2_lien || '',
    accompagnant2_photo_id: page.props.accompagnateur?.accompagnant2_photo_id || '',
    accompagnant2_photo_url: page.props.accompagnateur?.accompagnant2_photo_url || null,
    // Accompagnant 3
    accompagnant3_civilite: page.props.accompagnateur?.accompagnant3_civilite || '',
    accompagnant3_nom: page.props.accompagnateur?.accompagnant3_nom || '',
    accompagnant3_prenoms: page.props.accompagnateur?.accompagnant3_prenoms || '',
    accompagnant3_nom_complet: page.props.accompagnateur?.accompagnant3_nom_complet || '',
    accompagnant3_lien: page.props.accompagnateur?.accompagnant3_lien || '',
    accompagnant3_photo_id: page.props.accompagnateur?.accompagnant3_photo_id || '',
    accompagnant3_photo_url: page.props.accompagnateur?.accompagnant3_photo_url || null,
    // Audit
    etat: page.props.accompagnateur?.etat || 'actif',
});
</script>

<template>
    <Head :title="page.props.title || 'Accompagnateur'" />
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
                                <h5 class="title mb-0">{{ page.props.title || 'Voir un accompagnateur' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <!-- Apprenants transportés/accompagnés -->
                            <div class="apprenants-section mb-4">
                                <h5 class="section-header">
                                    <i class="fa fa-users me-2"></i>
                                    Apprenants accompagnés
                                    <span class="badge bg-primary ms-2">{{ accompagnateur?.apprenants?.length || 0 }}</span>
                                </h5>
                                <ApprenantsBadges
                                    :apprenants="accompagnateur?.apprenants || []"
                                    mode="card"
                                    empty-label="Aucun apprenant rattaché à cet accompagnateur"
                                />
                            </div>

                            <AccompagnateurForm
                                :form="form"
                                :ecoles="ecoles"
                                :institutions="institutions"
                                :campuses="campuses"
                                mode="show"
                            />
                            <!-- Boutons -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('accompagnateurs.index')" class="btn btn-danger">
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

<style scoped>
.apprenants-section {
    background: #ffffff;
    border-radius: 12px;
    padding: 18px 20px;
    border: 1px solid #e2e8f0;
}
.section-header {
    color: #0b5697;
    font-weight: 700;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid #0b5697;
}
</style>
