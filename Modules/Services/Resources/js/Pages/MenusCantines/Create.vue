<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import MenuCantineForm from './MenuCantineForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showStoreLoader, hideLoader } = useLoader();

const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    servicesCantines: Array,
});

const form = useForm({
    service_cantine_id: null,
    week_start_date: '',
    week_end_date: '',
    week_name: '',
    statut: 'actif',
    menus: {
        lundi: { jour: 'lundi', entree: '', plat: '', dessert: '', remarques: '' },
        mardi: { jour: 'mardi', entree: '', plat: '', dessert: '', remarques: '' },
        mercredi: { jour: 'mercredi', entree: '', plat: '', dessert: '', remarques: '' },
        jeudi: { jour: 'jeudi', entree: '', plat: '', dessert: '', remarques: '' },
        vendredi: { jour: 'vendredi', entree: '', plat: '', dessert: '', remarques: '' },
        samedi: { jour: 'samedi', entree: '', plat: '', dessert: '', remarques: '' },
    },
});

const submitForm = () => {
    // Convert menus object to array
    const menusArray = Object.values(form.menus);

    showStoreLoader();
    form.post(route('menu-cantines.storeWeek'), {
        data: {
            ...form.data(),
            menus: menusArray,
        },
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
</script>

<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge"><i class="fa fa-plus"></i></span>
                                <h5 class="title mb-0">{{ t('common.menu_cantine') || 'Créer un Menu de Cantine' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <form @submit.prevent="submitForm">
                                <MenuCantineForm
                                    :form="form"
                                    :services-cantines="servicesCantines"
                                    mode="create"
                                />
                                <!-- Buttons -->
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('menu-cantines.index')" class="btn btn-danger">
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
