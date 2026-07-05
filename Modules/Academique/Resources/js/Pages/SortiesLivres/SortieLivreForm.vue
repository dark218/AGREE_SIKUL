<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    form: { type: Object, required: true },
    livres: { type: Array, default: () => [] },
    structures: { type: Array, default: () => [] },
    mode: { type: String, default: 'create', validator: (v) => ['create', 'edit', 'show'].includes(v) },
});

const isReadOnly = computed(() => props.mode === 'show');

const livresOptions = computed(() => props.livres.map(b => ({ id: b.id, libelle: b.titre_manuel })));
const structuresOptions = computed(() => props.structures.map(s => ({ id: s.id, libelle: s.libelle })));

const typeOptions = [
    { id: 'pret', libelle: 'Prêt' },
    { id: 'vente', libelle: 'Vente' },
    { id: 'don', libelle: 'Don' },
];
const statusOptions = [
    { id: 'actif', libelle: 'Actif' },
    { id: 'inactif', libelle: 'Inactif' },
];

const selectedLivre = computed(() => props.livres.find(b => String(b.id) === String(props.form.bibliotheque_id)) || {});
</script>

<template>
    <div class="row g-3 custom-input">
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.book_title') || 'Titre du livre' }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <SearchableSelect v-model="form.bibliotheque_id" :options="livresOptions" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner un livre --'" :disabled="isReadOnly" />
                <span v-if="form.errors?.bibliotheque_id" class="text-danger"><strong>{{ form.errors.bibliotheque_id }}</strong></span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="mb-3">
                <label>{{ t('fields.library') || 'Bibliothèque' }}</label>
                <SearchableSelect v-model="form.bibliotheque_structure_id" :options="structuresOptions" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                <span v-if="form.errors?.bibliotheque_structure_id" class="text-danger"><strong>{{ form.errors.bibliotheque_structure_id }}</strong></span>
            </div>
        </div>

        <div v-if="form.bibliotheque_id" class="col-12">
            <div class="alert alert-light border small mb-0">
                <span class="me-3"><strong>Sujet:</strong> {{ selectedLivre.sujet || '-' }}</span>
                <span class="me-3"><strong>Langue:</strong> {{ selectedLivre.langue || '-' }}</span>
                <span class="me-3"><strong>Auteur(s):</strong> {{ selectedLivre.auteurs || '-' }}</span>
                <span class="me-3"><strong>Editeur(s):</strong> {{ selectedLivre.editeurs || '-' }}</span>
                <span><strong>Année:</strong> {{ selectedLivre.annee_edition || '-' }}</span>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.exit_type') || "Type de sortie" }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <SearchableSelect v-model="form.type_sortie" :options="typeOptions" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                <span v-if="form.errors?.type_sortie" class="text-danger"><strong>{{ form.errors.type_sortie }}</strong></span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.exit_date') || "Date de sortie" }}</label>
                <input v-model="form.date_sortie" type="date" class="form-control" :disabled="isReadOnly" />
                <span v-if="form.errors?.date_sortie" class="text-danger"><strong>{{ form.errors.date_sortie }}</strong></span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.quantity') || 'Quantité' }} <span v-if="!isReadOnly" class="text-danger">*</span></label>
                <input v-model.number="form.quantite" type="number" min="1" class="form-control" :disabled="isReadOnly" />
                <span v-if="form.errors?.quantite" class="text-danger"><strong>{{ form.errors.quantite }}</strong></span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.return_date') || 'Date de retour' }}</label>
                <input v-model="form.date_retour" type="date" class="form-control" :disabled="isReadOnly" />
                <span v-if="form.errors?.date_retour" class="text-danger"><strong>{{ form.errors.date_retour }}</strong></span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.borrower') || 'Emprunteur / Acheteur / Donateur' }}</label>
                <input v-model="form.tiers" type="text" class="form-control" :disabled="isReadOnly" />
                <span v-if="form.errors?.tiers" class="text-danger"><strong>{{ form.errors.tiers }}</strong></span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.physical_condition') || 'État physique' }}</label>
                <input v-model="form.etat_physique" type="text" class="form-control" :disabled="isReadOnly" />
                <span v-if="form.errors?.etat_physique" class="text-danger"><strong>{{ form.errors.etat_physique }}</strong></span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="mb-3">
                <label>{{ t('fields.status') || 'État' }}</label>
                <SearchableSelect v-model="form.etat" :options="statusOptions" optionValue="id" optionLabel="libelle" :placeholder="t('actions.select') || '-- Sélectionner --'" :disabled="isReadOnly" />
                <span v-if="form.errors?.etat" class="text-danger"><strong>{{ form.errors.etat }}</strong></span>
            </div>
        </div>
    </div>
</template>
