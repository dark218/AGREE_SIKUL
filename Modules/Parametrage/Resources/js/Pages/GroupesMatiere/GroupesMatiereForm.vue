<script setup>
import { computed, watch, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const { t } = useI18n();
const props = defineProps({
    form: { type: Object, required: true },
    mode: {
        type: String,
        default: 'create',
        validator: (v) => ['create', 'edit', 'show'].includes(v),
    },
    ecoles: { type: Array, default: () => [] },
    institutions: { type: Array, default: () => [] },
    niveaux: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    cycles: { type: Array, default: () => [] },
    matieres: { type: Array, default: () => [] },
});

const isReadOnly = computed(() => props.mode === 'show');
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const autoLabel = (list, id) => {
    if (!id || !list?.length) return '—';
    const found = list.find((i) => String(i.id) === String(id));
    return found?.libelle || found?.nom || '—';
};
const sectionLabel = computed(() => autoLabel(props.sections, props.form.section_id));
const cycleLabel = computed(() => autoLabel(props.cycles, props.form.cycle_id));
const niveauSelected = computed(() => !!props.form.niveau_id);

// HÉRITAGE depuis École : Institution remonte automatiquement
watch(() => props.form.ecole_id, (newEcoleId) => {
    if (!newEcoleId || isReadOnly.value) return;
    const ecole = props.ecoles.find((e) => String(e.id) === String(newEcoleId));
    if (!ecole) return;
    if (ecole.institution_id) props.form.institution_id = ecole.institution_id;
});

// Cascade Niveau → Section + Cycle (auto-fill depuis niveau_etudes)
watch(() => props.form.niveau_id, (newNiveauId) => {
    if (isReadOnly.value) return;
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

// ── Multi-select recherchable « Matières du groupe » ──────────────────────
if (!Array.isArray(props.form.matieres)) props.form.matieres = [];
const matieresOpen = ref(false);
const matieresQuery = ref('');
const filteredMatieres = computed(() => {
    const q = matieresQuery.value.trim().toLowerCase();
    if (!q) return props.matieres;
    return props.matieres.filter((m) => (m.libelle || '').toLowerCase().includes(q));
});
const isMatiereSelected = (id) => (props.form.matieres || []).map(String).includes(String(id));
const toggleMatiere = (id) => {
    if (isReadOnly.value) return;
    if (!Array.isArray(props.form.matieres)) props.form.matieres = [];
    const idx = props.form.matieres.findIndex((x) => String(x) === String(id));
    if (idx === -1) props.form.matieres.push(id);
    else props.form.matieres.splice(idx, 1);
};
const selectedMatieresLabels = computed(() =>
    (props.form.matieres || [])
        .map((id) => props.matieres.find((m) => String(m.id) === String(id))?.libelle)
        .filter(Boolean)
);
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- LIGNE 1 : Code | Libellé -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.code') }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.code" class="form-control" :placeholder="t('fields.code')" :disabled="isReadOnly">
                <span v-if="form.errors?.code" class="text-danger"><strong>{{ form.errors.code }}</strong></span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.libelle') }} <span class="text-danger">*</span></label>
                <input type="text" v-model="form.libelle" class="form-control" :placeholder="t('fields.libelle')" :disabled="isReadOnly">
                <span v-if="form.errors?.libelle" class="text-danger"><strong>{{ form.errors.libelle }}</strong></span>
            </div>
        </div>

        <!-- LIGNE 2 : École | Institution (auto) -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.ecole') || 'École' }}</label>
                <SearchableSelect
                    v-model.number="form.ecole_id"
                    :options="ecoles"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.ecole_id" class="text-danger"><strong>{{ form.errors.ecole_id }}</strong></span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.institution') || 'Institution' }}
                    <small class="text-muted">(auto depuis école)</small>
                </label>
                <SearchableSelect
                    v-model.number="form.institution_id"
                    :options="institutions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.institution_id" class="text-danger"><strong>{{ form.errors.institution_id }}</strong></span>
            </div>
        </div>

        <!-- LIGNE 3 : Niveau | Section (auto lecture seule) -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.niveau') || 'Niveau' }} <span class="text-danger">*</span></label>
                <SearchableSelect
                    v-model.number="form.niveau_id"
                    :options="niveaux"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.niveau_id" class="text-danger"><strong>{{ form.errors.niveau_id }}</strong></span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.section') || 'Section' }}
                    <span v-if="niveauSelected" class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span>
                </label>
                <input
                    type="text"
                    class="form-control"
                    :value="sectionLabel"
                    disabled
                    style="background:#eef2f7; color:#64748b;"
                />
                <span v-if="form.errors?.section_id" class="text-danger"><strong>{{ form.errors.section_id }}</strong></span>
            </div>
        </div>

        <!-- LIGNE 4 : Cycle (auto lecture seule) | Matière 1 -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.cycle') || 'Cycle' }}
                    <span v-if="niveauSelected" class="badge bg-secondary bg-opacity-25 text-secondary ms-1" style="font-size:10px;">auto</span>
                </label>
                <input
                    type="text"
                    class="form-control"
                    :value="cycleLabel"
                    disabled
                    style="background:#eef2f7; color:#64748b;"
                />
                <span v-if="form.errors?.cycle_id" class="text-danger"><strong>{{ form.errors.cycle_id }}</strong></span>
            </div>
        </div>
        <!-- §UX : Matières du groupe en LISTE DÉROULANTE multi-sélection
             recherchable (demande user). -->
        <div class="col-12">
            <div class="mb-3">
                <label class="mb-2">
                    <i class="fa fa-book me-1 text-primary"></i>
                    Matières enseignées
                </label>
                <div v-if="matieres.length === 0" class="alert alert-info">
                    Aucune matière disponible. Créez d'abord des matières unités dans Paramétrage.
                </div>
                <div v-else class="ms-multiselect" :class="{ 'is-open': matieresOpen }">
                    <!-- Zone de contrôle : tags des matières choisies -->
                    <div class="ms-control" @click="!isReadOnly && (matieresOpen = !matieresOpen)">
                        <template v-if="selectedMatieresLabels.length">
                            <span v-for="(lbl, i) in selectedMatieresLabels" :key="i" class="ms-tag">{{ lbl }}</span>
                        </template>
                        <span v-else class="ms-placeholder">Sélectionner une ou plusieurs matières…</span>
                        <i class="fa fa-chevron-down ms-caret"></i>
                    </div>
                    <!-- Panneau déroulant -->
                    <div v-if="matieresOpen" class="ms-panel">
                        <div class="ms-search">
                            <input v-model="matieresQuery" type="text" class="form-control" placeholder="Rechercher…" @keydown.stop />
                        </div>
                        <div class="ms-options">
                            <label v-for="m in filteredMatieres" :key="m.id" class="ms-option">
                                <input type="checkbox" :checked="isMatiereSelected(m.id)" @change="toggleMatiere(m.id)" :disabled="isReadOnly" />
                                <span>{{ m.libelle }}</span>
                            </label>
                            <div v-if="filteredMatieres.length === 0" class="ms-empty">Aucune matière trouvée.</div>
                        </div>
                    </div>
                    <!-- Ferme le panneau au clic extérieur -->
                    <div v-if="matieresOpen" class="ms-backdrop" @click="matieresOpen = false"></div>
                </div>
                <div v-if="form.matieres?.length" class="text-muted small mt-2">
                    <i class="fa fa-check-circle text-success"></i>
                    {{ form.matieres.length }} matière{{ form.matieres.length > 1 ? 's' : '' }} sélectionnée{{ form.matieres.length > 1 ? 's' : '' }}
                </div>
                <span v-if="form.errors?.matieres" class="text-danger d-block mt-2">
                    <strong>{{ form.errors.matieres }}</strong>
                </span>
            </div>
        </div>

        <!-- État -->
        <div class="col-md-4">
            <div class="mb-3">
                <label>{{ t('fields.etat') || 'État' }}</label>
                <SearchableSelect
                    v-model="form.etat"
                    :options="statusOptions"
                    optionValue="id"
                    optionLabel="libelle"
                    :placeholder="t('actions.select') || '-- Sélectionner --'"
                    :disabled="isReadOnly"
                />
                <span v-if="form.errors?.etat" class="text-danger"><strong>{{ form.errors.etat }}</strong></span>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ── Multi-select recherchable ────────────────────────────────────────── */
.ms-multiselect { position: relative; }
.ms-control {
    min-height: 44px;
    display: flex; flex-wrap: wrap; align-items: center; gap: 6px;
    padding: 6px 34px 6px 10px;
    background: #fff;
    border: 1.5px solid #d0d7e2;
    border-radius: 8px;
    cursor: pointer;
    position: relative;
}
.ms-multiselect.is-open .ms-control { border-color: #0b5697; box-shadow: 0 0 0 2px rgba(11,86,151,.12); }
.ms-placeholder { color: #9aa5b5; font-size: 14px; }
.ms-tag {
    background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;
    border-radius: 6px; padding: 2px 8px; font-size: 13px; font-weight: 500;
}
.ms-caret { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 12px; }
.ms-panel {
    position: absolute; z-index: 30; top: calc(100% + 4px); left: 0; right: 0;
    background: #fff; border: 1px solid #e5e7eb; border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,.12); overflow: hidden;
}
.ms-search { padding: 10px; border-bottom: 1px solid #eef2f7; }
.ms-options { max-height: 260px; overflow-y: auto; padding: 6px; }
.ms-option {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 10px; border-radius: 6px; cursor: pointer; margin: 0; font-weight: 500;
}
.ms-option:hover { background: #f0f7ff; }
.ms-option input { width: 17px; height: 17px; cursor: pointer; }
.ms-empty { padding: 12px; color: #94a3b8; text-align: center; font-size: 14px; }
.ms-backdrop { position: fixed; inset: 0; z-index: 20; }
</style>
