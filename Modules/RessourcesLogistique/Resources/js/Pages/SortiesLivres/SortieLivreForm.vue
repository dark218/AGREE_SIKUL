<!--
  SortieLivreForm.vue — Bibliothèque › Sortie de livres.
  Anti-redondance : on choisit un OUVRAGE du catalogue → infos livre en auto.
-->
<template>
    <form @submit.prevent="submit" class="rl-form">
        <div class="row g-3">
            <div class="col-md-6">
                <label>Bibliothèque</label>
                <SearchableSelect v-model="form.bibliotheque_structure_id" :options="structures" option-value="id" option-label="libelle" placeholder="-- Sélectionner --" :disabled="isReadOnly" />
            </div>
            <div class="col-md-6">
                <label>Livre (ouvrage) <span class="text-danger">*</span></label>
                <SearchableSelect v-model="form.ouvrage_id" :options="ouvrages" option-value="id" option-label="libelle" placeholder="-- Choisir un ouvrage --" :disabled="isReadOnly" />
                <small class="text-muted">Les infos du livre se remplissent automatiquement.</small>
            </div>

            <div class="col-12">
                <div class="auto-block">
                    <div class="auto-title"><i class="fa fa-book"></i> Informations du livre <span class="badge bg-secondary">auto</span></div>
                    <div class="row g-2">
                        <div class="col-md-4"><span class="lbl">Titre</span><span class="val">{{ selectedOuvrage.titre || '—' }}</span></div>
                        <div class="col-md-4"><span class="lbl">Sujet / Matière</span><span class="val">{{ selectedOuvrage.sujet || '—' }}</span></div>
                        <div class="col-md-4"><span class="lbl">Langue</span><span class="val">{{ selectedOuvrage.langue || '—' }}</span></div>
                        <div class="col-md-4"><span class="lbl">Auteur(s)</span><span class="val">{{ selectedOuvrage.auteur || '—' }}</span></div>
                        <div class="col-md-4"><span class="lbl">Éditeur(s)</span><span class="val">{{ selectedOuvrage.editeur || '—' }}</span></div>
                        <div class="col-md-4"><span class="lbl">Année d'édition</span><span class="val">{{ selectedOuvrage.annee_publication || '—' }}</span></div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <label>Type de sortie <span class="text-danger">*</span></label>
                <SearchableSelect v-model="form.type_sortie" :options="typeOptions" option-value="id" option-label="libelle" :disabled="isReadOnly" />
            </div>
            <div class="col-md-4">
                <label>Date de sortie</label>
                <input v-model="form.date_sortie" type="date" class="form-control" :disabled="isReadOnly" />
            </div>
            <div class="col-md-4">
                <label>Quantité <span class="text-danger">*</span></label>
                <input v-model.number="form.quantite" type="number" min="1" class="form-control" :disabled="isReadOnly" />
            </div>

            <div class="col-md-4">
                <label>Date de retour</label>
                <input v-model="form.date_retour" type="date" class="form-control" :disabled="isReadOnly" />
            </div>
            <div class="col-md-4">
                <label>{{ tiersLabel }}</label>
                <input v-model="form.tiers" type="text" class="form-control" maxlength="255" :disabled="isReadOnly" />
            </div>
            <div class="col-md-4">
                <label>État physique</label>
                <input v-model="form.etat_physique" type="text" class="form-control" maxlength="100" :disabled="isReadOnly" placeholder="Neuf, bon, usé…" />
            </div>
        </div>

        <div v-if="!isReadOnly" class="form-actions mt-4">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> {{ submitButtonLabel }}</button>
            <Link :href="route('sorties-livres.index')" class="btn btn-outline-secondary ms-2">Annuler</Link>
        </div>
    </form>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const props = defineProps({
    sortie:            { type: Object,  default: () => ({}) },
    structures:        { type: Array,   default: () => [] },
    ouvrages:          { type: Array,   default: () => [] },
    isReadOnly:        { type: Boolean, default: false },
    submitButtonLabel: { type: String,  default: 'Enregistrer' },
});
const emit = defineEmits(['submit']);

const typeOptions = [
    { id: 'pret',  libelle: 'Prêt' },
    { id: 'vente', libelle: 'Vente' },
    { id: 'don',   libelle: 'Don' },
];

const form = ref({
    bibliotheque_structure_id: props.sortie?.bibliotheque_structure_id || '',
    ouvrage_id:                props.sortie?.ouvrage_id || '',
    type_sortie:               props.sortie?.type_sortie || 'pret',
    date_sortie:               props.sortie?.date_sortie || '',
    quantite:                  props.sortie?.quantite || 1,
    date_retour:               props.sortie?.date_retour || '',
    tiers:                     props.sortie?.tiers || '',
    etat_physique:             props.sortie?.etat_physique || '',
    etat:                      props.sortie?.etat || 'actif',
});

const selectedOuvrage = computed(() =>
    props.ouvrages.find(o => String(o.id) === String(form.value.ouvrage_id)) || {}
);

const tiersLabel = computed(() => ({
    pret: 'Emprunteur',
    vente: 'Acheteur',
    don: 'Donateur',
}[form.value.type_sortie] || 'Emprunteur / Acheteur / Donateur'));

function submit() { emit('submit', form.value); }
</script>

<style scoped>
.rl-form { background:#fff; padding:20px; border-radius:6px; box-shadow:0 2px 4px rgba(0,0,0,.08); }
label { font-weight:500; color:#374151; font-size:.9rem; margin-bottom:.4rem; display:block; }
.form-actions { display:flex; gap:10px; padding-top:20px; border-top:1px solid #dee2e6; }
.auto-block { background:#f8fafc; border:1px solid #e9eef5; border-radius:10px; padding:14px 16px; }
.auto-title { font-weight:600; color:#0B5697; margin-bottom:10px; display:flex; align-items:center; gap:8px; }
.auto-block .lbl { display:block; font-size:.72rem; text-transform:uppercase; letter-spacing:.4px; color:#94a3b8; }
.auto-block .val { display:block; font-weight:600; color:#1e293b; }
</style>
