<!--
  ClasseForm.vue — refonte selon spec Orchidée

  BLOC 1 — Informations de base :
    L1: Code | Libellé
    L2: Libellé à afficher | Bâtiment

  BLOC 2 — Structure académique :
    L1: École | Campus (auto-fill depuis école)
    L2: Section | Niveau (filtré par école)
    L3: Cycle | Année scolaire

  BLOC 3 — Enseignant et capacité :
    L1: Enseignant titulaire | Capacité maximale
    L2: Capacité actuelle | Statut
-->
<script setup>
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    form: { type: Object, required: true },
    ecoles: { type: Array, default: () => [] },
    niveaux: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    cycles: { type: Array, default: () => [] },
    enseignants: { type: Array, default: () => [] },
    anneesScolaires: { type: Array, default: () => [] },
    campuses: { type: Array, default: () => [] },
    mode: {
        type: String,
        default: 'create',
        validator: (v) => ['create', 'edit', 'show'].includes(v),
    },
});

const isReadOnly = props.mode === 'show';
const ecoleSelected = computed(() => !!props.form.ecole_id);

const autoLabel = (list, id) => {
    if (!id || !list?.length) return '—';
    const found = list.find((item) => String(item.id) === String(id));
    return found?.libelle || found?.nom || '—';
};

const campusLabel = computed(() => autoLabel(props.campuses, props.form.campus_id));

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'non_actif', libelle: 'Inactif' },
    { id: 'suspendu', libelle: 'Suspendu' },
];

// Cascade École → Campus (auto-fill du campus depuis l'école)
watch(() => props.form.ecole_id, (newEcoleId) => {
    if (!newEcoleId || isReadOnly) return;
    const ecole = props.ecoles.find((e) => String(e.id) === String(newEcoleId));
    if (ecole?.campus_id) {
        props.form.campus_id = ecole.campus_id;
    }
});

// Filtrage des niveaux par école sélectionnée
const filteredNiveaux = computed(() => {
    if (!props.form.ecole_id) return props.niveaux;
    return props.niveaux.filter((n) => !n.ecole_id || String(n.ecole_id) === String(props.form.ecole_id));
});

// Si le niveau sélectionné disparaît de la liste filtrée → reset
watch(filteredNiveaux, (newList) => {
    if (props.form.niveau_id && !newList.find((n) => String(n.id) === String(props.form.niveau_id))) {
        props.form.niveau_id = null;
    }
});

const enseignantLabel = (opt) => opt ? `${opt.nom} ${opt.prenoms || ''}`.trim() : '';
</script>

<template>
    <div class="row g-3 custom-input">

        <!-- ============================================== -->
        <!-- BLOC 1 : INFORMATIONS DE BASE -->
        <!-- ============================================== -->
        <div class="col-12">
            <h6 class="section-header"><i class="fa fa-bookmark"></i> Informations de base</h6>
        </div>

        <div class="col-sm-6">
            <label class="form-label fw-medium">Code</label>
            <input
                v-model="form.code"
                type="text"
                class="form-control"
                placeholder="Code de la classe"
                :disabled="isReadOnly"
                maxlength="100"
            />
            <span v-if="form.errors?.code" class="text-danger small">{{ form.errors.code }}</span>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-medium">Libellé <span class="text-danger">*</span></label>
            <input
                v-model="form.libelle"
                type="text"
                class="form-control"
                placeholder="Libellé de la classe"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.libelle" class="text-danger small">{{ form.errors.libelle }}</span>
        </div>

        <div class="col-sm-6">
            <label class="form-label fw-medium">Libellé à afficher</label>
            <input
                v-model="form.libelle_affichage"
                type="text"
                class="form-control"
                placeholder="Libellé à afficher"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.libelle_affichage" class="text-danger small">{{ form.errors.libelle_affichage }}</span>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-medium">Bâtiment</label>
            <input
                v-model="form.batiment"
                type="text"
                class="form-control"
                placeholder="Bâtiment / salle"
                :disabled="isReadOnly"
                maxlength="100"
            />
            <span v-if="form.errors?.batiment" class="text-danger small">{{ form.errors.batiment }}</span>
        </div>

        <!-- ============================================== -->
        <!-- BLOC 2 : STRUCTURE ACADÉMIQUE -->
        <!-- ============================================== -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-sitemap"></i> Structure académique
            </h6>
            <p class="text-muted small mb-2">
                <i class="bx bx-info-circle"></i>
                Sélectionnez d'abord l'école — le campus et les niveaux seront chargés automatiquement.
            </p>
        </div>

        <div class="col-sm-6">
            <label class="form-label fw-medium">École</label>
            <SearchableSelect
                v-model="form.ecole_id"
                :options="ecoles"
                optionValue="id"
                optionLabel="nom"
                placeholder="-- Sélectionner une école --"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.ecole_id" class="text-danger small">{{ form.errors.ecole_id }}</span>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-medium">
                Campus
                <span v-if="ecoleSelected" class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span>
            </label>
            <input type="text" class="form-control" :value="campusLabel" disabled style="background:#eef2f7; color:#64748b;" />
        </div>

        <div class="col-sm-6">
            <label class="form-label fw-medium">Section</label>
            <SearchableSelect
                v-model="form.section_id"
                :options="sections"
                optionValue="id"
                optionLabel="libelle"
                placeholder="-- Sélectionner --"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.section_id" class="text-danger small">{{ form.errors.section_id }}</span>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-medium">Niveau</label>
            <SearchableSelect
                v-model="form.niveau_id"
                :options="filteredNiveaux"
                optionValue="id"
                optionLabel="libelle"
                :placeholder="ecoleSelected ? '-- Sélectionner --' : 'Sélectionnez d\'abord une école'"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.niveau_id" class="text-danger small">{{ form.errors.niveau_id }}</span>
        </div>

        <div class="col-sm-6">
            <label class="form-label fw-medium">Cycle</label>
            <SearchableSelect
                v-model="form.cycle_id"
                :options="cycles"
                optionValue="id"
                optionLabel="libelle"
                placeholder="-- Sélectionner --"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.cycle_id" class="text-danger small">{{ form.errors.cycle_id }}</span>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-medium">Année scolaire</label>
            <SearchableSelect
                v-model="form.annee_scolaire_id"
                :options="anneesScolaires"
                optionValue="id"
                optionLabel="libelle"
                placeholder="-- Sélectionner --"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.annee_scolaire_id" class="text-danger small">{{ form.errors.annee_scolaire_id }}</span>
        </div>

        <!-- ============================================== -->
        <!-- BLOC 3 : ENSEIGNANT ET CAPACITÉ -->
        <!-- ============================================== -->
        <div class="col-12">
            <h6 class="section-header">
                <i class="fa fa-user-tie"></i> Enseignant et capacité
            </h6>
        </div>

        <div class="col-sm-6">
            <label class="form-label fw-medium">Enseignant titulaire</label>
            <SearchableSelect
                v-model="form.enseignant_titulaire_id"
                :options="enseignants"
                optionValue="id"
                :optionLabel="enseignantLabel"
                placeholder="-- Sélectionner --"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.enseignant_titulaire_id" class="text-danger small">{{ form.errors.enseignant_titulaire_id }}</span>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-medium">Capacité maximale</label>
            <input
                v-model.number="form.capacite_max"
                type="number"
                class="form-control"
                placeholder="Nombre maxi"
                :disabled="isReadOnly"
                min="1"
            />
            <span v-if="form.errors?.capacite_max" class="text-danger small">{{ form.errors.capacite_max }}</span>
        </div>

        <div class="col-sm-6">
            <label class="form-label fw-medium">Capacité actuelle</label>
            <input
                v-model.number="form.capacite_actuelle"
                type="number"
                class="form-control"
                placeholder="Effectif actuel"
                :disabled="isReadOnly"
                min="0"
            />
            <span v-if="form.errors?.capacite_actuelle" class="text-danger small">{{ form.errors.capacite_actuelle }}</span>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-medium">Statut</label>
            <SearchableSelect
                v-model="form.statut"
                :options="statusOptions"
                optionValue="id"
                optionLabel="libelle"
                placeholder="-- Sélectionner --"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.statut" class="text-danger small">{{ form.errors.statut }}</span>
        </div>
    </div>
</template>

<style scoped>
.custom-input .section-header {
    margin-top: 20px;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #0056b3;
    color: #0056b3;
    font-weight: 600;
    font-size: 1rem;
}
.custom-input .col-12 h6 { margin: 0; }
.custom-input .form-control,
.custom-input select {
    border: 1px solid #dee2e6;
    padding: 0.5rem 0.75rem;
}
.custom-input .form-control:focus,
.custom-input select:focus {
    border-color: #0056b3;
    box-shadow: 0 0 0 0.2rem rgba(0, 86, 179, 0.25);
}
.custom-input .form-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}
</style>
