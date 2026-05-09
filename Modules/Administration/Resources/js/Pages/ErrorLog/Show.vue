<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
defineOptions({
    layout: DashboardLayout
});
const { t } = useI18n();
const props = defineProps({
    title: String,
    errorlog: Object,
});
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
// Formater la date
function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleString('fr-FR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
};
</script>
<template>
    <Head :title="t('modules.administration.errorlog.show')" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <!-- Header -->
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">
                                    <i class="fa fa-exclamation-triangle"></i>
                                </span>
                                <h5 class="title mb-0">
                                    {{ t('modules.administration.errorlog.show') }}
                                </h5>
                            </div>
                            <!-- Bouton de réduction -->
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <!-- Body -->
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <div class="col-xl-12 col-lg-6 mb-20">
                                <div class="">
                                    <!-- Alert Messages -->
                                    <AlertMessage />
                                    <div class="">
                                        <!-- Détails de l'erreur -->
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <div class="row mb-3">
                                                            <div class="col-md-3">
                                                                <strong>{{ t('fields.module') }}:</strong>
                                                            </div>
                                                            <div class="col-md-9">
                                                                <span class="badge bg-primary">{{ errorlog.module }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <div class="col-md-3">
                                                                <strong>{{ t('fields.methode') }}:</strong>
                                                            </div>
                                                            <div class="col-md-9">
                                                                <span class="badge bg-info">{{ errorlog.methode }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <div class="col-md-3">
                                                                <strong>{{ t('fields.date') }}:</strong>
                                                            </div>
                                                            <div class="col-md-9">
                                                                {{ formatDate(errorlog.created_at) }}
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <div class="col-md-3">
                                                                <strong>{{ t('fields.message') }}:</strong>
                                                            </div>
                                                            <div class="col-md-9">
                                                                <div class="alert alert-danger" role="alert">
                                                                    <pre class="mb-0" style="white-space: pre-wrap; word-wrap: break-word;">{{ errorlog.message }}</pre>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Boutons d'action -->
                                        <div class="row mt-4">
                                            <div class="col">
                                                <div class="text-end">
                                                    <Link
                                                        :href="route('administration.errorlog.index')"
                                                        class="btn btn-danger"
                                                    >
                                                        <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                                    </Link>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>
.card {
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
}
.badge {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
}
pre {
    font-family: 'Courier New', Courier, monospace;
    font-size: 0.9rem;
    color: #721c24;
}
</style>
