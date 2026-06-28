<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const page = usePage();

defineProps({
    title: {
        type: String,
        required: true,
    },
    data: {
        type: Object,
        required: true,
    },
    options: {
        type: Array,
        default: () => [],
    },
    editRoute: {
        type: String,
        required: true,
    },
});

// Helper to get label from dropdown
const getLabel = (id, options, fallback = '-') => {
    if (!id || !options) return fallback;
    const item = options.find(o => o.id == id);
    return item ? (item.libelle || item.nom || item.titre || item.label) : fallback;
};

// Group fields into sections
const fieldGroups = [];
</script>

<template>
    <Head :title="page.props.title" />
    <div class="body-wrapper">
        <div class="table-area">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ page.props.title }}</h4>
            </div>
            <AlertMessage />

            <div class="row m-0">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fa fa-eye"></i> {{ t('actions.view') || 'Voir' }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Dynamic fields rendering -->
                            <div class="row">
                                <template v-for="(value, key, index) in data" :key="key">
                                    <div v-if="value !== null && value !== undefined && value !== ''" class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">{{ t(`fields.${key}`) || key }}</label>
                                        <p class="form-control-plaintext">{{ value || '-' }}</p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="card-footer bg-light">
                            <Link :href="indexRoute || '#'" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}
                            </Link>
                            <Link :href="editRoute" class="btn btn-primary">
                                <i class="fa fa-edit"></i> {{ t('actions.edit') || 'Modifier' }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.form-control-plaintext {
    padding: 0.375rem 0;
    border: none;
    background: transparent;
}

.card {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e0e0e0;
}

.card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.card-title {
    font-size: 1.125rem;
    color: #333;
}
</style>
