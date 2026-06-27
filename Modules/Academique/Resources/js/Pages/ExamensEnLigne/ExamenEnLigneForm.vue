<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';
import InheritedContextBar from '@/Components/Common/InheritedContextBar.vue';
import { useClasseCascade } from '@/Composables/useClasseCascade';

const { t } = useI18n();

const props = defineProps({
    form: Object,
    matieres: Array,
    classes: Array,
    enseignants: Array,
    statuts: {
        type: Array,
        default: () => ['brouillon', 'publie', 'en_cours', 'termine', 'corrige'],
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});

const isReadOnly = props.mode === 'show';

// Cascade : Classe → École + Campus + ...
useClasseCascade(props.form, () => props.classes);

const statutLabels = {
    brouillon: 'Brouillon',
    publie: 'Publié',
    en_cours: 'En cours',
    termine: 'Terminé',
    corrige: 'Corrigé',
};

const statutOptions = computed(() =>
    props.statuts.map(s => ({ id: s, libelle: statutLabels[s] || s }))
);

const typeQuestionOptions = [
    { id: 'qcm', libelle: 'QCM (Choix multiples)' },
    { id: 'vrai_faux', libelle: 'Vrai / Faux' },
    { id: 'reponse_libre', libelle: 'Réponse libre' },
    { id: 'texte_trous', libelle: 'Texte à trous' },
];
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Section 1: Informations de base -->
        <div class="form-section">
            <h6 class="section-title">
                <i class="fa fa-info-circle me-1"></i>
                {{ t('exam.basic_info') || 'Informations de base' }}
            </h6>
            <div class="col-sm-6">
                <label class="form-label">{{ t('fields.titre') || 'Titre' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.titre"
                    type="text"
                    class="form-control form-control-sm"
                    :placeholder="'Ex: Examen de Mathématiques - Chapitre 5'"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.titre" class="text-danger small mt-1">{{ form.errors.titre }}</div>
            </div>
            <div class="col-sm-6">
                <label class="form-label">{{ t('common.matiere') || 'Matière' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.matiere_id"
                    :options="matieres"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || 'Sélectionner'"
                    class="form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.matiere_id" class="text-danger small mt-1">{{ form.errors.matiere_id }}</div>
            </div>
            <div class="col-sm-6">
                <label class="form-label">{{ t('common.classe') || 'Classe' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.classe_id"
                    :options="classes"
                    optionValue="id"
                    optionLabel="nom"
                    :placeholder="t('common.select') || 'Sélectionner'"
                    class="form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.classe_id" class="text-danger small mt-1">{{ form.errors.classe_id }}</div>
            </div>
            <InheritedContextBar
                :source="classes?.find(c => String(c.id) === String(form.classe_id)) || null"
                title="Hérité de la classe"
            />
            <div class="col-sm-6">
                <label class="form-label">{{ t('common.enseignant') || 'Enseignant' }}</label>
                <SearchableSelect
                    v-model="form.enseignant_id"
                    :options="enseignants"
                    optionValue="id"
                    :optionLabel="(option) => option.prenoms + ' ' + option.nom"
                    :placeholder="t('common.select') || 'Sélectionner'"
                    class="form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.enseignant_id" class="text-danger small mt-1">{{ form.errors.enseignant_id }}</div>
            </div>
        </div>

        <!-- Section 2: Description et Instructions -->
        <div class="form-section mt-3">
            <h6 class="section-title">
                <i class="fa fa-file-alt me-1"></i>
                {{ t('exam.description_instructions') || 'Description et Instructions' }}
            </h6>
            <div class="col-sm-6">
                <label class="form-label">{{ t('fields.description') || 'Description' }}</label>
                <textarea
                    v-model="form.description"
                    class="form-control form-control-sm"
                    rows="3"
                    :placeholder="'Description générale de l\'examen...'"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.description" class="text-danger small mt-1">{{ form.errors.description }}</div>
            </div>
            <div class="col-sm-6">
                <label class="form-label">{{ t('exam.instructions') || 'Instructions pour les apprenants' }}</label>
                <textarea
                    v-model="form.instructions"
                    class="form-control form-control-sm"
                    rows="3"
                    :placeholder="'Consignes à afficher avant le début de l\'examen...'"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.instructions" class="text-danger small mt-1">{{ form.errors.instructions }}</div>
            </div>
        </div>

        <!-- Section 3: Planification (Dates et Durée) -->
        <div class="form-section mt-3">
            <h6 class="section-title">
                <i class="fa fa-calendar-alt me-1"></i>
                {{ t('exam.planning') || 'Planification' }}
            </h6>
            <div class="col-sm-4">
                <label class="form-label">{{ t('fields.date_debut') || 'Date de début' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.date_debut"
                    type="datetime-local"
                    class="form-control form-control-sm"
                    :disabled="isReadOnly"
                />
                <small class="text-muted">Date et heure d'ouverture de l'examen</small>
                <div v-if="form.errors.date_debut" class="text-danger small mt-1">{{ form.errors.date_debut }}</div>
            </div>
            <div class="col-sm-4">
                <label class="form-label">{{ t('fields.date_fin') || 'Date de fin' }} <span class="text-danger">*</span></label>
                <input
                    v-model="form.date_fin"
                    type="datetime-local"
                    class="form-control form-control-sm"
                    :disabled="isReadOnly"
                />
                <small class="text-muted">Date et heure de fermeture</small>
                <div v-if="form.errors.date_fin" class="text-danger small mt-1">{{ form.errors.date_fin }}</div>
            </div>
            <div class="col-sm-4">
                <label class="form-label">{{ t('exam.duree_minutes') || 'Durée de composition (min)' }} <span class="text-danger">*</span></label>
                <input
                    v-model.number="form.duree_minutes"
                    type="number"
                    min="1"
                    class="form-control form-control-sm"
                    :placeholder="'Ex: 120 pour 2h'"
                    :disabled="isReadOnly"
                />
                <small class="text-muted">Temps alloué à chaque apprenant pour composer (en minutes)</small>
                <div v-if="form.errors.duree_minutes" class="text-danger small mt-1">{{ form.errors.duree_minutes }}</div>
            </div>
        </div>

        <!-- Section 4: Notation -->
        <div class="form-section mt-3">
            <h6 class="section-title">
                <i class="fa fa-star me-1"></i>
                {{ t('exam.notation') || 'Barème et Notation' }}
            </h6>
            <div class="col-sm-4">
                <label class="form-label">{{ t('fields.note_maximum') || 'Note maximum' }} <span class="text-danger">*</span></label>
                <input
                    v-model.number="form.note_maximum"
                    type="number"
                    min="0"
                    step="0.5"
                    class="form-control form-control-sm"
                    :placeholder="'Ex: 20'"
                    :disabled="isReadOnly"
                />
                <small class="text-muted">Barème total de l'examen</small>
                <div v-if="form.errors.note_maximum" class="text-danger small mt-1">{{ form.errors.note_maximum }}</div>
            </div>
            <div class="col-sm-4">
                <label class="form-label">{{ t('fields.note_minimum_passage') || 'Note minimum de passage' }}</label>
                <input
                    v-model.number="form.note_minimum_passage"
                    type="number"
                    min="0"
                    step="0.5"
                    class="form-control form-control-sm"
                    :placeholder="'Ex: 10'"
                    :disabled="isReadOnly"
                />
                <small class="text-muted">Seuil de réussite</small>
                <div v-if="form.errors.note_minimum_passage" class="text-danger small mt-1">{{ form.errors.note_minimum_passage }}</div>
            </div>
            <div class="col-sm-4">
                <label class="form-label">{{ t('exam.nombre_tentatives') || 'Nombre de tentatives' }}</label>
                <input
                    v-model.number="form.nombre_tentatives"
                    type="number"
                    min="1"
                    max="10"
                    class="form-control form-control-sm"
                    :placeholder="'1'"
                    :disabled="isReadOnly"
                />
                <small class="text-muted">Combien de fois l'apprenant peut tenter</small>
                <div v-if="form.errors.nombre_tentatives" class="text-danger small mt-1">{{ form.errors.nombre_tentatives }}</div>
            </div>
        </div>

        <!-- Section 5: Paramètres de l'examen -->
        <div class="form-section mt-3">
            <h6 class="section-title">
                <i class="fa fa-cog me-1"></i>
                {{ t('exam.settings') || 'Paramètres de l\'examen' }}
            </h6>
            <div class="col-12">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <div class="form-check form-switch">
                            <input
                                v-model="form.melange_questions"
                                type="checkbox"
                                class="form-check-input"
                                id="melange_questions"
                                :disabled="isReadOnly"
                            />
                            <label class="form-check-label" for="melange_questions">
                                <i class="fa fa-random me-1"></i>
                                {{ t('exam.melange_questions') || 'Mélanger les questions' }}
                            </label>
                        </div>
                        <small class="text-muted d-block ms-4">Ordre aléatoire pour chaque apprenant</small>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-check form-switch">
                            <input
                                v-model="form.melange_reponses"
                                type="checkbox"
                                class="form-check-input"
                                id="melange_reponses"
                                :disabled="isReadOnly"
                            />
                            <label class="form-check-label" for="melange_reponses">
                                <i class="fa fa-random me-1"></i>
                                {{ t('exam.melange_reponses') || 'Mélanger les réponses' }}
                            </label>
                        </div>
                        <small class="text-muted d-block ms-4">Ordre aléatoire des choix de réponse</small>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-check form-switch">
                            <input
                                v-model="form.retour_arriere"
                                type="checkbox"
                                class="form-check-input"
                                id="retour_arriere"
                                :disabled="isReadOnly"
                            />
                            <label class="form-check-label" for="retour_arriere">
                                <i class="fa fa-arrow-left me-1"></i>
                                {{ t('exam.retour_arriere') || 'Retour en arrière' }}
                            </label>
                        </div>
                        <small class="text-muted d-block ms-4">L'apprenant peut revenir aux questions précédentes</small>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-check form-switch">
                            <input
                                v-model="form.afficher_resultat"
                                type="checkbox"
                                class="form-check-input"
                                id="afficher_resultat"
                                :disabled="isReadOnly"
                            />
                            <label class="form-check-label" for="afficher_resultat">
                                <i class="fa fa-chart-bar me-1"></i>
                                {{ t('exam.afficher_resultat') || 'Afficher le résultat' }}
                            </label>
                        </div>
                        <small class="text-muted d-block ms-4">Score visible après soumission</small>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-check form-switch">
                            <input
                                v-model="form.afficher_correction"
                                type="checkbox"
                                class="form-check-input"
                                id="afficher_correction"
                                :disabled="isReadOnly"
                            />
                            <label class="form-check-label" for="afficher_correction">
                                <i class="fa fa-check-double me-1"></i>
                                {{ t('exam.afficher_correction') || 'Afficher la correction' }}
                            </label>
                        </div>
                        <small class="text-muted d-block ms-4">Bonnes réponses visibles après correction</small>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">
                            <i class="fa fa-lock me-1"></i>
                            {{ t('exam.mot_de_passe') || 'Mot de passe (optionnel)' }}
                        </label>
                        <input
                            v-model="form.mot_de_passe"
                            type="text"
                            class="form-control form-control-sm"
                            :placeholder="'Laisser vide = pas de mot de passe'"
                            :disabled="isReadOnly"
                        />
                        <small class="text-muted">Pour contrôler l'accès en salle</small>
                        <div v-if="form.errors.mot_de_passe" class="text-danger small mt-1">{{ form.errors.mot_de_passe }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 6: Statut -->
        <div class="form-section mt-3">
            <h6 class="section-title">
                <i class="fa fa-flag me-1"></i>
                {{ t('common.status') || 'Statut' }}
            </h6>
            <div class="col-sm-6">
                <label class="form-label">{{ t('exam.cycle_vie') || 'État de l\'examen' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="statutOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('common.select') || 'Sélectionner'"
                    class="form-control-sm"
                    :disabled="isReadOnly"
                />
                <div v-if="form.errors.etat" class="text-danger small mt-1">{{ form.errors.etat }}</div>
            </div>
            <div class="col-sm-6 d-flex align-items-end">
                <div class="alert alert-info mb-0 w-100 py-2 small">
                    <strong>Cycle de vie :</strong> Brouillon → Publié → En cours → Terminé → Corrigé
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.form-section {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.form-section .col-sm-6,
.form-section .col-sm-4 {
    flex: 1;
    min-width: 200px;
}

.section-title {
    width: 100%;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #0B5697;
    border-bottom: 2px solid #0FBCAF;
    padding-bottom: 0.4rem;
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
