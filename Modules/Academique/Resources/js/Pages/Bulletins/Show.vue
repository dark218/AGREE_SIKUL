<script setup>
import { ref, reactive, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import BulletinForm from './BulletinForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    title: String,
    bulletin: Object,
    apprenants: Array,
    classes: Array,
    anneesScolaires: Array,
    periodes: Array,
    decisionConseilOptions: Array,
});
const form = reactive({
    apprenant_id: page.props.bulletin?.apprenant_id || null,
    classe_id: page.props.bulletin?.classe_id || null,
    annee_scolaire_id: page.props.bulletin?.annee_scolaire_id || null,
    rang: page.props.bulletin?.rang || '',
    periode: page.props.bulletin?.periode || '',
    decision_conseil: page.props.bulletin?.decision_conseil || '',
});

const moyenneGenerale = computed(() => page.props.bulletin?.moyenne_generale || 'A calculer');

// Templates de bulletin disponibles
const selectedTemplate = ref('auto');
const templates = [
    { id: 'auto',               label: 'Auto-detection (selon le cycle)' },
    { id: 'bulletin',           label: 'Standard' },
    { id: 'bulletin-primaire',  label: 'Primaire (CP - CM2)' },
    { id: 'bulletin-secondaire',label: 'Secondaire (College / Lycee)' },
    { id: 'bulletin-universite',label: 'Universite (CC + Galop + Examen)' },
    { id: 'releve-universite',  label: 'Releve Universitaire (Master, UE/EC)' },
    { id: 'releve-licence',     label: 'Releve Licence (UE Fondamentales)' },
    { id: 'grille-prof',        label: 'Grille Enseignant (Partielles, Devoirs)' },
];

const pdfUrl = computed(() => {
    const base = route('academique.bulletins.pdf', props.bulletin.id);
    return selectedTemplate.value === 'auto' ? base : base + '?template=' + selectedTemplate.value;
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
                            <BulletinForm
                                :form="form"
                                mode="show"
                                :apprenants="apprenants"
                                :classes="classes"
                                :anneesScolaires="anneesScolaires"
                                :periodes="periodes"
                                :decision-conseil-options="decisionConseilOptions"
                            />

                            <!-- Sélecteur de template + Boutons -->
                            <div class="row mt-4">
                                <div class="col-sm-6">
                                    <div class="template-selector">
                                        <label class="template-label">
                                            <i class="fa fa-file-pdf"></i> Modele de bulletin
                                        </label>
                                        <select v-model="selectedTemplate" class="form-select template-select">
                                            <option v-for="tpl in templates" :key="tpl.id" :value="tpl.id">
                                                {{ tpl.label }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6 d-flex align-items-end justify-content-end gap-2">
                                    <a :href="pdfUrl" class="btn btn-success btn-pdf" target="_blank">
                                        <i class="fa fa-download"></i> Telecharger PDF
                                    </a>
                                    <Link :href="route('academique.bulletins.index')" class="btn btn-secondary">
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
</template>

<style scoped>
.template-selector {
    margin-bottom: 10px;
}
.template-label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #0B5697;
    margin-bottom: 6px;
}
.template-label i {
    color: #E5590C;
    margin-right: 4px;
}
.template-select {
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 13px;
    font-weight: 500;
    color: #1e293b;
    transition: border-color 0.3s;
}
.template-select:focus {
    border-color: #0FBCAF;
    box-shadow: 0 0 0 3px rgba(15, 188, 175, 0.12);
    outline: none;
}
.btn-pdf {
    padding: 10px 20px;
    font-weight: 700;
    border-radius: 10px;
}
</style>
