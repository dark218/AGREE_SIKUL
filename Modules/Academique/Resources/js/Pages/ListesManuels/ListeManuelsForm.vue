<script setup>
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    form: { type: Object, required: true },
    anneesScolaires: { type: Array, default: () => [] },
    ecoles: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    niveaux: { type: Array, default: () => [] },
    cycles: { type: Array, default: () => [] },
    pays: { type: Array, default: () => [] },
    mode: {
        type: String,
        default: 'create',
        validator: (v) => ['create', 'edit', 'show'].includes(v),
    },
});

const isReadOnly = computed(() => props.mode === 'show');

const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const opt = (list, labelKeys = ['libelle', 'nom']) =>
    (list || []).map(item => ({
        id: item.id,
        libelle: labelKeys.map(k => item[k]).find(Boolean) || '',
    }));

const anneesOptions = computed(() => opt(props.anneesScolaires));
const ecolesOptions = computed(() => opt(props.ecoles, ['libelle', 'nom']));
// Section = ancre pédagogique : filtrée par l'école si celle-ci est choisie.
const filteredSections = computed(() => {
    if (!props.form.ecole_id) return props.sections;
    return props.sections.filter(s => String(s.ecole_id) === String(props.form.ecole_id));
});
const sectionsOptions = computed(() => opt(filteredSections.value, ['libelle', 'nom']));
const niveauxOptions = computed(() => opt(props.niveaux, ['libelle', 'nom']));
const cyclesOptions = computed(() => opt(props.cycles));
const paysOptions = computed(() => opt(props.pays));

// Libellés du bloc « contexte remonté automatiquement ».
const findLabel = (list, id, keys = ['libelle', 'nom']) => {
    const f = (list || []).find(x => String(x.id) === String(id));
    return f ? (keys.map(k => f[k]).find(Boolean) || '—') : '—';
};
const niveauLabel = computed(() => findLabel(props.niveaux, props.form.niveau_id));
const cycleLabel = computed(() => findLabel(props.cycles, props.form.cycle_id));
const paysLabel = computed(() => findLabel(props.pays, props.form.pays_id));

// Cascade : choisir une SECTION remonte École / Niveau, puis Cycle + Pays via le niveau.
watch(() => props.form.section_id, (id) => {
    const s = props.sections.find(x => String(x.id) === String(id));
    if (!s) return;
    if (!props.form.ecole_id && s.ecole_id) props.form.ecole_id = s.ecole_id;
    if (s.niveau_etude_id) props.form.niveau_id = s.niveau_etude_id;
});

// Choisir un NIVEAU remonte automatiquement son Cycle et son Pays.
watch(() => props.form.niveau_id, (id) => {
    const n = props.niveaux.find(x => String(x.id) === String(id));
    if (!n) return;
    if (n.cycle_id) props.form.cycle_id = n.cycle_id;
    if (n.pays_id) props.form.pays_id = n.pays_id;
});

// Si on change d'école, on invalide la section qui n'appartient plus à l'école.
watch(() => props.form.ecole_id, (id) => {
    if (!id || !props.form.section_id) return;
    const s = props.sections.find(x => String(x.id) === String(props.form.section_id));
    if (s && String(s.ecole_id) !== String(id)) props.form.section_id = null;
});

// ===== Blocs répétables =====
const ensureArray = (key) => {
    if (!Array.isArray(props.form[key])) props.form[key] = [];
    return props.form[key];
};

const addLivre = () => ensureArray('livres').push({ titre: '', sujet: '', langue: '', auteurs: '', editeurs: '', annee_edition: '' });
const removeLivre = (i) => props.form.livres.splice(i, 1);

const addCahier = () => ensureArray('cahiers').push({ utilite: '', type_cahier: '', nombre_pages: '', quantite: 1 });
const removeCahier = (i) => props.form.cahiers.splice(i, 1);

const addFourniture = () => ensureArray('fournitures').push({ utilite: '', designation: '', quantite: 1, fournisseur: '' });
const removeFourniture = (i) => props.form.fournitures.splice(i, 1);
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- ===== Bloc Informations de base ===== -->
        <div class="col-12">
            <h6 class="section-title">{{ t('common.basic_information') || 'Informations de base' }}</h6>
        </div>

        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.academic_year') || 'Année scolaire' }}</label>
                <SearchableSelect v-model="form.annee_scolaire_id" :options="anneesOptions" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                <span v-if="form.errors?.annee_scolaire_id" class="text-danger"><strong>{{ form.errors.annee_scolaire_id }}</strong></span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.school') || 'École' }}</label>
                <SearchableSelect v-model="form.ecole_id" :options="ecolesOptions" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Toutes les écoles --'" :disabled="isReadOnly" />
                <small class="text-muted">Filtre les sections (facultatif).</small>
                <span v-if="form.errors?.ecole_id" class="text-danger"><strong>{{ form.errors.ecole_id }}</strong></span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.section') || 'Section' }}</label>
                <SearchableSelect v-model="form.section_id" :options="sectionsOptions" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                <small class="text-muted">Remonte l'école, le niveau, le cycle et le pays.</small>
                <span v-if="form.errors?.section_id" class="text-danger"><strong>{{ form.errors.section_id }}</strong></span>
            </div>
        </div>

        <!-- Contexte remonté automatiquement depuis la section / le niveau -->
        <div class="col-12">
            <div class="auto-block">
                <div class="auto-title"><i class="fa fa-sitemap"></i> Contexte pédagogique <span class="badge bg-secondary">auto</span></div>
                <div class="row g-3">
                    <div class="col-sm-4">
                        <label>{{ t('fields.level') || 'Niveau' }}</label>
                        <SearchableSelect v-model="form.niveau_id" :options="niveauxOptions" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                        <small class="text-muted">{{ niveauLabel }}</small>
                        <span v-if="form.errors?.niveau_id" class="text-danger d-block"><strong>{{ form.errors.niveau_id }}</strong></span>
                    </div>
                    <div class="col-sm-4">
                        <label>{{ t('fields.cycle') || 'Cycle' }}</label>
                        <SearchableSelect v-model="form.cycle_id" :options="cyclesOptions" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                        <small class="text-muted">{{ cycleLabel }}</small>
                        <span v-if="form.errors?.cycle_id" class="text-danger d-block"><strong>{{ form.errors.cycle_id }}</strong></span>
                    </div>
                    <div class="col-sm-4">
                        <label>{{ t('fields.country') || 'Pays' }}</label>
                        <SearchableSelect v-model="form.pays_id" :options="paysOptions" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                        <small class="text-muted">{{ paysLabel }}</small>
                        <span v-if="form.errors?.pays_id" class="text-danger d-block"><strong>{{ form.errors.pays_id }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== Bloc livres ===== -->
        <div class="col-12 d-flex justify-content-between align-items-center mt-2">
            <h6 class="section-title mb-0">{{ t('fields.books') || 'Livres' }}</h6>
            <button v-if="!isReadOnly" type="button" class="btn btn-sm btn-primary" @click="addLivre"><i class="fa fa-plus"></i> {{ t('actions.add_line') || 'Ajouter une ligne' }}</button>
        </div>
        <div class="col-12">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width:160px;">{{ t('fields.book_title') || 'Titre du livre' }}</th>
                            <th style="min-width:140px;">{{ t('fields.subject') || 'Sujet/Matière' }}</th>
                            <th style="min-width:110px;">{{ t('fields.language') || 'Langue' }}</th>
                            <th style="min-width:150px;">{{ t('fields.authors') || 'Auteur(s)' }}</th>
                            <th style="min-width:150px;">{{ t('fields.publishers') || 'Editeur(s)' }}</th>
                            <th style="min-width:110px;">{{ t('fields.edition_year') || 'Année édition' }}</th>
                            <th v-if="!isReadOnly" style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(l, i) in (form.livres || [])" :key="'liv-' + i">
                            <td><input v-model="l.titre" type="text" class="form-control" :disabled="isReadOnly" /></td>
                            <td><input v-model="l.sujet" type="text" class="form-control" :disabled="isReadOnly" /></td>
                            <td><input v-model="l.langue" type="text" class="form-control" :disabled="isReadOnly" /></td>
                            <td><input v-model="l.auteurs" type="text" class="form-control" :disabled="isReadOnly" /></td>
                            <td><input v-model="l.editeurs" type="text" class="form-control" :disabled="isReadOnly" /></td>
                            <td><input v-model="l.annee_edition" type="number" class="form-control" :disabled="isReadOnly" /></td>
                            <td v-if="!isReadOnly" class="text-center"><button type="button" class="btn btn-sm btn-danger" @click="removeLivre(i)"><i class="fa fa-trash"></i></button></td>
                        </tr>
                        <tr v-if="!(form.livres && form.livres.length)"><td :colspan="isReadOnly ? 6 : 7" class="text-center text-muted py-3">{{ t('common.no_data') || 'Aucune ligne' }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== Bloc cahiers ===== -->
        <div class="col-12 d-flex justify-content-between align-items-center mt-2">
            <h6 class="section-title mb-0">{{ t('fields.notebooks') || 'Cahiers' }}</h6>
            <button v-if="!isReadOnly" type="button" class="btn btn-sm btn-primary" @click="addCahier"><i class="fa fa-plus"></i> {{ t('actions.add_line') || 'Ajouter une ligne' }}</button>
        </div>
        <div class="col-12">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width:180px;">{{ t('fields.usage_activity') || 'Utilité/Activité' }}</th>
                            <th style="min-width:150px;">{{ t('fields.notebook_type') || 'Type de cahier' }}</th>
                            <th style="min-width:130px;">{{ t('fields.page_count') || 'Nombre de pages' }}</th>
                            <th style="min-width:110px;">{{ t('fields.quantity') || 'Quantité' }}</th>
                            <th v-if="!isReadOnly" style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(c, i) in (form.cahiers || [])" :key="'cah-' + i">
                            <td><input v-model="c.utilite" type="text" class="form-control" :disabled="isReadOnly" /></td>
                            <td><input v-model="c.type_cahier" type="text" class="form-control" :disabled="isReadOnly" /></td>
                            <td><input v-model="c.nombre_pages" type="number" min="0" class="form-control" :disabled="isReadOnly" /></td>
                            <td><input v-model="c.quantite" type="number" min="0" class="form-control" :disabled="isReadOnly" /></td>
                            <td v-if="!isReadOnly" class="text-center"><button type="button" class="btn btn-sm btn-danger" @click="removeCahier(i)"><i class="fa fa-trash"></i></button></td>
                        </tr>
                        <tr v-if="!(form.cahiers && form.cahiers.length)"><td :colspan="isReadOnly ? 4 : 5" class="text-center text-muted py-3">{{ t('common.no_data') || 'Aucune ligne' }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== Bloc autres fournitures ===== -->
        <div class="col-12 d-flex justify-content-between align-items-center mt-2">
            <h6 class="section-title mb-0">{{ t('fields.other_supplies') || 'Autres fournitures' }}</h6>
            <button v-if="!isReadOnly" type="button" class="btn btn-sm btn-primary" @click="addFourniture"><i class="fa fa-plus"></i> {{ t('actions.add_line') || 'Ajouter une ligne' }}</button>
        </div>
        <div class="col-12">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width:180px;">{{ t('fields.usage_activity') || 'Utilité/Activité' }}</th>
                            <th style="min-width:160px;">{{ t('fields.designation') || 'Désignation' }}</th>
                            <th style="min-width:110px;">{{ t('fields.quantity') || 'Quantité' }}</th>
                            <th style="min-width:150px;">{{ t('fields.supplier') || 'Fournisseur' }}</th>
                            <th v-if="!isReadOnly" style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(f, i) in (form.fournitures || [])" :key="'four-' + i">
                            <td><input v-model="f.utilite" type="text" class="form-control" :disabled="isReadOnly" /></td>
                            <td><input v-model="f.designation" type="text" class="form-control" :disabled="isReadOnly" /></td>
                            <td><input v-model="f.quantite" type="number" min="0" class="form-control" :disabled="isReadOnly" /></td>
                            <td><input v-model="f.fournisseur" type="text" class="form-control" :disabled="isReadOnly" /></td>
                            <td v-if="!isReadOnly" class="text-center"><button type="button" class="btn btn-sm btn-danger" @click="removeFourniture(i)"><i class="fa fa-trash"></i></button></td>
                        </tr>
                        <tr v-if="!(form.fournitures && form.fournitures.length)"><td :colspan="isReadOnly ? 4 : 5" class="text-center text-muted py-3">{{ t('common.no_data') || 'Aucune ligne' }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== État ===== -->
        <div class="col-12"><h6 class="section-title">{{ t('common.settings') || 'Paramètres' }}</h6></div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.status') || 'Statut' }}</label>
                <SearchableSelect v-model="form.etat" :options="statusOptions" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                <span v-if="form.errors?.etat" class="text-danger"><strong>{{ form.errors.etat }}</strong></span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.section-title {
    font-weight: 600;
    color: #333;
    margin-top: 15px;
    margin-bottom: 10px;
    padding-bottom: 5px;
    border-bottom: 2px solid #f0f0f0;
}
.auto-block { background:#f8fafc; border:1px solid #e9eef5; border-radius:10px; padding:14px 16px; }
.auto-title { font-weight:600; color:#0B5697; margin-bottom:10px; display:flex; align-items:center; gap:8px; }
</style>
