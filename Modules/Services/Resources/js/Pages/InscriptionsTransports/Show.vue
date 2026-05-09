<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '@/Composables/usePermissions';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InscriptionTransportForm from './InscriptionTransportForm.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
defineOptions({
    layout: DashboardLayout,
});
const { t } = useI18n();
const { can } = usePermissions();
const { showStoreLoader, hideLoader } = useLoader();
const showDeleteConfirm = ref(false);
const isDeleting = ref(false);

const props = defineProps({
    item: Object,
    apprenants: Array,
    servicesTransports: Array,
    anneeScolaires: Array,
});

function confirmDelete() {
    showDeleteConfirm.value = true;
}
function performDelete() {
    isDeleting.value = true;
    router.delete(route('inscriptions-transport.destroy', props.item.id), {
        onSuccess: () => {
            showDeleteConfirm.value = false;
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        }
    });
}
</script>

<template>

    <Head :title="item.nom || item.name || item.titre" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ item.nom || item.name || item.titre }}</h4>
            <div class="actions">
                <Link :href="route('inscriptions-transport.edit', item.id)" class="btn btn-primary">
                    <i class="fa fa-edit"></i> {{ t('actions.edit') }}
                </Link>
                <button @click="confirmDelete" class="btn btn-danger">
                    <i class="fa fa-trash"></i> {{ t('actions.delete') }}
                </button>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <InscriptionTransportForm
                    :read-only="true"
                    :inscription-transport="item"
                    :apprenants="apprenants"
                    :services-transports="servicesTransports"
                    :annees-scolaires="anneeScolaires"
                />
            </div>
        </div>
        <ConfirmModal
            :show="showDeleteConfirm"
            :title="t('common.confirm_delete')"
            :message="t('common.confirm_delete_message')"
            @confirm="performDelete"
            @cancel="showDeleteConfirm = false"
        />
        <FullPageLoader :show="isDeleting" :message="t('common.deleting')" />
    </div>
</template>
