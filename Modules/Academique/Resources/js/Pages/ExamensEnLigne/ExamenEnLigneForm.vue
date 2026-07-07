<!--
  ExamenEnLigneForm.vue — Refonte Phase 4.4 (Steppers).
  Historique : 410 lignes / 6 sections empilées → 4 steps guidés.

  Steps :
    1. Contexte     (titre, matière, classe → contexte auto, enseignant, description)
    2. Planning     (date début, date fin, durée en minutes, instructions)
    3. Notation     (note max, seuil de réussite, nombre de tentatives)
    4. Paramètres   (switches mélange/retour/afficher, mot de passe, statut)
-->

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import InheritedContextBar from '@/Components/Common/InheritedContextBar.vue';
import FormStepper from '@/Components/Common/FormStepper.vue';
import { useClasseCascade } from '@/Composables/useClasseCascade';

const { t } = useI18n();

const props = defineProps({
    form:        { type: Object, required: true },
    matieres:    { type: Array,  default: () => [] },
    classes:     { type: Array,  default: () => [] },
    enseignants: { type: Array,  default: () => [] },
    statuts:     { type: Array,  default: () => ['brouillon', 'publie', 'en_cours', 'termine', 'corrige'] },
    mode:        { type: String, default: 'create', validator: (v) => ['create', 'edit', 'show'].includes(v) },
});

const emit = defineEmits(['submit']);

const isReadOnly = props.mode === 'show';
const currentStep = ref(0);

useClasseCascade(props.form, () => props.classes);

const statutLabels = {
    brouillon: 'Brouillon',
    publie:    'Publié',
    en_cours:  'En cours',
    termine:   'Terminé',
    corrige:   'Corrigé',
};
const statutOptions = computed(() =>
    props.statuts.map(s => ({ id: s, libelle: statutLabels[s] || s }))
);

const steps = [
    { key: 'contexte',   label: 'Contexte',    icon: 'fas fa-info-circle',  requiredFields: ['titre', 'matiere_id', 'classe_id'] },
    { key: 'planning',   label: 'Planning',    icon: 'fas fa-calendar-alt', requiredFields: ['date_debut', 'date_fin', 'duree_minutes'] },
    { key: 'notation',   label: 'Notation',    icon: 'fas fa-star',         requiredFields: ['note_maximum'] },
    { key: 'parametres', label: 'Paramètres',  icon: 'fas fa-cog',           requiredFields: ['etat'] },
];
</script>

<template>
    <FormStepper
        v-model="currentStep"
        :steps="steps"
        :form="form"
        persist-key="examen-en-ligne-form"
        @submit="$emit('submit')"
    >
        <!-- STEP 1 : CONTEXTE -->
        <template #contexte>
            <div class="row g-3">
                <div class="col-md-8">
                    <label>Titre <span class="text-danger">*</span></label>
                    <input v-model="form.titre" :disabled="isReadOnly" type="text" class="form-control" placeholder="Ex : Examen de Mathématiques - Chapitre 5" />
                    <span v-if="form.errors?.titre" class="text-danger small">{{ form.errors.titre }}</span>
                </div>
                <div class="col-md-4">
                    <label>Matière <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.matiere_id"
                        :options="matieres"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('common.select') || 'Sélectionner'"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.matiere_id" class="text-danger small">{{ form.errors.matiere_id }}</span>
                </div>
                <div class="col-md-6">
                    <label>Classe <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.classe_id"
                        :options="classes"
                        optionValue="id"
                        optionLabel="nom"
                        :placeholder="t('common.select') || 'Sélectionner'"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.classe_id" class="text-danger small">{{ form.errors.classe_id }}</span>
                </div>
                <div class="col-md-6">
                    <label>Enseignant</label>
                    <SearchableSelect
                        v-model="form.enseignant_id"
                        :options="enseignants"
                        optionValue="id"
                        :optionLabel="(o) => `${o.prenoms || ''} ${o.nom || ''}`.trim()"
                        :placeholder="t('common.select') || 'Sélectionner'"
                        :disabled="isReadOnly"
                    />
                </div>
                <div class="col-12">
                    <InheritedContextBar
                        v-if="form.classe_id"
                        :source="classes?.find(c => String(c.id) === String(form.classe_id)) || null"
                        title="Hérité de la classe"
                    />
                </div>
                <div class="col-12">
                    <label>Description</label>
                    <textarea v-model="form.description" :disabled="isReadOnly" class="form-control" rows="2" placeholder="Description générale de l'examen…"></textarea>
                </div>
            </div>
        </template>

        <!-- STEP 2 : PLANNING -->
        <template #planning>
            <div class="row g-3">
                <div class="col-md-4">
                    <label>Date de début <span class="text-danger">*</span></label>
                    <input v-model="form.date_debut" :disabled="isReadOnly" type="datetime-local" class="form-control" />
                    <small class="text-muted">Ouverture de l'examen</small>
                    <span v-if="form.errors?.date_debut" class="text-danger small d-block">{{ form.errors.date_debut }}</span>
                </div>
                <div class="col-md-4">
                    <label>Date de fin <span class="text-danger">*</span></label>
                    <input v-model="form.date_fin" :disabled="isReadOnly" type="datetime-local" class="form-control" />
                    <small class="text-muted">Fermeture de l'examen</small>
                    <span v-if="form.errors?.date_fin" class="text-danger small d-block">{{ form.errors.date_fin }}</span>
                </div>
                <div class="col-md-4">
                    <label>Durée (minutes) <span class="text-danger">*</span></label>
                    <input v-model.number="form.duree_minutes" :disabled="isReadOnly" type="number" min="1" class="form-control" placeholder="Ex : 120 pour 2h" />
                    <small class="text-muted">Temps alloué par apprenant</small>
                    <span v-if="form.errors?.duree_minutes" class="text-danger small d-block">{{ form.errors.duree_minutes }}</span>
                </div>
                <div class="col-12">
                    <label>Instructions pour les apprenants</label>
                    <textarea v-model="form.instructions" :disabled="isReadOnly" class="form-control" rows="3" placeholder="Consignes à afficher avant le début de l'examen…"></textarea>
                </div>
            </div>
        </template>

        <!-- STEP 3 : NOTATION -->
        <template #notation>
            <div class="row g-3">
                <div class="col-md-4">
                    <label>Note maximum <span class="text-danger">*</span></label>
                    <input v-model.number="form.note_maximum" :disabled="isReadOnly" type="number" min="0" step="0.5" class="form-control" placeholder="Ex : 20" />
                    <small class="text-muted">Barème total</small>
                    <span v-if="form.errors?.note_maximum" class="text-danger small d-block">{{ form.errors.note_maximum }}</span>
                </div>
                <div class="col-md-4">
                    <label>Note minimum de passage</label>
                    <input v-model.number="form.note_minimum_passage" :disabled="isReadOnly" type="number" min="0" step="0.5" class="form-control" placeholder="Ex : 10" />
                    <small class="text-muted">Seuil de réussite</small>
                </div>
                <div class="col-md-4">
                    <label>Nombre de tentatives</label>
                    <input v-model.number="form.nombre_tentatives" :disabled="isReadOnly" type="number" min="1" max="10" class="form-control" placeholder="1" />
                    <small class="text-muted">Combien de fois l'apprenant peut tenter</small>
                </div>
            </div>
        </template>

        <!-- STEP 4 : PARAMÈTRES & STATUT -->
        <template #parametres>
            <div class="row g-3">
                <div class="col-12">
                    <h6 class="text-primary"><i class="fa fa-sliders-h me-1"></i> Options de composition</h6>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input v-model="form.melange_questions" type="checkbox" class="form-check-input" id="melange_questions" :disabled="isReadOnly" />
                        <label class="form-check-label" for="melange_questions">
                            <i class="fa fa-random me-1"></i> Mélanger les questions
                        </label>
                    </div>
                    <small class="text-muted d-block ms-4">Ordre aléatoire par apprenant</small>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input v-model="form.melange_reponses" type="checkbox" class="form-check-input" id="melange_reponses" :disabled="isReadOnly" />
                        <label class="form-check-label" for="melange_reponses">
                            <i class="fa fa-random me-1"></i> Mélanger les réponses
                        </label>
                    </div>
                    <small class="text-muted d-block ms-4">Ordre aléatoire des choix</small>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input v-model="form.retour_arriere" type="checkbox" class="form-check-input" id="retour_arriere" :disabled="isReadOnly" />
                        <label class="form-check-label" for="retour_arriere">
                            <i class="fa fa-arrow-left me-1"></i> Retour en arrière
                        </label>
                    </div>
                    <small class="text-muted d-block ms-4">Revenir aux questions précédentes</small>
                </div>

                <hr class="mt-3" />
                <div class="col-12">
                    <h6 class="text-primary"><i class="fa fa-eye me-1"></i> Restitution</h6>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input v-model="form.afficher_resultat" type="checkbox" class="form-check-input" id="afficher_resultat" :disabled="isReadOnly" />
                        <label class="form-check-label" for="afficher_resultat">
                            <i class="fa fa-chart-bar me-1"></i> Afficher le résultat
                        </label>
                    </div>
                    <small class="text-muted d-block ms-4">Score visible après soumission</small>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input v-model="form.afficher_correction" type="checkbox" class="form-check-input" id="afficher_correction" :disabled="isReadOnly" />
                        <label class="form-check-label" for="afficher_correction">
                            <i class="fa fa-check-double me-1"></i> Afficher la correction
                        </label>
                    </div>
                    <small class="text-muted d-block ms-4">Bonnes réponses visibles</small>
                </div>
                <div class="col-md-4">
                    <label><i class="fa fa-lock me-1"></i> Mot de passe (optionnel)</label>
                    <input v-model="form.mot_de_passe" :disabled="isReadOnly" type="text" class="form-control" placeholder="Vide = pas de mot de passe" />
                    <small class="text-muted">Pour contrôler l'accès en salle</small>
                </div>

                <hr class="mt-3" />
                <div class="col-md-6">
                    <label>État de l'examen <span class="text-danger">*</span></label>
                    <SearchableSelect
                        v-model="form.etat"
                        :options="statutOptions"
                        optionValue="id"
                        optionLabel="libelle"
                        :placeholder="t('common.select') || 'Sélectionner'"
                        :disabled="isReadOnly"
                    />
                    <span v-if="form.errors?.etat" class="text-danger small">{{ form.errors.etat }}</span>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="alert alert-info mb-0 w-100 py-2 small">
                        <strong>Cycle de vie :</strong> Brouillon → Publié → En cours → Terminé → Corrigé
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
.form-check-input:checked {
    background-color: #0FBCAF;
    border-color: #0FBCAF;
}
.form-switch .form-check-input {
    width: 2.5em;
    height: 1.25em;
}
.alert-info {
    background-color: #e8f4f8;
    border-color: #0FBCAF;
    color: #0B5697;
}
</style>
