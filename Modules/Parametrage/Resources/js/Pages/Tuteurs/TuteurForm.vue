<script setup>
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
const { t } = useI18n();
const props = defineProps({
    form: { type: Object, required: true },
    mode: { type: String, default: 'create', validator: (value) => ['create', 'edit', 'show'].includes(value) },
});
const isReadOnly = props.mode === 'show';
const statusOptions = [
    { id: 'actif', libelle: t('common.active') || 'Actif' },
    { id: 'non_actif', libelle: t('common.inactive') || 'Inactif' },
    { id: 'suspendu', libelle: t('common.suspended') || 'Suspendu' },
];
</script>
<template>
    <div class="row g-3 custom-input">
        <div class="col-12"><h5 class="section-title">{{ t('fields.identity') || 'Identité' }}</h5></div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.nom') || 'Nom' }} <span class="text-danger">*</span></label>
                <input v-model="form.nom" type="text" class="form-control" :placeholder="t('fields.nom')" :disabled="isReadOnly" />
                <span v-if="form.errors?.nom" class="text-danger"><strong>{{ form.errors.nom }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.prenoms') || 'Prénoms' }}</label>
                <input v-model="form.prenoms" type="text" class="form-control" :placeholder="t('fields.prenoms')" :disabled="isReadOnly" />
            </div>
        </div>
        <div class="col-12"><h5 class="section-title">{{ t('fields.contact') || 'Contact' }}</h5></div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.email') || 'Email' }}</label>
                <input v-model="form.email" type="email" class="form-control" :placeholder="t('fields.email')" :disabled="isReadOnly" />
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.telephone') || 'Téléphone' }}</label>
                <input v-model="form.telephone" type="text" class="form-control" :placeholder="t('fields.telephone')" :disabled="isReadOnly" maxlength="20" />
            </div>
        </div>
        <div class="col-12">
            <div class="mb-3">
                <label>{{ t('fields.adresse') || 'Adresse' }}</label>
                <textarea v-model="form.adresse" class="form-control" :placeholder="t('fields.adresse')" :disabled="isReadOnly" rows="2"></textarea>
            </div>
        </div>
        <div class="col-12"><h5 class="section-title">{{ t('fields.professional') || 'Professionnel' }}</h5></div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.profession') || 'Profession' }}</label>
                <input v-model="form.profession" type="text" class="form-control" :placeholder="t('fields.profession')" :disabled="isReadOnly" />
            </div>
        </div>
        <div class="col-12"><h5 class="section-title">{{ t('fields.status') || 'Statut' }}</h5></div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.statut') || 'Statut' }}</label>
                <SearchableSelect v-model="form.statut" :options="statusOptions" optionValue="id" optionLabel="libelle" :disabled="isReadOnly" />
            </div>
        </div>
    </div>
</template>
<style scoped>
.section-title {
    font-weight: 600;
    margin-top: 1rem;
    margin-bottom: 1rem;
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 0.5rem;
}
</style>
