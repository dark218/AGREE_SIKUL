<script setup>
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import EntreeLivreForm from './EntreeLivreForm.vue';

defineOptions({ layout: DashboardLayout });
const { t } = useI18n();

const props = defineProps({
    entree: { type: Object, required: true },
    livres: { type: Array, default: () => [] },
    structures: { type: Array, default: () => [] },
});

const d = (v) => (v ? String(v).substring(0, 10) : '');

const form = {
    bibliotheque_id: props.entree?.bibliotheque_id || null,
    bibliotheque_structure_id: props.entree?.bibliotheque_structure_id || null,
    type_entree: props.entree?.type_entree || 'achat',
    date_entree: d(props.entree?.date_entree),
    quantite: props.entree?.quantite || 1,
    date_retour: d(props.entree?.date_retour),
    tiers: props.entree?.tiers || '',
    etat_physique: props.entree?.etat_physique || '',
    etat: props.entree?.etat || 'actif',
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
                            <h5 class="title mb-0">{{ t('actions.view') || 'Détails' }} — {{ t('fields.book_entry') || "Entrée de livre" }}</h5>
                        </div>
                        <div class="dash-payment-body">
                            <EntreeLivreForm :form="form" :livres="livres" :structures="structures" mode="show" />
                            <div class="text-end mt-4">
                                <Link :href="route('academique.entrees-livres.index')" class="btn btn-danger"><i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}</Link>
                                <Link :href="route('academique.entrees-livres.edit', entree.id)" class="btn btn-primary"><i class="fa fa-edit"></i> {{ t('actions.edit') || 'Modifier' }}</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
