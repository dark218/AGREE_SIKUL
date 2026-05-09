<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '@/Composables/usePermissions';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InscriptionCantineForm from './InscriptionCantineForm.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
defineOptions({
    layout: DashboardLayout,
});
const { t } = useI18n();
const { can } = usePermissions();
const { showStoreLoader, hideLoader } = useLoader();
const props = defineProps({
    inscription: Object,
    apprenants: Array,
    servicesCantines: Array,
    anneeScolaires: Array,
});
const showDeleteConfirm = ref(false);
const isDeleting = ref(false);
function confirmDelete() {
    showDeleteConfirm.value = true;
}
function performDelete() {
    isDeleting.value = true;
    router.put(route('inscriptions-cantine.statut', props.inscription.id), {}, {
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

    <Head :title="t('common.inscription-cantine')" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ t('common.view') || 'Voir' }} - {{ inscription.apprenant?.nom }} {{ inscription.apprenant?.prenoms }}</h4>
            <div class="actions">
                <Link v-if="can('inscriptions-cantine-edit')" :href="route('inscriptions-cantine.edit', inscription.id)" class="btn btn-warning">
                    {{ t('common.edit') }}
                </Link>
                <button v-if="can('inscriptions-cantine-delete')" @click="confirmDelete" class="btn btn-danger">
                    {{ t('common.delete') }}
                </button>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <InscriptionCantineForm
                    :read-only="true"
                    :inscriptionCantine="inscription"
                    :apprenants="apprenants"
                    :servicesCantines="servicesCantines"
                    :anneeScolaires="anneeScolaires"
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
