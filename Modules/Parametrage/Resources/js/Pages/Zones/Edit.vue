<script setup>
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ZoneForm from './ZoneForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, showUpdateLoader, hideLoader } = useLoader();
const form = useForm({
    code: page.props.item.code,
    libelle: page.props.item.libelle,
    type_zone: page.props.item.type_zone || '',
    coordinates: page.props.item.coordinates || '',
    description: page.props.item.description || '',
    region_id: page.props.item.region_id || null,
    pays_id: page.props.item.pays_id || null,
    etat: page.props.item.etat,
    });
const submitForm = () => {
    showUpdateLoader();
    form.put(route('parametrage.zones.update', page.props.item?.id), {
        onSuccess: () => hideLoader(),
        onError: () => hideLoader(),
    });
};
</script>
<template>
    <div class="body-wrapper">
        <div class="form-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title"><i class="fa fa-pencil"></i> {{ t('actions.edit') }}</h4>
            </div>
            <form @submit.prevent="submitForm" class="custom-form">
                <ZoneForm
                    :form="form"
                    mode="edit"
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
