<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import HierarchyContextBar from '@/Components/Common/HierarchyContextBar.vue';

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
    ecoles: {
        type: Array,
        default: () => [],
    },
    institutions: {
        type: Array,
        default: () => [],
    },
    campuses: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'create',
    },
});

const classeSelected = computed(() => !!props.form.classe_id);

const autoLabel = (list, id) => {
    if (!id || !list?.length) return '\u2014';
    const found = list.find(item => String(item.id) === String(id));
    return found?.libelle || found?.nom || found?.label || '\u2014';
};

const ecoleLabel = computed(() => autoLabel(props.ecoles, props.form.ecole_id));
const campusLabel = computed(() => autoLabel(props.campuses, props.form.campus_id));
</script>

<template>
    <div class="row g-3 custom-input">
        <div class="col-sm-12">
            <h6 class="section-title mb-3">{{ t('common.information') || 'Informations' }}</h6>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.classe') || 'Classe' }}</label>
            <SearchableSelect
                v-model="form.classe_id"
                :options="classes"
                optionValue="id"
                optionLabel="libelle"
                :placeholder="t('fields.classe') || 'Sélectionner une classe'"
                :disabled="mode === 'show'"
            />
            <div v-if="form.errors?.classe_id" class="invalid-feedback d-block">
                {{ form.errors.classe_id[0] || form.errors.classe_id }}
            </div>
        </div>

        <!-- HierarchyContextBar: shows auto-derived hierarchy when classe is selected -->
        <HierarchyContextBar
            :form="form"
            :ecoles="ecoles"
            :campuses="campuses"
        />

        <div class="col-sm-6">
            <label>{{ t('fields.ecole') || 'Ecole' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span> <span class="text-danger">*</span></label>
            <input type="text" class="form-control" :value="ecoleLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.institution') || 'Institution' }}</label>
            <SearchableSelect
                v-model="form.institution_id"
                :options="institutions"
                optionValue="id"
                optionLabel="libelle"
                :placeholder="t('fields.institution') || 'Sélectionner une institution'"
                :disabled="mode === 'show'"
            />
            <div v-if="form.errors?.institution_id" class="invalid-feedback d-block">
                {{ form.errors.institution_id[0] || form.errors.institution_id }}
            </div>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.campus') || 'Campus' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
            <input type="text" class="form-control" :value="campusLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
        </div>

        <div class="col-sm-12">
            <h6 class="section-title mb-3">{{ t('common.effectifs') || 'Effectifs' }}</h6>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.nombre_inscrits') || 'Nombre d\'inscrits' }}</label>
            <input v-model.number="form.nombre_inscrits" type="number" class="form-control" :class="{ 'is-invalid': form.errors?.nombre_inscrits }" :disabled="mode === 'show'" />
            <div v-if="form.errors?.nombre_inscrits" class="invalid-feedback d-block">{{ form.errors.nombre_inscrits[0] || form.errors.nombre_inscrits }}</div>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.nombre_filles') || 'Nombre de filles' }}</label>
            <input v-model.number="form.nombre_filles" type="number" class="form-control" :class="{ 'is-invalid': form.errors?.nombre_filles }" :disabled="mode === 'show'" />
            <div v-if="form.errors?.nombre_filles" class="invalid-feedback d-block">{{ form.errors.nombre_filles[0] || form.errors.nombre_filles }}</div>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.nombre_garcons') || 'Nombre de garçons' }}</label>
            <input v-model.number="form.nombre_garcons" type="number" class="form-control" :class="{ 'is-invalid': form.errors?.nombre_garcons }" :disabled="mode === 'show'" />
            <div v-if="form.errors?.nombre_garcons" class="invalid-feedback d-block">{{ form.errors.nombre_garcons[0] || form.errors.nombre_garcons }}</div>
        </div>

        <div class="col-sm-12">
            <h6 class="section-title mb-3">{{ t('common.enseignants') || 'Enseignants' }}</h6>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.nombre_enseignants') || 'Nombre d\'enseignants' }}</label>
            <input v-model.number="form.nombre_enseignants" type="number" class="form-control" :class="{ 'is-invalid': form.errors?.nombre_enseignants }" :disabled="mode === 'show'" />
            <div v-if="form.errors?.nombre_enseignants" class="invalid-feedback d-block">{{ form.errors.nombre_enseignants[0] || form.errors.nombre_enseignants }}</div>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.nombre_enseignants_permanent') || 'Nombre d\'enseignants permanent' }}</label>
            <input v-model.number="form.nombre_enseignants_permanent" type="number" class="form-control" :class="{ 'is-invalid': form.errors?.nombre_enseignants_permanent }" :disabled="mode === 'show'" />
            <div v-if="form.errors?.nombre_enseignants_permanent" class="invalid-feedback d-block">{{ form.errors.nombre_enseignants_permanent[0] || form.errors.nombre_enseignants_permanent }}</div>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.nombre_enseignants_vacataires') || 'Nombre d\'enseignants vacataires' }}</label>
            <input v-model.number="form.nombre_enseignants_vacataires" type="number" class="form-control" :class="{ 'is-invalid': form.errors?.nombre_enseignants_vacataires }" :disabled="mode === 'show'" />
            <div v-if="form.errors?.nombre_enseignants_vacataires" class="invalid-feedback d-block">{{ form.errors.nombre_enseignants_vacataires[0] || form.errors.nombre_enseignants_vacataires }}</div>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.enseignant_referent') || 'Enseignant référent' }}</label>
            <input v-model="form.enseignant_referent" type="text" class="form-control" :class="{ 'is-invalid': form.errors?.enseignant_referent }" :disabled="mode === 'show'" />
            <div v-if="form.errors?.enseignant_referent" class="invalid-feedback d-block">{{ form.errors.enseignant_referent[0] || form.errors.enseignant_referent }}</div>
        </div>

        <div class="col-sm-12">
            <h6 class="section-title mb-3">{{ t('common.services') || 'Services' }}</h6>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.produits_ecole') || 'Produits de l\'école' }}</label>
            <textarea v-model="form.produits_ecole" class="form-control" :class="{ 'is-invalid': form.errors?.produits_ecole }" :disabled="mode === 'show'" rows="3"></textarea>
            <div v-if="form.errors?.produits_ecole" class="invalid-feedback d-block">{{ form.errors.produits_ecole[0] || form.errors.produits_ecole }}</div>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.services_offerts') || 'Services offerts' }}</label>
            <textarea v-model="form.services_offerts" class="form-control" :class="{ 'is-invalid': form.errors?.services_offerts }" :disabled="mode === 'show'" rows="3"></textarea>
            <div v-if="form.errors?.services_offerts" class="invalid-feedback d-block">{{ form.errors.services_offerts[0] || form.errors.services_offerts }}</div>
        </div>

        <div class="col-sm-12">
            <h6 class="section-title mb-3">{{ t('common.status') || 'Statut' }}</h6>
        </div>
        <div class="col-sm-6">
            <label>{{ t('fields.etat') || 'État' }} <span class="text-danger">*</span></label>
            <SearchableSelect v-model="form.etat" :options="[{ id: 'actif', libelle: 'Actif' }, { id: 'inactif', libelle: 'Inactif' }]" optionValue="id" optionLabel="libelle" :placeholder="t('fields.etat') || 'Sélectionner un état'" :disabled="mode === 'show'" />
            <div v-if="form.errors?.etat" class="invalid-feedback d-block">{{ form.errors.etat[0] || form.errors.etat }}</div>
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
