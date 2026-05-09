<script setup>
import { ref, watch, onMounted } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import ManuelForm from './ManuelForm.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();

const isCollapsed = ref(false);

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

const props = defineProps({
    manuel: {
        type: Object,
        required: true,
    },
    anneesScolaires: {
        type: Array,
        default: () => [],
    },
    ecoles: {
        type: Array,
        default: () => [],
    },
    sections: {
        type: Array,
        default: () => [],
    },
    pays: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    annee_scolaire_id: props.manuel?.annee_scolaire_id || '',
    ecole_id: props.manuel?.ecole_id || '',
    section_id: props.manuel?.section_id || '',
    type_manuel: props.manuel?.type_manuel || '',
    titre_manuel: props.manuel?.titre_manuel || '',
    auteurs: props.manuel?.auteurs || '',
    editeur: props.manuel?.editeur || '',
    annee_edition: props.manuel?.annee_edition || '',
    pays_id: props.manuel?.pays_id || '',
    etat: props.manuel?.etat || 'actif',
});

// Initialize form with manuel data when component mounts
onMounted(() => {
    if (props.manuel) {
        form.annee_scolaire_id = props.manuel.annee_scolaire_id || '';
        form.ecole_id = props.manuel.ecole_id || '';
        form.section_id = props.manuel.section_id || '';
        form.type_manuel = props.manuel.type_manuel || '';
        form.titre_manuel = props.manuel.titre_manuel || '';
        form.auteurs = props.manuel.auteurs || '';
        form.editeur = props.manuel.editeur || '';
        form.annee_edition = props.manuel.annee_edition || '';
        form.pays_id = props.manuel.pays_id || '';
        form.etat = props.manuel.etat || 'actif';
    }
});

// Watch for changes to props.manuel and update form
watch(() => props.manuel, (newManuel) => {
    if (newManuel) {
        form.annee_scolaire_id = newManuel.annee_scolaire_id || '';
        form.ecole_id = newManuel.ecole_id || '';
        form.section_id = newManuel.section_id || '';
        form.type_manuel = newManuel.type_manuel || '';
        form.titre_manuel = newManuel.titre_manuel || '';
        form.auteurs = newManuel.auteurs || '';
        form.editeur = newManuel.editeur || '';
        form.annee_edition = newManuel.annee_edition || '';
        form.pays_id = newManuel.pays_id || '';
        form.etat = newManuel.etat || 'actif';
    }
}, { deep: true });
</script>

<template>
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
                                <h5 class="title mb-0">{{ t('modules.academique.manuels.show') || 'Voir un manuel' }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <ManuelForm
                                :form="form"
                                :annees-scolaires="anneesScolaires"
                                :ecoles="ecoles"
                                :sections="sections"
                                :pays="pays"
                                mode="show"
                            />
                            <!-- Bouton Retour -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('academique.manuels.index')" class="btn btn-danger">
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
</template>
