<script setup>
import { ref } from 'vue';
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import NiveauxÉtudeForm from './NiveauxÉtudeForm.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const form = useForm({
    code: page.props.niveauEtude?.code || '',
    libelle: page.props.niveauEtude?.libelle || '',
    sigle: page.props.niveauEtude?.sigle || '',
    cycle_id: page.props.niveauEtude?.cycle_id || null,
    section_id: page.props.niveauEtude?.section_id || null,
    etat: page.props.niveauEtude?.etat || 'actif',
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
                                <span class="dash-payment-badge"><i class="fa fa-eye"></i></span>
                                <h5 class="title mb-0"><i class="fa fa-eye"></i> {{ t('actions.view') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <div>
                                <NiveauxÉtudeForm
                                    :form="form"
                                    :cycles="page.props.cycles"
                                    :sections="page.props.sections"
                                    mode="show"
                                />
                                <!-- Boutons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link v-if="page.props.niveauEtude?.id" :href="route('parametrage.niveaux_etude.edit', page.props.niveauEtude?.id)" class="btn btn-primary">
                                                <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                                            </Link>
                                            <button v-else class="btn btn-primary" disabled>
                                                <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                                            </button>
                                            <Link :href="route('parametrage.niveaux_etude.index')" class="btn btn-danger">
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
        <FullPageLoader :show="false" />
    </div>
</template>
