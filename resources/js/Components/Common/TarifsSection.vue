<!--
  TarifsSection.vue — Composant partagé §12.
  Bloc de tarifs (mensuel / trimestriel / semestriel / annuel) réutilisable.

  Utilisation :
    <TarifsSection
      :form="form"
      :prefix="'tarif_'"
      :disabled="isReadOnly"
    />

  Utilisé par : ServiceCantineForm, ServiceTransportForm.
  Économise ~150 lignes dupliquées.
-->

<script setup>
const props = defineProps({
    form:     { type: Object,  required: true },
    prefix:   { type: String,  default: 'tarif_' },
    disabled: { type: Boolean, default: false },
    // Périodes à afficher, avec libellé métier.
    periodes: {
        type: Array,
        default: () => [
            { key: 'mensuel',     label: 'Mensuel' },
            { key: 'trimestriel', label: 'Trimestriel' },
            { key: 'semestriel', label: 'Semestriel' },
            { key: 'annuel',     label: 'Annuel' },
        ],
    },
});

// Résout la clé finale du form pour une période donnée.
const fieldKey = (periode) => `${props.prefix}${periode}`;
</script>

<template>
    <div class="row g-3">
        <div v-for="p in periodes" :key="p.key" class="col-md-3">
            <label>Tarif {{ p.label.toLowerCase() }}</label>
            <input
                v-model.number="form[fieldKey(p.key)]"
                :disabled="disabled"
                type="number"
                min="0"
                step="0.01"
                class="form-control"
                placeholder="0"
            />
        </div>
    </div>
</template>

<style scoped>
label {
    font-weight: 500;
    color: #374151;
    font-size: 0.9rem;
    margin-bottom: 0.4rem;
    display: block;
}
</style>
