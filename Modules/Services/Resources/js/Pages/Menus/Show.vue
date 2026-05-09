<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '@/Composables/usePermissions';
import { useLoader } from '@/Composables/useLoader';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import MenuForm from './MenuForm.vue';
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

// Create reactive form data from props for MenuForm binding
const formData = computed(() => props.item || {});

const showDeleteConfirm = ref(false);
const isDeleting = ref(false);
const isTogglingStatus = ref(false);

function confirmDelete() {
    showDeleteConfirm.value = true;
}

function performDelete() {
    isDeleting.value = true;
    router.delete(route('menus.destroy', formData.value.id), {
        onSuccess: () => {
            showDeleteConfirm.value = false;
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        }
    });
}

function toggleStatus() {
    isTogglingStatus.value = true;
    router.put(route('menus.statut', formData.value.id), {}, {
        onSuccess: () => {
            isTogglingStatus.value = false;
        },
        onError: () => {
            isTogglingStatus.value = false;
        }
    });
}

function downloadPdf() {
    window.location.href = route('menus.pdf', formData.value.id);
}
</script>

<template>
    <Head :title="formData.week_name || formData.nom || formData.name || formData.titre" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ formData.week_name || formData.nom || formData.name || formData.titre }}</h4>
            <div class="actions">
                <button @click="downloadPdf" class="btn btn-info">
                    <i class="fa fa-file-pdf"></i> {{ t('actions.download_pdf') || 'Télécharger PDF' }}
                </button>
                <button v-if="can('menus-edit')" @click="toggleStatus" :disabled="isTogglingStatus" class="btn btn-secondary">
                    <i :class="formData.statut === 'actif' ? 'fa fa-ban' : 'fa fa-check'"></i>
                    {{ formData.statut === 'actif' ? (t('actions.deactivate') || 'Désactiver') : (t('actions.activate') || 'Activer') }}
                </button>
                <Link v-if="can('menus-edit')" :href="route('menus.edit', formData.id)" class="btn btn-warning">
                    <i class="fa fa-edit"></i> {{ t('actions.edit') }}
                </Link>
                <button v-if="can('menus-delete')" @click="confirmDelete" class="btn btn-danger">
                    <i class="fa fa-trash"></i> {{ t('actions.delete') }}
                </button>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <MenuForm :form="formData" mode="show" />
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
