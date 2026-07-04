<script setup>
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
const { t } = useI18n();
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    classes: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});
const isReadOnly = props.mode === 'show';
const statusOptions = [
    { id: 'actif', libelle: t('common.active') || 'Actif' },
    { id: 'inactif', libelle: t('common.inactive') || 'Inactif' },
    { id: 'suspendu', libelle: t('common.suspended') || 'Suspendu' },
    { id: 'exclus', libelle: t('common.excluded') || 'Exclus' },
];
const sexeOptions = [
    { id: 'M', libelle: 'Masculin' },
    { id: 'F', libelle: 'Féminin' },
];
</script>
<template>
    <div class="row g-3 custom-input">
        <!-- SECTION 1: IDENTITY -->
        <div class="col-12">
            <h5 class="section-title">{{ t('fields.identity') || 'Identité' }}</h5>
        </div>
        <!-- Matricule -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.matricule') || 'Matricule' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.matricule"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.matricule') || 'Matricule unique'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.matricule" class="text-danger">
                    <strong>{{ form.errors.matricule }}</strong>
                </span>
            </div>
        </div>
        <!-- Nom -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.nom') || 'Nom' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.nom"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.nom') || 'Nom'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.nom" class="text-danger">
                    <strong>{{ form.errors.nom }}</strong>
                </span>
            </div>
        </div>
        <!-- Prénoms -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.prenoms') || 'Prénoms' }}</label>
                <input
                    v-model="form.prenoms"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.prenoms') || 'Prénoms'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.prenoms" class="text-danger">
                    <strong>{{ form.errors.prenoms }}</strong>
                </span>
            </div>
        </div>
        <!-- Sexe -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.sexe') || 'Sexe' }}</label>
                <SearchableSelect
                    v-model="form.sexe"
                    :options="sexeOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.sexe" class="text-danger">
                    <strong>{{ form.errors.sexe }}</strong>
                </span>
            </div>
        </div>
        <!-- SECTION 2: CONTACT -->
        <div class="col-12">
            <h5 class="section-title">{{ t('fields.contact') || 'Contact' }}</h5>
        </div>
        <!-- Email -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.email') || 'Email' }}</label>
                <input
                    v-model="form.email"
                    type="email"
                    class="form-control"
                    :placeholder="t('fields.email') || 'Email'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.email" class="text-danger">
                    <strong>{{ form.errors.email }}</strong>
                </span>
            </div>
        </div>
        <!-- Téléphone -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.telephone') || 'Téléphone' }}</label>
                <input
                    v-model="form.telephone"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.telephone') || 'Téléphone'"
                    :disabled="isReadOnly"
                    maxlength="20"
                />
                <span v-if="form.errors?.telephone" class="text-danger">
                    <strong>{{ form.errors.telephone }}</strong>
                </span>
            </div>
        </div>
        <!-- Adresse -->
        <div class="col-12">
            <div class="mb-3">
                <label>{{ t('fields.adresse') || 'Adresse' }}</label>
                <textarea
                    v-model="form.adresse"
                    class="form-control"
                    :placeholder="t('fields.adresse') || 'Adresse'"
                    :disabled="isReadOnly"
                    rows="2"
                ></textarea>
                <span v-if="form.errors?.adresse" class="text-danger">
                    <strong>{{ form.errors.adresse }}</strong>
                </span>
            </div>
        </div>
        <!-- SECTION 3: ACADEMIC -->
        <div class="col-12">
            <h5 class="section-title">{{ t('fields.academic') || 'Académique' }}</h5>
        </div>
        <!-- Classe -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.classe') || 'Classe' }}</label>
                <SearchableSelect
                    v-model="form.classe_id"
                    :options="classes"
                    optionValue="id"
                    optionLabel="nom"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.classe_id" class="text-danger">
                    <strong>{{ form.errors.classe_id }}</strong>
                </span>
            </div>
        </div>
        <!-- Numéro Inscription -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.numero_inscription') || 'Numéro inscription' }}</label>
                <input
                    v-model="form.numero_inscription"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.numero_inscription') || 'Numéro inscription'"
                    :disabled="isReadOnly"
                    maxlength="100"
                />
                <span v-if="form.errors?.numero_inscription" class="text-danger">
                    <strong>{{ form.errors.numero_inscription }}</strong>
                </span>
            </div>
        </div>
        <!-- Date Naissance -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.date_naissance') || 'Date de naissance' }}</label>
                <input
                    v-model="form.date_naissance"
                    type="date"
                    class="form-control"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.date_naissance" class="text-danger">
                    <strong>{{ form.errors.date_naissance }}</strong>
                </span>
            </div>
        </div>
        <!-- SECTION 4: STATUS -->
        <div class="col-12">
            <h5 class="section-title">{{ t('fields.status') || 'Statut' }}</h5>
        </div>
        <!-- Statut -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.statut') || 'Statut' }}</label>
                <SearchableSelect
                    v-model="form.statut"
                    :options="statusOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.statut" class="text-danger">
                    <strong>{{ form.errors.statut }}</strong>
                </span>
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
