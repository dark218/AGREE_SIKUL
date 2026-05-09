<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

defineOptions({ layout: DashboardLayout });

const { t } = useI18n();

const props = defineProps({
    exemplaires: Array,
    apprenants: Array,
});

console.log("CreateTest props:", props);
console.log("Exemplaires:", props.exemplaires);
console.log("Apprenants:", props.apprenants);
</script>

<template>
    <Head :title="t('common.add') || 'Ajouter'" />
    <div class="body-wrapper">
        <div class="card">
            <div class="card-body">
                <h3>Test Emprunt Create</h3>

                <p><strong>Exemplaires reçus:</strong> {{ exemplaires?.length || 0 }}</p>
                <ul>
                    <li v-for="ex in exemplaires" :key="ex.id">
                        {{ ex.id }} - {{ ex.code_exemplaire }}
                    </li>
                </ul>

                <p><strong>Apprenants reçus:</strong> {{ apprenants?.length || 0 }}</p>
                <ul>
                    <li v-for="ap in apprenants" :key="ap.id">
                        {{ ap.id }} - {{ ap.name }}
                    </li>
                </ul>

                <p>✓ Si tu vois les listes ci-dessus, les données sont chargées correctement!</p>
            </div>
        </div>
    </div>
</template>
