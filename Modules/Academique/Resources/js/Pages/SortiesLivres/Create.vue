<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
import SortieLivreForm from './SortieLivreForm.vue';

defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();

const props = defineProps({
    livres: { type: Array, default: () => [] },
    structures: { type: Array, default: () => [] },
});

const form = useForm({
    bibliotheque_id: null,
    bibliotheque_structure_id: null,
    type_sortie: 'pret',
    date_sortie: '',
    quantite: 1,
    date_retour: '',
    tiers: '',
    etat_physique: '',
    etat: 'actif',
});

const submit = () => {
    showStoreLoader();
    form.post(route('academique.sorties-livres.store'), { onFinish: () => hideLoader() });
};
</script>

<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex align-items-center">
                            <span class="dash-payment-badge"><i class="fa fa-arrow-up"></i></span>
                            <h5 class="title mb-0">{{ t('actions.create') || 'Créer' }} — {{ t('fields.book_exit') || "Sortie de livre" }}</h5>
                        </div>
                        <div class="dash-payment-body">
                            <AlertMessage />
                            <form @submit.prevent="submit">
                                <SortieLivreForm :form="form" :livres="livres" :structures="structures" mode="create" />
                                <div class="text-end mt-4">
                                    <Link :href="route('academique.sorties-livres.index')" class="btn btn-danger"><i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}</Link>
                                    <button type="submit" class="btn btn-primary" :disabled="form.processing"><i class="fa fa-save"></i> {{ t('actions.validate') || 'Enregistrer' }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
