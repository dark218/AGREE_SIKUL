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
const props = defineProps({
    cours: Object,
});
const showDeleteModal = ref(false);
const isTogglingState = ref(false);
const deleteItem = () => {
    showDeleteLoader();
    router.visit(route('parametrage.cours.destroy', page.props.cours?.id), {
        method: 'delete',
        onSuccess: () => {
            showDeleteModal.value = false;
        },
        onFinish: () => hideLoader(),
    });
};
const toggleState = () => {
    isTogglingState.value = true;
    router.visit(route('parametrage.cours.statut', page.props.cours?.id), {
        method: 'put',
        onSuccess: () => {
            isTogglingState.value = false;
        },
        onFinish: () => hideLoader(),
    });
};
</script>
<template>
    <Head :title="t('entities.show_cours') || 'Détails du cours'" />
    <div class="body-wrapper">
        <div class="show-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ t('entities.show_cours') || 'Détails du cours' }}</h4>
                <div class="dashboard-btn-wrapper">
                    <Link :href="route('parametrage.cours.edit', page.props.cours?.id)" class="btn btn-warning">
                        {{ t('actions.edit') || 'Éditer' }}
                    </Link>
                    <button @click="isTogglingState = true" class="btn btn-info" style="margin-left: 0.5rem;">
                        {{ page.props.cours?.statut === 'actif' ? (t('actions.deactivate') || 'Désactiver') : (t('actions.activate') || 'Activer') }}
                    </button>
                    <button @click="showDeleteModal = true" class="btn btn-danger" style="margin-left: 0.5rem;">
                        {{ t('actions.delete') || 'Supprimer' }}
                    </button>
                </div>
            </div>
            <AlertMessage />
            <div class="details-box">
                <div class="detail-row">
                    <span class="detail-label">{{ t('fields.code') || 'Code' }}:</span>
                    <span class="detail-value">{{ page.props.cours?.code }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">{{ t('fields.titre') || 'Titre' }}:</span>
                    <span class="detail-value">{{ page.props.cours?.titre }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">{{ t('fields.description') || 'Description' }}:</span>
                    <span class="detail-value">{{ page.props.cours?.description }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">{{ t('fields.matiere') || 'Matière' }}:</span>
                    <span class="detail-value">{{ page.props.cours?.matiere?.titre }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">{{ t('fields.classe') || 'Classe' }}:</span>
                    <span class="detail-value">{{ page.props.cours?.classe?.nom }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">{{ t('fields.date_debut') || 'Date début' }}:</span>
                    <span class="detail-value">{{ page.props.cours?.date_debut }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">{{ t('fields.date_fin') || 'Date fin' }}:</span>
                    <span class="detail-value">{{ page.props.cours?.date_fin }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">{{ t('fields.statut') || 'Statut' }}:</span>
                    <span :class="'badge badge-' + (page.props.cours?.statut === 'actif' ? 'success' : 'danger')">
                        {{ t('common.' + page.props.cours?.statut) || page.props.cours?.statut }}
                    </span>
                </div>
            </div>
            <div style="margin-top: 2rem;">
                <Link :href="route('parametrage.cours.index')" class="btn btn-secondary">
                    {{ t('actions.back') || 'Retour' }}
                </Link>
            </div>
        </div>
        <ConfirmModal
            :show="showDeleteModal"
            @update:show="showDeleteModal = $event"
            :title="t('messages.confirm.delete.title') || 'Confirmer la suppression'"
            :message="t('messages.confirm.delete.message') || 'Êtes-vous sûr de vouloir supprimer cet élément?'"
            @confirm="deleteItem"
        />
        <ConfirmModal
            :show="isTogglingState"
            @update:show="isTogglingState = $event"
            :title="t('messages.confirm.toggle_status.title') || 'Confirmer le changement de statut'"
            :message="t('messages.confirm.toggle_status.message') || 'Êtes-vous sûr de vouloir changer le statut?'"
            @confirm="toggleState"
        />
        <FullPageLoader :show="isLoading" />
    </div>
</template>
<style scoped>
.details-box {
    border: 1px solid #ddd;
    padding: 1.5rem;
    border-radius: 0.25rem;
    background: #f9f9f9;
}
.detail-row {
    display: flex;
    padding: 0.75rem 0;
    border-bottom: 1px solid #eee;
}
.detail-row:last-child {
    border-bottom: none;
}
.detail-label {
    font-weight: 600;
    min-width: 150px;
    color: #333;
}
.detail-value {
    flex: 1;
    color: #666;
}
</style>
