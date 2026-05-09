<script setup>
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import ListeManuelsForm from './ListeManuelsForm.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();

const props = defineProps({
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

const showCreateLoader = ref(false);

const form = useForm({
    annee_scolaire_id: '',
    ecole_id: '',
    section_id: '',
    type_manuel: '',
    titre_manuel: '',
    auteurs: '',
    editeur: '',
    annee_edition: '',
    pays_id: '',
    etat: 'actif',
});

const submit = () => {
    showCreateLoader.value = true;
    form.post(route('academique.listes-manuels.store'), {
        onFinish: () => {
            showCreateLoader.value = false;
        },
    });
};
</script>

<template>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area">
                            <h4 class="dash-payment-title">
                                <span class="dash-payment-badge">!</span>
                                {{ t('actions.create') || 'Créer' }} {{ t('modules.academique.listes_manuels') || 'un manuel' }}
                            </h4>
                            <button type="button" class="dash-payment-title-btn" data-bs-toggle="collapse" data-bs-target="#collapseCreate">
                                <i class="fa fa-angle-down"></i>
                            </button>
                        </div>
                        <div id="collapseCreate" class="collapse show">
                            <div class="dash-payment-body">
                                <form @submit.prevent="submit">
                                    <ListeManuelsForm
                                        :form="form"
                                        :annees-scolaires="anneesScolaires"
                                        :ecoles="ecoles"
                                        :sections="sections"
                                        :pays="pays"
                                        mode="create"
                                    />

                                    <div class="text-end mt-4">
                                        <router-link
                                            :href="route('academique.listes-manuels.index')"
                                            class="btn btn-danger"
                                        >
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') || 'Retour' }}
                                        </router-link>
                                        <button
                                            type="submit"
                                            class="btn btn-primary"
                                            :disabled="form.processing || showCreateLoader"
                                        >
                                            <i class="fa fa-save"></i> {{ t('actions.save') || 'Enregistrer' }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dash-payment-item-wrapper {
    margin-top: 2rem;
}
</style>
