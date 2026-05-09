<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '@/Composables/usePermissions';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import BibliothequeForm from './BibliothequeForm.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
defineOptions({
    layout: DashboardLayout,
});
const { t } = useI18n();
const { can } = usePermissions();
const { showStoreLoader, hideLoader } = useLoader();
const props = defineProps({
    item: Object,
});
const showDeleteConfirm = ref(false);
const isDeleting = ref(false);
function confirmDelete() {
    showDeleteConfirm.value = true;
}
function performDelete() {
    isDeleting.value = true;
    router.put(route('bibliotheque.statut', props.item.id), {}, {
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
                <Link v-if="can('bibliotheque-edit')" :href="route('bibliotheque.edit', item.id)" class="btn btn-warning">
                    {{ t('common.edit') }}
                </Link>
                <button v-if="can('bibliotheque-delete')" @click="confirmDelete" class="btn btn-danger">
                    {{ t('common.delete') }}
                </button>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <BibliothequeForm :read-only="true" :bibliotheque="item" />
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
