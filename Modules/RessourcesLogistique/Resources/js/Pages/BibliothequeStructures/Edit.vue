<template>
    <Head title="Modifier la bibliothèque" />
    <div class="body-wrapper">
        <div class="dashboard-header-wrapper">
            <h4 class="title">Modifier — {{ structure?.libelle }}</h4>
        </div>
        <BibliothequeStructureForm
            :structure="structure"
            :campuses="campuses"
            :errors="form.errors"
            submit-button-label="Mettre à jour"
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

const props = defineProps({
    structure: { type: Object, required: true },
    campuses:  { type: Array, default: () => [] },
});

const isSubmitting = ref(false);
const form = ref({ errors: {} });

function submitForm(formData) {
    isSubmitting.value = true;
    router.put(route('bibliotheque-structures.update', props.structure.id), formData, {
        onError: (errors) => { form.value.errors = errors; },
        onFinish: () => { isSubmitting.value = false; },
    });
}
</script>
