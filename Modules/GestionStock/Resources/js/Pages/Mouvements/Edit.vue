<script setup>
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import MouvementForm from './MouvementForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showLoader, hideLoader } = useLoader();
const props = defineProps({
    mouvement: Object,
    articles: Array,
    pointsVente: Array,
    typesMouvement: Array,
});
const form = useForm({
    article_id: props.mouvement.article_id || '',
    type_mouvement: props.mouvement.type_mouvement || '',
    quantite: props.mouvement.quantite || '',
    emplacement_source_id: props.mouvement.emplacement_source_id || '',
    emplacement_destination_id: props.mouvement.emplacement_destination_id || '',
    commentaire: props.mouvement.commentaire || '',
});
const submit = () => {
    showLoader(
        t('modules.business.mouvementStock.messages.modificationEnCours'),
        t('modules.business.mouvementStock.messages.veuilezPatienter'),
        'info'
    );
    form.put(route('mouvement-stock.update', props.mouvement.id), {
        onError: () => {
            hideLoader();
        },
        onFinish: () => {
            hideLoader();
        },
    });
};
</script>
<template>
    <Head :title="page.props.title" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('modules.business.mouvementStock.edit') }}</h4>
                <div class="dashboard-btn-wrapper">
                    <Link :href="route('mouvement-stock.index')" class="btn btn-secondary">
                        <i class="fa fa-arrow-left me-1"></i>
                        <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                    </Link>
                </div>
            </div>
            <AlertMessage />
            <div class="card">
                <div class="card-body">
                    <form @submit.prevent="submit">
                        <MouvementForm
                            :form="form"
                            :articles="articles"
                            :points-vente="pointsVente"
                            :types-mouvement="typesMouvement"
                            mode="edit"
                        />
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <Link :href="route('mouvement-stock.index')" class="btn btn-secondary">
                                {{ t('actions.cancel') }}
                            </Link>
                            <button
                                type="submit"
                                class="btn btn-primary"
                                :disabled="form.processing"
                            >
                                <i class="fa fa-save me-1"></i>
                                {{ t('actions.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <FullPageLoader
            :show="isLoading"
            :message="loaderMessage"
            :sub-message="loaderSubMessage"
            :variant="loaderVariant"
        />
    </div>
</template>
