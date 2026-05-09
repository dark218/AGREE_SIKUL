<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { usePermissions } from '@/Composables/usePermissions';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import TraductionForm from './TraductionForm.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';

defineOptions({
    layout: DashboardLayout,
});

const { t } = useI18n();
const { can } = usePermissions();

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
});

const showCollapse = ref(true);

const form = ref({
    id: props.item?.id || '',
    code_fr: props.item?.code_fr || '',
    intitule_fr: props.item?.intitule_fr || '',
    code_en: props.item?.code_en || '',
    intitule_en: props.item?.intitule_en || '',
    groupe: props.item?.groupe || '',
    etat: props.item?.etat || 'actif',
    errors: {},
});
</script>

<template>
    <Head :title="t('modules.communication.traductions.show') || 'Détails de la traduction'" />

    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ t('modules.communication.traductions.show') || 'Détails de la traduction' }}</h4>
            <div class="header-actions">
                <button
                    @click="showCollapse = !showCollapse"
                    class="btn btn-secondary btn-sm"
                    type="button"
                >
                    <i :class="['fa', showCollapse ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                    {{ showCollapse ? t('common.collapse') : t('common.expand') }}
                </button>
                <Link
                    v-if="can('traduction-edit')"
                    :href="route('traductions.edit', item.id)"
                    class="btn btn-primary btn-sm"
                >
                    <i class="fa fa-edit"></i> {{ t('common.edit') || 'Modifier' }}
                </Link>
            </div>
        </div>

        <AlertMessage />

        <div v-if="showCollapse" class="dash-payment-item-wrapper">
            <TraductionForm
                :form="form"
                mode="show"
            />
        </div>
    </div>
</template>

<style scoped>
.body-wrapper {
    padding: 2rem;
}

.dashboard-header-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 0.5rem;
}

.dash-payment-item-wrapper {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 2rem;
    animation: slideDown 0.3s ease-in-out;
}

.btn {
    padding: 0.625rem 1.5rem;
    border-radius: 0.375rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
}

.btn-primary {
    background-color: #0B5697;
    color: white;
}

.btn-primary:hover {
    background-color: #084385;
}

.btn-secondary {
    background-color: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background-color: #4b5563;
}

.btn-sm {
    padding: 0.375rem 0.875rem;
    font-size: 0.8125rem;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .body-wrapper {
        padding: 1rem;
    }

    .dashboard-header-wrapper {
        flex-direction: column;
        align-items: flex-start;
    }

    .header-actions {
        width: 100%;
        flex-direction: column;
    }

    .btn {
        width: 100%;
    }

    .dash-payment-item-wrapper {
        padding: 1.5rem;
    }
}
</style>
