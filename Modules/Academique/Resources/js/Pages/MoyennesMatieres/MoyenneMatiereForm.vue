<script setup>
import { useI18n } from 'vue-i18n';
import { watch, ref, onMounted } from 'vue';
const { t } = useI18n();

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    apprenants: {
        type: Array,
        default: () => [],
    },
    matieres: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit', 'show'].includes(value),
    },
});

const isReadOnly = props.mode === 'show';
const isLoading = ref(false);
const calculatedData = ref(null);
const apprenantNotes = ref([]);
const errorMessage = ref(null);
const alreadyExists = ref(false);
const createdAverages = ref([]);
const isLoadingAverages = ref(false);

/**
 * Récupérer les moyennes déjà créées pour cet apprenant
 */
const fetchAveragesByApprenant = async () => {
    if (!props.form.apprenant_id) {
        createdAverages.value = [];
        return;
    }

    isLoadingAverages.value = true;
    try {
        const response = await fetch(
            `/academique/moyennes-matieres/api/averages-by-apprenant?apprenant_id=${props.form.apprenant_id}`
        );

        if (response.ok) {
            const result = await response.json();
            createdAverages.value = result.averages || [];
        }
    } catch (error) {
        console.error('Error fetching averages:', error);
        createdAverages.value = [];
    } finally {
        isLoadingAverages.value = false;
    }
};

/**
 * Vérifier si une MoyenneMatiere existe déjà pour cet apprenant + matière
 */
const checkIfAlreadyExists = async () => {
    if (!props.form.apprenant_id || !props.form.matiere_id) {
        alreadyExists.value = false;
        return;
    }

    try {
        const response = await fetch(
            `/academique/moyennes-matieres/check-exists?` +
            `apprenant_id=${props.form.apprenant_id}&` +
            `matiere_id=${props.form.matiere_id}`
        );

        if (response.ok) {
            const result = await response.json();
            alreadyExists.value = result.exists;
            if (result.exists) {
                errorMessage.value = `⚠️ Cette moyenne existe déjà! Apprenant: ${result.data?.apprenant_id}, Matière: ${result.data?.matiere_id}`;
            }
        }
    } catch (error) {
        console.error('Error checking existence:', error);
    }
};

/**
 * Vérifier automatiquement si les matières ont changé au démarrage
 */
const autoCheckMatieres = async () => {
    try {
        const localHash = props.matieres.map(m => `${m.id}:${m.coefficient}`).join('|');
        const savedHash = sessionStorage.getItem('matieres_hash');

        if (!savedHash || savedHash !== localHash) {
            sessionStorage.setItem('matieres_hash', localHash);

            if (savedHash) {
                console.log('🔄 Données des matières actualisées - rechargement...');
                setTimeout(() => window.location.reload(), 800);
            } else {
                console.log('✅ Données des matières chargées');
            }
        }
    } catch (error) {
        console.debug('Auto-check matières: skipped');
    }
};

/**
 * Au démarrage du composant
 */
onMounted(() => {
    autoCheckMatieres();
});

const appreciationOptions = [
    { id: 'excellent', libelle: 'Excellent' },
    { id: 'bien', libelle: 'Bien' },
    { id: 'assez', libelle: 'Assez' },
    { id: 'moyen', libelle: 'Moyen' },
    { id: 'faible', libelle: 'Faible' },
];

/**
 * Appelle l'API pour calculer automatiquement les moyennes
 */
const fetchCalculatedData = async () => {
    if (!props.form.apprenant_id) return;

    isLoading.value = true;
    errorMessage.value = null;

    try {
        const url = `/academique/apprenants/api/calculate-moyennes?apprenant_id=${props.form.apprenant_id}`;

        const response = await fetch(url);
        let result;
        try {
            result = await response.json();
        } catch (e) {
            throw new Error(`Erreur de réponse serveur (HTTP ${response.status}): ${response.statusText}`);
        }

        if (result.success) {
            calculatedData.value = result.data;
            apprenantNotes.value = result.data.notes || [];
            errorMessage.value = null;
        } else {
            errorMessage.value = result.message || 'Erreur lors du calcul';
            calculatedData.value = null;
        }
    } catch (error) {
        console.error('API Error:', error);
        errorMessage.value = '❌ ' + (error.message || 'Erreur lors du calcul');
        calculatedData.value = null;
    } finally {
        isLoading.value = false;
    }
};

/**
 * Watch: Quand l'apprenant change, réinitialiser et charger les moyennes existantes
 */
watch(() => props.form.apprenant_id, async () => {
    if (!isReadOnly) {
        props.form.matiere_id = '';
        props.form.moyenne = null;
        props.form.coefficient = null;
        props.form.appreciation = '';
        props.form.rang = null;
        calculatedData.value = null;
        errorMessage.value = null;
        alreadyExists.value = false;
    }
    // Charger les moyennes déjà créées pour cet apprenant
    await fetchAveragesByApprenant();
});

/**
 * Watch: Quand la matière change, CALCULER LA MOYENNE pour cette matière
 */
watch(() => props.form.matiere_id, async (newMatiereId) => {
    if (!newMatiereId || !props.form.apprenant_id || isReadOnly) return;

    // Réinitialiser les champs
    props.form.moyenne = null;
    props.form.appreciation = '';
    props.form.rang = null;
    errorMessage.value = null;

    // 1️⃣ Auto-remplir le Coefficient depuis la matière
    const matiere = props.matieres.find(m => m.id === parseInt(newMatiereId));
    if (matiere) {
        props.form.coefficient = matiere.coefficient;
    }

    // 2️⃣ Vérifier si cette moyenne existe déjà
    await checkIfAlreadyExists();
    if (alreadyExists.value) return;

    // 3️⃣ Appeler l'API pour CALCULER la moyenne de cette matière
    isLoading.value = true;
    try {
        const url = `/academique/apprenants/api/calculate-moyennes?apprenant_id=${props.form.apprenant_id}&matiere_id=${newMatiereId}`;
        const response = await fetch(url);

        let result;
        try {
            result = await response.json();
        } catch (e) {
            throw new Error(`Erreur de réponse serveur (HTTP ${response.status}): ${response.statusText}`);
        }

        if (result.success && result.data?.moyenne !== null) {
            const moyenneSimple = result.data.moyenne;
            const coefficient = matiere?.coefficient || 1;

            // Stocker SEULEMENT la moyenne simple (sans coefficient)
            // Le coefficient ne s'applique que dans le Bulletin (moyenne générale pondérée)
            props.form.moyenne = moyenneSimple;
            props.form.appreciation = result.data.appreciation;
            apprenantNotes.value = result.data.notes || [];

            // Auto-calculer le rang si disponible
            if (result.data.rang) {
                props.form.rang = result.data.rang;
            }
        } else {
            props.form.moyenne = null;
            props.form.appreciation = '';
            errorMessage.value = result.message || `Pas de notes validées pour ${matiere?.libelle || 'cette matière'}`;
        }
    } catch (error) {
        console.error('Calculation Error:', error);
        errorMessage.value = '❌ ' + (error.message || 'Erreur lors du calcul');
        props.form.moyenne = null;
        props.form.appreciation = '';
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Apprenant (remplace Bulletin) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.apprenant') || 'Apprenant' }} <span class="text-danger">*</span></label>
                <select v-model="form.apprenant_id" class="form-control" :disabled="isReadOnly">
                    <option value="">{{ t('actions.select') || '-- Sélectionner --' }}</option>
                    <option v-for="apprenant in apprenants" :key="apprenant.id" :value="apprenant.id">
                        {{ apprenant.libelle }}
                    </option>
                </select>
                <span v-if="form.errors?.apprenant_id" class="text-danger">
                    <strong>{{ form.errors.apprenant_id }}</strong>
                </span>
                <small v-if="isLoading" class="text-info d-block mt-2">
                    <i class="fa fa-spinner fa-spin"></i> Calcul en cours...
                </small>
                <small v-if="errorMessage" class="text-danger d-block mt-2">
                    {{ errorMessage }}
                </small>
            </div>
        </div>

        <!-- Matière -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('common.matiere') || 'Matière' }} <span class="text-danger">*</span></label>
                <select v-model="form.matiere_id" class="form-control" :disabled="isReadOnly || !form.apprenant_id">
                    <option value="">{{ t('actions.select') || '-- Sélectionner --' }}</option>
                    <option v-for="matiere in matieres" :key="matiere.id" :value="matiere.id">
                        {{ matiere.libelle }}
                    </option>
                </select>

                <!-- Messages d'aide -->
                <small v-if="!form.apprenant_id" class="text-warning d-block mt-2">
                    <i class="fa fa-exclamation-circle"></i> Sélectionnez d'abord un apprenant
                </small>
                <small v-else-if="alreadyExists" class="text-danger d-block mt-2">
                    <i class="fa fa-check-circle"></i> <strong>Cette moyenne existe déjà!</strong> Vous pouvez l'éditer
                </small>
                <small v-else-if="isLoading && form.matiere_id" class="text-info d-block mt-2">
                    <i class="fa fa-spinner fa-spin"></i> Calcul de la moyenne en cours...
                </small>
                <small v-else-if="errorMessage" class="text-danger d-block mt-2">
                    <i class="fa fa-exclamation-triangle"></i> {{ errorMessage }}
                </small>

                <span v-if="form.errors?.matiere_id" class="text-danger">
                    <strong>{{ form.errors.matiere_id }}</strong>
                </span>
            </div>
        </div>

        <!-- Moyenne (readonly) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.moyenne') || 'Moyenne' }} <span class="text-danger">*</span></label>
                <input
                    type="number"
                    v-model.number="form.moyenne"
                    class="form-control"
                    placeholder="Calculée automatiquement"
                    readonly
                    min="0"
                    max="20"
                    step="0.01">
                <small class="text-muted d-block mt-1">
                    🔒 Lecture-seule | Calculée depuis les notes validées
                </small>
                <span v-if="form.errors?.moyenne" class="text-danger">
                    <strong>{{ form.errors.moyenne }}</strong>
                </span>
            </div>
        </div>

        <!-- Coefficient (readonly) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.coefficient') || 'Coefficient' }} <span class="text-danger">*</span></label>
                <input
                    type="number"
                    v-model.number="form.coefficient"
                    class="form-control"
                    placeholder="Auto-rempli depuis la matière"
                    readonly
                    min="0"
                    step="0.01">
                <small class="text-muted d-block mt-1">
                    🔒 Lecture-seule | Auto-rempli depuis la matière
                </small>
                <span v-if="form.errors?.coefficient" class="text-danger">
                    <strong>{{ form.errors.coefficient }}</strong>
                </span>
            </div>
        </div>

        <!-- Rang (optionnel) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.rang') || 'Rang' }}</label>
                <input type="number" v-model.number="form.rang" class="form-control" :placeholder="t('fields.rang')" :disabled="isReadOnly" min="1">
                <span v-if="form.errors?.rang" class="text-danger">
                    <strong>{{ form.errors.rang }}</strong>
                </span>
            </div>
        </div>

        <!-- Appréciation (readonly) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.appreciation') || 'Appréciation' }} <span class="text-danger">*</span></label>
                <input
                    type="text"
                    v-model="form.appreciation"
                    class="form-control"
                    placeholder="Calculée automatiquement"
                    readonly>
                <small class="text-muted d-block mt-1">
                    🔒 Lecture-seule | Calculée depuis la moyenne
                </small>
                <span v-if="form.errors?.appreciation" class="text-danger">
                    <strong>{{ form.errors.appreciation }}</strong>
                </span>
            </div>
        </div>

        <!-- Panel: Affichage des notes source -->
        <div v-if="calculatedData && form.matiere_id" class="col-12">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">📋 Notes source et calcul</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>✅ Notes VALIDÉES de la matière sélectionnée:</strong>
                            <div class="alert alert-light mt-2" v-if="apprenantNotes.length">
                                <ul class="list-unstyled">
                                    <li v-for="note in apprenantNotes" :key="note.id" class="mb-2">
                                        <span class="badge bg-info">{{ note.evaluation }}</span>
                                        <strong class="ms-2">{{ note.note }}/20</strong>
                                        <small class="text-muted">(Statut: validee ✓)</small>
                                    </li>
                                </ul>
                                <hr>
                                <strong>Total notes:</strong> <span class="badge bg-success">{{ apprenantNotes.length }}</span>
                            </div>
                            <div v-else class="alert alert-danger">
                                <i class="fa fa-exclamation-circle"></i> ❌ Aucune note VALIDÉE trouvée
                            </div>
                        </div>
                        <div class="col-md-6">
                            <strong>Calcul de la moyenne:</strong>
                            <div class="alert alert-light mt-2">
                                <small>
                                    <strong>Formule:</strong><br>
                                    Moyenne = SUM(notes) / COUNT(notes)<br><br>
                                    <strong>Résultat:</strong><br>
                                    {{ form.moyenne }} / 20<br><br>
                                    <strong>Coefficient matière:</strong><br>
                                    {{ form.coefficient }}<br><br>
                                    <strong>Appréciation:</strong><br>
                                    <span class="badge" :class="{
                                        'bg-success': form.appreciation === 'excellent',
                                        'bg-primary': form.appreciation === 'bien',
                                        'bg-info': form.appreciation === 'assez',
                                        'bg-warning': form.appreciation === 'moyen',
                                        'bg-danger': form.appreciation === 'faible'
                                    }">
                                        {{ appreciationOptions.find(a => a.id === form.appreciation)?.libelle || 'N/A' }}
                                    </span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau des Moyennes Créées pour cet Apprenant -->
        <div v-if="form.apprenant_id && createdAverages.length > 0" class="col-12">
            <div class="card shadow-lg" style="border-left: 5px solid #0FBCAF;">
                <div class="card-header" style="background: linear-gradient(135deg, #0FBCAF 0%, #00d4cc 100%); color: white; padding: 1rem;">
                    <div style="display: flex; align-items: center; gap: 1rem; justify-content: space-between;">
                        <div>
                            <h6 class="mb-0" style="font-size: 1.1rem; font-weight: 600;">
                                <i class="fa fa-list-check"></i> Moyennes Déjà Créées pour cet Apprenant
                            </h6>
                            <small style="opacity: 0.9;">{{ createdAverages.length }} matière(s) complétée(s)</small>
                        </div>
                        <span class="badge bg-white text-info" style="font-size: 1rem; padding: 0.5rem 1rem; font-weight: 600;">{{ createdAverages.length }}/{{ matieres.length || '?' }}</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size: 0.95rem;">
                        <thead style="background: #f8f9fa; border-bottom: 2px solid #0FBCAF;">
                            <tr>
                                <th style="color: #0B5697; font-weight: 600;">📚 Matière</th>
                                <th style="color: #0B5697; font-weight: 600; text-align: center;">Coef.</th>
                                <th style="color: #0B5697; font-weight: 600; text-align: center;">Moyenne</th>
                                <th style="color: #0B5697; font-weight: 600; text-align: center;">Appréciation</th>
                                <th v-if="createdAverages.some(a => a.rang)" style="color: #0B5697; font-weight: 600; text-align: center;">🏆 Rang</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="avg in createdAverages" :key="avg.id" style="border-bottom: 1px solid #e9ecef;">
                                <td style="vertical-align: middle; font-weight: 500; color: #0B5697;">
                                    {{ avg.matiere_libelle }}
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <span class="badge" style="background: #E5590C; color: white;">{{ avg.coefficient }}</span>
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <div style="font-weight: 600; color: #0B5697; font-size: 1.1rem;">
                                        {{ avg.moyenne?.toFixed(2) || 'N/A' }}
                                    </div>
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <span :class="['badge', {
                                        'bg-success': avg.appreciation === 'excellent',
                                        'bg-primary': avg.appreciation === 'bien',
                                        'bg-info': avg.appreciation === 'assez',
                                        'bg-warning': avg.appreciation === 'moyen',
                                        'bg-danger': avg.appreciation === 'faible'
                                    }]">
                                        {{ avg.appreciation || '-' }}
                                    </span>
                                </td>
                                <td v-if="createdAverages.some(a => a.rang)" style="text-align: center; vertical-align: middle;">
                                    <span v-if="avg.rang" class="badge bg-warning text-dark">{{ avg.rang }}</span>
                                    <span v-else style="color: #999;">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer" style="background: #f8f9fa; border-top: 2px solid #0FBCAF;">
                    <small style="color: #0B5697;">
                        <i class="fa fa-info-circle"></i>
                        <strong>{{ matieres.length - createdAverages.length }} matière(s)</strong> restante(s) à créer
                    </small>
                </div>
            </div>
        </div>

        <!-- Message: Aucune Moyenne Créée -->
        <div v-else-if="form.apprenant_id && createdAverages.length === 0 && !isLoadingAverages" class="col-12">
            <div class="alert alert-info" style="border-left: 4px solid #0FBCAF; background: rgba(15, 188, 175, 0.1);">
                <i class="fa fa-info-circle" style="color: #0FBCAF;"></i>
                <strong style="color: #0B5697;">Aucune moyenne créée</strong> - Commencez à ajouter les moyennes des matières pour cet apprenant
            </div>
        </div>

        <!-- Résumé final PROFESSIONNEL -->
        <div v-if="form.matiere_id && form.moyenne !== null && form.coefficient" class="col-12">
            <div class="card shadow-lg" style="border-top: 5px solid #0B5697;">
                <!-- Header -->
                <div class="card-header text-white d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #0B5697 0%, #0D5F8F 100%); border-bottom: 3px solid #0FBCAF;">
                    <div>
                        <h5 class="mb-0" style="color: #0FBCAF;">✅ CALCUL FINALISÉ - PRÊT À VALIDER</h5>
                        <small style="color: rgba(255,255,255,0.9);">Phase 1: Création des moyennes</small>
                    </div>
                    <div style="font-size: 2rem; color: #E5590C;">
                        <i class="fa fa-check-circle"></i>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Matière seulement -->
                    <div class="mb-4 pb-3" style="border-bottom: 2px solid #0FBCAF;">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle p-2 text-white me-3" style="width: 45px; height: 45px; background: #0FBCAF; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                                <i class="fa fa-book"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block" style="font-weight: 600;">MATIÈRE</small>
                                <strong class="fs-6" style="color: #0B5697;">{{ matieres.find(m => m.id === parseInt(form.matiere_id))?.libelle }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Formule -->
                    <div class="mb-4 pb-3" style="border-bottom: 2px solid #E5590C;">
                        <small class="text-muted d-block mb-2"><i class="fa fa-calculator" style="color: #0B5697;"></i> <strong style="color: #0B5697;">FORMULE:</strong></small>
                        <div class="p-3 rounded" style="font-family: monospace; background: rgba(15, 188, 175, 0.1); border-left: 4px solid #0FBCAF;">
                            Moyenne = <strong style="color: #0B5697;">SUM(notes_validées)</strong> / <strong style="color: #0B5697;">COUNT(notes)</strong>
                        </div>
                    </div>

                    <!-- Notes source -->
                    <div v-if="apprenantNotes.length > 0" class="mb-4 pb-3" style="border-bottom: 2px solid #0FBCAF;">
                        <small class="text-muted d-block mb-2"><i class="fa fa-list" style="color: #0B5697;"></i> <strong style="color: #0B5697;">NOTES ({{ apprenantNotes.length }}):</strong></small>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="d-flex flex-wrap gap-2">
                                    <span v-for="(note, idx) in apprenantNotes" :key="idx" class="badge bg-primary-subtle text-primary">
                                        {{ note.note }}/20
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Total: <strong>{{ apprenantNotes.length > 0 ? apprenantNotes.reduce((sum, n) => sum + (parseFloat(n.note) || 0), 0).toFixed(1) : '0.0' }}</strong></small><br>
                                <small class="text-muted">Moyenne: <strong>{{ apprenantNotes.length > 0 ? (apprenantNotes.reduce((sum, n) => sum + (parseFloat(n.note) || 0), 0) / apprenantNotes.length).toFixed(2) : '0.00' }}</strong>/20</small>
                            </div>
                        </div>
                    </div>

                    <!-- Résultat final -->
                    <div class="row text-center">
                        <div class="col-md-6 mb-3">
                            <div class="p-4 rounded" style="background: linear-gradient(135deg, rgba(11, 86, 151, 0.1) 0%, rgba(15, 188, 175, 0.1) 100%); border: 2px solid #0B5697;">
                                <small class="text-muted d-block" style="font-weight: 600; color: #0B5697;">MOYENNE MATIÈRE</small>
                                <h4 class="mb-0" style="font-size: 2.5rem; color: #0B5697; font-weight: 800;">
                                    {{ form.moyenne?.toFixed ? form.moyenne.toFixed(2) : form.moyenne }}/20
                                </h4>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="p-4 rounded" style="background: linear-gradient(135deg, rgba(229, 89, 12, 0.1) 0%, rgba(15, 188, 175, 0.1) 100%); border: 2px solid #E5590C;">
                                <small class="text-muted d-block" style="font-weight: 600; color: #E5590C;">APPRÉCIATION</small>
                                <h4 class="mb-0" :class="{
                                    'text-success': form.appreciation === 'excellent',
                                    'text-primary': form.appreciation === 'bien',
                                    'text-info': form.appreciation === 'assez',
                                    'text-warning': form.appreciation === 'moyen',
                                    'text-danger': form.appreciation === 'faible'
                                }" style="font-size: 2rem; font-weight: 800;">
                                    {{ appreciationOptions.find(a => a.id === form.appreciation)?.libelle }}
                                </h4>
                            </div>
                        </div>
                    </div>

                    <!-- Info système -->
                    <div class="mt-3 pt-3" style="border-top: 2px solid #0FBCAF; padding-top: 1rem;">
                        <small class="text-muted d-block" style="color: #0B5697;">
                            <i class="fa fa-shield" style="color: #0FBCAF;"></i> <strong>Calcul automatique certifié</strong><br>
                            <span style="font-size: 0.9rem;">✅ Tous les champs sont calculés - Phase 1 complète</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
