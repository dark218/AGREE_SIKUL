<script setup>
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ZoneForm from './ZoneForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, showStoreLoader, hideLoader } = useLoader();
defineProps({
    regions: {
        type: Array,
        default: () => [],
    },
    pays: {
        type: Array,
        default: () => [],
    },
});
const form = useForm({
    code: '',
    libelle: '',
    type_zone: '',
    coordinates: '',
    description: '',
    region_id: null,
    pays_id: null,
    etat: 'actif',
    });
const submitForm = () => {
    showStoreLoader();
    form.post(route('parametrage.zones.store'), {
        onFinish: () => {
            hideLoader();
        }
    });
};
</script>
<template>
    <div class="body-wrapper">
        <div class="form-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('actions.create') }}</h4>
            </div>
            <form @submit.prevent="submitForm" class="custom-form">
                <ZoneForm
                    :form="form"
                    mode="create"
                    :regions="page.props.regions || []"
                    :pays="page.props.pays || []"
                />
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">{{ t('actions.save') }}</button>
                    <Link :href="route('parametrage.zones.index')" class="btn btn-secondary ms-2">
                        {{ t('actions.cancel') }}
                    </Link>
                </div>
            </form>
            <AlertMessage v-if="form.errors" type="danger" :messages="form.errors" />
        </div>
        <FullPageLoader :show="isLoading" />
    </div>
</template>
