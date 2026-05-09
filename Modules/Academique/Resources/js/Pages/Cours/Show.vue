<script setup>
import { ref, onMounted } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import CoursForm from './CoursForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
onMounted(() => {
    console.log('📄 Show.vue mounted');
    console.log('✅ page.props.cours:', page.props.cours);
    console.log('📚 page.props.matieres:', page.props.matieres?.length);
    console.log('🏫 page.props.classes:', page.props.classes?.length);
    console.log('👨‍🏫 page.props.enseignants:', page.props.enseignants?.length);
});
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    title: String,
    matieres: Array,
    classes: Array,
    enseignants: Array,
});
const form = useForm({
    code: page.props.cours?.code || '',
    titre: page.props.cours?.titre || '',
    description: page.props.cours?.description || '',
    matiere_id: page.props.cours?.matiere_id || null,
    classe_id: page.props.cours?.classe_id || null,
    enseignant_id: page.props.cours?.enseignant_id || null,
    date_debut: page.props.cours?.date_debut || '',
    date_fin: page.props.cours?.date_fin || '',
    statut: page.props.cours?.statut || 'actif',
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
                                <h5 class="title mb-0">{{ t('modules.academique.cours.show') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <CoursForm :form="form" mode="show" :matieres="matieres" :classes="classes" :enseignants="enseignants" />
                            <!-- Bouton Retour -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('academique.cours.index')" class="btn btn-danger">
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
