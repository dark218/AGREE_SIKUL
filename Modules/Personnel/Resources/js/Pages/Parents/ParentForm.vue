<script setup>
import { computed, defineProps, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import ApprenantsPicker from '@/Components/Common/ApprenantsPicker.vue';

const { t } = useI18n();

console.log('ParentForm component loading...');

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    apprenants: {
        type: Array,
        default: () => [],
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
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});

console.log('✓ ParentForm props received:', {
    apprenants: props.apprenants?.length || 0,
    classes: props.classes?.length || 0,
    ecoles: props.ecoles?.length || 0,
    institutions: props.institutions?.length || 0,
    campuses: props.campuses?.length || 0,
    mode: props.mode,
});

const isReadOnly = props.mode === 'show';

const autoLabel = (list, id) => {
    if (!id || !list?.length) return '\u2014';
    const found = list.find(item => String(item.id) === String(id));
    return found?.libelle || found?.nom || found?.label || '\u2014';
};

const classeLabel = computed(() => autoLabel(props.classes, props.form.classe_id));
const ecoleLabel = computed(() => autoLabel(props.ecoles, props.form.ecole_id));
const institutionLabel = computed(() => autoLabel(props.institutions, props.form.institution_id));
const campusLabel = computed(() => autoLabel(props.campuses, props.form.campus_id));

const etatOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

// Init des listes si absentes du form
if (!props.form.apprenant_ids) props.form.apprenant_ids = [];
if (!props.form.lien_parente) props.form.lien_parente = [];

/**
 * Auto-fill : quand l'utilisateur sélectionne un apprenant, on pré-remplit
 * les champs Père / Mère / Tuteur / Responsable légal depuis les
 * `nom_pere`, `nom_mere`, `nom_tuteur`, `nom_responsable_legal` saisis
 * sur la fiche apprenant. On ne remplace que si le champ est encore vide,
 * pour ne pas écraser une saisie manuelle en cours.
 */
const onApprenantSelected = ({ apprenant, isFirst }) => {
    if (!apprenant || !isFirst) return; // n'auto-fill que sur la 1ère ligne (apprenant principal)

    const fillIfEmpty = (target, value) => {
        if (value && (!props.form[target] || String(props.form[target]).trim() === '')) {
            props.form[target] = value;
        }
    };

    // Père — apprenant.nom_pere ex "Jean DUPONT" → on split prénoms+nom au mieux
    if (apprenant.nom_pere) {
        const parts = apprenant.nom_pere.trim().split(/\s+/);
        const nom = parts.length > 1 ? parts.pop() : parts[0];
        const prenoms = parts.join(' ');
        fillIfEmpty('pere_nom', nom);
        fillIfEmpty('pere_prenoms', prenoms);
        fillIfEmpty('pere_nom_complet', apprenant.nom_pere);
    }

    // Mère
    if (apprenant.nom_mere) {
        const parts = apprenant.nom_mere.trim().split(/\s+/);
        const nom = parts.length > 1 ? parts.pop() : parts[0];
        const prenoms = parts.join(' ');
        fillIfEmpty('mere_nom', nom);
        fillIfEmpty('mere_prenoms', prenoms);
        fillIfEmpty('mere_nom_complet', apprenant.nom_mere);
    }

    // Tuteur 1
    if (apprenant.nom_tuteur) {
        const parts = apprenant.nom_tuteur.trim().split(/\s+/);
        const nom = parts.length > 1 ? parts.pop() : parts[0];
        const prenoms = parts.join(' ');
        fillIfEmpty('tuteur1_nom', nom);
        fillIfEmpty('tuteur1_prenoms', prenoms);
        fillIfEmpty('tuteur1_nom_complet', apprenant.nom_tuteur);
    }

    // Responsable légal → Tuteur 2 si Tuteur 1 déjà rempli, sinon Tuteur 1
    if (apprenant.nom_responsable_legal) {
        const parts = apprenant.nom_responsable_legal.trim().split(/\s+/);
        const nom = parts.length > 1 ? parts.pop() : parts[0];
        const prenoms = parts.join(' ');
        const targetPrefix = props.form.tuteur1_nom_complet ? 'tuteur2' : 'tuteur1';
        fillIfEmpty(`${targetPrefix}_nom`, nom);
        fillIfEmpty(`${targetPrefix}_prenoms`, prenoms);
        fillIfEmpty(`${targetPrefix}_nom_complet`, apprenant.nom_responsable_legal);
    }
};

// Auto-fill de l'ecole seulement (la classe/campus n'ont plus de sens
// puisqu'un parent peut avoir plusieurs enfants dans des classes différentes).
// On garde ecole_id pour le filtre + rattachement métier.
watch(() => props.form.apprenant_ids, (ids) => {
    if (!ids || ids.length === 0) return;
    const firstApprenant = props.apprenants.find(a => String(a.id) === String(ids[0]));
    if (firstApprenant?.ecole_id) props.form.ecole_id = firstApprenant.ecole_id;
}, { deep: true, immediate: true });
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- SECTION 1: INFORMATIONS DE L'APPRENANT -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-0">{{ t('common.student_information') || 'Informations de l\'apprenant' }}</h5>
        </div>

        <div class="col-12">
            <div class="mb-3">
                <label class="fw-medium">
                    {{ t('fields.apprenants') || 'Apprenants rattachés' }}
                    <small class="text-muted">— fratrie dans la même école</small>
                </label>
                <ApprenantsPicker
                    v-model="form.apprenant_ids"
                    v-model:lien-parente="form.lien_parente"
                    :apprenants="apprenants"
                    :show-lien="true"
                    :disabled="isReadOnly"
                    @apprenant-selected="onApprenantSelected"
                />
                <span v-if="form.errors?.apprenant_ids" class="text-danger d-block mt-1">
                    <strong>{{ Array.isArray(form.errors.apprenant_ids) ? form.errors.apprenant_ids[0] : form.errors.apprenant_ids }}</strong>
                </span>
            </div>
        </div>

        <!-- Bloc classe/école/institution/campus retiré :
             un parent pouvant avoir des enfants dans des classes/campus
             différents (mais dans la même école — cf. règle métier),
             il n'y a plus de sens à dupliquer ces infos sur la fiche
             Parent. L'école commune est déjà visible dans le badge
             du composant <ApprenantsPicker> ci-dessus. -->

        <!-- SECTION 2: INFORMATIONS DU PÈRE -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">Informations du père</h5>
        </div>

        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.pere_nom') || 'Nom du père' }}</label>
                <input v-model="form.pere_nom" :disabled="isReadOnly" type="text" class="form-control" placeholder="Nom du père" />
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.pere_prenoms') || 'Prénom(s) du père' }}</label>
                <input v-model="form.pere_prenoms" :disabled="isReadOnly" type="text" class="form-control" placeholder="Prénom(s) du père" />
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.pere_nom_complet') || 'Nom complet du père' }}</label>
                <input v-model="form.pere_nom_complet" :disabled="isReadOnly" type="text" class="form-control" placeholder="Nom complet du père" />
            </div>
        </div>

        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.pere_profession') || 'Profession du père' }}</label>
                <input v-model="form.pere_profession" :disabled="isReadOnly" type="text" class="form-control" placeholder="Profession du père" />
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.pere_organisation_travail') || 'Organisation de travail' }}</label>
                <input v-model="form.pere_organisation_travail" :disabled="isReadOnly" type="text" class="form-control" placeholder="Organisation de travail" />
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.pere_ville_travail') || 'Ville de travail' }}</label>
                <input v-model="form.pere_ville_travail" :disabled="isReadOnly" type="text" class="form-control" placeholder="Ville de travail" />
            </div>
        </div>

        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.pere_pays_travail') || 'Pays de travail' }}</label>
                <input v-model="form.pere_pays_travail" :disabled="isReadOnly" type="text" class="form-control" placeholder="Pays de travail" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.pere_adresse_residence') || 'Adresse de résidence' }}</label>
                <textarea v-model="form.pere_adresse_residence" :disabled="isReadOnly" class="form-control" rows="2" placeholder="Adresse de résidence"></textarea>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.pere_quartier') || 'Quartier' }}</label>
                <input v-model="form.pere_quartier" :disabled="isReadOnly" type="text" class="form-control" placeholder="Quartier" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.pere_commune') || 'Commune' }}</label>
                <input v-model="form.pere_commune" :disabled="isReadOnly" type="text" class="form-control" placeholder="Commune" />
            </div>
        </div>

        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.pere_departement') || 'Département' }}</label>
                <input v-model="form.pere_departement" :disabled="isReadOnly" type="text" class="form-control" placeholder="Département" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.pere_region') || 'Région' }}</label>
                <input v-model="form.pere_region" :disabled="isReadOnly" type="text" class="form-control" placeholder="Région" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.pere_code_postal') || 'Code postal' }}</label>
                <input v-model="form.pere_code_postal" :disabled="isReadOnly" type="text" class="form-control" placeholder="Code postal" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.pere_boite_postal') || 'Boîte postal' }}</label>
                <input v-model="form.pere_boite_postal" :disabled="isReadOnly" type="text" class="form-control" placeholder="Boîte postal" />
            </div>
        </div>

        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.pere_telephone_1') || 'Téléphone 1' }}</label>
                <input v-model="form.pere_telephone_1" :disabled="isReadOnly" type="tel" class="form-control" placeholder="Téléphone 1" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.pere_telephone_2') || 'Téléphone 2' }}</label>
                <input v-model="form.pere_telephone_2" :disabled="isReadOnly" type="tel" class="form-control" placeholder="Téléphone 2" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.pere_whatsapp_1') || 'WhatsApp 1' }}</label>
                <input v-model="form.pere_whatsapp_1" :disabled="isReadOnly" type="tel" class="form-control" placeholder="WhatsApp 1" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.pere_whatsapp_2') || 'WhatsApp 2' }}</label>
                <input v-model="form.pere_whatsapp_2" :disabled="isReadOnly" type="tel" class="form-control" placeholder="WhatsApp 2" />
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.pere_email_1') || 'Email 1' }}</label>
                <input v-model="form.pere_email_1" :disabled="isReadOnly" type="email" class="form-control" placeholder="Email 1" />
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.pere_email_2') || 'Email 2' }}</label>
                <input v-model="form.pere_email_2" :disabled="isReadOnly" type="email" class="form-control" placeholder="Email 2" />
            </div>
        </div>

        <!-- SECTION 3: INFORMATIONS DE LA MÈRE -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">Informations de la mère</h5>
        </div>

        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.mere_nom') || 'Nom de la mère' }}</label>
                <input v-model="form.mere_nom" :disabled="isReadOnly" type="text" class="form-control" placeholder="Nom de la mère" />
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.mere_prenoms') || 'Prénom(s) de la mère' }}</label>
                <input v-model="form.mere_prenoms" :disabled="isReadOnly" type="text" class="form-control" placeholder="Prénom(s) de la mère" />
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.mere_nom_complet') || 'Nom complet de la mère' }}</label>
                <input v-model="form.mere_nom_complet" :disabled="isReadOnly" type="text" class="form-control" placeholder="Nom complet de la mère" />
            </div>
        </div>

        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.mere_profession') || 'Profession de la mère' }}</label>
                <input v-model="form.mere_profession" :disabled="isReadOnly" type="text" class="form-control" placeholder="Profession de la mère" />
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.mere_organisation_travail') || 'Organisation de travail' }}</label>
                <input v-model="form.mere_organisation_travail" :disabled="isReadOnly" type="text" class="form-control" placeholder="Organisation de travail" />
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.mere_ville_travail') || 'Ville de travail' }}</label>
                <input v-model="form.mere_ville_travail" :disabled="isReadOnly" type="text" class="form-control" placeholder="Ville de travail" />
            </div>
        </div>

        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.mere_pays_travail') || 'Pays de travail' }}</label>
                <input v-model="form.mere_pays_travail" :disabled="isReadOnly" type="text" class="form-control" placeholder="Pays de travail" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.mere_adresse_residence') || 'Adresse de résidence' }}</label>
                <textarea v-model="form.mere_adresse_residence" :disabled="isReadOnly" class="form-control" rows="2" placeholder="Adresse de résidence"></textarea>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.mere_quartier') || 'Quartier' }}</label>
                <input v-model="form.mere_quartier" :disabled="isReadOnly" type="text" class="form-control" placeholder="Quartier" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.mere_commune') || 'Commune' }}</label>
                <input v-model="form.mere_commune" :disabled="isReadOnly" type="text" class="form-control" placeholder="Commune" />
            </div>
        </div>

        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.mere_departement') || 'Département' }}</label>
                <input v-model="form.mere_departement" :disabled="isReadOnly" type="text" class="form-control" placeholder="Département" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.mere_region') || 'Région' }}</label>
                <input v-model="form.mere_region" :disabled="isReadOnly" type="text" class="form-control" placeholder="Région" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.mere_code_postal') || 'Code postal' }}</label>
                <input v-model="form.mere_code_postal" :disabled="isReadOnly" type="text" class="form-control" placeholder="Code postal" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.mere_boite_postal') || 'Boîte postal' }}</label>
                <input v-model="form.mere_boite_postal" :disabled="isReadOnly" type="text" class="form-control" placeholder="Boîte postal" />
            </div>
        </div>

        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.mere_telephone_1') || 'Téléphone 1' }}</label>
                <input v-model="form.mere_telephone_1" :disabled="isReadOnly" type="tel" class="form-control" placeholder="Téléphone 1" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.mere_telephone_2') || 'Téléphone 2' }}</label>
                <input v-model="form.mere_telephone_2" :disabled="isReadOnly" type="tel" class="form-control" placeholder="Téléphone 2" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.mere_whatsapp_1') || 'WhatsApp 1' }}</label>
                <input v-model="form.mere_whatsapp_1" :disabled="isReadOnly" type="tel" class="form-control" placeholder="WhatsApp 1" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.mere_whatsapp_2') || 'WhatsApp 2' }}</label>
                <input v-model="form.mere_whatsapp_2" :disabled="isReadOnly" type="tel" class="form-control" placeholder="WhatsApp 2" />
            </div>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.mere_email_1') || 'Email 1' }}</label>
                <input v-model="form.mere_email_1" :disabled="isReadOnly" type="email" class="form-control" placeholder="Email 1" />
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.mere_email_2') || 'Email 2' }}</label>
                <input v-model="form.mere_email_2" :disabled="isReadOnly" type="email" class="form-control" placeholder="Email 2" />
            </div>
        </div>

        <!-- SECTION 4: TUTEUR 1 -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">Informations du tuteur 1</h5>
        </div>

        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_nom') || 'Nom du tuteur' }}</label>
                <input v-model="form.tuteur1_nom" :disabled="isReadOnly" type="text" class="form-control" placeholder="Nom du tuteur" />
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_prenoms') || 'Prénom(s) du tuteur' }}</label>
                <input v-model="form.tuteur1_prenoms" :disabled="isReadOnly" type="text" class="form-control" placeholder="Prénom(s) du tuteur" />
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_nom_complet') || 'Nom complet du tuteur' }}</label>
                <input v-model="form.tuteur1_nom_complet" :disabled="isReadOnly" type="text" class="form-control" placeholder="Nom complet du tuteur" />
            </div>
        </div>

        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_profession') || 'Profession' }}</label>
                <input v-model="form.tuteur1_profession" :disabled="isReadOnly" type="text" class="form-control" placeholder="Profession" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_organisation_travail') || 'Organisation de travail' }}</label>
                <input v-model="form.tuteur1_organisation_travail" :disabled="isReadOnly" type="text" class="form-control" placeholder="Organisation de travail" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_ville_travail') || 'Ville de travail' }}</label>
                <input v-model="form.tuteur1_ville_travail" :disabled="isReadOnly" type="text" class="form-control" placeholder="Ville de travail" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_pays_travail') || 'Pays de travail' }}</label>
                <input v-model="form.tuteur1_pays_travail" :disabled="isReadOnly" type="text" class="form-control" placeholder="Pays de travail" />
            </div>
        </div>

        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_adresse_residence') || 'Adresse' }}</label>
                <textarea v-model="form.tuteur1_adresse_residence" :disabled="isReadOnly" class="form-control" rows="2" placeholder="Adresse"></textarea>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_quartier') || 'Quartier' }}</label>
                <input v-model="form.tuteur1_quartier" :disabled="isReadOnly" type="text" class="form-control" placeholder="Quartier" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_commune') || 'Commune' }}</label>
                <input v-model="form.tuteur1_commune" :disabled="isReadOnly" type="text" class="form-control" placeholder="Commune" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_arrondissement') || 'Arrondissement' }}</label>
                <input v-model="form.tuteur1_arrondissement" :disabled="isReadOnly" type="text" class="form-control" placeholder="Arrondissement" />
            </div>
        </div>

        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_ville') || 'Ville' }}</label>
                <input v-model="form.tuteur1_ville" :disabled="isReadOnly" type="text" class="form-control" placeholder="Ville" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_departement') || 'Département' }}</label>
                <input v-model="form.tuteur1_departement" :disabled="isReadOnly" type="text" class="form-control" placeholder="Département" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_region') || 'Région' }}</label>
                <input v-model="form.tuteur1_region" :disabled="isReadOnly" type="text" class="form-control" placeholder="Région" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_pays') || 'Pays' }}</label>
                <input v-model="form.tuteur1_pays" :disabled="isReadOnly" type="text" class="form-control" placeholder="Pays" />
            </div>
        </div>

        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_code_postal') || 'Code postal' }}</label>
                <input v-model="form.tuteur1_code_postal" :disabled="isReadOnly" type="text" class="form-control" placeholder="Code postal" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_boite_postal') || 'Boîte postal' }}</label>
                <input v-model="form.tuteur1_boite_postal" :disabled="isReadOnly" type="text" class="form-control" placeholder="Boîte postal" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_telephone_1') || 'Téléphone 1' }}</label>
                <input v-model="form.tuteur1_telephone_1" :disabled="isReadOnly" type="tel" class="form-control" placeholder="Téléphone 1" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_telephone_2') || 'Téléphone 2' }}</label>
                <input v-model="form.tuteur1_telephone_2" :disabled="isReadOnly" type="tel" class="form-control" placeholder="Téléphone 2" />
            </div>
        </div>

        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_email') || 'Email' }}</label>
                <input v-model="form.tuteur1_email" :disabled="isReadOnly" type="email" class="form-control" placeholder="Email" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_whatsapp_1') || 'WhatsApp 1' }}</label>
                <input v-model="form.tuteur1_whatsapp_1" :disabled="isReadOnly" type="tel" class="form-control" placeholder="WhatsApp 1" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur1_whatsapp_2') || 'WhatsApp 2' }}</label>
                <input v-model="form.tuteur1_whatsapp_2" :disabled="isReadOnly" type="tel" class="form-control" placeholder="WhatsApp 2" />
            </div>
        </div>

        <!-- SECTION 5: TUTEUR 2 -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">Informations du tuteur 2</h5>
        </div>

        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_nom') || 'Nom du tuteur' }}</label>
                <input v-model="form.tuteur2_nom" :disabled="isReadOnly" type="text" class="form-control" placeholder="Nom du tuteur" />
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_prenoms') || 'Prénom(s) du tuteur' }}</label>
                <input v-model="form.tuteur2_prenoms" :disabled="isReadOnly" type="text" class="form-control" placeholder="Prénom(s) du tuteur" />
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_nom_complet') || 'Nom complet du tuteur' }}</label>
                <input v-model="form.tuteur2_nom_complet" :disabled="isReadOnly" type="text" class="form-control" placeholder="Nom complet du tuteur" />
            </div>
        </div>

        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_profession') || 'Profession' }}</label>
                <input v-model="form.tuteur2_profession" :disabled="isReadOnly" type="text" class="form-control" placeholder="Profession" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_organisation_travail') || 'Organisation de travail' }}</label>
                <input v-model="form.tuteur2_organisation_travail" :disabled="isReadOnly" type="text" class="form-control" placeholder="Organisation de travail" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_ville_travail') || 'Ville de travail' }}</label>
                <input v-model="form.tuteur2_ville_travail" :disabled="isReadOnly" type="text" class="form-control" placeholder="Ville de travail" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_pays_travail') || 'Pays de travail' }}</label>
                <input v-model="form.tuteur2_pays_travail" :disabled="isReadOnly" type="text" class="form-control" placeholder="Pays de travail" />
            </div>
        </div>

        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_adresse_residence') || 'Adresse' }}</label>
                <textarea v-model="form.tuteur2_adresse_residence" :disabled="isReadOnly" class="form-control" rows="2" placeholder="Adresse"></textarea>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_quartier') || 'Quartier' }}</label>
                <input v-model="form.tuteur2_quartier" :disabled="isReadOnly" type="text" class="form-control" placeholder="Quartier" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_commune') || 'Commune' }}</label>
                <input v-model="form.tuteur2_commune" :disabled="isReadOnly" type="text" class="form-control" placeholder="Commune" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_arrondissement') || 'Arrondissement' }}</label>
                <input v-model="form.tuteur2_arrondissement" :disabled="isReadOnly" type="text" class="form-control" placeholder="Arrondissement" />
            </div>
        </div>

        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_ville') || 'Ville' }}</label>
                <input v-model="form.tuteur2_ville" :disabled="isReadOnly" type="text" class="form-control" placeholder="Ville" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_departement') || 'Département' }}</label>
                <input v-model="form.tuteur2_departement" :disabled="isReadOnly" type="text" class="form-control" placeholder="Département" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_region') || 'Région' }}</label>
                <input v-model="form.tuteur2_region" :disabled="isReadOnly" type="text" class="form-control" placeholder="Région" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_pays') || 'Pays' }}</label>
                <input v-model="form.tuteur2_pays" :disabled="isReadOnly" type="text" class="form-control" placeholder="Pays" />
            </div>
        </div>

        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_code_postal') || 'Code postal' }}</label>
                <input v-model="form.tuteur2_code_postal" :disabled="isReadOnly" type="text" class="form-control" placeholder="Code postal" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_boite_postal') || 'Boîte postal' }}</label>
                <input v-model="form.tuteur2_boite_postal" :disabled="isReadOnly" type="text" class="form-control" placeholder="Boîte postal" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_telephone_1') || 'Téléphone 1' }}</label>
                <input v-model="form.tuteur2_telephone_1" :disabled="isReadOnly" type="tel" class="form-control" placeholder="Téléphone 1" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_telephone_2') || 'Téléphone 2' }}</label>
                <input v-model="form.tuteur2_telephone_2" :disabled="isReadOnly" type="tel" class="form-control" placeholder="Téléphone 2" />
            </div>
        </div>

        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_email') || 'Email' }}</label>
                <input v-model="form.tuteur2_email" :disabled="isReadOnly" type="email" class="form-control" placeholder="Email" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_whatsapp_1') || 'WhatsApp 1' }}</label>
                <input v-model="form.tuteur2_whatsapp_1" :disabled="isReadOnly" type="tel" class="form-control" placeholder="WhatsApp 1" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="mb-3">
                <label>{{ t('fields.tuteur2_whatsapp_2') || 'WhatsApp 2' }}</label>
                <input v-model="form.tuteur2_whatsapp_2" :disabled="isReadOnly" type="tel" class="form-control" placeholder="WhatsApp 2" />
            </div>
        </div>

        <!-- SECTION 6: ÉTAT -->
        <div class="col-12">
            <h5 class="section-title mb-3 mt-4">{{ t('common.status') || 'État' }}</h5>
        </div>

        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.status') || 'État d\'activation' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="etatOptions"
                    :disabled="isReadOnly"
                    option-value="id"
                    option-label="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                />
                <span v-if="form.errors?.etat" class="text-danger">
                    <strong>{{ form.errors.etat }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-input {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.section-title {
    font-weight: 700;
    color: #2c3e50;
    font-size: 1.15rem;
    border-bottom: 3px solid #007bff;
    padding-bottom: 0.75rem;
    margin-bottom: 1.5rem !important;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-title::before {
    content: '';
    display: inline-block;
    width: 4px;
    height: 24px;
    background: linear-gradient(180deg, #007bff 0%, #0056b3 100%);
    border-radius: 2px;
}

.row.g-3 > [class*='col-'] {
    margin-bottom: 0.5rem;
}

label {
    font-weight: 600;
    color: #495057;
    font-size: 0.95rem;
    margin-bottom: 0.6rem !important;
    display: block;
}

.form-control,
.form-select {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 0.65rem 0.875rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.form-control:focus,
.form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
}

.form-control:disabled,
.form-control[disabled] {
    background-color: #f8f9fa;
    color: #6c757d;
    cursor: not-allowed;
    border-color: #dee2e6;
}

.form-control.is-invalid {
    border-color: #dc3545;
}

.text-danger {
    color: #dc3545;
    font-size: 0.85rem;
    margin-top: 0.35rem;
    display: block;
}

textarea.form-control {
    min-height: 80px;
    resize: vertical;
}

/* Section background alternation */
.row.g-3 > .col-12:nth-of-type(4n+1) ~ [class*='col-']:not(.col-12) {
    background: rgba(0, 123, 255, 0.02);
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 0.5rem;
}
</style>
