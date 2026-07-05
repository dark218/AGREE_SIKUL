<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import ListeManuelsForm from './ListeManuelsForm.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const page = usePage();

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
    niveaux: {
        type: Array,
        default: () => [],
    },
    cycles: {
        type: Array,
        default: () => [],
    },
    pays: {
        type: Array,
        default: () => [],
    },
});

const showUpdateLoader = ref(false);

const mapRows = (rows, keys) => (rows || []).map(r => {
    const o = {};
    keys.forEach(k => { o[k] = r[k] ?? ''; });
    return o;
});

const form = useForm({
    annee_scolaire_id: props.manuel?.annee_scolaire_id || '',
    ecole_id: props.manuel?.ecole_id || '',
    section_id: props.manuel?.section_id || '',
    niveau_id: props.manuel?.niveau_id || '',
    cycle_id: props.manuel?.cycle_id || '',
    pays_id: props.manuel?.pays_id || '',
    livres: mapRows(props.manuel?.livres, ['titre', 'sujet', 'langue', 'auteurs', 'editeurs', 'annee_edition']),
    cahiers: mapRows(props.manuel?.cahiers, ['utilite', 'type_cahier', 'nombre_pages', 'quantite']),
    fournitures: mapRows(props.manuel?.fournitures, ['utilite', 'designation', 'quantite', 'fournisseur']),
    etat: props.manuel?.etat || 'actif',
});

const submit = () => {
    showUpdateLoader.value = true;
    form.put(route('academique.listes-manuels.update', props.manuel.id), {
        onFinish: () => {
            showUpdateLoader.value = false;
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
                                <span class="dash-payment-badge"><i class="fa fa-edit"></i></span>
                                {{ t('actions.edit') || 'Éditer' }} {{ manuel?.titre_manuel }}
                            </h4>
                            <button type="button" class="dash-payment-title-btn" data-bs-toggle="collapse" data-bs-target="#collapseEdit">
                                <i class="fa fa-angle-down"></i>
                            </button>
                        </div>
                        <div id="collapseEdit" class="collapse show">
                            <div class="dash-payment-body">
                                <form @submit.prevent="submit">
                                    <ListeManuelsForm
                                        :form="form"
                                        :annees-scolaires="anneesScolaires"
                                        :ecoles="ecoles"
                                        :sections="sections"
                                        :niveaux="niveaux"
                                        :cycles="cycles"
                                        :pays="pays"
                                        mode="edit"
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
                                            :disabled="form.processing || showUpdateLoader"
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
