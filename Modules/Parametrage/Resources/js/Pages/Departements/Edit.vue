<template>
    <Head :title="t('common.edit')" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ t('common.edit') }} - {{ props.item.libelle || props.item.nom || props.item.code }}</h4>
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
import { Head, useForm, usePage } from '@inertiajs/vue3';
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
    item: { type: Object, required: true },
    regions: { type: Array, default: () => [] },
    pays: { type: Array, default: () => [] },
});

const form = useForm({
    code: props.item?.code || '',
    libelle: props.item?.libelle || '',
    region_id: props.item?.region_id || null,
    pays_id: props.item?.pays_id || null,
    etat: props.item?.etat || 'actif',
});

function submitForm() {
    showStoreLoader();
    form.put(route('parametrage.departements.update', props.item?.id), {
        onSuccess: () => hideLoader(),
        onError: () => hideLoader(),
    });
}
</script>
