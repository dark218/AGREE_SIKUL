<!--
  ApprenantsPicker.vue — sélecteur multi-apprenants avec contrainte "même école"

  Usage :
    <ApprenantsPicker
        v-model="form.apprenant_ids"
        :apprenants="apprenants"
        :show-lien="true"
        v-model:lien-parente="form.lien_parente"
    />

  Props :
    - modelValue (Array<Number>)   : IDs des apprenants sélectionnés (v-model)
    - apprenants (Array<{id, libelle, ecole_id}>) : liste complète des apprenants disponibles
    - showLien (Boolean)           : afficher la colonne "Lien de parenté" (pour Parent)
    - lienParente (Array<String>)  : v-model additionnel pour les liens (aligné par index avec modelValue)
    - disabled (Boolean)
    - maxRows (Number, def=10)     : sécurité anti-abus

  Émissions :
    - update:modelValue (Array<Number>)
    - update:lienParente (Array<String>)

  Comportement :
    - 1 ligne par apprenant, bouton "+ Ajouter" pour en ajouter une
    - Dès qu'1 apprenant est choisi, la liste des autres se restreint à ceux de la même école
    - Alerte visible si sélections mixtes école (ne devrait pas arriver via UI, mais safety)
    - Bouton corbeille par ligne pour retirer
-->
<script setup>
import { computed, ref, watch } from 'vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    apprenants: { type: Array, default: () => [] },
    showLien: { type: Boolean, default: false },
    lienParente: { type: Array, default: () => [] },
    disabled: { type: Boolean, default: false },
    maxRows: { type: Number, default: 10 },
});

const emit = defineEmits(['update:modelValue', 'update:lienParente']);

// État interne : un tableau de lignes {apprenant_id, lien_parente}
const rows = ref(
    props.modelValue.length > 0
        ? props.modelValue.map((id, i) => ({
            apprenant_id: id,
            lien_parente: props.lienParente[i] ?? null,
        }))
        : [{ apprenant_id: null, lien_parente: null }]
);

// Synchronise vers le parent quand rows change
watch(rows, (v) => {
    emit('update:modelValue', v.map((r) => r.apprenant_id).filter((id) => id != null));
    if (props.showLien) {
        emit('update:lienParente', v.map((r) => r.lien_parente));
    }
}, { deep: true });

// École commune : dérivée du 1er apprenant sélectionné
const commonEcoleId = computed(() => {
    const selectedIds = rows.value.map((r) => r.apprenant_id).filter(Boolean);
    if (selectedIds.length === 0) return null;
    const firstApprenant = props.apprenants.find((a) => String(a.id) === String(selectedIds[0]));
    return firstApprenant?.ecole_id ?? null;
});

const commonEcoleName = computed(() => {
    if (!commonEcoleId.value) return null;
    const app = props.apprenants.find((a) => a.ecole_id === commonEcoleId.value);
    return app?.ecole_nom || null;
});

// Options filtrées : si une école est verrouillée, on n'affiche que ses apprenants
const filteredApprenants = computed(() => {
    if (!commonEcoleId.value) return props.apprenants;
    return props.apprenants.filter(
        (a) => !a.ecole_id || String(a.ecole_id) === String(commonEcoleId.value)
    );
});

// Détection d'incohérence (safeguard : ne devrait plus arriver avec le filtre)
const hasEcoleMismatch = computed(() => {
    const selectedIds = rows.value.map((r) => r.apprenant_id).filter(Boolean);
    if (selectedIds.length < 2) return false;
    const ecoles = new Set(
        selectedIds.map((id) => {
            const a = props.apprenants.find((x) => String(x.id) === String(id));
            return a?.ecole_id ?? null;
        }).filter(Boolean)
    );
    return ecoles.size > 1;
});

// Options disponibles par ligne : on enlève les apprenants déjà pris ailleurs
const availableOptionsForRow = (rowIndex) => {
    const takenIds = rows.value
        .map((r, i) => (i === rowIndex ? null : r.apprenant_id))
        .filter(Boolean)
        .map(String);
    return filteredApprenants.value.filter((a) => !takenIds.includes(String(a.id)));
};

const lienOptions = [
    { id: 'pere', libelle: 'Père' },
    { id: 'mere', libelle: 'Mère' },
    { id: 'tuteur_legal', libelle: 'Tuteur légal' },
    { id: 'autre', libelle: 'Autre' },
];

const canAdd = computed(() => rows.value.length < props.maxRows && !props.disabled);

const addRow = () => {
    if (canAdd.value) rows.value.push({ apprenant_id: null, lien_parente: null });
};

const removeRow = (index) => {
    if (props.disabled) return;
    rows.value.splice(index, 1);
    if (rows.value.length === 0) {
        rows.value.push({ apprenant_id: null, lien_parente: null });
    }
};
</script>

<template>
    <div class="apprenants-picker">
        <!-- Badge "même école" -->
        <div v-if="commonEcoleName" class="badge-ecole">
            <i class="fa fa-school me-1"></i>
            École rattachée : <strong>{{ commonEcoleName }}</strong>
            <small class="ms-2 text-muted">— tous les apprenants sélectionnés doivent en dépendre.</small>
        </div>

        <!-- Alerte incohérence -->
        <div v-if="hasEcoleMismatch" class="alert alert-warning py-2 mb-2">
            <i class="fa fa-exclamation-triangle me-1"></i>
            Certains apprenants sélectionnés ne sont pas dans la même école. Cela empêchera l'enregistrement.
        </div>

        <!-- Lignes -->
        <div v-for="(row, idx) in rows" :key="idx" class="row-item">
            <div class="row-content">
                <div class="row-label">
                    <span class="row-num">{{ idx + 1 }}</span>
                </div>
                <div class="row-select">
                    <SearchableSelect
                        v-model="row.apprenant_id"
                        :options="availableOptionsForRow(idx)"
                        optionValue="id"
                        optionLabel="libelle"
                        placeholder="Sélectionner un apprenant…"
                        searchPlaceholder="Rechercher un apprenant"
                        :disabled="disabled"
                    />
                </div>
                <div v-if="showLien" class="row-lien">
                    <SearchableSelect
                        v-model="row.lien_parente"
                        :options="lienOptions"
                        optionValue="id"
                        optionLabel="libelle"
                        placeholder="Lien de parenté"
                        :disabled="disabled || !row.apprenant_id"
                    />
                </div>
                <div class="row-actions">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        :disabled="disabled"
                        title="Retirer cet apprenant"
                        @click="removeRow(idx)"
                    >
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Bouton ajout -->
        <button
            type="button"
            class="btn btn-outline-primary btn-add"
            :disabled="!canAdd"
            @click="addRow"
        >
            <i class="fa fa-plus me-1"></i>
            Ajouter un apprenant
        </button>

        <div v-if="rows.length >= maxRows" class="text-muted small mt-1">
            Limite atteinte : {{ maxRows }} apprenants max.
        </div>
    </div>
</template>

<style scoped>
.apprenants-picker {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.badge-ecole {
    background: #e0f2fe;
    border: 1px solid #7dd3fc;
    color: #075985;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 0.85rem;
}

.row-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 12px;
}

.row-content {
    display: grid;
    grid-template-columns: 32px 1fr auto auto;
    gap: 12px;
    align-items: center;
}

.row-content:has(.row-lien) {
    grid-template-columns: 32px 1fr 200px auto;
}

.row-label {
    text-align: center;
}

.row-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: #0b5697;
    color: white;
    border-radius: 50%;
    font-weight: 700;
    font-size: 0.85rem;
}

.row-select,
.row-lien {
    min-width: 0;
}

.row-actions {
    flex-shrink: 0;
}

.btn-add {
    align-self: flex-start;
}

/* Responsive : sur mobile, une ligne par élément */
@media (max-width: 768px) {
    .row-content,
    .row-content:has(.row-lien) {
        grid-template-columns: 1fr;
        gap: 8px;
    }
    .row-label {
        text-align: left;
    }
    .row-actions {
        text-align: right;
    }
}
</style>
