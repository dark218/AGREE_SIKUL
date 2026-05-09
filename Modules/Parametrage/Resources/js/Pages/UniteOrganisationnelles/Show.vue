<script setup>
import { ref } from 'vue';
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import UniteOrganisationnellesForm from './UniteOrganisationnellesForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const form = useForm({
    code: page.props.uniteOrganisationnelle?.code || '',
    libelle: page.props.uniteOrganisationnelle?.libelle || '',
    type_unite: page.props.uniteOrganisationnelle?.type_unite || '',
    responsable_id: page.props.uniteOrganisationnelle?.responsable_id || null,
    budget_annuel: page.props.uniteOrganisationnelle?.budget_annuel || null,
    effectif_max: page.props.uniteOrganisationnelle?.effectif_max || null,
    niveau_hierarchique: page.props.uniteOrganisationnelle?.niveau_hierarchique || null,
    ecole_id: page.props.uniteOrganisationnelle?.ecole_id || null,
    unite_mere_id: page.props.uniteOrganisationnelle?.unite_mere_id || null,
    etat: page.props.uniteOrganisationnelle?.etat || 'actif',
    });
</script>
<template>
    <Head :title="t('actions.view')" />
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
                                <h5 class="title mb-0"><i class="fa fa-eye"></i> {{ t('actions.view') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <UniteOrganisationnellesForm
                                :form="form"
                                :unites="page.props.unites"
                                :ecoles="page.props.ecoles"
                                :responsables="page.props.responsables"
                                mode="show"
                            />
                            <!-- Bouton Retour -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('parametrage.unite_organisationnelles.index')" class="btn btn-danger">
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
