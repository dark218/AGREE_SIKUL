<script setup>
import { ref, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import MenuForm from './MenuForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();

const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    item: Object,
});

console.log('🔍 Edit.vue mounted - props.item:', props.item);
console.log('🔍 props.item?.id:', props.item?.id);

const initialId = props.item?.id || null;
console.log('🔍 initialId for form:', initialId);

const form = useForm({
    id: initialId,
    week_start_date: props.item?.week_start_date || '',
    week_end_date: props.item?.week_end_date || '',
    week_name: props.item?.week_name || '',
    menus: props.item?.menus || {
        lundi: [{ entree: '', plat: '', dessert: '', remarques: '' }],
        mardi: [{ entree: '', plat: '', dessert: '', remarques: '' }],
        mercredi: [{ entree: '', plat: '', dessert: '', remarques: '' }],
        jeudi: [{ entree: '', plat: '', dessert: '', remarques: '' }],
        vendredi: [{ entree: '', plat: '', dessert: '', remarques: '' }],
        samedi: [{ entree: '', plat: '', dessert: '', remarques: '' }],
    },
    statut: props.item?.statut || 'actif',
});

console.log('🔍 After useForm - form.id:', form.id);
console.log('🔍 Full form object:', form.data());

// Watch for changes to props.item and update form
watch(
    () => props.item,
    (newItem) => {
        console.log('⏰ watch: props.item changed', newItem);
        if (newItem) {
            console.log('📌 Setting form.id to:', newItem.id);
            form.id = newItem.id || null;
            form.week_start_date = newItem.week_start_date || '';
            form.week_end_date = newItem.week_end_date || '';
            form.week_name = newItem.week_name || '';
            form.menus = newItem.menus || form.menus;
            form.statut = newItem.statut || 'actif';
            console.log('📌 form.id is now:', form.id);
        }
    },
    { deep: true, immediate: true }
);

const submitForm = () => {
    console.log('📝 submitForm called');
    console.log('form.id:', form.id);
    console.log('props.item?.id:', props.item?.id);

    // Use props.item.id as fallback if form.id is not set
    const menuId = form.id || props.item?.id;
    console.log('🔑 Using menuId:', menuId);

    if (!menuId) {
        console.error('❌ ERROR: No menu ID available!');
        hideLoader();
        return;
    }

    const url = route('menus.update', menuId);
    console.log('Generated route URL:', url);

    showStoreLoader();
    // Use POST with _method: 'PUT' for method spoofing instead of form.put()
    form.post(url, {
        _method: 'PUT',
        onSuccess: () => {
            setTimeout(() => {
                hideLoader();
            }, 500);
        },
        onError: () => {
            hideLoader();
        }
    });
};

const downloadPdf = () => {
    const menuId = form.id || props.item?.id;
    if (menuId) {
        window.location.href = route('menus.pdf', menuId);
    }
};
</script>

<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge"><i class="fa fa-edit"></i></span>
                                <h5 class="title mb-0">{{ t('modules.services.menus.edit') || 'Modifier le Menu' }} - {{ props.item?.week_name }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <MenuForm
                                    :form="form"
                                    mode="edit"
                                />
                                <!-- Buttons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <button @click="downloadPdf" type="button" class="btn btn-info">
                                                <i class="fa fa-file-pdf"></i> {{ t('actions.download_pdf') || 'Télécharger PDF' }}
                                            </button>
                                            <Link :href="route('menus.index')" class="btn btn-danger">
                                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                            </Link>
                                            <button
                                                type="submit"
                                                class="btn btn-primary"
                                                :disabled="form.processing"
                                            >
                                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                                <i class="fa fa-save"></i> {{ t('actions.validate') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Full Page Loader -->
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
