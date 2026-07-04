<script setup>
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import ApprenantsPicker from '@/Components/Common/ApprenantsPicker.vue';

const { t } = useI18n();

const props = defineProps({
    form: { type: Object, required: true },
    mode: {
        type: String,
        default: 'create',
        validator: (v) => ['create', 'edit', 'show'].includes(v),
    },
    apprenants: { type: Array, default: () => [] },
    liensParente: { type: Array, default: () => [] }, // depuis Paramétrage
});

const isReadOnly = props.mode === 'show';

// Fallback si Paramétrage/LienParente pas encore alimenté
const defaultRelations = [
    { id: 'pere', libelle: 'Père' }, { id: 'mere', libelle: 'Mère' },
    { id: 'tuteur_legal', libelle: 'Tuteur légal' }, { id: 'grand_parent', libelle: 'Grand-parent' },
    { id: 'oncle', libelle: 'Oncle' }, { id: 'tante', libelle: 'Tante' },
    { id: 'frere', libelle: 'Frère' }, { id: 'soeur', libelle: 'Sœur' },
    { id: 'cousin', libelle: 'Cousin' }, { id: 'cousine', libelle: 'Cousine' },
    { id: 'autre', libelle: 'Autre' },
];
const relationOptions = props.liensParente?.length > 0 ? props.liensParente : defaultRelations;

/**
 * Auto-fill : quand un apprenant est sélectionné, on utilise son
 * `nom_tuteur` (ou nom_responsable_legal en fallback) pour pré-remplir
 * les champs nom/prénoms du tuteur en cours de création.
 */
const onApprenantSelected = ({ apprenant, isFirst }) => {
    if (!apprenant || !isFirst) return;
    const source = apprenant.nom_tuteur || apprenant.nom_responsable_legal;
    if (!source) return;

    const parts = source.trim().split(/\s+/);
    const nom = parts.length > 1 ? parts.pop() : parts[0];
    const prenoms = parts.join(' ');

    if (!props.form.nom || String(props.form.nom).trim() === '') props.form.nom = nom;
    if (!props.form.prenoms || String(props.form.prenoms).trim() === '') props.form.prenoms = prenoms;
    if (apprenant.telephone && (!props.form.telephone || String(props.form.telephone).trim() === '')) {
        props.form.telephone = apprenant.telephone;
    }
    if (apprenant.email && (!props.form.email || String(props.form.email).trim() === '')) {
        props.form.email = apprenant.email;
    }
};
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- SECTION 1 : APPRENANTS SUIVIS -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-0">
                <i class="fa fa-users me-2 text-primary"></i>Apprenants suivis
                <small class="text-muted fs-6">— fratrie dans la même école</small>
            </h5>
        </div>
        <div class="col-12">
            <ApprenantsPicker
                v-model="form.apprenant_ids"
                :apprenants="apprenants"
                :disabled="isReadOnly"
                @apprenant-selected="onApprenantSelected"
            />
            <span v-if="form.errors?.apprenant_ids" class="text-danger d-block mt-1">
                <strong>{{ Array.isArray(form.errors.apprenant_ids) ? form.errors.apprenant_ids[0] : form.errors.apprenant_ids }}</strong>
            </span>
        </div>

        <!-- SECTION 2 : IDENTITÉ DU TUTEUR -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">
                <i class="fa fa-id-card me-2 text-primary"></i>Identité du tuteur
            </h5>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>Nom <span class="text-danger">*</span></label>
                <input v-model="form.nom" type="text" class="form-control" :disabled="isReadOnly" placeholder="Nom du tuteur" />
                <span v-if="form.errors?.nom" class="text-danger"><strong>{{ form.errors.nom }}</strong></span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>Prénom(s) <span class="text-danger">*</span></label>
                <input v-model="form.prenoms" type="text" class="form-control" :disabled="isReadOnly" placeholder="Prénom(s)" />
                <span v-if="form.errors?.prenoms" class="text-danger"><strong>{{ form.errors.prenoms }}</strong></span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>Relation</label>
                <SearchableSelect
                    v-model="form.relation"
                    :options="relationOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    placeholder="Sélectionner la relation"
                    :disabled="isReadOnly"
                />
                <small class="text-muted" v-if="liensParente?.length === 0">
                    Paramétrable depuis Paramétrage → Liens de parenté
                </small>
                <span v-if="form.errors?.relation" class="text-danger"><strong>{{ form.errors.relation }}</strong></span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>Profession</label>
                <input v-model="form.profession" type="text" class="form-control" :disabled="isReadOnly" placeholder="Profession" />
            </div>
        </div>

        <!-- SECTION 3 : CONTACT -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">
                <i class="fa fa-phone me-2 text-primary"></i>Contact
            </h5>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>Téléphone</label>
                <input v-model="form.telephone" type="text" class="form-control" :disabled="isReadOnly" placeholder="Numéro de téléphone" />
                <span v-if="form.errors?.telephone" class="text-danger"><strong>{{ form.errors.telephone }}</strong></span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>Email</label>
                <input v-model="form.email" type="email" class="form-control" :disabled="isReadOnly" placeholder="Email" />
                <span v-if="form.errors?.email" class="text-danger"><strong>{{ form.errors.email }}</strong></span>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>Numéro d'urgence</label>
                <input v-model="form.numero_urgence" type="text" class="form-control" :disabled="isReadOnly" placeholder="Numéro d'urgence" />
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>Employeur</label>
                <input v-model="form.employeur" type="text" class="form-control" :disabled="isReadOnly" placeholder="Employeur" />
            </div>
        </div>

        <div class="col-12">
            <div class="mb-3">
                <label>Adresse</label>
                <input v-model="form.adresse" type="text" class="form-control" :disabled="isReadOnly" placeholder="Adresse complète" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.section-title {
    font-weight: 600;
    color: #0b5697;
    border-bottom: 2px solid #0b5697;
    padding-bottom: 8px;
    margin-bottom: 16px;
}
</style>
