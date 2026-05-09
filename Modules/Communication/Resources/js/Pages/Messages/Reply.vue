<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import MessageForm from './MessageForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
import { useLoader } from '@/Composables/useLoader';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();
const { isLoading, loaderMessage, showStoreLoader, hideLoader } = useLoader();

const props = defineProps({
    item: Object,
    users: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    destinataires_ids: [props.item?.expediteur_id],
    objet: `RE: ${props.item?.objet || ''}`,
    contenu: '',
    etat: 'actif',
});

const submitForm = () => {
    showStoreLoader('Envoi de la réponse...', 'Veuillez patienter');
    form.post(route('messages.store'), {
        onSuccess: () => {
            hideLoader();
            setTimeout(() => {
                window.location.href = route('messages.index');
            }, 1000);
        },
        onError: () => {
            hideLoader();
        },
    });
};

const goBack = () => {
    window.history.back();
};
</script>

<template>
    <Head :title="`Répondre: ${item?.objet || 'Message'}`" />
    <div class="body-wrapper">
        <FullPageLoader
            v-if="isLoading"
            :message="loaderMessage"
            :subMessage="loaderMessage"
        />
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-8 col-lg-10">
                <!-- Conversation Thread -->
                <div class="card mb-30">
                    <div class="card-body">
                        <!-- Original Message -->
                        <div class="message-thread" style="margin-bottom: 30px;">
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #0B5697;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                    <div>
                                        <strong style="color: #0B5697;">{{ item?.expediteur?.nom }} {{ item?.expediteur?.prenoms }}</strong>
                                        <div style="font-size: 12px; color: #666; margin-top: 3px;">
                                            {{ item?.expediteur?.email }}
                                        </div>
                                    </div>
                                    <div style="font-size: 12px; color: #999;">
                                        {{ new Date(item?.date_envoi).toLocaleDateString('fr-FR') }}
                                    </div>
                                </div>
                                <div style="margin: 15px 0; padding-top: 10px; border-top: 1px solid #ddd;">
                                    <strong style="display: block; margin-bottom: 8px;">{{ item?.objet }}</strong>
                                    <p style="margin: 0; color: #333; line-height: 1.6;">
                                        {{ item?.contenu }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Reply Form -->
                        <div style="border-top: 2px solid #e9ecef; padding-top: 20px;">
                            <h5 style="margin-bottom: 20px; color: #0B5697;">Votre réponse</h5>
                            <MessageForm
                                :form="form"
                                :users="users"
                                mode="create"
                            />

                            <div style="margin-top: 20px; display: flex; gap: 10px;">
                                <button
                                    @click="submitForm"
                                    class="btn btn-primary"
                                    :disabled="form.processing"
                                >
                                    <i class="fa fa-paper-plane"></i>
                                    {{ t('actions.send') || 'Envoyer' }}
                                </button>
                                <button
                                    @click="goBack"
                                    class="btn btn-secondary"
                                    :disabled="form.processing"
                                >
                                    {{ t('actions.cancel') || 'Annuler' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.message-thread {
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
