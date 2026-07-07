<!--
  ApprenantSelect.vue — Composant partagé §12.
  Sélecteur d'apprenant standardisé avec format lisible unique (nom + prénoms).

  Utilisation :
    <ApprenantSelect
      v-model="form.apprenant_id"
      :apprenants="apprenants"
      :disabled="isReadOnly"
      required
    />

  Utilisé par : Emprunt, Reservation, InscriptionCantine, InscriptionTransport,
  ConsultationInfirmerie, FacturationApprenant, Versement.
-->

<script setup>
import { computed } from 'vue';
import SearchableSelect from './SearchableSelect.vue';

const props = defineProps({
    modelValue: { type: [String, Number, null], default: '' },
    apprenants: { type: Array, default: () => [] },
    disabled:   { type: Boolean, default: false },
    required:   { type: Boolean, default: false },
    placeholder: { type: String, default: '-- Sélectionner un apprenant --' },
});

const emit = defineEmits(['update:modelValue']);

// Normalise le format d'affichage : `matricule — nom prenoms` si matricule dispo,
// sinon `nom prenoms`. Gère aussi la propriété `libelle` déjà pré-calculée.
const options = computed(() => (props.apprenants || []).map(a => {
    if (a.libelle) return a; // déjà formaté par le controller
    const nom = [a.nom, a.prenoms].filter(Boolean).join(' ').trim();
    const prefix = a.matricule ? `${a.matricule} — ` : '';
    return { ...a, libelle: `${prefix}${nom}` || `Apprenant #${a.id}` };
}));
</script>

<template>
    <SearchableSelect
        :model-value="modelValue"
        :options="options"
        option-value="id"
        option-label="libelle"
        :placeholder="placeholder"
        :disabled="disabled"
        @update:model-value="(v) => emit('update:modelValue', v)"
    />
</template>
