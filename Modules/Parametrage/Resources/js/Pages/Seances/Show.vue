<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import ConfirmModal from '@/Components/Common/ConfirmModal.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const { isLoading, showDeleteLoader, hideLoader } = useLoader();
const showDeleteModal = ref(false);
const isTogglingState = ref(false);
const deleteItem = () => { showDeleteLoader(); router.visit(route('parametrage.seances.destroy', page.props.seance?.id), { method: 'delete', onSuccess: () => { showDeleteModal.value = false; }, onFinish: () => hideLoader() }); };
const toggleState = () => { isTogglingState.value = true; router.visit(route('parametrage.seances.statut', page.props.seance?.id), { method: 'put', onSuccess: () => { isTogglingState.value = false; }, onFinish: () => hideLoader() }); };
</script>
<template>
    <Head :title="t('entities.show_seances') || 'Détails de la séance'" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ t('entities.show_seances') }}</h4>
            <div style="display: flex; gap: 0.5rem;">
                <Link :href="route('parametrage.seances.edit', page.props.seance?.id)" class="btn btn-warning"><i class="fa fa-pencil"></i> {{ t('actions.edit') }}</Link>
                <button @click="isTogglingState = true" class="btn btn-info">{{ page.props.seance?.statut === 'actif' ? t('actions.deactivate') : t('actions.activate') }}</button>
                <button @click="showDeleteModal = true" class="btn btn-danger"><i class="fa fa-trash"></i> {{ t('actions.delete') }}</button>
            </div>
        </div>
        <AlertMessage />
        <div class="details-box">
            <div class="detail-row"><span class="detail-label">{{ t('fields.code') }}:</span><span class="detail-value">{{ page.props.seance?.code }}</span></div>
            <div class="detail-row"><span class="detail-label">{{ t('fields.titre') }}:</span><span class="detail-value">{{ page.props.seance?.titre }}</span></div>
            <div class="detail-row"><span class="detail-label">{{ t('fields.date') }}:</span><span class="detail-value">{{ page.props.seance?.date }}</span></div>
            <div class="detail-row"><span class="detail-label">{{ t('fields.heure_debut') }}:</span><span class="detail-value">{{ page.props.seance?.heure_debut }}</span></div>
            <div class="detail-row"><span class="detail-label">{{ t('fields.classe') }}:</span><span class="detail-value">{{ page.props.seance?.classe?.nom }}</span></div>
            <div class="detail-row"><span class="detail-label">{{ t('fields.statut') }}:</span><span :class="'badge badge-' + (page.props.seance?.statut === 'actif' ? 'success' : 'danger')">{{ t('common.' + page.props.seance?.statut) }}</span></div>
        </div>
        <div style="margin-top: 2rem;"><Link :href="route('parametrage.seances.index')" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> {{ t('actions.back') }}</Link></div>
        <ConfirmModal :show="showDeleteModal" @update:show="showDeleteModal = $event" :title="t('messages.confirm.delete.title')" @confirm="deleteItem" />
        <ConfirmModal :show="isTogglingState" @update:show="isTogglingState = $event" :title="t('messages.confirm.toggle_status.title')" @confirm="toggleState" />
        <FullPageLoader :show="isLoading" />
    </div>
</template>
<style scoped>
.details-box { border: 1px solid #ddd; padding: 1.5rem; border-radius: 0.25rem; background: #f9f9f9; }
.detail-row { display: flex; padding: 0.75rem 0; border-bottom: 1px solid #eee; }
.detail-row:last-child { border-bottom: none; }
.detail-label { font-weight: 600; min-width: 150px; }
.detail-value { flex: 1; }
</style>
