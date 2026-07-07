<!--
  ChampsPaiement.vue — Composant partagé §12.
  Remplace les 24+24+12+12 champs hardcodés (versement_1..12,
  paiement_1..6, avance_1..4) par une liste dynamique add/remove.

  Utilisation :
    <ChampsPaiement
      v-model="form.paiements"
      :types-versement="typesVersement"
      :disabled="isReadOnly"
      :max-lignes="12"
      label-singulier="Versement"
    />

  Émets un tableau d'objets {type, montant, date, reference}.
  Le controller doit accepter `paiements: array<obj>` (ex: JSON en base ou
  éclaté en lignes de paiement dans une table pivot).

  Utilisé par : FacturationApprenant, Versement, AchatDepense, Salaire (avances).
-->

<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue:     { type: Array,   default: () => [] },
    typesVersement: { type: Array,   default: () => [] },
    disabled:       { type: Boolean, default: false },
    maxLignes:      { type: Number,  default: 12 },
    labelSingulier: { type: String,  default: 'Versement' },
    // Colonne(s) supplémentaires par ligne, par défaut : type, montant, date, référence.
    // Pass un array de {key, label, type} pour surcharger.
    colonnes: {
        type: Array,
        default: () => [
            { key: 'nature',    label: 'Type / nature', type: 'select-nature' },
            { key: 'montant',   label: 'Montant',       type: 'number' },
            { key: 'date',      label: 'Date',          type: 'date' },
            { key: 'reference', label: 'Référence',     type: 'text' },
        ],
    },
});

const emit = defineEmits(['update:modelValue']);

const lignes = computed(() => props.modelValue || []);

function addLigne() {
    if (lignes.value.length >= props.maxLignes) return;
    emit('update:modelValue', [
        ...lignes.value,
        { nature: '', montant: 0, date: '', reference: '' },
    ]);
}

function removeLigne(index) {
    const next = [...lignes.value];
    next.splice(index, 1);
    emit('update:modelValue', next);
}

function updateLigne(index, key, value) {
    const next = [...lignes.value];
    next[index] = { ...next[index], [key]: value };
    emit('update:modelValue', next);
}

const totalMontant = computed(() =>
    lignes.value.reduce((sum, l) => sum + (Number(l.montant) || 0), 0)
);
</script>

<template>
    <div class="champs-paiement">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0 text-primary">
                <i class="fa fa-list-ol me-1"></i> {{ labelSingulier }}s
                <span v-if="lignes.length" class="badge bg-primary ms-2">{{ lignes.length }} / {{ maxLignes }}</span>
            </h6>
            <button
                v-if="!disabled && lignes.length < maxLignes"
                type="button"
                class="btn btn-sm btn-outline-primary"
                @click="addLigne"
            >
                <i class="fa fa-plus"></i> Ajouter un {{ labelSingulier.toLowerCase() }}
            </button>
        </div>

        <div v-if="lignes.length === 0" class="alert alert-info py-2 mb-0">
            <i class="fa fa-info-circle"></i> Aucun {{ labelSingulier.toLowerCase() }} enregistré.
        </div>

        <div v-for="(ligne, i) in lignes" :key="i" class="row g-2 align-items-end mb-2 p-2 border rounded">
            <div class="col-md-1 text-muted small">
                #{{ i + 1 }}
            </div>
            <template v-for="col in colonnes" :key="col.key">
                <div :class="col.type === 'text' ? 'col-md-3' : 'col-md-2'">
                    <label class="small text-muted">{{ col.label }}</label>
                    <select
                        v-if="col.type === 'select-nature'"
                        :value="ligne[col.key] || ''"
                        :disabled="disabled"
                        class="form-control form-control-sm"
                        @change="updateLigne(i, col.key, $event.target.value)"
                    >
                        <option value="">-- Type --</option>
                        <option v-for="t in typesVersement" :key="t.id || t" :value="t.code || t.id || t">
                            {{ t.libelle || t }}
                        </option>
                    </select>
                    <input
                        v-else
                        :value="ligne[col.key] ?? ''"
                        :disabled="disabled"
                        :type="col.type"
                        :step="col.type === 'number' ? '0.01' : undefined"
                        :min="col.type === 'number' ? '0' : undefined"
                        class="form-control form-control-sm"
                        @input="updateLigne(i, col.key, col.type === 'number' ? Number($event.target.value) : $event.target.value)"
                    />
                </div>
            </template>
            <div class="col-md-1 text-center">
                <button
                    v-if="!disabled"
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    @click="removeLigne(i)"
                    :title="`Supprimer ce ${labelSingulier.toLowerCase()}`"
                >
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>

        <div v-if="lignes.length > 0" class="text-end mt-2">
            <span class="badge bg-success">Total : {{ totalMontant.toFixed(2) }}</span>
        </div>
    </div>
</template>

<style scoped>
.champs-paiement label.small {
    margin-bottom: 0.2rem;
}
</style>
