<template>
    <Head title="Ajouter une bibliothèque" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">Ajouter une bibliothèque</h4>
        </div>
        <BibliothequeStructureForm
            :campuses="campuses"
            :errors="form.errors"
            submit-button-label="Enregistrer"
            @submit="submitForm"
        />
        <FullPageLoader :show="isSubmitting" message="Enregistrement…" />
    </div>
</template>
<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import BibliothequeStructureForm from './BibliothequeStructureForm.vue';
import FullPageLoader from '@/Components/Common/FullPageLoader.vue';
defineOptions({ layout: DashboardLayout });

defineProps({
    campuses: { type: Array, default: () => [] },
});

const isSubmitting = ref(false);
const form = ref({ errors: {} });

function submitForm(formData) {
    isSubmitting.value = true;
    router.post(route('bibliotheque-structures.store'), formData, {
        onError: (errors) => { form.value.errors = errors; },
        onFinish: () => { isSubmitting.value = false; },
    });
}
</script>
