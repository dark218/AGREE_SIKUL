<script setup>
import { ref } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import DossierApprenantForm from './DossierApprenantForm.vue';
defineOptions({ layout: DashboardLayout });
const page = usePage();
const { t } = useI18n();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    title: String,
    apprenants: Array,
});
const form = useForm({
    apprenant_id: page.props.dossierApprenant?.apprenant_id || null,
    extrait_naissance: page.props.dossierApprenant?.extrait_naissance || null,
    certificat_residence: page.props.dossierApprenant?.certificat_residence || null,
    carnet_sante: page.props.dossierApprenant?.carnet_sante || null,
    dernier_bulletin: page.props.dossierApprenant?.dernier_bulletin || null,
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
                                <h5 class="title mb-0">{{ title }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <DossierApprenantForm
                                :form="form"
                                mode="show"
                                :apprenants="apprenants"
                            />
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('academique.dossiers_apprenants.index')" class="btn btn-danger">
                                            {{ t('actions.back') }}
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
