<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import MatiereForm from './MatiereForm.vue';

defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const toggleCollapse = () => { isCollapsed.value = !isCollapsed.value; };

const props = defineProps({
    matiere: { type: Object, required: true },
    niveaux: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    cycles: { type: Array, default: () => [] },
    anneesScolaires: { type: Array, default: () => [] },
    ecoles: { type: Array, default: () => [] },
});

const form = useForm({
    code: props.matiere?.code || '',
    libelle: props.matiere?.libelle || '',
    description: props.matiere?.description || '',
    coefficient: props.matiere?.coefficient || '',
    note_max: props.matiere?.note_max || '',
    niveau_id: props.matiere?.niveau_id || '',
    section_id: props.matiere?.section_id || '',
    cycle_id: props.matiere?.cycle_id || '',
    annee_scolaire_id: props.matiere?.annee_scolaire_id || '',
    ecole_id: props.matiere?.ecole_id || '',
    statut: props.matiere?.statut || 'actif',
});
</script>

<template>
    <Head :title="page.props.title" />
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
                                <h5 class="title mb-0">{{ t('actions.view') || 'Voir' }} — {{ matiere?.libelle }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <MatiereForm
                                :form="form"
                                :niveaux="niveaux"
                                :sections="sections"
                                :cycles="cycles"
                                :anneesScolaires="anneesScolaires"
                                :ecoles="ecoles"
                                mode="show"
                            />
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('academique.matieres.index')" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                        </Link>
                                        <Link :href="route('academique.matieres.edit', matiere?.id)" class="btn btn-warning ms-2">
                                            <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
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
