<!--
  ClasseForm.vue

  BLOC 1 — Informations de base :
    L1: Code | Libellé
    L2: Libellé à afficher | Bâtiment

  BLOC 2 — Structure académique :
    L1: École | Campus (auto depuis l'école, lecture seule)
    L2: Niveau | Section (auto depuis le niveau, lecture seule)
    L3: Cycle (auto depuis le niveau, lecture seule)

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
    campuses: { type: Array, default: () => [] },
    mode: {
        type: String,
        default: 'create',
        validator: (v) => ['create', 'edit', 'show'].includes(v),
    },
});

const isReadOnly = props.mode === 'show';
const ecoleSelected = computed(() => !!props.form.ecole_id);
const niveauSelected = computed(() => !!props.form.niveau_id);

const autoLabel = (list, id) => {
    if (!id || !list?.length) return '—';
    const found = list.find((item) => String(item.id) === String(id));
    return found?.libelle || found?.nom || '—';
};

const campusLabel = computed(() => autoLabel(props.campuses, props.form.campus_id));
const sectionLabel = computed(() => autoLabel(props.sections, props.form.section_id));
const cycleLabel = computed(() => autoLabel(props.cycles, props.form.cycle_id));

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'non_actif', libelle: 'Inactif' },
    { id: 'suspendu', libelle: 'Suspendu' },
];

// Cascade École → Campus (auto-fill du campus depuis l'école)
watch(() => props.form.ecole_id, (newEcoleId) => {
    if (isReadOnly) return;
    if (!newEcoleId) {
        props.form.campus_id = null;
        return;
    }
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

// Reset niveau si absent de la liste filtrée
watch(filteredNiveaux, (newList) => {
    if (props.form.niveau_id && !newList.find((n) => String(n.id) === String(props.form.niveau_id))) {
        props.form.niveau_id = null;
    }
});

// Cascade Niveau → Section + Cycle (auto-fill depuis le niveau sélectionné)
watch(() => props.form.niveau_id, (newNiveauId) => {
    if (isReadOnly) return;
    if (!newNiveauId) {
        props.form.section_id = null;
        props.form.cycle_id = null;
        return;
    }
    const niveau = props.niveaux.find((n) => String(n.id) === String(newNiveauId));
    if (niveau) {
        props.form.section_id = niveau.section_id ?? null;
        props.form.cycle_id = niveau.cycle_id ?? null;
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
            <!-- École sélectionnée → campus auto-rempli (lecture seule) -->
            <input
                v-if="ecoleSelected"
                type="text"
                class="form-control"
                :value="campusLabel"
                disabled
                style="background:#eef2f7; color:#64748b;"
            />
            <!-- Pas d'école → saisie manuelle du campus -->
            <SearchableSelect
                v-else
                v-model="form.campus_id"
                :options="campuses"
                optionValue="id"
                optionLabel="libelle"
                placeholder="-- Sélectionner un campus --"
                :disabled="isReadOnly"
            />
            <span v-if="form.errors?.campus_id" class="text-danger small">{{ form.errors.campus_id }}</span>
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
            <label class="form-label fw-medium">
                Section
                <span v-if="niveauSelected" class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span>
            </label>
            <input
                type="text"
                class="form-control"
                :value="sectionLabel"
                disabled
                style="background:#eef2f7; color:#64748b;"
            />
            <span v-if="form.errors?.section_id" class="text-danger small">{{ form.errors.section_id }}</span>
        </div>

        <div class="col-sm-6">
            <label class="form-label fw-medium">
                Cycle
                <span v-if="niveauSelected" class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span>
            </label>
            <input
                type="text"
                class="form-control"
                :value="cycleLabel"
                disabled
                style="background:#eef2f7; color:#64748b;"
            />
            <span v-if="form.errors?.cycle_id" class="text-danger small">{{ form.errors.cycle_id }}</span>
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
