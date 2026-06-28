<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import HierarchyContextBar from '@/Components/Common/HierarchyContextBar.vue';
import { useClasseAutoFill } from '../../composables/useClasseAutoFill';
import { useGeoCascade } from '@/Composables/useGeoCascade';
import { ref } from 'vue';

const { t } = useI18n();

const photoPreview = ref(null);
const photoInputRef = ref(null);

const onPhotoChange = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
        alert('Veuillez sélectionner une image (JPG, PNG, etc.)');
        e.target.value = '';
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        alert('La photo ne doit pas dépasser 5 Mo.');
        e.target.value = '';
        return;
    }
    props.form.photo = file;
    const reader = new FileReader();
    reader.onload = (ev) => { photoPreview.value = ev.target.result; };
    reader.readAsDataURL(file);
};

const clearPhoto = () => {
    props.form.photo = null;
    photoPreview.value = null;
    if (photoInputRef.value) photoInputRef.value.value = '';
};

const photoUrl = (path) => {
    if (!path) return null;
    if (typeof path !== 'string') return null;
    if (path.startsWith('http')) return path;
    return '/storage/' + path.replace(/^\/+/, '');
};

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
    classes: {
        type: Array,
        default: () => [],
    },
    sections: {
        type: Array,
        default: () => [],
    },
    cycles: {
        type: Array,
        default: () => [],
    },
    ecoles: {
        type: Array,
        default: () => [],
    },
    campuses: {
        type: Array,
        default: () => [],
    },
    communes: {
        type: Array,
        default: () => [],
    },
    departements: {
        type: Array,
        default: () => [],
    },
    regions: {
        type: Array,
        default: () => [],
    },
    pays: {
        type: Array,
        default: () => [],
    },
    quartiers: {
        type: Array,
        default: () => [],
    },
    anneesScolaires: {
        type: Array,
        default: () => [],
    },
    typesApprenant: {
        type: Array,
        default: () => [],
    },
    categoriesApprenant: {
        type: Array,
        default: () => [],
    },
});

const isReadOnly = props.mode === 'show';
const classeSelected = computed(() => !!props.form.classe_id);

// Cascade géographique : Quartier → Commune → Département → Région → Pays
useGeoCascade(props.form, {
    quartiers: () => props.quartiers,
    communes: () => props.communes,
    departements: () => props.departements,
    regions: () => props.regions,
});

const autoLabel = (list, id) => {
    if (!id || !list?.length) return '—';
    const found = list.find(item => String(item.id) === String(id));
    return found?.libelle || found?.nom || found?.label || '—';
};
const sectionLabel = computed(() => autoLabel(props.sections, props.form.section_id));
const cycleLabel = computed(() => autoLabel(props.cycles, props.form.cycle_id));
const ecoleLabel = computed(() => autoLabel(props.ecoles, props.form.ecole_id));
const campusLabel = computed(() => autoLabel(props.campuses, props.form.campus_id));

// Auto-fill classe → ecole, campus, section, cycle, annee_scolaire
useClasseAutoFill(props.form);

const statusOptions = [
    { id: 'actif', libelle: t('common.active') || 'Actif' },
    { id: 'suspendu', libelle: t('common.suspended') || 'Suspendu' },
    { id: 'exclu', libelle: 'Exclu' },
    { id: 'diplome', libelle: 'Diplômé' },
    { id: 'abandonne', libelle: 'Abandonné' },
];

const sexeOptions = [
    { id: 'M', libelle: 'Masculin' },
    { id: 'F', libelle: 'Féminin' },
];

const estInterneOptions = [
    { id: true, libelle: 'Oui' },
    { id: false, libelle: 'Non' },
];

// Calculate age from date_naissance
const age = computed(() => {
    if (!props.form.date_naissance) return null;
    const birthDate = new Date(props.form.date_naissance);
    const today = new Date();
    let calculatedAge = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        calculatedAge--;
    }
    return calculatedAge;
});
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- SECTION 1: IDENTITÉ -->
        <div class="col-12">
            <h5 class="section-title">{{ t('fields.identity') || 'Identité' }}</h5>
        </div>

        <!-- Photo de l'apprenant -->
        <div class="col-12">
            <div class="mb-3 photo-upload-block">
                <label class="d-block fw-medium mb-2">
                    <i class="fa fa-camera me-1 text-primary"></i>
                    {{ t('fields.photo') || 'Photo de l\'apprenant' }}
                    <small class="text-muted ms-2">(utilisée pour les cartes apprenant et certificats de scolarité)</small>
                </label>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <!-- Aperçu -->
                    <div class="photo-preview">
                        <img v-if="photoPreview" :src="photoPreview" alt="Aperçu" />
                        <img v-else-if="typeof form.photo === 'string' && form.photo" :src="photoUrl(form.photo)" alt="Photo actuelle" />
                        <div v-else class="photo-placeholder">
                            <i class="fa fa-user"></i>
                        </div>
                    </div>
                    <!-- Actions -->
                    <div class="d-flex flex-column gap-2">
                        <input
                            ref="photoInputRef"
                            type="file"
                            accept="image/*"
                            class="form-control"
                            :disabled="isReadOnly"
                            @change="onPhotoChange"
                        />
                        <small class="text-muted">JPG, PNG ou WEBP — max 5 Mo</small>
                        <button
                            v-if="(photoPreview || (typeof form.photo === 'string' && form.photo)) && !isReadOnly"
                            type="button"
                            class="btn btn-sm btn-outline-danger align-self-start"
                            @click="clearPhoto"
                        >
                            <i class="fa fa-times"></i> Retirer la photo
                        </button>
                    </div>
                </div>
                <span v-if="form.errors?.photo" class="text-danger d-block mt-1">
                    <strong>{{ form.errors.photo }}</strong>
                </span>
            </div>
        </div>

        <!-- Nom -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.nom') || 'Nom' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.nom"
                    type="text"
                    class="form-control"
                    maxlength="255"
                    :placeholder="t('fields.nom') || 'Nom de l\'apprenant'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.nom" class="text-danger">
                    <strong>{{ form.errors.nom }}</strong>
                </span>
            </div>
        </div>

        <!-- Prénoms -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.prenoms') || 'Prénoms' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.prenoms"
                    type="text"
                    class="form-control"
                    maxlength="255"
                    :placeholder="t('fields.prenoms') || 'Prénoms'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.prenoms" class="text-danger">
                    <strong>{{ form.errors.prenoms }}</strong>
                </span>
            </div>
        </div>

        <!-- Matricule -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.matricule') || 'Matricule' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.matricule"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.matricule') || 'Matricule'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.matricule" class="text-danger">
                    <strong>{{ form.errors.matricule }}</strong>
                </span>
            </div>
        </div>

        <!-- Numéro d'inscription -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.numero_inscription') || 'Numéro d\'inscription' }}</label>
                <input
                    v-model="form.numero_inscription"
                    type="text"
                    class="form-control"
                    maxlength="100"
                    :placeholder="t('fields.numero_inscription') || 'Numéro d\'inscription'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.numero_inscription" class="text-danger">
                    <strong>{{ form.errors.numero_inscription }}</strong>
                </span>
            </div>
        </div>

        <!-- Date de naissance -->
        <div class="col-sm-6">
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

        <!-- Âge (calculé) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.age') || 'Âge' }}</label>
                <input
                    :value="age || ''"
                    type="text"
                    class="form-control"
                    :placeholder="t('fields.age') || 'Âge (calculé automatiquement)'"
                    disabled
                />
            </div>
        </div>

        <!-- Lieu de naissance -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.lieu_naissance') || 'Lieu de naissance' }}</label>
                <input
                    v-model="form.lieu_naissance"
                    type="text"
                    class="form-control"
                    maxlength="255"
                    :placeholder="t('fields.lieu_naissance') || 'Lieu de naissance'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.lieu_naissance" class="text-danger">
                    <strong>{{ form.errors.lieu_naissance }}</strong>
                </span>
            </div>
        </div>

        <!-- Commune de naissance -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.commune_naissance') || 'Commune de naissance' }}</label>
                <SearchableSelect
                    v-model="form.commune_naissance_id"
                    :options="communes"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.commune_naissance_id" class="text-danger">
                    <strong>{{ form.errors.commune_naissance_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Département de naissance -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.departement_naissance') || 'Département de naissance' }}</label>
                <SearchableSelect
                    v-model="form.departement_naissance_id"
                    :options="departements"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.departement_naissance_id" class="text-danger">
                    <strong>{{ form.errors.departement_naissance_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Région de naissance -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.region_naissance') || 'Région de naissance' }}</label>
                <SearchableSelect
                    v-model="form.region_naissance_id"
                    :options="regions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.region_naissance_id" class="text-danger">
                    <strong>{{ form.errors.region_naissance_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Pays de naissance -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.pays_naissance') || 'Pays de naissance' }}</label>
                <SearchableSelect
                    v-model="form.pays_naissance_id"
                    :options="pays"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.pays_naissance_id" class="text-danger">
                    <strong>{{ form.errors.pays_naissance_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Nationalité -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.nationalite') || 'Nationalité' }}</label>
                <input
                    v-model="form.nationalite"
                    type="text"
                    class="form-control"
                    maxlength="100"
                    :placeholder="t('fields.nationalite') || 'Nationalité'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.nationalite" class="text-danger">
                    <strong>{{ form.errors.nationalite }}</strong>
                </span>
            </div>
        </div>

        <!-- Sexe -->
        <div class="col-sm-6">
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

        <!-- Groupe sanguin -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.groupe_sanguin') || 'Groupe sanguin' }}</label>
                <input
                    v-model="form.groupe_sanguin"
                    type="text"
                    class="form-control"
                    maxlength="10"
                    :placeholder="t('fields.groupe_sanguin') || 'Groupe sanguin (ex: O+, AB-)'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.groupe_sanguin" class="text-danger">
                    <strong>{{ form.errors.groupe_sanguin }}</strong>
                </span>
            </div>
        </div>

        <!-- SECTION 2: SCOLARITÉ -->
        <div class="col-12">
            <h5 class="section-title">{{ t('fields.scholarity') || 'Scolarité' }}</h5>
        </div>

        <!-- Classe -->
        <div class="col-sm-6">
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

        <!-- Contexte hiérarchique (auto-rempli par la classe) -->
        <HierarchyContextBar :form="form" :ecoles="ecoles" :campuses="campuses" :sections="sections" :cycles="cycles" />

        <!-- Section -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.section') || 'Section' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="sectionLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Cycle -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.cycle') || 'Cycle' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="cycleLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- École -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.ecole') || 'École' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="ecoleLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Campus -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.campus') || 'Campus' }} <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span></label>
                <input type="text" class="form-control" :value="campusLabel" disabled style="background:#eef2f7; color:#64748b; cursor:not-allowed;" />
            </div>
        </div>

        <!-- Année scolaire -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.annee_scolaire') || 'Année scolaire' }}</label>
                <SearchableSelect
                    v-model="form.annee_scolaire_id"
                    :options="anneesScolaires"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.annee_scolaire_id" class="text-danger">
                    <strong>{{ form.errors.annee_scolaire_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Type d'apprenant -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.type_apprenant') || 'Type d\'apprenant' }}</label>
                <SearchableSelect
                    v-model="form.type_apprenant_id"
                    :options="typesApprenant"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.type_apprenant_id" class="text-danger">
                    <strong>{{ form.errors.type_apprenant_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Catégorie d'apprenant -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.categorie_apprenant') || 'Catégorie d\'apprenant' }}</label>
                <SearchableSelect
                    v-model="form.categorie_apprenant_id"
                    :options="categoriesApprenant"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.categorie_apprenant_id" class="text-danger">
                    <strong>{{ form.errors.categorie_apprenant_id }}</strong>
                </span>
            </div>
        </div>

        <!-- École précédente -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.ecole_precedente') || 'École précédente' }}</label>
                <input
                    v-model="form.ecole_precedente"
                    type="text"
                    class="form-control"
                    maxlength="255"
                    :placeholder="t('fields.ecole_precedente') || 'École précédente'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.ecole_precedente" class="text-danger">
                    <strong>{{ form.errors.ecole_precedente }}</strong>
                </span>
            </div>
        </div>

        <!-- Classe précédente -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.classe_precedente') || 'Classe précédente' }}</label>
                <input
                    v-model="form.classe_precedente"
                    type="text"
                    class="form-control"
                    maxlength="100"
                    :placeholder="t('fields.classe_precedente') || 'Classe précédente'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.classe_precedente" class="text-danger">
                    <strong>{{ form.errors.classe_precedente }}</strong>
                </span>
            </div>
        </div>

        <!-- SECTION 3: HÉBERGEMENT -->
        <div class="col-12">
            <h5 class="section-title">{{ t('fields.accommodation') || 'Hébergement' }}</h5>
        </div>

        <!-- Est interne -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.est_interne') || 'Est interne' }}</label>
                <SearchableSelect
                    v-model="form.est_interne"
                    :options="estInterneOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.est_interne" class="text-danger">
                    <strong>{{ form.errors.est_interne }}</strong>
                </span>
            </div>
        </div>

        <!-- Bâtiment -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.batiment') || 'Bâtiment' }}</label>
                <input
                    v-model="form.batiment"
                    type="text"
                    class="form-control"
                    maxlength="100"
                    :placeholder="t('fields.batiment') || 'Bâtiment'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.batiment" class="text-danger">
                    <strong>{{ form.errors.batiment }}</strong>
                </span>
            </div>
        </div>

        <!-- Étage -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.etage') || 'Étage' }}</label>
                <input
                    v-model="form.etage"
                    type="text"
                    class="form-control"
                    maxlength="50"
                    :placeholder="t('fields.etage') || 'Étage'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.etage" class="text-danger">
                    <strong>{{ form.errors.etage }}</strong>
                </span>
            </div>
        </div>

        <!-- Chambre -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.chambre') || 'Chambre' }}</label>
                <input
                    v-model="form.chambre"
                    type="text"
                    class="form-control"
                    maxlength="50"
                    :placeholder="t('fields.chambre') || 'Chambre'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.chambre" class="text-danger">
                    <strong>{{ form.errors.chambre }}</strong>
                </span>
            </div>
        </div>

        <!-- Numéro de lit -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.numero_lit') || 'Numéro de lit' }}</label>
                <input
                    v-model="form.numero_lit"
                    type="text"
                    class="form-control"
                    maxlength="50"
                    :placeholder="t('fields.numero_lit') || 'Numéro de lit'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.numero_lit" class="text-danger">
                    <strong>{{ form.errors.numero_lit }}</strong>
                </span>
            </div>
        </div>

        <!-- SECTION 4: FAMILLE -->
        <div class="col-12">
            <h5 class="section-title">{{ t('fields.family') || 'Famille' }}</h5>
        </div>

        <!-- Nom père -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.nom_pere') || 'Nom du père' }}</label>
                <input
                    v-model="form.nom_pere"
                    type="text"
                    class="form-control"
                    maxlength="255"
                    :placeholder="t('fields.nom_pere') || 'Nom du père'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.nom_pere" class="text-danger">
                    <strong>{{ form.errors.nom_pere }}</strong>
                </span>
            </div>
        </div>

        <!-- Nom mère -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.nom_mere') || 'Nom de la mère' }}</label>
                <input
                    v-model="form.nom_mere"
                    type="text"
                    class="form-control"
                    maxlength="255"
                    :placeholder="t('fields.nom_mere') || 'Nom de la mère'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.nom_mere" class="text-danger">
                    <strong>{{ form.errors.nom_mere }}</strong>
                </span>
            </div>
        </div>

        <!-- Nom tuteur -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.nom_tuteur') || 'Nom du tuteur' }}</label>
                <input
                    v-model="form.nom_tuteur"
                    type="text"
                    class="form-control"
                    maxlength="255"
                    :placeholder="t('fields.nom_tuteur') || 'Nom du tuteur'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.nom_tuteur" class="text-danger">
                    <strong>{{ form.errors.nom_tuteur }}</strong>
                </span>
            </div>
        </div>

        <!-- Nom responsable légal -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.nom_responsable_legal') || 'Nom du responsable légal' }}</label>
                <input
                    v-model="form.nom_responsable_legal"
                    type="text"
                    class="form-control"
                    maxlength="255"
                    :placeholder="t('fields.nom_responsable_legal') || 'Nom du responsable légal'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.nom_responsable_legal" class="text-danger">
                    <strong>{{ form.errors.nom_responsable_legal }}</strong>
                </span>
            </div>
        </div>

        <!-- SECTION 5: ADRESSE DE RÉSIDENCE -->
        <div class="col-12">
            <h5 class="section-title">{{ t('fields.residence_address') || 'Adresse de résidence' }}</h5>
        </div>

        <!-- Adresse -->
        <div class="col-12">
            <div class="mb-3">
                <label>{{ t('fields.adresse') || 'Adresse complète' }}</label>
                <input
                    v-model="form.adresse"
                    type="text"
                    class="form-control"
                    maxlength="255"
                    :placeholder="t('fields.adresse') || 'Adresse complète'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.adresse" class="text-danger">
                    <strong>{{ form.errors.adresse }}</strong>
                </span>
            </div>
        </div>

        <!-- Quartier -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.quartier') || 'Quartier' }}</label>
                <SearchableSelect
                    v-model="form.quartier_id"
                    :options="quartiers"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.quartier_id" class="text-danger">
                    <strong>{{ form.errors.quartier_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Commune de résidence -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.commune_residence') || 'Commune de résidence' }}</label>
                <SearchableSelect
                    v-model="form.commune_residence_id"
                    :options="communes"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.commune_residence_id" class="text-danger">
                    <strong>{{ form.errors.commune_residence_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Arrondissement -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.arrondissement') || 'Arrondissement' }}</label>
                <input
                    v-model="form.arrondissement"
                    type="text"
                    class="form-control"
                    maxlength="100"
                    :placeholder="t('fields.arrondissement') || 'Arrondissement'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.arrondissement" class="text-danger">
                    <strong>{{ form.errors.arrondissement }}</strong>
                </span>
            </div>
        </div>

        <!-- Ville -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.ville') || 'Ville' }}</label>
                <input
                    v-model="form.ville"
                    type="text"
                    class="form-control"
                    maxlength="100"
                    :placeholder="t('fields.ville') || 'Ville'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.ville" class="text-danger">
                    <strong>{{ form.errors.ville }}</strong>
                </span>
            </div>
        </div>

        <!-- Département de résidence -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.departement_residence') || 'Département de résidence' }}</label>
                <SearchableSelect
                    v-model="form.departement_residence_id"
                    :options="departements"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.departement_residence_id" class="text-danger">
                    <strong>{{ form.errors.departement_residence_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Région de résidence -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.region_residence') || 'Région de résidence' }}</label>
                <SearchableSelect
                    v-model="form.region_residence_id"
                    :options="regions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.region_residence_id" class="text-danger">
                    <strong>{{ form.errors.region_residence_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Pays de résidence -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.pays_residence') || 'Pays de résidence' }}</label>
                <SearchableSelect
                    v-model="form.pays_residence_id"
                    :options="pays"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.pays_residence_id" class="text-danger">
                    <strong>{{ form.errors.pays_residence_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Code postal -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.code_postal') || 'Code postal' }}</label>
                <input
                    v-model="form.code_postal"
                    type="text"
                    class="form-control"
                    maxlength="20"
                    :placeholder="t('fields.code_postal') || 'Code postal'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.code_postal" class="text-danger">
                    <strong>{{ form.errors.code_postal }}</strong>
                </span>
            </div>
        </div>

        <!-- Boîte postale -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.boite_postal') || 'Boîte postale' }}</label>
                <input
                    v-model="form.boite_postal"
                    type="text"
                    class="form-control"
                    maxlength="20"
                    :placeholder="t('fields.boite_postal') || 'Boîte postale'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.boite_postal" class="text-danger">
                    <strong>{{ form.errors.boite_postal }}</strong>
                </span>
            </div>
        </div>

        <!-- SECTION 6: CONTACTS -->
        <div class="col-12">
            <h5 class="section-title">{{ t('fields.contacts') || 'Contacts' }}</h5>
        </div>

        <!-- Téléphone 1 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.telephone') || 'Téléphone' }}</label>
                <input
                    v-model="form.telephone"
                    type="tel"
                    class="form-control"
                    maxlength="20"
                    :placeholder="t('fields.telephone') || 'Téléphone'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.telephone" class="text-danger">
                    <strong>{{ form.errors.telephone }}</strong>
                </span>
            </div>
        </div>

        <!-- Téléphone 2 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.telephone2') || 'Téléphone 2' }}</label>
                <input
                    v-model="form.telephone2"
                    type="tel"
                    class="form-control"
                    maxlength="20"
                    :placeholder="t('fields.telephone2') || 'Téléphone 2'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.telephone2" class="text-danger">
                    <strong>{{ form.errors.telephone2 }}</strong>
                </span>
            </div>
        </div>

        <!-- Email -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.email') || 'Email' }}</label>
                <input
                    v-model="form.email"
                    type="email"
                    class="form-control"
                    maxlength="255"
                    :placeholder="t('fields.email') || 'Email'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.email" class="text-danger">
                    <strong>{{ form.errors.email }}</strong>
                </span>
            </div>
        </div>

        <!-- WhatsApp 1 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.whatsapp1') || 'WhatsApp 1' }}</label>
                <input
                    v-model="form.whatsapp1"
                    type="tel"
                    class="form-control"
                    maxlength="20"
                    :placeholder="t('fields.whatsapp1') || 'WhatsApp 1'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.whatsapp1" class="text-danger">
                    <strong>{{ form.errors.whatsapp1 }}</strong>
                </span>
            </div>
        </div>

        <!-- WhatsApp 2 -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.whatsapp2') || 'WhatsApp 2' }}</label>
                <input
                    v-model="form.whatsapp2"
                    type="tel"
                    class="form-control"
                    maxlength="20"
                    :placeholder="t('fields.whatsapp2') || 'WhatsApp 2'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.whatsapp2" class="text-danger">
                    <strong>{{ form.errors.whatsapp2 }}</strong>
                </span>
            </div>
        </div>

        <!-- SECTION 7: ENTRÉE/SORTIE -->
        <div class="col-12">
            <h5 class="section-title">{{ t('fields.entry_exit') || 'Entrée/Sortie à l\'école' }}</h5>
        </div>

        <!-- Date d'entrée à l'école -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_entree_ecole') || 'Date d\'entrée à l\'école' }}</label>
                <input
                    v-model="form.date_entree_ecole"
                    type="date"
                    class="form-control"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.date_entree_ecole" class="text-danger">
                    <strong>{{ form.errors.date_entree_ecole }}</strong>
                </span>
            </div>
        </div>

        <!-- Date de départ de l'école -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.date_depart_ecole') || 'Date de départ de l\'école' }}</label>
                <input
                    v-model="form.date_depart_ecole"
                    type="date"
                    class="form-control"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.date_depart_ecole" class="text-danger">
                    <strong>{{ form.errors.date_depart_ecole }}</strong>
                </span>
            </div>
        </div>

        <!-- Motif de départ de l'école -->
        <div class="col-12">
            <div class="mb-3">
                <label>{{ t('fields.motif_depart_ecole') || 'Motif de départ de l\'école' }}</label>
                <textarea
                    v-model="form.motif_depart_ecole"
                    class="form-control"
                    maxlength="500"
                    :placeholder="t('fields.motif_depart_ecole') || 'Motif de départ (optionnel)'"
                    :disabled="isReadOnly"
                    rows="3"
                />
                <span v-if="form.errors?.motif_depart_ecole" class="text-danger">
                    <strong>{{ form.errors.motif_depart_ecole }}</strong>
                </span>
            </div>
        </div>

        <!-- SECTION 8: STATUT -->
        <div class="col-12">
            <h5 class="section-title">{{ t('fields.status') || 'Statut' }}</h5>
        </div>

        <!-- Statut -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.statut') || 'Statut' }} <span class="text-danger">*</span></label>
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

.photo-upload-block {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    padding: 16px 18px;
}

.photo-preview {
    width: 110px;
    height: 130px;
    border-radius: 10px;
    overflow: hidden;
    border: 2px solid #e2e8f0;
    background: #fff;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.photo-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.photo-placeholder {
    color: #cbd5e1;
    font-size: 42px;
}
</style>
