<script setup>
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import BibliothequeStructureForm from './BibliothequeStructureForm.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();

const props = defineProps({
    structure: { type: Object, required: true },
    campuses: { type: Array, default: () => [] },
});

const form = {
    code: props.structure?.code || '',
    libelle: props.structure?.libelle || '',
    localisation: props.structure?.localisation || '',
    campus_id: props.structure?.campus_id || null,
    responsable: props.structure?.responsable || '',
    statut_disponibilite: props.structure?.statut_disponibilite || 'disponible',
    etat: props.structure?.etat || 'actif',
    errors: {},
};
</script>

<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex align-items-center">
                            <span class="dash-payment-badge"><i class="fa fa-eye"></i></span>
                            <h5 class="title mb-0">{{ t('actions.view') || 'Détails' }} — {{ structure?.libelle }}</h5>
                        </div>
                        <div class="dash-payment-body">
                            <BibliothequeStructureForm :form="form" :campuses="campuses" mode="show" />
                            <div class="text-end mt-4">
                                <Link :href="route('academique.bibliotheque-structures.index')" class="btn btn-danger">
                                    <i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}
                                </Link>
                                <Link :href="route('academique.bibliotheque-structures.edit', structure.id)" class="btn btn-primary">
                                    <i class="fa fa-edit"></i> {{ t('actions.edit') || 'Modifier' }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
