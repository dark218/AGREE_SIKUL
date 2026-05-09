<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const page = usePage();

const { title, pays } = defineProps({
    title: String,
    pays: Object,
});
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
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">{{ t('fields.code') || 'Code' }}</label>
                                    <p class="form-control-plaintext">{{ pays.code || '-' }}</p>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">{{ t('fields.code_3_chars') || 'Code 3 caractères' }}</label>
                                    <p class="form-control-plaintext">{{ pays.code_3_chars || '-' }}</p>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">{{ t('fields.code_2_chars') || 'Code 2 caractères' }}</label>
                                    <p class="form-control-plaintext">{{ pays.code_2_chars || '-' }}</p>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">{{ t('fields.libelle') || 'Libellé' }}</label>
                                    <p class="form-control-plaintext">{{ pays.libelle || '-' }}</p>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">{{ t('fields.capitale') || 'Capitale' }}</label>
                                    <p class="form-control-plaintext">{{ pays.capitale || '-' }}</p>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">{{ t('fields.nombre') || 'Nombre' }}</label>
                                    <p class="form-control-plaintext">{{ pays.nombre || '-' }}</p>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">{{ t('fields.continent') || 'Continent' }}</label>
                                    <p class="form-control-plaintext">{{ pays.continent || '-' }}</p>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">{{ t('fields.etat') || 'État' }}</label>
                                    <p class="form-control-plaintext">
                                        <span v-if="pays.etat === 'actif'" class="badge bg-success">Actif</span>
                                        <span v-else-if="pays.etat === 'inactif'" class="badge bg-danger">Inactif</span>
                                        <span v-else class="badge bg-secondary">{{ pays.etat }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer bg-light">
                            <Link href="#" @click.prevent="$router.back()" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}
                            </Link>
                            <Link :href="route('parametrage.pays.edit', pays.id)" class="btn btn-primary">
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
