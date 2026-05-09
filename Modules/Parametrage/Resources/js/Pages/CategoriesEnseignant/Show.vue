<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import CategoriesEnseignantForm from './CategoriesEnseignantForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const form = useForm({
    id: null,
    code: '',
    libelle: '',
    ecole_id: null,
    etat: 'actif',
    niveau_qualification: '',
    charge_horaire_min: null,
    charge_horaire_max: null,
    taux_horaire_base: null,
    peut_etre_titulaire: false,
    anciennete_requise: null,
});
// Populate form when component mounts and data is available
onMounted(() => {
    const item = page.props.categorieEnseignant;
    if (item) {
        Object.assign(form, {
            id: item.id,
            code: item.code || '',
            libelle: item.libelle || '',
            ecole_id: item.ecole_id || null,
            etat: item.etat || 'actif',
            niveau_qualification: item.niveau_qualification || '',
            charge_horaire_min: item.charge_horaire_min || null,
            charge_horaire_max: item.charge_horaire_max || null,
            taux_horaire_base: item.taux_horaire_base || null,
            peut_etre_titulaire: item.peut_etre_titulaire || false,
            anciennete_requise: item.anciennete_requise || null,
        });
    }
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
                            <CategoriesEnseignantForm :form="form" :ecoles="page.props.ecoles" :pays="page.props.pays" mode="show" />
                            <!-- Boutons -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link v-if="page.props.categorieEnseignant?.id" :href="route('parametrage.categories_enseignant.edit', page.props.categorieEnseignant?.id)" class="btn btn-primary">
                                            <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                                        </Link>
                                        <button v-else class="btn btn-primary" disabled>
                                            <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                                        </button>
                                        <Link :href="route('parametrage.categories_enseignant.index')" class="btn btn-danger ms-2">
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
