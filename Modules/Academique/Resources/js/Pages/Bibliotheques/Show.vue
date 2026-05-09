<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import BibliothequeForm from './BibliothequeForm.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();

const isCollapsed = ref(false);

const props = defineProps({
    item: Object,
    niveaux: {
        type: Array,
        default: () => [],
    },
});

const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};

// Read-only form object
const form = {
    sujet: props.item?.sujet || '',
    langue: props.item?.langue || '',
    niveau_id: props.item?.niveau_id || null,
    type_manuel: props.item?.type_manuel || '',
    titre_manuel: props.item?.titre_manuel || '',
    auteurs: props.item?.auteurs || '',
    editeurs: props.item?.editeurs || '',
    annee_edition: props.item?.annee_edition || '',
    quantite: props.item?.quantite || 0,
    sorties: props.item?.sorties || 0,
    disponibles: props.item?.disponibles || 0,
    etat: props.item?.etat || 'actif',
};
</script>

<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge"><i class="fa fa-eye"></i></span>
                                <h5 class="title mb-0">{{ t('common.view') || t('actions.view') }} - {{ t('modules.academique.bibliotheques.show') }}</h5>
                            </div>
                            <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <BibliothequeForm :form="form" :disabled="true" :niveaux="props.niveaux" mode="show" />
                            <!-- Boutons -->
                            <div class="row mt-3">
                                <div class="col">
                                    <div class="text-end">
                                        <Link :href="route('academique.bibliotheques.index')" class="btn btn-danger">
                                            <i class="fa fa-arrow-left"></i> {{ t('actions.back') }}
                                        </Link>
                                        <Link v-if="can('bibliotheques-edit')" :href="route('academique.bibliotheques.edit', item.id)" class="btn btn-primary">
                                            <i class="fa fa-edit"></i> {{ t('actions.edit') }}
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

<script>
export default {
    methods: {
        can(permission) {
            return true;
        }
    }
}
</script>
