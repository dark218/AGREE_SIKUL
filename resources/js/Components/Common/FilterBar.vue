<script setup>
/**
 * FilterBar — barre de recherche/filtres réutilisable et homogène.
 *
 * Usage :
 *   <FilterBar
 *     v-model="searchFilters"
 *     :fields="[
 *       { key:'search', type:'text', placeholder:'Rechercher', icon:'fa-search' },
 *       { key:'statut', type:'select', placeholder:'Statut', options:statusOptions,
 *         optionValue:'id', optionLabel:'libelle' },
 *     ]"
 *     @search="search"
 *     @reset="resetFilters"
 *   >
 *     <template #actions>
 *       <a :href="..." class="fb-btn fb-btn-pdf"><i class="fa fa-file-pdf"></i> PDF</a>
 *     </template>
 *   </FilterBar>
 */
import { computed } from 'vue';
import SearchableSelect from '@/Components/Common/SearchableSelect.vue';

const props = defineProps({
    modelValue: { type: Object, default: () => ({}) },
    // Liste des champs filtrables : { key, type: 'text'|'select', placeholder, options?, optionValue?, optionLabel?, width?, icon? }
    fields: { type: Array, default: () => [] },
    searchLabel: { type: String, default: 'Rechercher' },
    resetLabel: { type: String, default: 'Réinitialiser' },
    showSearch: { type: Boolean, default: true },
    showReset: { type: Boolean, default: true },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'search', 'reset']);

function setField(key, value) {
    emit('update:modelValue', { ...props.modelValue, [key]: value });
}

const hasActiveFilters = computed(() =>
    props.fields.some((f) => {
        const v = props.modelValue?.[f.key];
        return v !== null && v !== undefined && v !== '';
    })
);
</script>

<template>
    <form class="filter-bar" @submit.prevent="emit('search')">
        <div class="fb-fields">
            <template v-for="field in fields" :key="field.key">
                <!-- Champ texte -->
                <div
                    v-if="field.type === 'text' || !field.type"
                    class="fb-field fb-field-text"
                    :style="field.width ? { width: field.width } : {}"
                >
                    <i v-if="field.icon" :class="['fa', field.icon, 'fb-field-icon']"></i>
                    <input
                        type="text"
                        class="fb-input"
                        :class="{ 'has-icon': field.icon }"
                        :value="modelValue[field.key]"
                        :placeholder="field.placeholder || ''"
                        @input="setField(field.key, $event.target.value)"
                    />
                    <button
                        v-if="modelValue[field.key]"
                        type="button"
                        class="fb-input-clear"
                        title="Vider"
                        @click="setField(field.key, '')"
                    >
                        <i class="fa fa-times"></i>
                    </button>
                </div>

                <!-- Champ select (recherche) -->
                <div
                    v-else-if="field.type === 'select'"
                    class="fb-field fb-field-select"
                    :style="{ width: field.width || '190px' }"
                >
                    <SearchableSelect
                        :model-value="modelValue[field.key]"
                        :options="field.options || []"
                        :option-value="field.optionValue || 'id'"
                        :option-label="field.optionLabel || 'libelle'"
                        :placeholder="field.placeholder || 'Sélectionner…'"
                        @update:model-value="setField(field.key, $event)"
                    />
                </div>
            </template>
        </div>

        <div class="fb-actions">
            <button
                v-if="showSearch"
                type="submit"
                class="fb-btn fb-btn-search"
                :disabled="loading"
                :title="searchLabel"
            >
                <i class="fa" :class="loading ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                <span class="fb-btn-label">{{ searchLabel }}</span>
            </button>
            <button
                v-if="showReset"
                type="button"
                class="fb-btn fb-btn-reset"
                :class="{ 'is-active': hasActiveFilters }"
                :title="resetLabel"
                @click="emit('reset')"
            >
                <i class="fa fa-rotate-left"></i>
            </button>
            <!-- Actions supplémentaires (PDF, Ajouter, Export…) -->
            <slot name="actions" />
        </div>
    </form>
</template>

<style scoped>
.filter-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 14px 16px;
    background: #f8fafc;
    border: 1px solid #e9eef5;
    border-radius: 14px;
    margin-bottom: 16px;
}

.fb-fields {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    flex: 1;
    min-width: 0;
}

/* Champ texte */
.fb-field-text {
    position: relative;
    width: 240px;
    max-width: 100%;
}

.fb-field-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 13px;
    pointer-events: none;
}

.fb-input {
    width: 100%;
    height: 40px;
    padding: 0 32px 0 12px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    background: #fff;
    color: #1e293b;
    font-size: 13px;
    font-weight: 500;
    outline: none;
    transition: all 0.2s ease;
}

.fb-input.has-icon {
    padding-left: 34px;
}

.fb-input::placeholder {
    color: #94a3b8;
    font-weight: 400;
}

.fb-input:focus {
    border-color: #0FBCAF;
    box-shadow: 0 0 0 3px rgba(15, 188, 175, 0.12);
}

.fb-input-clear {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 50%;
    background: #f1f5f9;
    color: #94a3b8;
    font-size: 11px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.fb-input-clear:hover {
    background: #fee2e2;
    color: #ef4444;
}

/* Zone actions */
.fb-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.fb-btn {
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 16px;
    border: none;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
    white-space: nowrap;
    font-family: inherit;
}

.fb-btn:active {
    transform: translateY(1px);
}

.fb-btn-search {
    background: linear-gradient(135deg, #0B5697, #0FBCAF);
    color: #fff;
    box-shadow: 0 4px 12px rgba(11, 86, 151, 0.25);
}

.fb-btn-search:hover {
    box-shadow: 0 6px 16px rgba(11, 86, 151, 0.35);
}

.fb-btn-search:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.fb-btn-reset {
    width: 40px;
    padding: 0;
    background: #eef2f7;
    color: #64748b;
}

.fb-btn-reset:hover,
.fb-btn-reset.is-active {
    background: #e2e8f0;
    color: #0B5697;
}

/* Boutons "actions" fournis via le slot (PDF, Ajouter…) — classes utilitaires */
.fb-actions :slotted(.fb-btn-pdf) {
    height: 40px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 0 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    background: #ef4444;
    color: #fff;
    transition: all 0.2s ease;
}

.fb-actions :slotted(.fb-btn-pdf:hover) {
    background: #dc2626;
}

.fb-actions :slotted(.fb-btn-add) {
    height: 40px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 0 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    background: #0c1e36;
    color: #fff;
    transition: all 0.2s ease;
}

.fb-actions :slotted(.fb-btn-add:hover) {
    background: #13294d;
}

.fb-btn-label {
    display: inline;
}

/* Responsive */
@media (max-width: 640px) {
    .filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    .fb-fields {
        flex-direction: column;
        align-items: stretch;
    }
    .fb-field-text,
    .fb-field-select {
        width: 100% !important;
    }
    .fb-actions {
        justify-content: flex-end;
    }
    .fb-btn-label {
        display: none;
    }
}
</style>
