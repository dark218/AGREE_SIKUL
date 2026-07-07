<!--
  ApprenantForm.vue — Refonte Phase 2.3 (Steppers).
  Historique : 1197 lignes / 47 v-model → 5 steps / ~30 champs effectifs.

  Steps :
    1. Identité         — photo, nom, prénoms, date naiss + âge computed,
                          lieu naissance, genre, nationalité
    2. Sanitaire        — groupe sanguin, allergies, aliments interdits,
                          hôpitaux+médecins, drépano/asthme/diabète/épilepsie, apte sport
    3. Scolarité        — classe (auto section/cycle/école/campus/année),
                          type apprenant, école/classe précédente, matricule,
                          n° inscription
    4. Famille & Contact — noms complets père/mère/tuteur/resp. légal,
                          adresse, quartier (auto commune/dept/region/pays),
                          téléphone, whatsapp, email
    5. Hébergement & Suivi — interne? → bâtiment/étage/chambre/lit (conditionnel),
                          date entrée, date départ, motif départ, statut

  Champs supprimés (redondants) : age (computed), telephone2, whatsapp2, sexe,
  commune/departement/region/pays_naissance (dérivés de commune_naissance_id),
  arrondissement/ville/code_postal/boite_postal résidence.

  Auto-fill préservé :
    - Classe → école, campus, section, cycle, année scolaire (useClasseAutoFill)
    - Quartier → commune, département, région, pays (useGeoCascade)
-->

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import FormStepper from '@/Components/Common/FormStepper.vue';
import { useClasseAutoFill } from '../../composables/useClasseAutoFill';
import { useGeoCascade } from '@/Composables/useGeoCascade';

const { t } = useI18n();

const props = defineProps({
    form: { type: Object, required: true },
    mode: {
        type: String,
        default: 'create',
        validator: (v) => ['create', 'edit', 'show'].includes(v),
    },
    classes:           { type: Array, default: () => [] },
    sections:          { type: Array, default: () => [] },
    cycles:            { type: Array, default: () => [] },
    ecoles:            { type: Array, default: () => [] },
    campuses:          { type: Array, default: () => [] },
    communes:          { type: Array, default: () => [] },
    departements:      { type: Array, default: () => [] },
    regions:           { type: Array, default: () => [] },
    pays:              { type: Array, default: () => [] },
    quartiers:         { type: Array, default: () => [] },
    anneesScolaires:   { type: Array, default: () => [] },
    typesApprenant:    { type: Array, default: () => [] },
    genres:            { type: Array, default: () => [] },
    statutsApprenants: { type: Array, default: () => [] },
    groupesSanguins:   { type: Array, default: () => [] },
});

defineEmits(['submit']);

const isReadOnly = props.mode === 'show';
const currentStep = ref(0);

// Auto-fill scolaire : classe → section, cycle, école, campus, année
useClasseAutoFill(props.form);

// Cascade géo : quartier → commune → dept → région → pays
useGeoCascade(props.form, {
    quartiers:    () => props.quartiers,
    communes:     () => props.communes,
    departements: () => props.departements,
    regions:      () => props.regions,
});

// Photo
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
    if (!path || typeof path !== 'string') return null;
    if (path.startsWith('http')) return path;
    return '/storage/' + path.replace(/^\/+/, '');
};

// Âge computed
const age = computed(() => {
    if (!props.form.date_naissance) return null;
    const birthDate = new Date(props.form.date_naissance);
    const today = new Date();
    let a = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) a--;
    return a;
});

// Nationalité auto depuis pays_naissance
const paysNaissanceChange = () => {
    if (!props.form.pays_naissance_id) return;
    const p = props.pays.find(x => String(x.id) === String(props.form.pays_naissance_id));
    if (p?.nationalite && !props.form.nationalite) {
        props.form.nationalite = p.nationalite;
    }
};

// Auto-fill labels (readonly display)
const autoLabel = (list, id) => {
    if (!id || !list?.length) return '—';
    const found = list.find(item => String(item.id) === String(id));
    return found?.libelle || found?.nom || '—';
};
const sectionLabel = computed(() => autoLabel(props.sections, props.form.section_id));
const cycleLabel   = computed(() => autoLabel(props.cycles,   props.form.cycle_id));
const ecoleLabel   = computed(() => autoLabel(props.ecoles,   props.form.ecole_id));
const campusLabel  = computed(() => autoLabel(props.campuses, props.form.campus_id));

// Options fixes
const statusOptions = computed(() => {
    if (props.statutsApprenants?.length > 0) {
        return props.statutsApprenants.map(s => ({ id: s.code, libelle: s.libelle }));
    }
    return [
        { id: 'actif',     libelle: 'Actif' },
        { id: 'suspendu',  libelle: 'Suspendu' },
        { id: 'exclu',     libelle: 'Exclu' },
        { id: 'diplome',   libelle: 'Diplômé' },
        { id: 'abandonne', libelle: 'Abandonné' },
    ];
});
const yesNoOptions = [
    { id: true,  libelle: 'Oui' },
    { id: false, libelle: 'Non' },
];

// Steps déclaratifs
const steps = [
    { key: 'identite',  label: 'Identité',         icon: 'fas fa-id-badge',       requiredFields: ['nom', 'prenoms'] },
    { key: 'sante',     label: 'Sanitaire',        icon: 'fas fa-heartbeat' },
    { key: 'scolarite', label: 'Scolarité',        icon: 'fas fa-graduation-cap', requiredFields: ['matricule', 'classe_id'] },
    { key: 'famille',   label: 'Famille & Contact', icon: 'fas fa-users' },
    { key: 'suivi',     label: 'Hébergement & Suivi', icon: 'fas fa-clipboard-list' },
];
</script>

<template>
    <FormStepper
        v-model="currentStep"
        :steps="steps"
        :form="form"
        persist-key="apprenant-form"
        @submit="$emit('submit')"
    >
        <!-- STEP 1 : IDENTITÉ -->
        <template #identite>
            <div class="row g-3">
                <!-- Photo -->
                <div class="col-12">
                    <label class="fw-medium">
                        <i class="fa fa-camera text-primary me-1"></i>
                        Photo de l'apprenant
                    </label>
                    <div class="d-flex align-items-center gap-3 mt-2">
                        <div class="photo-preview">
                            <img v-if="photoPreview" :src="photoPreview" alt="Aperçu" />
                            <img v-else-if="typeof form.photo === 'string' && form.photo" :src="photoUrl(form.photo)" alt="Photo actuelle" />
                            <div v-else class="photo-placeholder"><i class="fa fa-user"></i></div>
                        </div>
                        <div>
                            <input ref="photoInputRef" type="file" accept="image/*" class="form-control form-control-sm" :disabled="isReadOnly" @change="onPhotoChange" />
                            <button v-if="!isReadOnly && (photoPreview || form.photo)" type="button" class="btn btn-link btn-sm text-danger mt-1" @click="clearPhoto">
                                <i class="fa fa-times"></i> Retirer
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label>Nom <span class="text-danger">*</span></label>
                    <input v-model="form.nom" type="text" class="form-control" :disabled="isReadOnly" />
                    <span v-if="form.errors?.nom" class="text-danger small"><strong>{{ form.errors.nom }}</strong></span>
                </div>
                <div class="col-md-6">
                    <label>Prénom(s) <span class="text-danger">*</span></label>
                    <input v-model="form.prenoms" type="text" class="form-control" :disabled="isReadOnly" />
                </div>
                <div class="col-md-4">
                    <label>Date de naissance</label>
                    <input v-model="form.date_naissance" type="date" class="form-control" :disabled="isReadOnly" />
                </div>
                <div class="col-md-2">
                    <label>Âge <small class="text-muted">(auto)</small></label>
                    <input :value="age !== null ? age + ' ans' : '—'" type="text" class="form-control" disabled style="background:#eef2f7;color:#64748b;" />
                </div>
                <div class="col-md-6">
                    <label>Lieu de naissance</label>
                    <input v-model="form.lieu_naissance" type="text" class="form-control" :disabled="isReadOnly" />
                </div>
                <div class="col-md-4">
                    <label>Genre</label>
                    <SearchableSelect v-model.number="form.genre_id" :options="genres" option-value="id" option-label="libelle" :disabled="isReadOnly" placeholder="Sélectionner" />
                </div>
                <div class="col-md-4">
                    <label>Pays de naissance</label>
                    <SearchableSelect v-model.number="form.pays_naissance_id" :options="pays" option-value="id" option-label="libelle" :disabled="isReadOnly" placeholder="Sélectionner" @update:model-value="paysNaissanceChange" />
                </div>
                <div class="col-md-4">
                    <label>Nationalité</label>
                    <input v-model="form.nationalite" type="text" class="form-control" :disabled="isReadOnly" placeholder="Ex : Camerounaise" />
                </div>
            </div>
        </template>

        <!-- STEP 2 : SANITAIRE -->
        <template #sante>
            <div class="row g-3">
                <div class="col-md-4">
                    <label>Groupe sanguin</label>
                    <SearchableSelect v-model.number="form.groupe_sanguin_id" :options="groupesSanguins" option-value="id" option-label="libelle" :disabled="isReadOnly" placeholder="Sélectionner" />
                </div>
                <div class="col-md-4">
                    <label>Apte à la pratique du sport ?</label>
                    <SearchableSelect v-model="form.apte_sport" :options="yesNoOptions" option-value="id" option-label="libelle" :disabled="isReadOnly" placeholder="Sélectionner" />
                </div>

                <div class="col-12">
                    <div class="d-flex gap-4 flex-wrap">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="drepano" v-model="form.drepanocytaire" :disabled="isReadOnly" />
                            <label class="form-check-label" for="drepano">Drépanocytaire</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="asthme" v-model="form.asthmatique" :disabled="isReadOnly" />
                            <label class="form-check-label" for="asthme">Asthmatique</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="diabete" v-model="form.diabetique" :disabled="isReadOnly" />
                            <label class="form-check-label" for="diabete">Diabétique</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="epilepsie" v-model="form.epileptique" :disabled="isReadOnly" />
                            <label class="form-check-label" for="epilepsie">Épileptique</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label>Réactions allergiques</label>
                    <textarea v-model="form.allergies" class="form-control" rows="2" :disabled="isReadOnly"></textarea>
                </div>
                <div class="col-md-6">
                    <label>Aliments interdits</label>
                    <textarea v-model="form.aliments_interdits" class="form-control" rows="2" :disabled="isReadOnly"></textarea>
                </div>

                <div class="col-md-6">
                    <label>Hôpital préféré 1</label>
                    <input v-model="form.hopital_prefere_1" type="text" class="form-control" :disabled="isReadOnly" />
                </div>
                <div class="col-md-6">
                    <label>Hôpital préféré 2</label>
                    <input v-model="form.hopital_prefere_2" type="text" class="form-control" :disabled="isReadOnly" />
                </div>
                <div class="col-md-6">
                    <label>Téléphone médecin 1</label>
                    <input v-model="form.telephone_medecin_1" type="tel" class="form-control" :disabled="isReadOnly" />
                </div>
                <div class="col-md-6">
                    <label>Téléphone médecin 2</label>
                    <input v-model="form.telephone_medecin_2" type="tel" class="form-control" :disabled="isReadOnly" />
                </div>
            </div>
        </template>

        <!-- STEP 3 : SCOLARITÉ -->
        <template #scolarite>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Matricule <span class="text-danger">*</span></label>
                    <input v-model="form.matricule" type="text" class="form-control" :disabled="isReadOnly" />
                </div>
                <div class="col-md-6">
                    <label>Numéro d'inscription <small class="text-muted">(auto si vide)</small></label>
                    <input v-model="form.numero_inscription" type="text" class="form-control" :disabled="isReadOnly" placeholder="INS-YYYY-NNNNN" />
                </div>

                <div class="col-md-6">
                    <label>Classe <span class="text-danger">*</span></label>
                    <SearchableSelect v-model.number="form.classe_id" :options="classes" option-value="id" option-label="libelle" :disabled="isReadOnly" placeholder="Sélectionner la classe" />
                </div>
                <div class="col-md-6">
                    <label>Type d'apprenant</label>
                    <SearchableSelect v-model.number="form.type_apprenant_id" :options="typesApprenant" option-value="id" option-label="libelle" :disabled="isReadOnly" placeholder="Sélectionner" />
                </div>

                <!-- Auto-fill readonly depuis la classe -->
                <div class="col-md-3">
                    <label>Section <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px">auto</span></label>
                    <input :value="sectionLabel" type="text" class="form-control" disabled style="background:#eef2f7;color:#64748b;" />
                </div>
                <div class="col-md-3">
                    <label>Cycle <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px">auto</span></label>
                    <input :value="cycleLabel" type="text" class="form-control" disabled style="background:#eef2f7;color:#64748b;" />
                </div>
                <div class="col-md-3">
                    <label>École <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px">auto</span></label>
                    <input :value="ecoleLabel" type="text" class="form-control" disabled style="background:#eef2f7;color:#64748b;" />
                </div>
                <div class="col-md-3">
                    <label>Campus <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px">auto</span></label>
                    <input :value="campusLabel" type="text" class="form-control" disabled style="background:#eef2f7;color:#64748b;" />
                </div>

                <div class="col-md-6">
                    <label>École précédente</label>
                    <input v-model="form.ecole_precedente" type="text" class="form-control" :disabled="isReadOnly" />
                </div>
                <div class="col-md-6">
                    <label>Classe précédente</label>
                    <input v-model="form.classe_precedente" type="text" class="form-control" :disabled="isReadOnly" />
                </div>
            </div>
        </template>

        <!-- STEP 4 : FAMILLE & CONTACT -->
        <template #famille>
            <div class="row g-3">
                <div class="col-12">
                    <h6 class="text-primary"><i class="fa fa-users me-1"></i> Famille</h6>
                </div>
                <div class="col-md-6">
                    <label>Nom complet du père</label>
                    <input v-model="form.nom_pere" type="text" class="form-control" :disabled="isReadOnly" placeholder="Nom(s) et prénom(s) du père" />
                </div>
                <div class="col-md-6">
                    <label>Nom complet de la mère</label>
                    <input v-model="form.nom_mere" type="text" class="form-control" :disabled="isReadOnly" placeholder="Nom(s) et prénom(s) de la mère" />
                </div>
                <div class="col-md-6">
                    <label>Nom du tuteur légal</label>
                    <input v-model="form.nom_tuteur" type="text" class="form-control" :disabled="isReadOnly" placeholder="Nom(s) et prénom(s) du tuteur" />
                </div>
                <div class="col-md-6">
                    <label>Nom du responsable légal <small class="text-muted">(si distinct)</small></label>
                    <input v-model="form.nom_responsable_legal" type="text" class="form-control" :disabled="isReadOnly" />
                </div>

                <div class="col-12 mt-3">
                    <h6 class="text-primary"><i class="fa fa-house me-1"></i> Adresse & Contact</h6>
                </div>
                <div class="col-md-6">
                    <label>Quartier</label>
                    <SearchableSelect v-model.number="form.quartier_id" :options="quartiers" option-value="id" option-label="libelle" :disabled="isReadOnly" placeholder="Sélectionner (commune/dept/région/pays auto)" />
                </div>
                <div class="col-md-6">
                    <label>Adresse</label>
                    <input v-model="form.adresse" type="text" class="form-control" :disabled="isReadOnly" placeholder="Rue, immeuble, etc." />
                </div>

                <div class="col-md-4">
                    <label>Téléphone</label>
                    <input v-model="form.telephone" type="tel" class="form-control" :disabled="isReadOnly" />
                </div>
                <div class="col-md-4">
                    <label>WhatsApp</label>
                    <input v-model="form.whatsapp1" type="tel" class="form-control" :disabled="isReadOnly" />
                </div>
                <div class="col-md-4">
                    <label>Email</label>
                    <input v-model="form.email" type="email" class="form-control" :disabled="isReadOnly" />
                </div>
            </div>
        </template>

        <!-- STEP 5 : HÉBERGEMENT & SUIVI -->
        <template #suivi>
            <div class="row g-3">
                <div class="col-12">
                    <h6 class="text-primary"><i class="fa fa-bed me-1"></i> Hébergement</h6>
                </div>
                <div class="col-md-4">
                    <label>Apprenant interne ?</label>
                    <SearchableSelect v-model="form.est_interne" :options="yesNoOptions" option-value="id" option-label="libelle" :disabled="isReadOnly" />
                </div>

                <template v-if="form.est_interne">
                    <div class="col-md-2">
                        <label>Bâtiment</label>
                        <input v-model="form.batiment" type="text" class="form-control" :disabled="isReadOnly" />
                    </div>
                    <div class="col-md-2">
                        <label>Étage</label>
                        <input v-model="form.etage" type="text" class="form-control" :disabled="isReadOnly" />
                    </div>
                    <div class="col-md-2">
                        <label>Chambre</label>
                        <input v-model="form.chambre" type="text" class="form-control" :disabled="isReadOnly" />
                    </div>
                    <div class="col-md-2">
                        <label>N° de lit</label>
                        <input v-model="form.numero_lit" type="text" class="form-control" :disabled="isReadOnly" />
                    </div>
                </template>

                <div class="col-12 mt-3">
                    <h6 class="text-primary"><i class="fa fa-clipboard-list me-1"></i> Entrée / Sortie</h6>
                </div>
                <div class="col-md-4">
                    <label>Date d'entrée à l'école</label>
                    <input v-model="form.date_entree_ecole" type="date" class="form-control" :disabled="isReadOnly" />
                </div>
                <div class="col-md-4">
                    <label>Date de départ de l'école</label>
                    <input v-model="form.date_depart_ecole" type="date" class="form-control" :disabled="isReadOnly" />
                </div>
                <div class="col-md-4">
                    <label>Motif de départ</label>
                    <input v-model="form.motif_depart_ecole" type="text" class="form-control" :disabled="isReadOnly" />
                </div>

                <div class="col-md-4">
                    <label>Statut</label>
                    <SearchableSelect v-model="form.statut" :options="statusOptions" option-value="id" option-label="libelle" :disabled="isReadOnly" placeholder="Sélectionner le statut" />
                </div>
            </div>
        </template>
    </FormStepper>
</template>

<style scoped>
.form-control, .form-select {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 0.55rem 0.85rem;
    font-size: 0.95rem;
}
.form-control:focus, .form-select:focus {
    border-color: #0b5697;
    box-shadow: 0 0 0 0.2rem rgba(11, 86, 151, 0.15);
}
label {
    font-weight: 500;
    color: #374151;
    font-size: 0.9rem;
    margin-bottom: 0.4rem;
    display: block;
}
.photo-preview {
    width: 96px;
    height: 96px;
    border-radius: 8px;
    border: 2px dashed #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: #f8fafc;
}
.photo-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.photo-placeholder {
    color: #94a3b8;
    font-size: 32px;
}
.form-check-label {
    font-weight: 500;
    color: #374151;
}
h6 {
    font-weight: 600;
    padding-bottom: 0.4rem;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 0.5rem;
}
</style>
