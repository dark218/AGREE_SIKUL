<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import MoyenneMatiereForm from './MoyenneMatiereForm.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    moyenneMatiere: {
        type: Object,
        required: true,
    },
    apprenants: {
        type: Array,
        default: () => [],
    },
    matieres: {
        type: Array,
        default: () => [],
    },
});

// Convert moyenneMatiere to form for display in read-only mode
const form = useForm({
    apprenant_id: props.moyenneMatiere?.apprenant_id || '',
    matiere_id: props.moyenneMatiere?.matiere_id || '',
    moyenne: props.moyenneMatiere?.moyenne || '',
    coefficient: props.moyenneMatiere?.coefficient || '',
    rang: props.moyenneMatiere?.rang || '',
    appreciation: props.moyenneMatiere?.appreciation || '',
});
</script>

<template>
    <Head :title="t('actions.view')" />
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">
                                    <i class="fa fa-eye"></i>
                                </span>
                                <h5 class="title mb-0">{{ t('actions.view') || 'Détails' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <MoyenneMatiereForm
                                :form="form"
                                :apprenants="apprenants"
                                :matieres="matieres"
                                mode="show"
                            />
                            <!-- Boutons -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('academique.moyennes_matieres.index')" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}
                                        </Link>
                                        <Link :href="route('academique.moyennes_matieres.edit', moyenneMatiere.id)" class="btn btn-primary">
                                            <i class="fa fa-edit"></i> {{ t('actions.edit') || 'Modifier' }}
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
</template>
