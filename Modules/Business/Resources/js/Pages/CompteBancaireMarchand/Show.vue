<script setup>
import { ref, reactive } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import CompteBancaireMarchandForm from './CompteBancaireMarchandForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const props = defineProps({
    compte: Object,
});
// Créer des tableaux pour les selects en mode show
const marchands = props.compte?.marchand ? [{
    id: props.compte.marchand.id,
    raison_sociale: props.compte.marchand.raison_sociale,
}] : [];
const banques = props.compte?.banque ? [{
    id: props.compte.banque.id,
    nom: props.compte.banque.nom,
}] : [];
const form = reactive({
    marchand_id: props.compte?.marchand_id || '',
    banque_id: props.compte?.banque_id || '',
    nom_compte: props.compte?.nom_compte || '',
    numero_compte: props.compte?.numero_compte || '',
    iban: props.compte?.iban || '',
    bic_swift: props.compte?.bic_swift || '',
    is_principal: props.compte?.is_principal ?? false,
    is_active: props.compte?.is_active ?? true,
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
                                <h5 class="title mb-0">{{ t('modules.business.comptesBancaires.show') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <CompteBancaireMarchandForm
                                :form="form"
                                :marchands="marchands"
                                :banques="banques"
                                :is_marchand="false"
                                mode="show"
                            />
                            <!-- Bouton Retour -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('compte-bancaire-marchand.index')" class="btn btn-danger">
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
