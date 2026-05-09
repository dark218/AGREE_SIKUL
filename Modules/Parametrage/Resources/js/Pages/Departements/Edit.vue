<template>
    <Head :title="t('common.edit')" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ t('common.edit') }} - {{ item.nom || item.name || item.titre }}</h4>
        </div>
        <DepartementForm
            :form="form"
            :regions="regions"
            :pays="pays"
            mode="edit"
            @submit="submitForm"
        />
        <FullPageLoader :show="form.processing" :message="t('common.saving')" />
    </div>
</template>
<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import DepartementForm from './DepartementsForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
defineOptions({
    layout: DashboardLayout,
});
const { t } = useI18n();
const page = usePage();
const { showStoreLoader, hideLoader } = useLoader();
const props = defineProps({
    item: Object,
    regions: { type: Array, default: () => [] },
    pays: { type: Array, default: () => [] },
});
const form = useForm({
    code: page.props.item?.code || '',
    libelle: page.props.item?.libelle || '',
    region_id: page.props.item?.region_id || null,
    etat: page.props.item?.etat || 'actif',
    });
function submitForm() {
    showStoreLoader();
    form.put(route('parametrage.departements.update', page.props.item?.id), {
        onSuccess: () => hideLoader(),
        onError: () => hideLoader(),
    });
}
</script>
