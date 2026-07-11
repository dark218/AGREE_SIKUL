<template>
    <Head title="Nouvelle sortie de livre" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">Sortie de livre</h4>
        </div>
        <SortieLivreForm :structures="structures" :ouvrages="ouvrages" submit-button-label="Enregistrer" @submit="submitForm" />
        <FullPageLoader :show="isSubmitting" message="Enregistrement…" />
    </div>
</template>
<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import SortieLivreForm from './SortieLivreForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
defineOptions({ layout: DashboardLayout });

defineProps({
    structures: { type: Array, default: () => [] },
    ouvrages:   { type: Array, default: () => [] },
});

const isSubmitting = ref(false);
function submitForm(formData) {
    isSubmitting.value = true;
    router.post(route('sorties-livres.store'), formData, { onFinish: () => { isSubmitting.value = false; } });
}
</script>
