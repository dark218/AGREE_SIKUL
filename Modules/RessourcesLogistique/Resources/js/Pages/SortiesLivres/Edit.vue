<template>
    <Head title="Modifier la sortie" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">Modifier la sortie</h4>
        </div>
        <SortieLivreForm :sortie="sortie" :structures="structures" :ouvrages="ouvrages" submit-button-label="Mettre à jour" @submit="submitForm" />
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

const props = defineProps({
    sortie:     { type: Object, required: true },
    structures: { type: Array, default: () => [] },
    ouvrages:   { type: Array, default: () => [] },
});

const isSubmitting = ref(false);
function submitForm(formData) {
    isSubmitting.value = true;
    router.put(route('sorties-livres.update', props.sortie.id), formData, { onFinish: () => { isSubmitting.value = false; } });
}
</script>
