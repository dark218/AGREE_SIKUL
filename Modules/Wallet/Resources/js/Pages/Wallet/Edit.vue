<script setup>
import { ref } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';
import StylishSelect from '@/Components/Common/StylishSelect.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';
defineOptions({ layout: DashboardLayout });
const { t } = useI18n();
const { isLoading, loaderMessage, loaderSubMessage, loaderVariant, showLoader, hideLoader } = useLoader();
const props = defineProps({
    wallet: Object,
    statuts: Array,
});
const form = useForm({
    statut: props.wallet?.statut || '',
});
const isCollapsed = ref(false);
const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value;
};
function getStatutBadgeClass(status) {
    const classes = {
        'actif': 'bg-success',
        'suspendu': 'bg-warning',
        'ferme': 'bg-danger'
    };
    return classes[status] || 'bg-secondary';
}
function submit() {
    showLoader(t('common.loading'), t('messages.success.updated'));
    form.put(route('wallet.update', props.wallet.id), {
        preserveScroll: true,
        onFinish: () => {
            hideLoader();
        },
    });
}
</script>
<template>
    <div class="body-wrapper">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 mb-30">
                <div class="dash-payment-item-wrapper">
                    <div class="dash-payment-item active">
                        <div class="dash-payment-title-area d-flex justify-content-between align-items-center" @click="toggleCollapse">
                            <div class="d-flex align-items-center">
                                <span class="dash-payment-badge">!</span>
                                <h5 class="title mb-0">{{ t('modules.wallet.edit') }}</h5>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center gap-1">
                                    <small class="text-muted">{{ t('common.status') }}:</small>
                                    <span :class="['badge', getStatutBadgeClass(wallet?.statut)]">
                                        {{ wallet?.statut_label }}
                                    </span>
                                </div>
                                <button type="button" class="collapse-toggle" :class="{ collapsed: isCollapsed }" @click.stop="toggleCollapse">
                                    <i class="fa fa-chevron-up"></i>
                                </button>
                            </div>
                        </div>
                        <div class="dash-payment-body" :class="{ collapsed: isCollapsed }">
                            <AlertMessage />
                            <!-- Informations du wallet (lecture seule) -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0"><i class="fa fa-user me-2"></i>{{ t('modules.wallet.sections.owner') }}</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('modules.wallet.fields.ownerType') }}</div>
                                                <div class="col-8 fw-bold text-capitalize">{{ wallet?.owner?.type }}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('fields.name') }}</div>
                                                <div class="col-8">{{ wallet?.owner?.nom }} {{ wallet?.owner?.prenoms }}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('fields.email') }}</div>
                                                <div class="col-8">{{ wallet?.owner?.email || '-' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0"><i class="fa fa-wallet me-2"></i>{{ t('modules.wallet.sections.walletInfo') }}</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('modules.wallet.fields.reference') }}</div>
                                                <div class="col-8 fw-bold">{{ wallet?.reference || '-' }}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('modules.wallet.fields.solde') }}</div>
                                                <div class="col-8 fw-bold text-success">{{ wallet?.solde }}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-4 text-muted">{{ t('fields.countryCurrency') }}</div>
                                                <div class="col-8">{{ wallet?.pays_devise?.pays }} - {{ wallet?.pays_devise?.devise }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Formulaire de modification -->
                            <form @submit.prevent="submit">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">{{ t('common.status') }} <span class="text-danger">*</span></label>
                                            <StylishSelect
                                                v-model="form.statut"
                                                :options="statuts"
                                                option-value="value"
                                                option-label="label"
                                                :placeholder="t('common.status')"
                                                :searchable="false"
                                            />
                                            <div v-if="form.errors.statut" class="text-danger small mt-1">{{ form.errors.statut }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col">
                                        <div class="text-end">
                                            <Link :href="route('wallet.index')" class="btn btn-danger me-2">
                                                {{ t('actions.cancel') }}
                                            </Link>
                                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                                {{ t('actions.save') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <FullPageLoader :show="isLoading" :message="loaderMessage" :sub-message="loaderSubMessage" :variant="loaderVariant" />
    </div>
</template>
