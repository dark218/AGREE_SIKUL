<script setup>
import { ref, computed, watch } from 'vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const props = defineProps({
    form: { type: Object, required: true },
    apprenants: { type: Array, default: () => [] },
    classes: { type: Array, default: () => [] },
    matieres: { type: Array, default: () => [] },
    mode: { type: String, default: 'create', validator: (v) => ['create', 'edit', 'show'].includes(v) },
});
const isReadOnly = props.mode === 'show';

const statutOptions = [
    { id: 'en_attente', libelle: 'En attente' },
    { id: 'validee', libelle: 'Validée' },
    { id: 'rejetee', libelle: 'Rejetée' },
];
const etatOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

// Options apprenants avec libellé « NOM Prénoms (matricule) ».
const apprenantOptions = computed(() =>
    props.apprenants.map((a) => ({
        id: a.id,
        libelle: `${a.nom || ''} ${a.prenoms || ''}${a.matricule ? ' (' + a.matricule + ')' : ''}`.trim(),
        classe_id: a.classe_id,
    }))
);

// Apprenant = ancre : sa classe remonte automatiquement.
watch(() => props.form.apprenant_id, (id) => {
    if (isReadOnly) return;
    const a = props.apprenants.find((x) => String(x.id) === String(id));
    if (a && a.classe_id) props.form.classe_id = a.classe_id;
});

// Durée (heures) calculée à partir des deux dates.
const calculateHeures = () => {
    if (props.form.date_debut && props.form.date_fin) {
        const d = new Date(props.form.date_debut);
        const f = new Date(props.form.date_fin);
        const h = (f - d) / 3600000;
        props.form.nombre_heures = Math.max(0, parseFloat(h.toFixed(2)));
    }
};
watch(() => [props.form.date_debut, props.form.date_fin], calculateHeures);

const uploadedFiles = ref([]);
const handleFileChange = (e) => {
    const files = e.target.files;
    if (!files || !files.length) return;
    uploadedFiles.value = [...uploadedFiles.value, ...Array.from(files)];
    props.form.justificatif_path = uploadedFiles.value;
};
const clearFiles = () => {
    uploadedFiles.value = [];
    props.form.justificatif_path = null;
};
</script>

<template>
    <div class="row g-3 custom-input">
        <!-- Apprenant (ancre) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Apprenant <span class="text-danger">*</span></label>
                <SearchableSelect v-model="form.apprenant_id" :options="apprenantOptions" optionValue="id" optionLabel="libelle" placeholder="-- Sélectionner --" :disabled="isReadOnly" />
                <small class="text-muted">Sa classe se remplit automatiquement.</small>
                <span v-if="form.errors?.apprenant_id" class="text-danger"><strong>{{ form.errors.apprenant_id }}</strong></span>
            </div>
        </div>
        <!-- Classe (auto, modifiable) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Classe</label>
                <SearchableSelect v-model="form.classe_id" :options="classes" optionValue="id" optionLabel="nom" placeholder="-- Sélectionner --" :disabled="isReadOnly" />
                <span v-if="form.errors?.classe_id" class="text-danger"><strong>{{ form.errors.classe_id }}</strong></span>
            </div>
        </div>
        <!-- Matière (optionnel) -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Matière</label>
                <SearchableSelect v-model="form.matiere_id" :options="matieres" optionValue="id" optionLabel="nom" placeholder="-- Sélectionner --" :disabled="isReadOnly" />
                <span v-if="form.errors?.matiere_id" class="text-danger"><strong>{{ form.errors.matiere_id }}</strong></span>
            </div>
        </div>
        <!-- Statut -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Statut <span class="text-danger">*</span></label>
                <SearchableSelect v-model="form.statut" :options="statutOptions" optionValue="id" optionLabel="libelle" placeholder="-- Sélectionner --" :disabled="isReadOnly" />
                <span v-if="form.errors?.statut" class="text-danger"><strong>{{ form.errors.statut }}</strong></span>
            </div>
        </div>
        <!-- Date début -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Date et heure de début <span class="text-danger">*</span></label>
                <input v-model="form.date_debut" type="datetime-local" class="form-control" :disabled="isReadOnly" @change="calculateHeures" />
                <span v-if="form.errors?.date_debut" class="text-danger"><strong>{{ form.errors.date_debut }}</strong></span>
            </div>
        </div>
        <!-- Date fin -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Date et heure de fin <span class="text-danger">*</span></label>
                <input v-model="form.date_fin" type="datetime-local" class="form-control" :disabled="isReadOnly" @change="calculateHeures" />
                <span v-if="form.errors?.date_fin" class="text-danger"><strong>{{ form.errors.date_fin }}</strong></span>
            </div>
        </div>
        <!-- Durée -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Durée (en heures)</label>
                <input v-model.number="form.nombre_heures" type="number" step="0.01" min="0" class="form-control" disabled placeholder="Calculé automatiquement" />
                <small class="text-muted">Calculé automatiquement</small>
            </div>
        </div>
        <!-- Motif -->
        <div class="col-sm-6">
            <div class="mb-3">
                <label>Motif</label>
                <textarea v-model="form.motif" class="form-control" rows="2" :disabled="isReadOnly"></textarea>
                <span v-if="form.errors?.motif" class="text-danger"><strong>{{ form.errors.motif }}</strong></span>
            </div>
        </div>
        <!-- Justificatif -->
        <div class="col-sm-8">
            <div class="mb-3">
                <label>Justificatif (Documents/Images)</label>
                <input type="file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif" multiple :disabled="isReadOnly" @change="handleFileChange" />
                <div v-if="uploadedFiles.length" class="mt-2">
                    <button type="button" @click="clearFiles" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i> Effacer</button>
                    <small class="text-muted ms-2">{{ uploadedFiles.length }} fichier(s)</small>
                </div>
                <div v-if="mode !== 'create' && Array.isArray(form.justificatif_path) && form.justificatif_path.length" class="mt-2">
                    <a v-for="(p, i) in form.justificatif_path.filter(f => typeof f === 'string')" :key="i" :href="`/storage/${p}`" target="_blank" class="btn btn-sm btn-outline-success me-1">
                        <i class="fa fa-download"></i> {{ p.split('/').pop() }}
                    </a>
                </div>
            </div>
        </div>
        <!-- État -->
        <div class="col-sm-4">
            <div class="mb-3">
                <label>État</label>
                <SearchableSelect v-model="form.etat" :options="etatOptions" optionValue="id" optionLabel="libelle" placeholder="-- Sélectionner --" :disabled="isReadOnly" />
            </div>
        </div>
    </div>
</template>
