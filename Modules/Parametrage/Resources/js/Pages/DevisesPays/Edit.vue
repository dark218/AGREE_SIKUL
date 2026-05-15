<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import DevisesPaysForm from './DevisesPaysForm.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const page = usePage();

const props = defineProps({
    item: { type: Object, required: true },
    pays: { type: Array, default: () => [] },
    devises: { type: Array, default: () => [] },
});

const form = useForm({
    code: props.item?.code || '',
    pays_id: props.item?.pays_id || null,
    devise_id: props.item?.devise_id || null,
    taux_change: props.item?.taux_change || 1,
    etat: props.item?.etat || 'actif',
});

const submit = () => {
    form.put(route('parametrage.devises_pays.update', props.item?.id));
};
</script>
<template>
    <Head :title="t('actions.edit')" />
    <div class="body-wrapper">
        <div class="form-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title"><i class="fa fa-pencil"></i> {{ t('actions.edit') }}</h4>
            </div>
            <form @submit.prevent="submit" class="custom-form">
                <DevisesPaysForm :form="form" :pays="props.pays" :devises="props.devises" mode="edit" />
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">{{ t('actions.save') }}</button>
                    <Link :href="route('parametrage.devises_pays.index')" class="btn btn-secondary ms-2">
                        {{ t('actions.cancel') }}
                    </Link>
                </div>
            </form>
            <AlertMessage v-if="form.errors" type="danger" :messages="form.errors" />
        </div>
    </div>
</template>
