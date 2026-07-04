<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import CommunesForm from './CommunesForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    departements: {
        type: Array,
        default: () => [],
    },
    regions: {
        type: Array,
        default: () => [],
    },
});
const form = useForm({
    code: page.props.commune?.code || '',
    libelle: page.props.commune?.libelle || '',
    departement_id: page.props.commune?.departement_id || null,
    region_id: page.props.commune?.region_id || null,
    pays_id: page.props.commune?.pays_id || null,
    etat: page.props.commune?.etat || 'actif',
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
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0"><i class="fa fa-eye"></i> {{ t('actions.view') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <CommunesForm :form="form" :departements="props.departements" :regions="props.regions" mode="show" />
                            <!-- Boutons -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link v-if="page.props.commune?.id" :href="route('parametrage.communes.edit', page.props.commune?.id)" class="btn btn-primary">
                                            <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                                        </Link>
                                        <button v-else class="btn btn-primary" disabled>
                                            <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                                        </button>
                                        <Link :href="route('parametrage.communes.index')" class="btn btn-danger ms-2">
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
