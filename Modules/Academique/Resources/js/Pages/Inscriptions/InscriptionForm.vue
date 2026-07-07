<!--
  InscriptionForm.vue — Refonte Phase 2.5 (Steppers).
  Historique : 738 lignes / 4 sections empilées → 4 steps guidés.

  Steps :
    1. Apprenant & Année      (apprenant, num_inscription, date, type, premiere, annee_scolaire,
                                classe → ecole/campus/institution auto)
    2. Frais                  (dossier / inscription / scolarité — base, payé, reste + totaux)
    3. Pièces jointes         (8 uploads + dossier_complet)
    4. Validation             (statut + résumé + preview fichiers)

  Auto-fill préservé :
    - classe → ecole, campus, section, cycle, annee_scolaire (via useClasseAutoFill)
    - apprenant → classe (via useApprenantAutoFill)
    - apprenant → numero_inscription (fallback)
-->

<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import FormStepper from '@/Components/Common/FormStepper.vue';
import InheritedContextBar from '@/Components/Common/InheritedContextBar.vue';
import { useClasseAutoFill } from '../../composables/useClasseAutoFill';
import { useApprenantAutoFill } from '../../composables/useApprenantAutoFill';
import { useClasseCascade } from '@/Composables/useClasseCascade';
import { useApprenantCascade } from '@/Composables/useApprenantCascade';

const { t } = useI18n();

const props = defineProps({
    form:              { type: Object, required: true },
    apprenants:        { type: Array,  default: () => [] },
    classes:           { type: Array,  default: () => [] },
    anneesScolaires:   { type: Array,  default: () => [] },
    ecoles:            { type: Array,  default: () => [] },
    campuses:          { type: Array,  default: () => [] },
    institutions:      { type: Array,  default: () => [] },
    typesInscriptions: { type: Array,  default: () => [] },
    mode: {
        type: String,
        default: 'create',
        validator: (v) => ['create', 'edit', 'show'].includes(v),
    },
});

const emit = defineEmits(['submit']);

const isReadOnly = props.mode === 'show';
const currentStep = ref(0);

// Cascades — remplissent le form en réactif.
useClasseCascade(props.form, () => props.classes);
useApprenantCascade(props.form, () => props.apprenants);

if (!isReadOnly) {
    useClasseAutoFill(props.form);
    useApprenantAutoFill(props.form);

    // Auto-fill numero_inscription depuis l'apprenant (fallback si legacy).
    watch(() => props.form.apprenant_id, (id) => {
        if (!id) return;
        const a = props.apprenants.find(x => x.id === id);
        if (a?.numero_inscription && !props.form.numero_inscription) {
            props.form.numero_inscription = a.numero_inscription;
        }
    });
}

// Libellés auto-remplis pour affichage read-only.
const autoLabel = (list, id, keyLibelle = 'libelle', keyNom = 'nom') => {
    if (!id || !list?.length) return '—';
    const found = list.find(x => String(x.id) === String(id));
    return found?.[keyLibelle] || found?.[keyNom] || '—';
};
const ecoleLabel  = computed(() => autoLabel(props.ecoles,  props.form.ecole_id));
const campusLabel = computed(() => autoLabel(props.campuses, props.form.campus_id));

// Format date ISO → YYYY-MM-DD pour <input type="date">.
const formatDateForDateInput = (dateStr) => {
    if (!dateStr) return '';
    if (typeof dateStr === 'string') return dateStr.split('T')[0];
    if (dateStr instanceof Date) return dateStr.toISOString().split('T')[0];
    return '';
};
const formattedDateInscription = computed({
    get: () => formatDateForDateInput(props.form?.date_inscription),
    set: (v) => { props.form.date_inscription = v; },
});

// Options statut & type.
const statutOptions = [
    { id: 'en_attente', libelle: t('common.en_attente') || 'En attente' },
    { id: 'validee',    libelle: t('common.validee')    || 'Validée' },
    { id: 'rejetee',    libelle: t('common.rejetee')    || 'Rejetée' },
    { id: 'suspendue',  libelle: t('common.suspendue')  || 'Suspendue' },
];
const defaultTypeInscriptions = [
    { id: 'nouveau',      libelle: 'Nouveau' },
    { id: 'redoublement', libelle: 'Redoublement' },
    { id: 'transfert',    libelle: 'Transfert' },
    { id: 'reprise',      libelle: 'Reprise' },
];
const typeInscriptionOptions = computed(() =>
    (props.typesInscriptions?.length > 0
        ? props.typesInscriptions.map(x => ({ id: x.code.toLowerCase(), libelle: x.libelle }))
        : defaultTypeInscriptions)
);

// Calculs frais.
const num = (v) => Number(v) || 0;
const fraisDossierRestant    = computed(() => Math.max(0, num(props.form.frais_dossier)     - num(props.form.frais_dossier_paye)));
const fraisInscriptionRestant = computed(() => Math.max(0, num(props.form.frais_inscription) - num(props.form.frais_inscription_paye)));
const fraisScolariteRestant  = computed(() => Math.max(0, num(props.form.frais_scolarite)   - num(props.form.frais_scolarite_paye)));
const totalPaye    = computed(() => num(props.form.frais_dossier_paye) + num(props.form.frais_inscription_paye) + num(props.form.frais_scolarite_paye));
const totalRestant = computed(() => fraisDossierRestant.value + fraisInscriptionRestant.value + fraisScolariteRestant.value);

// Fichiers.
const handleFileUpload = (field, event) => {
    const file = event.target.files?.[0];
    if (file) props.form[field] = file;
};
const getFileUrl = (p) => (p ? `/storage/${p}` : null);

const fileFields = [
    { key: 'fiche_inscription',    label: 'Fiche d\'inscription' },
    { key: 'carnet_vaccination',   label: 'Carnet de vaccination' },
    { key: 'photos_4x4',           label: 'Photos 4x4' },
    { key: 'copie_acte_naissance', label: 'Copie acte de naissance' },
    { key: 'piece1',               label: 'Pièce 1' },
    { key: 'piece2',               label: 'Pièce 2' },
    { key: 'piece3',               label: 'Pièce 3' },
    { key: 'piece4',               label: 'Pièce 4' },
];

const getFilePreviewData = (field) => {
    const file = props.form[field];
    if (!file) return null;
    if (file instanceof File) {
        return {
            name: file.name,
            size: (file.size / 1024).toFixed(2) + ' KB',
            isImage: file.type.startsWith('image/'),
            isPdf: file.type === 'application/pdf',
            preview: URL.createObjectURL(file),
        };
    }
    if (typeof file === 'string') {
        const ext = file.split('.').pop().toLowerCase();
        return {
            name: file.split('/').pop(),
            size: 'Fichier existant',
            isImage: ['jpg', 'jpeg', 'png', 'gif'].includes(ext),
            isPdf: ext === 'pdf',
            preview: `/storage/${file}`,
        };
    }
    return null;
};
const selectedFiles = computed(() =>
    fileFields.map(f => ({ ...f, data: getFilePreviewData(f.key) }))
              .filter(f => f.data !== null)
);

const steps = [
    { key: 'apprenant', label: 'Apprenant & Année',  icon: 'fas fa-user-graduate', requiredFields: ['apprenant_id', 'classe_id', 'annee_scolaire_id', 'date_inscription', 'type_inscription'] },
    { key: 'frais',     label: 'Frais',              icon: 'fas fa-money-bill' },
    { key: 'pieces',    label: 'Pièces jointes',     icon: 'fas fa-file-upload' },
    { key: 'validation',label: 'Validation',         icon: 'fas fa-check-circle',   requiredFields: ['statut'] },
];
</script>

<template>
    <FormStepper
        v-model="currentStep"
        :steps="steps"
        :form="form"
        persist-key="inscription-form"
        @submit="$emit('submit')"
    >
        <!-- STEP 1 : APPRENANT & AFFECTATION -->
        <template #apprenant>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Apprenant <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.apprenant_id"
                        :options="apprenants"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select') || '-- Sélectionner --'"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.apprenant_id" class="text-danger small">{{ form.errors.apprenant_id }}</span>
                </div>
                <div class="col-md-6">
                    <label>Numéro d'inscription <small class="text-muted">(auto si vide)</small></label>
                    <input v-model="form.numero_inscription" :disabled="isReadOnly" type="text" class="form-control" placeholder="INS-2026-00001" />
                </div>
                <div class="col-md-4">
                    <label>Date d'inscription <span class="text-danger">*</span></label>
                    <input v-model="formattedDateInscription" :disabled="isReadOnly" type="date" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>Type d'inscription <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.type_inscription"
                        :options="typeInscriptionOptions"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select')"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <label class="form-check-label">
                        <input v-model="form.premiere_inscription" :disabled="isReadOnly" type="checkbox" class="form-check-input me-1" />
                        Première inscription
                    </label>
                </div>

                <hr class="mt-3" />
                <div class="col-md-6">
                    <label>Année scolaire <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.annee_scolaire_id"
                        :options="anneesScolaires"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select')"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-md-6">
                    <label>Classe (Salle de cours) <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.classe_id"
                        :options="classes"
                        optionValue="id"
                        optionLabel="nom"
                        :placeholder="t('actions.select')"
                        :disabled="isReadOnly"
                    />
                </div>

                <div class="col-12">
                    <InheritedContextBar
                        :source="classes?.find(c => String(c.id) === String(form.classe_id)) || null"
                        title="Hérité de la classe"
                    />
                </div>

                <div class="col-md-4">
                    <label>École <span class="badge bg-secondary">auto</span></label>
                    <input :value="ecoleLabel" type="text" class="form-control" readonly disabled />
                </div>
                <div class="col-md-4">
                    <label>Campus <span class="badge bg-secondary">auto</span></label>
                    <input :value="campusLabel" type="text" class="form-control" readonly disabled />
                </div>
                <div class="col-md-4">
                    <label>Institution</label>
                    <SearchableSelect
                        v-model="form.institution_id"
                        :options="institutions"
                        optionValue="id"
                        optionLabel="nom"
                        :placeholder="t('actions.select')"
                        :disabled="isReadOnly"
                    />
                </div>
            </div>
        </template>

        <!-- STEP 2 : FRAIS -->
        <template #frais>
            <div class="row g-3">
                <div class="col-12">
                    <h6 class="text-primary"><i class="fa fa-folder me-1"></i> Frais de dossier</h6>
                </div>
                <div class="col-md-4">
                    <label>Base</label>
                    <input v-model="form.frais_dossier" :disabled="isReadOnly" type="number" step="0.01" min="0" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>Payé</label>
                    <input v-model="form.frais_dossier_paye" :disabled="isReadOnly" type="number" step="0.01" min="0" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>Reste</label>
                    <input :value="fraisDossierRestant.toFixed(2)" type="text" class="form-control" readonly />
                </div>

                <div class="col-12 mt-3">
                    <h6 class="text-primary"><i class="fa fa-clipboard-list me-1"></i> Frais d'inscription</h6>
                </div>
                <div class="col-md-4">
                    <label>Base</label>
                    <input v-model="form.frais_inscription" :disabled="isReadOnly" type="number" step="0.01" min="0" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>Payé</label>
                    <input v-model="form.frais_inscription_paye" :disabled="isReadOnly" type="number" step="0.01" min="0" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>Reste</label>
                    <input :value="fraisInscriptionRestant.toFixed(2)" type="text" class="form-control" readonly />
                </div>

                <div class="col-12 mt-3">
                    <h6 class="text-primary"><i class="fa fa-graduation-cap me-1"></i> Frais de scolarité</h6>
                </div>
                <div class="col-md-4">
                    <label>Base</label>
                    <input v-model="form.frais_scolarite" :disabled="isReadOnly" type="number" step="0.01" min="0" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>Payé</label>
                    <input v-model="form.frais_scolarite_paye" :disabled="isReadOnly" type="number" step="0.01" min="0" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label>Reste</label>
                    <input :value="fraisScolariteRestant.toFixed(2)" type="text" class="form-control" readonly />
                </div>

                <hr class="mt-4" />
                <div class="col-md-6">
                    <div class="p-3 bg-success bg-opacity-10 rounded border border-success">
                        <strong class="text-success">Total payé : {{ totalPaye.toFixed(2) }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-warning bg-opacity-10 rounded border border-warning">
                        <strong class="text-warning">Total restant : {{ totalRestant.toFixed(2) }}</strong>
                    </div>
                </div>
            </div>
        </template>

        <!-- STEP 3 : PIÈCES JOINTES -->
        <template #pieces>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-check-label">
                        <input v-model="form.dossier_complet" :disabled="isReadOnly" type="checkbox" class="form-check-input me-1" />
                        <strong>Dossier complet</strong> — cochez si toutes les pièces obligatoires sont fournies
                    </label>
                </div>

                <div v-for="field in fileFields" :key="field.key" class="col-md-6">
                    <label>{{ field.label }}</label>
                    <input
                        v-if="!isReadOnly"
                        type="file"
                        class="form-control"
                        accept=".pdf,.jpg,.jpeg,.png"
                        @change="handleFileUpload(field.key, $event)"
                    />
                    <div v-if="form[field.key] && typeof form[field.key] === 'string'" class="mt-1">
                        <a :href="getFileUrl(form[field.key])" target="_blank" class="btn btn-sm btn-link p-0">
                            <i class="fa fa-download"></i> Télécharger le fichier actuel
                        </a>
                    </div>
                    <span v-if="form.errors?.[field.key]" class="text-danger small">{{ form.errors[field.key] }}</span>
                </div>

                <!-- Preview des fichiers sélectionnés -->
                <div v-if="selectedFiles.length > 0" class="col-12 mt-4">
                    <h6 class="text-primary">
                        <i class="fa fa-file me-1"></i> Fichiers sélectionnés
                        <span class="badge bg-primary ms-2">{{ selectedFiles.length }}</span>
                    </h6>
                    <div class="row g-2 mt-2">
                        <div v-for="file in selectedFiles" :key="file.key" class="col-md-4 col-sm-6">
                            <div class="card h-100 shadow-sm border-0">
                                <div v-if="file.data.isImage" style="height: 120px; overflow: hidden;">
                                    <img :src="file.data.preview" :alt="file.label" style="width: 100%; height: 100%; object-fit: cover;" />
                                </div>
                                <div v-else class="d-flex align-items-center justify-content-center bg-light" style="height: 120px;">
                                    <i :class="file.data.isPdf ? 'fa fa-file-pdf text-danger' : 'fa fa-file text-secondary'" style="font-size: 2.5rem;"></i>
                                </div>
                                <div class="card-body p-2">
                                    <small class="d-block text-truncate fw-bold">{{ file.label }}</small>
                                    <small class="text-muted d-block text-truncate">{{ file.data.name }}</small>
                                    <small class="text-muted">{{ file.data.size }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- STEP 4 : VALIDATION -->
        <template #validation>
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Statut de l'inscription <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.statut"
                        :options="statutOptions"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('actions.select')"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.statut" class="text-danger small">{{ form.errors.statut }}</span>
                </div>

                <div class="col-12 mt-3">
                    <div class="alert alert-info">
                        <h6 class="mb-2"><i class="fa fa-info-circle me-1"></i> Résumé avant validation</h6>
                        <ul class="mb-0 small">
                            <li>Apprenant : <strong>{{ apprenants.find(a => String(a.id) === String(form.apprenant_id))?.libelle || '—' }}</strong></li>
                            <li>Classe : <strong>{{ classes.find(c => String(c.id) === String(form.classe_id))?.nom || '—' }}</strong></li>
                            <li>Année scolaire : <strong>{{ anneesScolaires.find(a => String(a.id) === String(form.annee_scolaire_id))?.libelle || '—' }}</strong></li>
                            <li>Total payé : <strong>{{ totalPaye.toFixed(2) }}</strong> — Restant : <strong>{{ totalRestant.toFixed(2) }}</strong></li>
                            <li>Pièces jointes : <strong>{{ selectedFiles.length }} / 8</strong></li>
                            <li>Dossier complet : <strong>{{ form.dossier_complet ? 'Oui' : 'Non' }}</strong></li>
                        </ul>
                    </div>
                </div>
            </div>
        </template>
    </FormStepper>
</template>

<style scoped>
.form-control {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 0.55rem 0.85rem;
    font-size: 0.95rem;
}
.form-control:focus {
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
</style>
