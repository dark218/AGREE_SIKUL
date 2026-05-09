<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const { t } = useI18n();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    users: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'create',
    },
});
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Informations Section -->
        <div class="col-sm-12">
            <h6 class="section-title mb-3">{{ t('common.information') || 'Informations' }}</h6>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.titre') || 'Titre' }} <span class="text-danger">*</span></label>
            <input
                v-model="form.titre"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': form.errors?.titre }"
                :disabled="mode === 'show'"
            />
            <div v-if="form.errors?.titre" class="invalid-feedback d-block">
                {{ form.errors.titre[0] || form.errors.titre }}
            </div>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.categorie') || 'Catégorie' }} <span class="text-danger">*</span></label>
            <SearchableSelect
                v-model="form.categorie_id"
                :options="categories"
                optionValue="id"
                optionLabel="libelle"
                :placeholder="t('fields.categorie') || 'Sélectionner une catégorie'"
                :disabled="mode === 'show'"
            />
            <div v-if="form.errors?.categorie_id" class="invalid-feedback d-block">
                {{ form.errors.categorie_id[0] || form.errors.categorie_id }}
            </div>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.description') || 'Description' }}</label>
            <textarea
                v-model="form.description"
                class="form-control"
                :class="{ 'is-invalid': form.errors?.description }"
                :disabled="mode === 'show'"
                rows="3"
            ></textarea>
            <div v-if="form.errors?.description" class="invalid-feedback d-block">
                {{ form.errors.description[0] || form.errors.description }}
            </div>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.auteur') || 'Auteur' }} <span class="text-danger">*</span></label>
            <SearchableSelect
                v-model="form.auteur_id"
                :options="users"
                optionValue="id"
                optionLabel="name"
                :placeholder="t('fields.auteur') || 'Sélectionner un auteur'"
                :disabled="mode === 'show'"
            />
            <div v-if="form.errors?.auteur_id" class="invalid-feedback d-block">
                {{ form.errors.auteur_id[0] || form.errors.auteur_id }}
            </div>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.date_publication') || 'Date de publication' }}</label>
            <input
                v-model="form.date_publication"
                type="date"
                class="form-control"
                :class="{ 'is-invalid': form.errors?.date_publication }"
                :disabled="mode === 'show'"
            />
            <div v-if="form.errors?.date_publication" class="invalid-feedback d-block">
                {{ form.errors.date_publication[0] || form.errors.date_publication }}
            </div>
        </div>
    </div>
</template>

<style scoped>
.section-title {
    font-weight: 600;
    color: #333;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}
.custom-input {
    padding: 20px 0;
}
</style>
