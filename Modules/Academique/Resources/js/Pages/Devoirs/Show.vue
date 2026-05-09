<script setup>
import { ref } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import DevoirForm from './DevoirForm.vue';
defineOptions({ layout: DashboardLayout });
const page = usePage();
const { t } = useI18n();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    title: String,
    devoir: Object,
    matieres: Array,
    classes: Array,
});

// DEBUG: Log devoir data
console.log('🔍 SHOW.VUE - Props received:', {
    devoir: props.devoir,
    date_debut_raw: props.devoir?.date_debut,
    date_fin_raw: props.devoir?.date_fin,
    type_debut: typeof props.devoir?.date_debut,
    type_fin: typeof props.devoir?.date_fin,
});

const form = useForm({
    matiere_id: props.devoir?.matiere_id || null,
    classe_id: props.devoir?.classe_id || null,
    titre: props.devoir?.titre || '',
    description: props.devoir?.description || '',
    date_debut: props.devoir?.date_debut || '',
    date_fin: props.devoir?.date_fin || '',
    coefficient: props.devoir?.coefficient || '',
    statut: props.devoir?.statut || 'actif',
});

// DEBUG: Log form data
console.log('🔍 SHOW.VUE - Form initialized:', {
    form: form,
    date_debut_form: form.date_debut,
    date_fin_form: form.date_fin,
});
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
                                <h5 class="title mb-0">{{ title }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <DevoirForm
                                :form="form"
                                mode="show"
                                :matieres="matieres"
                                :classes="classes"
                            />
                            <!-- Bouton Retour -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('academique.devoirs.index')" class="btn btn-danger">
                                            {{ t('actions.back') }}
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
