<script setup>
import { ref } from 'vue';
import { Link, usePage, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import NaturesExamenForm from './NatureExamenForm.vue';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const page = usePage();
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
const form = useForm({
    code: page.props.item?.code || '',
    libelle: page.props.item?.libelle || '',
    ecole_id: page.props.item?.ecole_id || null,
    section_id: page.props.item?.section_id || null,
    niveau_id: page.props.item?.niveau_id || null,
    cycle_id: page.props.item?.cycle_id || null,
    poids: page.props.item?.poids || null,
    pays_id: page.props.item?.pays_id || null,
    est_eliminatoire: page.props.item?.est_eliminatoire || false,
    note_eliminatoire: page.props.item?.note_eliminatoire || null,
    duree_minutes: page.props.item?.duree_minutes || null,
    est_rattrapage: page.props.item?.est_rattrapage || false,
    etat: page.props.item?.etat || 'actif',
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
                                <h5 class="title mb-0"><i class="fa fa-eye"></i> {{ t('actions.view') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <NaturesExamenForm
                                :form="form"
                                :sections="page.props.sections"
                                :niveaux="page.props.niveaux"
                                :cycles="page.props.cycles"
                                :pays="page.props.pays"
                                :ecoles="page.props.ecoles"
                                mode="show"
                            />
                            <!-- Boutons -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link v-if="page.props.item?.id" :href="route('parametrage.natures_examens.edit', page.props.item?.id)" class="btn btn-primary">
                                            <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                                        </Link>
                                        <button v-else class="btn btn-primary" disabled>
                                            <i class="fa fa-pencil"></i> {{ t('actions.edit') }}
                                        </button>
                                        <Link :href="route('parametrage.natures_examens.index')" class="btn btn-danger ms-2">
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
