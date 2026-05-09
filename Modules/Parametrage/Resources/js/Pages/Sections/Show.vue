<script setup>
import { ref } from 'vue';
import { Link, usePage, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import SectionsForm from './SectionsForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
// Récupérer les données directement depuis page.props (source de vérité Inertia)
const ecolesData = page.props.ecoles || [];
const niveauxData = page.props.niveaux || [];
const form = useForm({
    code: page.props.section?.code || '',
    libelle: page.props.section?.libelle || '',
    ecole_id: page.props.section?.ecole_id || null,
    niveau_etude_id: page.props.section?.niveau_etude_id || null,
    annee_scolaire_id: page.props.section?.annee_scolaire_id || null,
    etat: page.props.section?.etat || 'actif',
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
                                <h5 class="title mb-0"><i class="fa fa-eye"></i> {{ t('actions.view') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <SectionsForm :form="form" :ecoles="ecolesData" :niveaux="niveauxData" :anneesScolaires="page.props.anneesScolaires" mode="show" />
                            <!-- Boutons -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link v-if="page.props.section?.id" :href="route('parametrage.sections.edit', page.props.section?.id)" class="btn btn-primary">
                                            <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                                        </Link>
                                        <button v-else class="btn btn-primary" disabled>
                                            <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                                        </button>
                                        <Link :href="route('parametrage.sections.index')" class="btn btn-danger ms-2">
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
