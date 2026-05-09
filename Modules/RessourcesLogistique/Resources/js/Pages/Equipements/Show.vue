<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import EquipementForm from './EquipementForm.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    item: Object,
    categories: Array,
    ecoles: Array,
});

// Helper function to format date for input (ISO to YYYY-MM-DD)
const formatDateForInput = (dateValue) => {
    if (!dateValue) return '';
    const dateStr = String(dateValue);
    return dateStr.includes('T') ? dateStr.split('T')[0] : dateStr.split(' ')[0];
};

const form = useForm({
    categorie_id: page.props.item?.categorie_id || '',
    ecole_id: page.props.item?.ecole_id || '',
    nom: page.props.item?.nom || '',
    reference: page.props.item?.reference || '',
    etat: page.props.item?.etat || '',
    localisation: page.props.item?.localisation || '',
    date_acquisition: formatDateForInput(page.props.item?.date_acquisition) || '',
    prix_cents: page.props.item?.prix_cents || 0,
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
                                <h5 class="title mb-0">{{ t('actions.view') }} {{ item?.nom || t('common.equipement') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <EquipementForm :form="form" :categories="categories" :ecoles="ecoles" mode="show" />
                            <!-- Bouton Retour -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('equipements.index')" class="btn btn-danger">
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
