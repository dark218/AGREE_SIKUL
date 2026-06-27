<!--
  InheritedContextBar.vue
  Affiche le contexte hiérarchique hérité d'une entité sélectionnée (Classe, Apprenant, Cours...).
  Lit DIRECTEMENT depuis l'objet sélectionné (qui doit contenir les noms via *_nom / *_libelle).

  Usage :
    <InheritedContextBar
      :source="selectedClasse"
      :fields="['ecole_nom', 'campus_nom', 'niveau_libelle', 'section_libelle', 'cycle_libelle', 'annee_scolaire_libelle']"
    />

  Le composant n'affiche que les champs présents dans `source` (auto-hide).
-->
<script setup>
import { computed } from 'vue';

const props = defineProps({
    source: { type: [Object, null], default: null },
    title: { type: String, default: 'Contexte hérité' },
    fields: {
        type: Array,
        default: () => [
            'ecole_nom',
            'campus_nom',
            'institution_nom',
            'niveau_libelle',
            'section_libelle',
            'cycle_libelle',
            'annee_scolaire_libelle',
            'pays_libelle',
        ],
    },
});

// Mapping field key → { icon, label } pour affichage
const FIELD_MAP = {
    ecole_nom:                { icon: 'fa fa-school',         label: 'École' },
    campus_nom:               { icon: 'fa fa-map-marker',     label: 'Campus' },
    institution_nom:          { icon: 'fa fa-building',       label: 'Institution' },
    niveau_libelle:           { icon: 'fa fa-bar-chart',      label: 'Niveau' },
    section_libelle:          { icon: 'fa fa-bookmark',       label: 'Section' },
    cycle_libelle:            { icon: 'fa fa-layer-group',    label: 'Cycle' },
    annee_scolaire_libelle:   { icon: 'fa fa-calendar',       label: 'Année scolaire' },
    pays_libelle:             { icon: 'fa fa-globe',          label: 'Pays' },
    region_libelle:           { icon: 'fa fa-map',            label: 'Région' },
    departement_libelle:      { icon: 'fa fa-sitemap',        label: 'Département' },
    commune_libelle:          { icon: 'fa fa-city',           label: 'Commune' },
    quartier_libelle:         { icon: 'fa fa-compass',        label: 'Quartier' },
    classe_nom:               { icon: 'fa fa-users',          label: 'Classe' },
};

const items = computed(() => {
    if (!props.source) return [];
    return props.fields
        .filter(key => props.source[key])
        .map(key => ({
            ...(FIELD_MAP[key] || { icon: 'fa fa-info', label: key }),
            value: props.source[key],
        }));
});
</script>

<template>
    <div v-if="items.length > 0" class="col-12 mb-3">
        <div class="ictx">
            <div class="ictx-label">
                <i class="fa fa-link"></i> {{ title }}
            </div>
            <div class="ictx-items">
                <span v-for="(item, i) in items" :key="i" class="ictx-chip">
                    <i :class="item.icon"></i>
                    {{ item.label }}: <strong>{{ item.value }}</strong>
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.ictx {
    background: #ecfdf5;
    border: 1px solid #6ee7b7;
    border-radius: 10px;
    padding: 10px 14px;
}
.ictx-label {
    font-size: 11px;
    font-weight: 700;
    color: #047857;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.ictx-items {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.ictx-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: white;
    border: 1px solid #d1fae5;
    border-radius: 6px;
    padding: 4px 10px;
    font-size: 12px;
    color: #334155;
}
.ictx-chip i {
    color: #10b981;
    font-size: 13px;
}
.ictx-chip strong {
    color: #064e3b;
}
</style>
