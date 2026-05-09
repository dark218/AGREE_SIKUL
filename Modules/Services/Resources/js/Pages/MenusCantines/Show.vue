<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import MenuCantineForm from './MenuCantineForm.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { showStoreLoader, hideLoader } = useLoader();

const props = defineProps({
    menu: Object,
    weekMenus: Array,
    serviceCantine: Object,
});

// Build form object from weekMenus
const menusObject = {};
const jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
jours.forEach(jour => {
    const existing = props.weekMenus?.find(m => m.jour === jour);
    menusObject[jour] = {
        jour,
        entree: existing?.entree || '',
        plat: existing?.plat || '',
        dessert: existing?.dessert || '',
        remarques: existing?.remarques || '',
    };
});

const formData = {
    service_cantine_id: props.menu?.service_cantine_id,
    week_start_date: props.menu?.week_start_date,
    week_end_date: props.menu?.week_end_date,
    week_name: props.menu?.week_name,
    statut: props.menu?.statut,
    menus: menusObject,
    errors: {},
    processing: false,
};

const showDeleteConfirm = ref(false);
const isDeleting = ref(false);

const confirmDelete = () => {
    showDeleteConfirm.value = true;
};

const performDelete = () => {
    isDeleting.value = true;
    router.delete(route('menu-cantines.destroy', props.menu.id), {
        onSuccess: () => {
            showDeleteConfirm.value = false;
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        }
    });
};
</script>

<template>
    <Head :title="menu?.week_name || 'Menu de Cantine'" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ menu?.week_name }}</h4>
            <div class="actions">
                <Link :href="route('menu-cantines.edit', menu.id)" class="btn btn-warning">
                    <i class="fa fa-edit"></i> {{ t('actions.edit') }}
                </Link>
                <Link :href="route('menu-cantines.pdf', menu.id)" class="btn btn-success" target="_blank">
                    <i class="fa fa-file-pdf"></i> {{ t('actions.download') }}
                </Link>
                <button @click="confirmDelete" class="btn btn-danger">
                    <i class="fa fa-trash"></i> {{ t('actions.delete') }}
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <MenuCantineForm
                    :form="formData"
                    :services-cantines="serviceCantine ? [{ id: serviceCantine.id, libelle: serviceCantine.nom }] : []"
                    mode="show"
                />
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmModal
            :show="showDeleteConfirm"
            :title="t('common.confirm_delete')"
            :message="t('common.confirm_delete_message')"
            @confirm="performDelete"
            @cancel="showDeleteConfirm = false"
        />

        <!-- Loader -->
        <FullPageLoader :show="isDeleting" :message="t('common.deleting')" />
    </div>
</template>
