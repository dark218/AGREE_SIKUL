<script setup>
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import SortieLivreForm from './SortieLivreForm.vue';

defineOptions({ layout: DashboardLayout });
const { t } = useI18n();

const props = defineProps({
    sortie: { type: Object, required: true },
    livres: { type: Array, default: () => [] },
    structures: { type: Array, default: () => [] },
});

const d = (v) => (v ? String(v).substring(0, 10) : '');

const form = {
    bibliotheque_id: props.sortie?.bibliotheque_id || null,
    bibliotheque_structure_id: props.sortie?.bibliotheque_structure_id || null,
    type_sortie: props.sortie?.type_sortie || 'pret',
    date_sortie: d(props.sortie?.date_sortie),
    quantite: props.sortie?.quantite || 1,
    date_retour: d(props.sortie?.date_retour),
    tiers: props.sortie?.tiers || '',
    etat_physique: props.sortie?.etat_physique || '',
    etat: props.sortie?.etat || 'actif',
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
                            <h5 class="title mb-0">{{ t('actions.view') || 'Détails' }} — {{ t('fields.book_exit') || "Sortie de livre" }}</h5>
                        </div>
                        <div class="dash-payment-body">
                            <SortieLivreForm :form="form" :livres="livres" :structures="structures" mode="show" />
                            <div class="text-end mt-4">
                                <Link :href="route('academique.sorties-livres.index')" class="btn btn-danger"><i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}</Link>
                                <Link :href="route('academique.sorties-livres.edit', sortie.id)" class="btn btn-primary"><i class="fa fa-edit"></i> {{ t('actions.edit') || 'Modifier' }}</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
