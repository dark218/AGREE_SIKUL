<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import OuvrageForm from './OuvrageForm.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const page = usePage();

const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    ouvrage: {
        type: Object,
        required: true,
    },
    bibliotheques: {
        type: Array,
        default: () => [],
    },
});

// Convert year to date (YYYY-01-01)
const yearToDate = (year) => {
    return year ? `${year}-01-01` : '';
};

const form = useForm({
    bibliotheque_id: page.props.ouvrage?.bibliotheque_id || null,
    titre: page.props.ouvrage?.titre || '',
    auteur: page.props.ouvrage?.auteur || '',
    isbn: page.props.ouvrage?.isbn || '',
    editeur: page.props.ouvrage?.editeur || '',
    date_publication: yearToDate(page.props.ouvrage?.annee_publication),
    categorie: page.props.ouvrage?.categorie || '',
    description: page.props.ouvrage?.description || '',
    nombre_exemplaires: page.props.ouvrage?.nombre_exemplaires || 1,
    statut: page.props.ouvrage?.statut || 'actif',
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
                                <h5 class="title mb-0">{{ t('modules.ressources_logistique.ouvrages.show') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <OuvrageForm :form="form" :bibliotheques="props.bibliotheques" mode="show" />
                            <!-- Bouton Retour -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('ouvrages.index')" class="btn btn-danger">
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
